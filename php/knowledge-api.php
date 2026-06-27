<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/knowledge-builder.php';

requireAdminAuth();
rateLimit('knowledge_api', 30, 3600);

$index = loadKnowledgeSearchIndex();

jsonResponse([
    'status' => 'ok',
    'updated' => $index['updated'] ?? null,
    'entry_count' => $index['entry_count'] ?? 0,
    'entries' => $index['entries'] ?? [],
]);
