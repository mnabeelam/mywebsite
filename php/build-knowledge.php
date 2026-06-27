<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/knowledge-builder.php';

requirePost();
requireAdminAuth();
requireCsrfFromRequest();
requireSameOrigin();
rateLimit('admin_rebuild', 10, 3600);

try {
    $index = rebuildKnowledgeSearchIndex();
    jsonResponse([
        'status' => 'success',
        'message' => 'Knowledge index rebuilt.',
        'entry_count' => $index['entry_count'] ?? 0,
        'updated' => $index['updated'] ?? null,
    ]);
} catch (Throwable $e) {
    appLog('build-knowledge: ' . $e->getMessage());
    jsonResponse(['error' => 'Knowledge rebuild failed.'], 500);
}
