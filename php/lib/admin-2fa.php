<?php
declare(strict_types=1);

require_once __DIR__ . '/totp.php';

function admin2faPath(): string
{
    return __DIR__ . '/../storage/admin-2fa.json';
}

function loadAdmin2faSettings(): array
{
    $path = admin2faPath();
    if (!is_readable($path)) {
        return ['enabled' => false, 'secret' => '', 'username' => '', 'updated' => null];
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return ['enabled' => false, 'secret' => '', 'username' => '', 'updated' => null];
    }

    return [
        'enabled' => (bool) ($data['enabled'] ?? false),
        'secret' => trim((string) ($data['secret'] ?? '')),
        'username' => trim((string) ($data['username'] ?? '')),
        'updated' => $data['updated'] ?? null,
    ];
}

function saveAdmin2faSettings(array $settings): void
{
    $dir = dirname(admin2faPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $payload = [
        'enabled' => (bool) ($settings['enabled'] ?? false),
        'secret' => trim((string) ($settings['secret'] ?? '')),
        'username' => trim((string) ($settings['username'] ?? '')),
        'updated' => date('c'),
    ];

    file_put_contents(admin2faPath(), json_encode($payload, JSON_PRETTY_PRINT), LOCK_EX);
}

function admin2faIsEnabled(): bool
{
    $settings = loadAdmin2faSettings();

    return $settings['enabled'] && $settings['secret'] !== '';
}

function admin2faAppliesToUser(string $username): bool
{
    if (!admin2faIsEnabled()) {
        return false;
    }

    $settings = loadAdmin2faSettings();
    $boundUser = $settings['username'];

    if ($boundUser === '') {
        return true;
    }

    return strcasecmp($boundUser, $username) === 0;
}

function beginPendingAdmin2fa(string $username): void
{
    $_SESSION['pending_2fa_user'] = $username;
    $_SESSION['pending_2fa_time'] = time();
    unset($_SESSION['admin_authenticated']);
}

function clearPendingAdmin2fa(): void
{
    unset($_SESSION['pending_2fa_user'], $_SESSION['pending_2fa_time']);
}

function pendingAdmin2faUser(): ?string
{
    $user = trim((string) ($_SESSION['pending_2fa_user'] ?? ''));
    $started = (int) ($_SESSION['pending_2fa_time'] ?? 0);
    if ($user === '' || $started <= 0) {
        return null;
    }

    if ((time() - $started) > 300) {
        clearPendingAdmin2fa();
        return null;
    }

    return $user;
}

function verifyAdmin2faCode(string $code): bool
{
    $settings = loadAdmin2faSettings();
    if (!$settings['enabled'] || $settings['secret'] === '') {
        return false;
    }

    return totpVerify($settings['secret'], $code, 2);
}

function admin2faPublicSummary(): array
{
    $settings = loadAdmin2faSettings();

    return [
        'enabled' => admin2faIsEnabled(),
        'username' => $settings['username'],
        'updated' => $settings['updated'],
    ];
}

function admin2faSetupPendingSecret(): ?string
{
    $secret = trim((string) ($_SESSION['pending_2fa_setup_secret'] ?? ''));
    return $secret !== '' ? $secret : null;
}

function admin2faBeginSetup(string $username): array
{
    $secret = totpGenerateSecret(16);
    $_SESSION['pending_2fa_setup_secret'] = $secret;
    $_SESSION['pending_2fa_setup_user'] = $username;
    $_SESSION['pending_2fa_setup_time'] = time();

    $issuer = 'MNA Portfolio Admin';
    $uri = totpProvisioningUri($secret, $username, $issuer);

    return [
        'secret' => $secret,
        'provisioning_uri' => $uri,
        'issuer' => $issuer,
    ];
}

function admin2faConfirmSetup(string $username, string $code): void
{
    $secret = admin2faSetupPendingSecret();
    $setupUser = trim((string) ($_SESSION['pending_2fa_setup_user'] ?? ''));
    $started = (int) ($_SESSION['pending_2fa_setup_time'] ?? 0);

    if ($secret === null || $setupUser === '' || strcasecmp($setupUser, $username) !== 0) {
        throw new InvalidArgumentException('2FA setup expired. Start again from the dashboard.');
    }

    if ($started > 0 && (time() - $started) > 600) {
        unset($_SESSION['pending_2fa_setup_secret'], $_SESSION['pending_2fa_setup_user'], $_SESSION['pending_2fa_setup_time']);
        throw new InvalidArgumentException('2FA setup expired. Start again.');
    }

    if (!totpVerify($secret, $code, 2)) {
        throw new InvalidArgumentException('Invalid verification code. Check Google Authenticator and try again.');
    }

    saveAdmin2faSettings([
        'enabled' => true,
        'secret' => $secret,
        'username' => $username,
    ]);

    unset($_SESSION['pending_2fa_setup_secret'], $_SESSION['pending_2fa_setup_user'], $_SESSION['pending_2fa_setup_time']);
    appLog('security: 2FA enabled for admin user "' . $username . '"');
}

function admin2faDisable(string $username, string $code): void
{
    if (!admin2faIsEnabled()) {
        return;
    }

    $settings = loadAdmin2faSettings();
    if ($settings['username'] !== '' && strcasecmp($settings['username'], $username) !== 0) {
        throw new InvalidArgumentException('2FA is bound to another admin account.');
    }

    if (!totpVerify($settings['secret'], $code, 2)) {
        throw new InvalidArgumentException('Invalid 2FA code.');
    }

    saveAdmin2faSettings(['enabled' => false, 'secret' => '', 'username' => $username]);
    appLog('security: 2FA disabled for admin user "' . $username . '"');
}
