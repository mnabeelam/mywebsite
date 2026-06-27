<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';

requirePost();
rateLimit('ai_chat', 30, 3600);

$input = readJsonBody();
$message = sanitizeText((string) ($input['message'] ?? ''), 1000);

if ($message === '') {
    jsonResponse(['error' => 'Message is required.'], 400);
}

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    jsonResponse(['error' => 'OPENAI_API_KEY not configured on server.'], 503);
}

jsonResponse([
    'status' => 'demo',
    'message' => 'Backend endpoint ready. Connect OpenAI API in Phase 3.',
]);
