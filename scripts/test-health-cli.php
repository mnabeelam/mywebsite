<?php
declare(strict_types=1);

require __DIR__ . '/../php/lib/bootstrap.php';
require __DIR__ . '/../php/lib/config.php';
require __DIR__ . '/../php/lib/auth.php';
require __DIR__ . '/../php/lib/knowledge-builder.php';

$index = loadKnowledgeSearchIndex();

echo json_encode([
    'status' => 'ok',
    'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
    'knowledge_entries' => (int) ($index['entry_count'] ?? 0),
    'admin_configured' => adminConfigured(),
    'ai_enabled' => filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN),
    'assistant_mode' => (filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN) && configValue('OPENAI_API_KEY') !== '')
        ? 'openai'
        : 'knowledge',
], JSON_PRETTY_PRINT) . PHP_EOL;
