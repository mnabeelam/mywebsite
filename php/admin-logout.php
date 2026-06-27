<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';

requirePost();
requireCsrfFromRequest();
logoutAdmin();
jsonResponse(['status' => 'ok', 'redirect' => 'index.php']);
