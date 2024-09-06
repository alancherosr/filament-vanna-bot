<?php

namespace Alancherosr\FilamentVannaBot\Components;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Alancherosr\FilamentVannaBot\Services\BedrockService;

class VannaBot extends Component
{

    public string $name;
    public array $messages;
    public string $question;
    public string $winWidth;
    public string $winPosition;
    public bool $showPositionBtn;
    public bool $panelHidden;
    public int $maxRowsForTables;
    private string $sessionKey;
    protected $listeners = [
        //shortcut
        'enter' => 'sendMessage',
        'ctrl+r' => 'changeWinWidth',
        'ctrl+p' => 'changeWinPosition',
        'ctrl+d' => 'resetSession',
        'ctrl+alt+z' => 'togglePanel',
    ];

    public function __construct()
    {
        $this->sessionKey = auth()->id() . '-messages';
    }

    public function mount(): void
    {
        $this->panelHidden = true;
        $this->winWidth = "width:550px;";
        $this->winPosition = "";
        $this->showPositionBtn = true;
        $this->messages = session($this->sessionKey, []);
        $this->question = "";
        $this->maxRowsForTables = 5;
    }

    public function render()
    {
        return view('filament-vanna-bot::livewire.chat-bot');
    }

    public function name(): void
    {
        $this->name =  config('filament-vanna-bot.botname') ?? 'Vanna';
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->question))) {
            $this->question = "";
            return;
        }

        // Store the question in a temporary variable before resetting it
        $user_question = trim($this->question);
        $this->messages[] = [
            "role" => 'user',
            "content" => $user_question,
        ];
        $this->dispatch('sendmessage', ['message' => $this->question]);
        $this->question = "";
        $this->chat($user_question);
    }

    public function changeWinWidth(): void
    {
        if ($this->winWidth == "width:350px;") {
            $this->winWidth = "width:100%;";
            $this->showPositionBtn = false;
        } else {
            $this->winWidth = "width:350px;";
            $this->showPositionBtn = true;
        }
    }

    public function changeWinPosition(): void
    {
        if ($this->winPosition != "left") {
            $this->winPosition = "left";
        } else {
            $this->winPosition = "";
        }
    }

    public function resetSession(): void
    {
        request()->session()->forget($this->sessionKey);
        $this->messages = [];
    }

    public function togglePanel(): void
    {
        $this->panelHidden = !$this->panelHidden;
    }

    protected function chat(string $user_question): void
    {
        $vanna_api_url = config('filament-vanna-bot.vanna_api_url');
        try {
            // First, send the question to Vanna API to generate SQL
            $response = Http::get("{$vanna_api_url}/generate_sql", [
                'question' => $user_question,
            ]);

            $response_body = $response->json();

            if (!$response->ok() || isset($response_body['error'])) {
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $response_body['error']['message'] ?? 'Error generating SQL',
                ];
                return;
            }

            $sql_id = $response_body['id'];

            // After receiving the SQL, execute it using Vanna API
            $sql_response = Http::get("{$vanna_api_url}/run_sql", [
                'id' => $sql_id,
                'head' => $this->maxRowsForTables
            ]);

            $sql_response_body = $sql_response->json();

            logger()->debug('Into chat', [
                'this->maxRowsForTables' => $this->maxRowsForTables,
                'question' => $user_question,
                'response_body' => $response_body,
                'sql_id' => $sql_id,
                'sql_response_body' => $sql_response_body,
            ]);

            if (!$sql_response->ok() || isset($sql_response_body['error'])) {
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $sql_response_body['error']['message'] ?? 'Error executing SQL',
                ];
            } else {
                $sql_response_parsed = $this->handleApiResponseData($sql_response_body);
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $sql_response_parsed,
                ];
            }
        } catch (\Exception $e) {
            logger()->debug($e->getMessage());
            $this->messages[] = ['role' => 'assistant', 'content' => 'Error communicating with KPI Copilot API'];
        }

        request()->session()->put($this->sessionKey, $this->messages);
    }

    function handleApiResponseData($data)
    {
        $vanna_api_url = config('filament-vanna-bot.vanna_api_url');
        
        // Check if the type is 'df'
        if (isset($data['type']) && $data['type'] === 'df') {
            // Decode the 'df' field which is a JSON string
            $df = json_decode($data['df'], true);

            // Assuming $df is an array of associative arrays representing rows of the table
            if (!empty($df)) {
                $html = "<table class='table-auto w-full'>";
                $html .= "<thead><tr>";

                // Instantiate BedrockService
                $bedrock_service = new BedrockService();

                // Generate table headers with translation
                foreach (array_keys($df[0]) as $column) {
                    $translatedHeader = $bedrock_service->translateTableHeader($column);
                    $html .= "<th class='px-4 py-2'>{$translatedHeader}</th>";
                }

                $html .= "</tr></thead><tbody>";

                // Generate table rows
                foreach ($df as $row) {
                    $html .= "<tr>";
                    foreach ($row as $key => $value) {
                        // Check if the value is numeric
                        $class = is_numeric($value) ? 'text-center' : '';
                        $html .= "<td class='border px-4 py-2 {$class}'>" . htmlspecialchars($value) . "</td>";
                    }
                    $html .= "</tr>";
                }

                $html .= "</tbody></table>";
            }

            // Download CSV file and store locally
            if (isset($data['df']) && !empty($data['df'])) {
                $df = json_decode($data['df'], true);
                if (count($df) == $this->maxRowsForTables) {
                    $id = htmlspecialchars($data['id']);
                    $csv_url = "{$vanna_api_url}/download_csv?id={$id}";
                    $csv_content = Http::get($csv_url)->body();
                    $file_path = "csv/".auth()->user()->id."/{$id}.csv";
                    Storage::put($file_path, $csv_content);

                    // Generate a secure download link
                    $download_link = route('download.csv', ['filename' => "{$id}.csv"]);
                    $html .= "<br><a href='{$download_link}' download class='text-blue-500 underline'>Descarga el conjunto de datos completo en csv</a>";
                }
            }

            return $html;
        }

        // Handle other types or return a default message
        return 'No table data available.';
    }
}
