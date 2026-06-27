<?php
declare(strict_types=1);

require __DIR__ . '/../php/lib/bootstrap.php';
require __DIR__ . '/../php/lib/config.php';
require __DIR__ . '/../php/lib/assistant-search.php';

$question = 'What Oracle experience do you have?';
$result = searchCombinedAssistantAnswer($question);

echo json_encode([
    'question' => $question,
    'source' => $result['source'] ?? null,
    'answer_preview' => mb_substr((string) ($result['answer'] ?? ''), 0, 200),
], JSON_PRETTY_PRINT) . PHP_EOL;
