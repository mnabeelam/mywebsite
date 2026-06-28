<?php
declare(strict_types=1);

/**
 * Reset admin login to credentials in config/local.php.
 * Run: php scripts/reset-admin-login.php
 */
require __DIR__ . '/../php/lib/bootstrap.php';
require __DIR__ . '/../php/lib/config.php';
require __DIR__ . '/../php/lib/site-settings.php';
require __DIR__ . '/../php/lib/user-services.php';
require __DIR__ . '/../php/lib/site-services.php';

$username = trim(configValue('ADMIN_USERNAME'));
$plain = configValue('ADMIN_PASSWORD');
$hash = trim(configValue('ADMIN_PASSWORD_HASH'));

if ($username === '') {
    fwrite(STDERR, "Set ADMIN_USERNAME in config/local.php first.\n");
    exit(1);
}

if ($hash === '' && $plain === '') {
    fwrite(STDERR, "Set ADMIN_PASSWORD or ADMIN_PASSWORD_HASH in config/local.php.\n");
    exit(1);
}

$passwordHash = $hash !== '' ? $hash : password_hash($plain, PASSWORD_DEFAULT);

$authPath = __DIR__ . '/../php/storage/admin-auth.json';
if (is_file($authPath)) {
    unlink($authPath);
    echo "Removed stored admin-auth.json (dashboard password override).\n";
}

saveStoredAdminAuth($username, $passwordHash);
echo "Stored admin credentials synced from config/local.php.\n";

if (databaseReady()) {
    $stmt = db()->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $id = (int) $stmt->fetchColumn();
    $now = date('c');
    if ($id > 0) {
        $upd = db()->prepare('UPDATE users SET password_hash = :hash, active = 1, updated_at = :ts WHERE id = :id');
        $upd->execute(['hash' => $passwordHash, 'ts' => $now, 'id' => $id]);
        echo "Updated database user password for \"{$username}\".\n";
    } else {
        $ins = db()->prepare(
            'INSERT INTO users (username, password_hash, display_name, email, role, active, created_at, updated_at)
             VALUES (:username, :hash, :display, :email, :role, 1, :created, :updated)'
        );
        $ins->execute([
            'username' => $username,
            'hash' => $passwordHash,
            'display' => $username,
            'email' => trim(configValue('CONTACT_EMAIL')),
            'role' => 'super_admin',
            'created' => $now,
            'updated' => $now,
        ]);
        echo "Created database super_admin user \"{$username}\".\n";
    }
}

$policyPath = __DIR__ . '/../php/storage/access-policy.json';
if (is_readable($policyPath)) {
    $policy = json_decode((string) file_get_contents($policyPath), true);
    if (is_array($policy)) {
        $policy['admin_ip_whitelist'] = [];
        file_put_contents($policyPath, json_encode($policy, JSON_PRETTY_PRINT), LOCK_EX);
        echo "Cleared admin IP whitelist (all IPs allowed until you set it in Network tab).\n";
    }
}

echo "\nLogin at /admin/index.php with:\n";
echo "  Username: {$username}\n";
if ($plain !== '') {
    echo "  Password: (value of ADMIN_PASSWORD in config/local.php)\n";
} else {
    echo "  Password: your ADMIN_PASSWORD_HASH plain text equivalent\n";
}
