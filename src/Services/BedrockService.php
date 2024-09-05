<?php

namespace Alancherosr\FilamentVannaBot\Services;

use Exception;

final class BedrockService{

    private $bedrock_client;

    public function __construct()
    {
        $this->bedrock_client = app('aws')->createClient('bedrock-runtime');
    }

    public function translateTableHeader(string $header): string {
        $prompt = "Translate the following table header name to spanish and format it as a human readable text:\n\n{$header}.";
        $prompt .= "Return only the translated text without any additional information before or after the translation.";

        return $this->getInferenceFromClaudeHaiku($prompt);
    }
    
    private function getInferenceFromClaudeHaiku(string $prompt, array $messages=[]): string {
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        // Create the body
        $body = [
            'messages' => $messages,
            'max_tokens' => 2000,
            'anthropic_version' => 'bedrock-2023-05-31',
            'temperature' => 0,
            'top_p' => 0.5
        ];

        // Convert $body to json
        $body_json = json_encode($body);

        // Log the request into laravel log
        info('Bedrock Messages API Request', [
            'user' => auth()->id(),
            'model' => 'anthropic.claude-3-haiku-20240307-v1:0',
            'body' => $body,
        ]);

        // Send the messages to the Bedrock Messages API
        $result = $this->bedrock_client->invokeModel([
            'modelId' => 'anthropic.claude-3-haiku-20240307-v1:0',
            'accept' => 'application/json',
            'body' => $body_json,
            'contentType' => 'application/json',
        ]);

        // Decode the JSON response
        $response_body = json_decode($result['body']);
        $response_content = '';

        // Check if 'content' exists and is not empty, and that the first element has a 'text' value
        if (isset($response_body->content) && !empty($response_body->content) && isset($response_body->content[0]->text)) {
            $response_content = $response_body->content[0]->text;
        }

        info('Bedrock Messages API Response', [
            'user' => auth()->id(),
            'model' => 'anthropic.claude-3-haiku-20240307-v1:0',
            'response' => $response_content,
        ]);

        // Return inference
        return $response_content;
    }
}
