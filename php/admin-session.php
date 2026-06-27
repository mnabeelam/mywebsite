<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';

if (isAdminAuthenticated()) {
    jsonResponse([
        'authenticated' => true,
        'csrf_token' => csrfToken(),
        'session' => adminSessionPayload(),
    ]);
}

jsonResponse([
    'authenticated' => false,
    'csrf_token' => csrfToken(),
]);
