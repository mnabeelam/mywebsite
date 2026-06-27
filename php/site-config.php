<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/site-settings.php';

header('Cache-Control: public, max-age=300');

jsonResponse([
    'status' => 'ok',
    'ai_enabled' => filter_var(configValue('AI_ENABLED'), FILTER_VALIDATE_BOOLEAN),
    'contact' => publicSiteContact(),
]);
