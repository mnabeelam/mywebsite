<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site-settings.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/user-services.php';
require_once __DIR__ . '/admin-2fa.php';

function adminConfigured(): bool
{
    if (databaseReady()) {
        $count = (int) db()->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn();
        if ($count > 0) {
            return true;
        }
    }

    $stored = loadStoredAdminAuth();
    if ($stored !== null) {
        return true;
    }

    $user = configValue('ADMIN_USERNAME');
    $pass = configValue('ADMIN_PASSWORD');
    $hash = configValue('ADMIN_PASSWORD_HASH');

    return $user !== '' && ($pass !== '' || $hash !== '');
}

function verifyAdminCredentials(string $username, string $password): bool
{
    if (!adminConfigured()) {
        return false;
    }

    if (databaseReady()) {
        $user = verifyDatabaseUserCredentials($username, $password);
        if ($user !== null) {
            return true;
        }

        $count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return false;
        }
    }

    $stored = loadStoredAdminAuth();
    if ($stored !== null) {
        if ($username !== $stored['username']) {
            return false;
        }
        return password_verify($password, $stored['password_hash']);
    }

    $expectedUser = configValue('ADMIN_USERNAME');
    if ($username !== $expectedUser) {
        return false;
    }

    $hash = configValue('ADMIN_PASSWORD_HASH');
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    $expectedPass = configValue('ADMIN_PASSWORD');
    return hash_equals($expectedPass, $password);
}

function loginAdmin(string $username): void
{
    if (databaseReady()) {
        $user = findUserByUsername($username);
        if ($user !== null && $user['active']) {
            loginAdminUser($user);
            return;
        }
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_user'] = $username;
    $_SESSION['admin_user_id'] = 0;
    $_SESSION['admin_role'] = 'super_admin';
    $_SESSION['admin_permissions'] = permissionsForRole('super_admin');
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
}

function logoutAdmin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function isAdminAuthenticated(): bool
{
    if (empty($_SESSION['admin_authenticated'])) {
        return false;
    }

    $maxAge = 3600 * 8;
    $idleMax = 1800;
    $loginTime = (int) ($_SESSION['admin_login_time'] ?? 0);
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? $loginTime);

    if ($loginTime > 0 && (time() - $loginTime) > $maxAge) {
        logoutAdmin();
        return false;
    }

    if ($lastActivity > 0 && (time() - $lastActivity) > $idleMax) {
        logoutAdmin();
        return false;
    }

    $_SESSION['admin_last_activity'] = time();
    return true;
}

function currentAdminUserId(): int
{
    return (int) ($_SESSION['admin_user_id'] ?? 0);
}

function currentAdminRole(): string
{
    return normalizeAdminRole((string) ($_SESSION['admin_role'] ?? 'viewer'));
}

function adminSessionPayload(): array
{
    $roles = adminRoleDefinitions();
    $role = currentAdminRole();

    return [
        'username' => (string) ($_SESSION['admin_user'] ?? ''),
        'user_id' => currentAdminUserId(),
        'role' => $role,
        'role_label' => $roles[$role]['label'] ?? $role,
        'permissions' => $_SESSION['admin_permissions'] ?? permissionsForRole($role),
    ];
}

function requireAdminAuth(): void
{
    require_once __DIR__ . '/site-services.php';
    requireAdminIpAllowed();

    if (!isAdminAuthenticated()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfFromRequest(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($token)) {
        jsonResponse(['error' => 'Invalid security token. Refresh and try again.'], 403);
    }
}

function wantsJsonResponse(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function completeAdminLoginOrRequire2fa(string $username): void
{
    if (admin2faAppliesToUser($username)) {
        beginPendingAdmin2fa($username);
        if (wantsJsonResponse()) {
            jsonResponse([
                'status' => '2fa_required',
                'redirect' => 'index.php?step=2fa',
                'message' => 'Enter the 6-digit code from your authenticator app.',
            ]);
        }
        redirectTo('index.php?step=2fa');
    }

    loginSuccessResponse($username);
}

function completeAdminLoginWith2fa(string $code): void
{
    $username = pendingAdmin2faUser();
    if ($username === null) {
        loginFailureResponse('Authentication session expired. Please log in again.', 401);
    }

    if (!verifyAdmin2faCode($code)) {
        logSecurityEvent('failed 2FA for user "' . $username . '" from ' . clientIp());
        loginFailureResponse('Invalid authentication code.', 401);
    }

    clearPendingAdmin2fa();
    loginSuccessResponse($username);
}

function loginFailureResponse(string $message, int $status = 401): void
{
    if (wantsJsonResponse()) {
        jsonResponse(['error' => $message], $status);
    }

    redirectTo('index.php?error=' . urlencode($message));
}

function loginSuccessResponse(string $username): void
{
    loginAdmin($username);

    if (wantsJsonResponse()) {
        jsonResponse([
            'status' => 'ok',
            'redirect' => 'dashboard.php',
            'session' => adminSessionPayload(),
        ]);
    }

    redirectTo('dashboard.php');
}
