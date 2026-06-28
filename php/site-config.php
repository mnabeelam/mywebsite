<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/site-settings.php';

header('Cache-Control: public, max-age=300');

$openaiConfigured = configValue('OPENAI_API_KEY') !== '';
$aiEnabled = filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN);

jsonResponse([
    'status' => 'ok',
    'ai_enabled' => $aiEnabled,
    'openai_configured' => $openaiConfigured,
    'assistant_mode' => ($aiEnabled && $openaiConfigured) ? 'openai' : 'knowledge',
    'contact' => publicSiteContact(),
]);
