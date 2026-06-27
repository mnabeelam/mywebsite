<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function adminRoleDefinitions(): array
{
    return [
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Full access including user management.',
            'permissions' => ['*'],
        ],
        'admin' => [
            'label' => 'Administrator',
            'description' => 'All site modules except user management.',
            'permissions' => ['account.*', 'network.*', 'content.*', 'shop.*', 'backup.*'],
        ],
        'shop_manager' => [
            'label' => 'Shop Manager',
            'description' => 'Online store products, orders, and stock.',
            'permissions' => ['account.view', 'shop.view', 'shop.edit'],
        ],
        'content_manager' => [
            'label' => 'Content Manager',
            'description' => 'Certifications, knowledge, and contact inbox.',
            'permissions' => ['account.view', 'content.view', 'content.edit'],
        ],
        'network_manager' => [
            'label' => 'Network Manager',
            'description' => 'Visitor log and IP access controls.',
            'permissions' => ['account.view', 'network.view', 'network.edit'],
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access to dashboard modules.',
            'permissions' => ['account.view', 'network.view', 'content.view', 'shop.view', 'backup.view'],
        ],
    ];
}

function normalizeAdminRole(string $role): string
{
    $role = strtolower(trim($role));
    $roles = adminRoleDefinitions();

    return array_key_exists($role, $roles) ? $role : 'viewer';
}

function permissionsForRole(string $role): array
{
    $role = normalizeAdminRole($role);
    $roles = adminRoleDefinitions();

    return $roles[$role]['permissions'] ?? ['account.view'];
}

function permissionMatches(string $granted, string $required): bool
{
    if ($granted === '*' || $granted === $required) {
        return true;
    }

    if (str_ends_with($granted, '.*')) {
        $prefix = substr($granted, 0, -1);
        return str_starts_with($required, $prefix);
    }

    return false;
}

function adminHasPermission(string $permission, ?array $permissions = null): bool
{
    if ($permissions === null) {
        $permissions = $_SESSION['admin_permissions'] ?? null;
    }
    if (!is_array($permissions) || $permissions === []) {
        if (!empty($_SESSION['admin_authenticated'])) {
            $permissions = permissionsForRole(currentAdminRole());
        } else {
            $permissions = [];
        }
    }

    foreach ($permissions as $granted) {
        if (permissionMatches((string) $granted, $permission)) {
            return true;
        }
    }

    return false;
}

function requireAdminPermission(string $permission): void
{
    requireAdminAuth();
    if (!adminHasPermission($permission)) {
        jsonResponse(['error' => 'You do not have permission for this action.'], 403);
    }
}

function findUserByUsername(string $username): ?array
{
    if (!databaseReady()) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch();

    return is_array($row) ? normalizeUserRow($row) : null;
}

function findUserById(int $id): ?array
{
    if (!databaseReady() || $id <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return is_array($row) ? normalizeUserRow($row) : null;
}

function normalizeUserRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'username' => (string) ($row['username'] ?? ''),
        'display_name' => (string) ($row['display_name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'role' => normalizeAdminRole((string) ($row['role'] ?? 'viewer')),
        'active' => !empty($row['active']),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'last_login_at' => (string) ($row['last_login_at'] ?? ''),
    ];
}

function publicUserPayload(array $user): array
{
    $roles = adminRoleDefinitions();

    return [
        'id' => $user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'role_label' => $roles[$user['role']]['label'] ?? $user['role'],
        'active' => $user['active'],
        'created_at' => $user['created_at'],
        'updated_at' => $user['updated_at'],
        'last_login_at' => $user['last_login_at'],
    ];
}

function verifyDatabaseUserCredentials(string $username, string $password): ?array
{
    $user = findUserByUsername($username);
    if ($user === null || !$user['active']) {
        return null;
    }

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $user['id']]);
    $hash = (string) ($stmt->fetchColumn() ?: '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return null;
    }

    return $user;
}

function loginAdminUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_user'] = $user['username'];
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_permissions'] = permissionsForRole($user['role']);
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();

    if (databaseReady()) {
        $stmt = db()->prepare('UPDATE users SET last_login_at = :ts, updated_at = :ts WHERE id = :id');
        $now = date('c');
        $stmt->execute(['ts' => $now, 'id' => $user['id']]);
    }
}

function listAdminUsers(): array
{
    if (!databaseReady()) {
        return [];
    }

    $rows = db()->query('SELECT * FROM users ORDER BY username ASC')->fetchAll();
    $users = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $users[] = publicUserPayload(normalizeUserRow($row));
        }
    }

    return $users;
}

