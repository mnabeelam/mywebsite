<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function openaiModel(): string
{
    $model = configValue('OPENAI_MODEL');
    return $model !== '' ? $model : 'gpt-4o-mini';
}

function openaiApiKey(): string
{
    return configValue('OPENAI_API_KEY');
}

function openaiChat(string $systemPrompt, string $userMessage): string
{
    $apiKey = openaiApiKey();
    if ($apiKey === '') {
        throw new RuntimeException('OPENAI_API_KEY not configured.');
    }

    if (!ini_get('allow_url_fopen')) {
        throw new RuntimeException('PHP allow_url_fopen must be enabled for OpenAI requests.');
    }

    $payload = json_encode([
        'model' => openaiModel(),
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ],
        'max_tokens' => 500,
        'temperature' => 0.4,
    ]);

    if ($payload === false) {
        throw new RuntimeException('Could not encode OpenAI request.');
    }

    $sslOptions = [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ];

    $caFile = ini_get('openssl.cafile');
    if ($caFile) {
        $sslOptions['cafile'] = $caFile;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $payload,
            'timeout' => 45,
            'ignore_errors' => true,
        ],
        'ssl' => $sslOptions,
    ]);

    $raw = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);

    if ($raw === false) {
        $error = error_get_last();
        $detail = is_array($error) ? ($error['message'] ?? 'unknown error') : 'unknown error';
        throw new RuntimeException('OpenAI request failed: ' . $detail);
    }

    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $status = (int) $matches[1];
    }

    $data = json_decode($raw, true);
    if ($status >= 400) {
        $message = $data['error']['message'] ?? ('OpenAI API error (' . $status . ')');
        throw new RuntimeException($message);
    }

    $answer = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    if ($answer === '') {
        throw new RuntimeException('OpenAI returned an empty response.');
    }

    return $answer;
}
