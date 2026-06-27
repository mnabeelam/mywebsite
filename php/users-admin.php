<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/user-services.php';
require_once __DIR__ . '/lib/db-records.php';
require_once __DIR__ . '/lib/rate-limit.php';

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminPermission('users.view');
    $roleDefs = adminRoleDefinitions();
    $roles = [];
    foreach ($roleDefs as $key => $role) {
        $roles[] = [
            'id' => $key,
            'label' => $role['label'],
            'description' => $role['description'] ?? '',
            'permissions' => $role['permissions'],
        ];
    }

    jsonResponse([
        'status' => 'ok',
        'users' => listAdminUsers(),
        'roles' => $roles,
        'database' => dbDatabaseSummary(),
    ]);
}

requirePost();
requireCsrfFromRequest();
requireSameOrigin();
rateLimit('users_admin', 40, 3600);

$action = sanitizeText($_POST['action'] ?? '', 40);

try {
    switch ($action) {
        case 'create_user':
            requireAdminPermission('users.edit');
            $user = createAdminUser([
                'username' => $_POST['username'] ?? '',
                'password' => (string) ($_POST['password'] ?? ''),
                'display_name' => $_POST['display_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? 'viewer',
                'active' => !empty($_POST['active']),
            ]);
            jsonResponse([
                'status' => 'ok',
                'message' => 'User account created.',
                'user' => $user,
            ]);
            break;

        case 'update_user':
            requireAdminPermission('users.edit');
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                jsonResponse(['error' => 'User ID is required.'], 400);
            }
            $user = updateAdminUser($userId, [
                'display_name' => $_POST['display_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? '',
                'active' => !empty($_POST['active']),
                'password' => (string) ($_POST['password'] ?? ''),
            ]);
            jsonResponse([
                'status' => 'ok',
                'message' => 'User account updated.',
                'user' => $user,
            ]);
            break;

        case 'delete_user':
            requireAdminPermission('users.edit');
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                jsonResponse(['error' => 'User ID is required.'], 400);
            }
            deleteAdminUser($userId, currentAdminUserId());
            jsonResponse([
                'status' => 'ok',
                'message' => 'User account deleted.',
            ]);
            break;

        default:
            jsonResponse(['error' => 'Unknown action.'], 400);
    }
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    appLog('users-admin: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not complete user action.'], 500);
}
