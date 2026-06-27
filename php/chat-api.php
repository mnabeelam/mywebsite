<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/openai.php';
require_once __DIR__ . '/lib/assistant-search.php';

requirePost();
requireSameOrigin();
rateLimit('chat_api', 30, 3600);

$question = sanitizeText($_POST['question'] ?? '', 1000);
if ($question === '') {
    jsonResponse(['error' => 'Question is required.'], 400);
}

$combined = searchCombinedAssistantAnswer($question);

if (!filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN)) {
    if ($combined['answer'] !== null) {
        jsonResponse([
            'status' => 'ok',
            'question' => $question,
            'answer' => $combined['answer'],
            'source' => $combined['source'],
        ]);
    }

    jsonResponse([
        'error' => 'AI assistant is not enabled on this site.',
        'fallback' => true,
    ], 503);
}

if (openaiApiKey() === '') {
    if ($combined['answer'] !== null) {
        jsonResponse([
            'status' => 'ok',
            'question' => $question,
            'answer' => $combined['answer'],
            'source' => $combined['source'],
        ]);
    }

    jsonResponse([
        'error' => 'Assistant is temporarily unavailable.',
        'fallback' => true,
    ], 503);
}

try {
    require_once __DIR__ . '/lib/knowledge.php';
    $answer = openaiChat(assistantSystemPrompt(), $question);
    jsonResponse([
        'status' => 'ok',
        'question' => $question,
        'answer' => $answer,
        'source' => 'openai',
    ]);
} catch (Throwable $e) {
    appLog('chat-api: ' . $e->getMessage());

    if ($combined['answer'] !== null) {
        jsonResponse([
            'status' => 'ok',
            'question' => $question,
            'answer' => $combined['answer'],
            'source' => $combined['source'],
            'fallback' => true,
        ]);
    }

    jsonResponse([
        'error' => 'Assistant is temporarily unavailable. Please try again later.',
        'fallback' => true,
    ], 502);
}
