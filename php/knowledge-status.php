<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/knowledge-builder.php';

requireAdminAuth();
jsonResponse([
    'status' => 'ok',
    'knowledge' => knowledgeStatus(),
]);
