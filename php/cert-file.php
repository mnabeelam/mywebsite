<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/cert-services.php';

requireAdminAuth();

$id = sanitizeText($_GET['id'] ?? '', 40);
if ($id === '') {
    http_response_code(400);
    echo 'Certificate id is required.';
    exit;
}

streamCertificationFile($id);
