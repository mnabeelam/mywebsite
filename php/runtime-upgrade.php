<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/runtime-upgrade.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = sanitizeText($_GET['action'] ?? $_POST['action'] ?? 'plan', 32);

if ($method === 'GET') {
    switch ($action) {
        case 'plan':
            jsonResponse([
                'status' => 'ok',
                'plan' => runtimeBuildUpgradePlan(true),
            ]);
            break;
        case 'job':
            $job = runtimeLoadUpgradeJob();
            jsonResponse([
                'status' => 'ok',
                'job' => runtimePublicJobSummary($job),
            ]);
            break;
        case 'history':
            jsonResponse([
                'status' => 'ok',
                'history' => runtimeListHistory(80),
                'prefs' => runtimeLoadPrefs(),
            ]);
            break;
        default:
            jsonResponse(['error' => 'Unknown action.'], 400);
    }
}

requirePost();
requireSameOrigin();

$body = readJsonBody();
if ($body !== []) {
    $_POST = array_merge($_POST, $body);
}

$csrf = sanitizeText($_POST['csrf_token'] ?? '', 128);
if (!validateCsrf($csrf)) {
    jsonResponse(['error' => 'Invalid security token. Refresh and try again.'], 403);
}

$username = (string) ($_SESSION['admin_user'] ?? 'admin');

switch ($action) {
    case 'start':
        requireAdminPermission('backup.edit');
        $confirm = sanitizeText($_POST['confirm_text'] ?? '', 64);
        if (strcasecmp($confirm, 'APPROVE UPGRADE') !== 0) {
            jsonResponse(['error' => 'Type APPROVE UPGRADE to confirm.'], 400);
        }
        try {
            $job = runtimeStartUpgradeJob($username);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
        jsonResponse([
            'status' => 'ok',
            'message' => 'Runtime upgrade started. Apache may restart briefly; this page will show progress.',
            'job' => $job,
            'plan' => runtimeBuildUpgradePlan(true),
        ]);
        break;

    case 'defer':
        requireAdminPermission('backup.view');
        $mode = sanitizeText($_POST['defer_mode'] ?? 'ask_again', 32);
        $plan = runtimeBuildUpgradePlan(true);
        try {
            $prefs = runtimeDeferUpgrade($username, $mode, $plan);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        $plan = runtimeBuildUpgradePlan(true);
        $message = match ($mode) {
            'remind_later' => 'Saved. We will remind you when pending updates change. See System Updates tab anytime.',
            'tab_only' => 'Saved to System Updates. The banner is hidden — review pending updates there when ready.',
            default => 'Closed. The reminder banner will show again on your next visit.',
        };
        jsonResponse([
            'status' => 'ok',
            'message' => $message,
            'prefs' => $prefs,
            'plan' => $plan,
        ]);
        break;

    case 'clear_defer':
        requireAdminPermission('backup.view');
        runtimeClearDeferPrefs();
        jsonResponse([
            'status' => 'ok',
            'message' => 'Update reminders are enabled again.',
            'plan' => runtimeBuildUpgradePlan(true),
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
