<?php

namespace Alancherosr\FilamentVannaBot\Components;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class VannaBot extends Component
{

    public string $name;
    public array $messages;
    public string $question;
    public string $winWidth;
    public string $winPosition;
    public bool $showPositionBtn;
    public bool $panelHidden;
    private string $sessionKey;
    protected $listeners = [
        //shortcut
        'ctrl+s' => 'sendMessage',
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
        if(empty(trim($this->question))){
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
        if($this->winWidth=="width:350px;"){
            $this->winWidth = "width:100%;";
            $this->showPositionBtn = false;
        }else{
            $this->winWidth = "width:350px;";
            $this->showPositionBtn = true;
        }
    }

    public function changeWinPosition(): void
    {
        if($this->winPosition != "left"){
            $this->winPosition = "left";
        }else{
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

        try {
            // First, send the question to Vanna API to generate SQL
            $response = Http::get('http://kpi-copilot-api:5000/api/v0/generate_sql', [
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
            $sql_response = Http::get('http://kpi-copilot-api:5000/api/v0/run_sql', [
                'id' => $sql_id,
            ]);

            $sql_response_body = $sql_response->json();

            logger()->debug('Into chat', [
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
            $this->messages[] = ['role' => 'assistant', 'content' => 'Error communicating with Vanna API'];
        }

        request()->session()->put($this->sessionKey, $this->messages);

    }

    function handleApiResponseData($data) {
        // Check if the type is 'df'
        if (isset($data['type']) && $data['type'] === 'df') {
            // Decode the 'df' field which is a JSON string
            $df = json_decode($data['df'], true);
    
            // Generate HTML table
            $html = '<table border="1"><tr>';
            // Add table headers
            foreach (array_keys($df[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
    
            // Add table rows
            foreach ($df as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
    
            return $html;
        }
    
        // Handle other types or return a default message
        return 'No table data available.';
    }
}