function countActiveSuperAdmins(?int $excludeUserId = null): int
{
    if (!databaseReady()) {
        return 0;
    }

    $sql = 'SELECT COUNT(*) FROM users WHERE role = :role AND active = 1';
    $params = ['role' => 'super_admin'];
    if ($excludeUserId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeUserId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function createAdminUser(array $input): array
{
    if (!databaseReady()) {
        throw new RuntimeException('Database is not available.');
    }

    $username = sanitizeText((string) ($input['username'] ?? ''), 80);
    $password = (string) ($input['password'] ?? '');
    $displayName = sanitizeText((string) ($input['display_name'] ?? ''), 120);
    $email = sanitizeText((string) ($input['email'] ?? ''), 180);
    $role = normalizeAdminRole((string) ($input['role'] ?? 'viewer'));
    $active = !empty($input['active']);

    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
        throw new InvalidArgumentException('Username must be 3–40 characters (letters, numbers, dot, dash, underscore).');
    }
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters.');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email address is not valid.');
    }
    if (findUserByUsername($username) !== null) {
        throw new InvalidArgumentException('Username already exists.');
    }

    $now = date('c');
    $stmt = db()->prepare(
        'INSERT INTO users (username, password_hash, display_name, email, role, active, created_at, updated_at)
         VALUES (:username, :password_hash, :display_name, :email, :role, :active, :created_at, :updated_at)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'display_name' => $displayName !== '' ? $displayName : $username,
        'email' => $email,
        'role' => $role,
        'active' => $active ? 1 : 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $user = findUserById((int) db()->lastInsertId());
    if ($user === null) {
        throw new RuntimeException('Could not create user.');
    }

    appLog('security: admin user created "' . $username . '" role=' . $role);
    return publicUserPayload($user);
}

function updateAdminUser(int $userId, array $input): array
{
    $user = findUserById($userId);
    if ($user === null) {
        throw new InvalidArgumentException('User not found.');
    }

    $displayName = sanitizeText((string) ($input['display_name'] ?? $user['display_name']), 120);
    $email = sanitizeText((string) ($input['email'] ?? $user['email']), 180);
    $role = normalizeAdminRole((string) ($input['role'] ?? $user['role']));
    $active = array_key_exists('active', $input) ? !empty($input['active']) : $user['active'];
    $password = (string) ($input['password'] ?? '');

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email address is not valid.');
    }
    if ($password !== '' && strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters.');
    }

    if ($user['role'] === 'super_admin' && $role !== 'super_admin' && countActiveSuperAdmins($userId) === 0) {
        throw new InvalidArgumentException('At least one active Super Admin is required.');
    }
    if (!$active && $user['role'] === 'super_admin' && countActiveSuperAdmins($userId) === 0) {
        throw new InvalidArgumentException('Cannot deactivate the last active Super Admin.');
    }

    $now = date('c');
    $sql = 'UPDATE users SET display_name = :display_name, email = :email, role = :role, active = :active, updated_at = :updated_at';
    $params = [
        'display_name' => $displayName,
        'email' => $email,
        'role' => $role,
        'active' => $active ? 1 : 0,
        'updated_at' => $now,
        'id' => $userId,
    ];

    if ($password !== '') {
        $sql .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $updated = findUserById($userId);
    if ($updated === null) {
        throw new RuntimeException('Could not update user.');
    }

    appLog('security: admin user updated id=' . $userId);
    return publicUserPayload($updated);
}

function deleteAdminUser(int $userId, int $currentUserId): void
{
    $user = findUserById($userId);
    if ($user === null) {
        throw new InvalidArgumentException('User not found.');
    }
    if ($userId === $currentUserId) {
        throw new InvalidArgumentException('You cannot delete your own account while signed in.');
    }
    if ($user['role'] === 'super_admin' && countActiveSuperAdmins($userId) === 0) {
        throw new InvalidArgumentException('Cannot delete the last active Super Admin.');
    }

    $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    appLog('security: admin user deleted id=' . $userId . ' username=' . $user['username']);
}

function updateOwnAdminPassword(int $userId, string $currentPassword, string $newPassword): void
{
    $user = findUserById($userId);
    if ($user === null) {
        throw new InvalidArgumentException('User not found.');
    }

    if (!verifyDatabaseUserCredentials($user['username'], $currentPassword)) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }
    if (strlen($newPassword) < 8) {
        throw new InvalidArgumentException('New password must be at least 8 characters.');
    }

    $stmt = db()->prepare('UPDATE users SET password_hash = :hash, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        'updated_at' => date('c'),
        'id' => $userId,
    ]);

    appLog('security: password updated for user id=' . $userId);
}
