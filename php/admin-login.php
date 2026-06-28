<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/site-services.php';

requirePost();
requireSameOrigin();
requireCsrfFromRequest();
rateLimit('admin_login', 8, 900);

$action = sanitizeText($_POST['login_action'] ?? 'password', 20);

if ($action === '2fa') {
    $otp = sanitizeText($_POST['otp_code'] ?? '', 12);
    if ($otp === '') {
        loginFailureResponse('Authentication code is required.', 400);
    }
    completeAdminLoginWith2fa($otp);
}

if (!adminConfigured()) {
    loginFailureResponse(
        'Admin login is not configured. Create config/local.php from config/local.example.php or set server env vars.',
        503
    );
}

$username = sanitizeText($_POST['username'] ?? '', 100);
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    loginFailureResponse('Username and password are required.', 400);
}

if (!verifyAdminCredentials($username, $password)) {
    logSecurityEvent('failed login for user "' . $username . '" from ' . clientIp());
    loginFailureResponse('Invalid username or password.', 401);
}

completeAdminLoginOrRequire2fa($username);
