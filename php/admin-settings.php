<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/site-settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminPermission('account.view');
    jsonResponse([
        'status' => 'ok',
        'settings' => adminSettingsSummary(),
    ]);
}

requirePost();
requireAdminAuth();
requireCsrfFromRequest();
requireSameOrigin();
require_once __DIR__ . '/lib/rate-limit.php';
rateLimit('admin_settings', 20, 3600);

$action = sanitizeText($_POST['action'] ?? '', 40);

switch ($action) {
    case 'save_contact':
        requireAdminPermission('account.edit');
        $contactEmail = sanitizeText($_POST['contact_email'] ?? '', 180);
        $displayEmail = sanitizeText($_POST['display_email'] ?? '', 180);
        $phone = sanitizeText($_POST['phone'] ?? '', 40);
        $linkedin = sanitizeText($_POST['linkedin'] ?? '', 300);

        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Notification email is not valid.'], 400);
        }
        if ($displayEmail !== '' && !filter_var($displayEmail, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Public display email is not valid.'], 400);
        }

        $settings = loadSiteSettings();
        $settings['contact_email'] = $contactEmail;
        $settings['display_email'] = $displayEmail;
        $settings['phone'] = $phone;
        $settings['linkedin'] = $linkedin;
        saveSiteSettings($settings);

        jsonResponse([
            'status' => 'ok',
            'message' => 'Contact settings saved.',
            'settings' => adminSettingsSummary(),
        ]);
        break;

    case 'change_password':
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $username = sanitizeText($_POST['username'] ?? configValue('ADMIN_USERNAME'), 80);
        $userId = currentAdminUserId();

        if ($username === '') {
            jsonResponse(['error' => 'Username is required.'], 400);
        }
        if ($current === '' || $newPass === '' || $confirm === '') {
            jsonResponse(['error' => 'All password fields are required.'], 400);
        }
        if ($newPass !== $confirm) {
            jsonResponse(['error' => 'New password and confirmation do not match.'], 400);
        }
        if (strlen($newPass) < 8) {
            jsonResponse(['error' => 'New password must be at least 8 characters.'], 400);
        }

        if ($userId > 0) {
            if ($username !== (string) ($_SESSION['admin_user'] ?? '')) {
                jsonResponse(['error' => 'You can only change your own password.'], 403);
            }

            try {
                updateOwnAdminPassword($userId, $current, $newPass);
            } catch (InvalidArgumentException $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }

            jsonResponse([
                'status' => 'ok',
                'message' => 'Password updated successfully.',
                'settings' => adminSettingsSummary(),
            ]);
            break;
        }

        if (!verifyAdminCredentials($username, $current)) {
            jsonResponse(['error' => 'Current password is incorrect.'], 401);
        }

        saveStoredAdminAuth($username, password_hash($newPass, PASSWORD_DEFAULT));
        loginAdmin($username);

        jsonResponse([
            'status' => 'ok',
            'message' => 'Password updated successfully.',
            'settings' => adminSettingsSummary(),
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
