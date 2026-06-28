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

$answer = $result['answer'];
if ($answer === null || trim((string) $answer) === '') {
    $answer = 'I could not find a specific match in the career knowledge base or shop catalog. '
        . 'Try asking about Oracle, VMware, projects, career timeline, certifications, shop products, or contact details.';
}

jsonResponse([
    'status' => 'ok',
    'question' => $question,
    'answer' => $answer,
    'source' => $result['source'],
]);
