<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/knowledge-builder.php';

header('Cache-Control: no-store, max-age=0');

$index = loadKnowledgeSearchIndex();

jsonResponse([
    'status' => 'ok',
    'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
    'knowledge_entries' => (int) ($index['entry_count'] ?? 0),
    'admin_configured' => adminConfigured(),
    'openai_configured' => configValue('OPENAI_API_KEY') !== '',
    'ai_enabled' => filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN),
]);
