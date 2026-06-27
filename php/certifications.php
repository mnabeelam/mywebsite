<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/cert-services.php';

jsonResponse([
    'status' => 'ok',
    'certifications' => listPublicCertifications(),
]);
