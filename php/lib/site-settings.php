<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/db-records.php';

function siteSettingsPath(): string
{
    return __DIR__ . '/../storage/site-settings.json';
}

function adminAuthPath(): string
{
    return __DIR__ . '/../storage/admin-auth.json';
}

function defaultSiteSettings(): array
{
    return [
        'contact_email' => '',
        'display_email' => '',
        'phone' => '',
        'linkedin' => '',
        'updated' => date('c'),
    ];
}

function loadSiteSettings(): array
{
    if (databaseReady()) {
        $settings = array_merge(defaultSiteSettings(), dbLoadSiteSettingsMap());
        $settings['updated'] = (string) ($settings['updated'] ?? date('c'));
        return $settings;
    }

    $path = siteSettingsPath();
    if (!is_readable($path)) {
        return defaultSiteSettings();
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return defaultSiteSettings();
    }

    return array_merge(defaultSiteSettings(), $data);
}

function saveSiteSettings(array $settings): void
{
    $settings['updated'] = date('c');

    if (databaseReady()) {
        dbSaveSiteSettingsMap($settings);
    }

    $dir = dirname(siteSettingsPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $settings['updated'] = date('c');
    file_put_contents(siteSettingsPath(), json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
}

function effectiveContactEmail(): string
{
    $settings = loadSiteSettings();
    $stored = trim((string) ($settings['contact_email'] ?? ''));
    if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_EMAIL)) {
        return $stored;
    }

    $config = trim(configValue('CONTACT_EMAIL'));
    if ($config !== '' && filter_var($config, FILTER_VALIDATE_EMAIL)) {
        return $config;
    }

    return '';
}

function adminNotificationEmails(): array
{
    $emails = [];

    $contact = effectiveContactEmail();
    if ($contact !== '') {
        $emails[] = $contact;
    }

    $notify = trim(configValue('ADMIN_NOTIFY_EMAIL'));
    if ($notify !== '' && filter_var($notify, FILTER_VALIDATE_EMAIL)) {
        $emails[] = $notify;
    }

    if (databaseReady()) {
        require_once __DIR__ . '/user-services.php';
        $rows = db()->query(
            'SELECT email FROM users WHERE active = 1 AND email != "" ORDER BY role = "super_admin" DESC, username ASC'
        )->fetchAll();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $email = trim((string) ($row['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }
    }

    return array_values(array_unique($emails));
}

function sendAdminNotification(string $subject, string $body, string $replyTo = ''): bool
{
    $recipients = adminNotificationEmails();
    if ($recipients === []) {
        appLog('mail: no admin notification email configured for "' . $subject . '"');
        return false;
    }

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $headerLine = implode("\r\n", $headers);
    $sent = false;

    foreach ($recipients as $to) {
        if (@mail($to, $subject, $body, $headerLine)) {
            $sent = true;
        }
    }

    if (!$sent) {
        appLog('mail: admin notification could not be sent for "' . $subject . '"');
    }

    return $sent;
}

function publicSiteContact(): array
{
    $settings = loadSiteSettings();

    return [
        'email' => trim((string) ($settings['display_email'] ?? '')) ?: effectiveContactEmail(),
        'phone' => trim((string) ($settings['phone'] ?? '')),
        'linkedin' => trim((string) ($settings['linkedin'] ?? '')),
    ];
}

function loadStoredAdminAuth(): ?array
{
    $path = adminAuthPath();
    if (!is_readable($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return null;
    }

    $username = trim((string) ($data['username'] ?? ''));
    $hash = trim((string) ($data['password_hash'] ?? ''));

    if ($username === '' || $hash === '') {
        return null;
    }

    return [
        'username' => $username,
        'password_hash' => $hash,
        'updated' => (string) ($data['updated'] ?? ''),
    ];
}

function saveStoredAdminAuth(string $username, string $passwordHash): void
{
    $dir = dirname(adminAuthPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    file_put_contents(adminAuthPath(), json_encode([
        'username' => $username,
        'password_hash' => $passwordHash,
        'updated' => date('c'),
    ], JSON_PRETTY_PRINT), LOCK_EX);

    appLog('security: admin password updated from dashboard');
}

function adminSettingsSummary(): array
{
    $settings = loadSiteSettings();
    $storedAuth = loadStoredAdminAuth();
    $sessionUser = isAdminAuthenticated() ? adminSessionPayload() : null;
    $username = $sessionUser['username'] ?? $storedAuth['username'] ?? configValue('ADMIN_USERNAME');

    return [
        'username' => $username,
        'contact_email' => $settings['contact_email'] ?? '',
        'display_email' => $settings['display_email'] ?? '',
        'phone' => $settings['phone'] ?? '',
        'linkedin' => $settings['linkedin'] ?? '',
        'effective_contact_email' => effectiveContactEmail(),
        'password_managed_in_dashboard' => ($sessionUser['user_id'] ?? 0) > 0 || $storedAuth !== null,
        'database_user' => ($sessionUser['user_id'] ?? 0) > 0,
        'role' => $sessionUser['role'] ?? '',
        'role_label' => $sessionUser['role_label'] ?? '',
        'database' => dbDatabaseSummary(),
        'updated' => $settings['updated'] ?? null,
    ];
}
