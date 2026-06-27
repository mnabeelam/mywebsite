<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/site-services.php';
require_once __DIR__ . '/lib/assistant-search.php';

requirePost();
requireSameOrigin();
requirePublicAccessAllowed();
rateLimit('knowledge_search', 60, 3600);

$question = sanitizeText($_POST['question'] ?? '', 1000);
if ($question === '') {
    jsonResponse(['error' => 'Question is required.'], 400);
}

$result = searchCombinedAssistantAnswer($question);

jsonResponse([
    'status' => 'ok',
    'question' => $question,
    'answer' => $result['answer'],
    'source' => $result['source'],
]);
