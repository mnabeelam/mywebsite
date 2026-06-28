<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/runtime-versions.php';

requireAdminAuth();

$action = sanitizeText($_GET['action'] ?? 'report', 32);

if ($action === 'verify') {
    jsonResponse([
        'status' => 'ok',
        'compatibility' => runtimeVerifySiteCompatibility(),
    ]);
}

jsonResponse([
    'status' => 'ok',
    'runtime' => runtimeBuildReport(true),
]);
