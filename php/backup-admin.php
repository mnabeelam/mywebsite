<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/backup-services.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminPermission('backup.view');
    $download = sanitizeText($_GET['download'] ?? '', 80);
    if ($download !== '') {
        try {
            if ($download === 'latest') {
                $backups = listStoredBackups();
                if ($backups === []) {
                    jsonResponse(['error' => 'No backup files found.'], 404);
                }
                streamBackupFile(storedBackupPath($backups[0]['filename']));
            }

            streamBackupFile(storedBackupPath($download));
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('backup-admin download: ' . $e->getMessage());
            jsonResponse(['error' => 'Backup could not be downloaded.'], 500);
        }
    }

    jsonResponse([
        'status' => 'ok',
        'backup' => backupAdminSummary(),
    ]);
}

requirePost();
requireCsrfFromRequest();
requireSameOrigin();
requireAdminPermission('backup.edit');
rateLimit('backup_admin', 10, 3600);

$action = sanitizeText($_POST['action'] ?? '', 40);

switch ($action) {
    case 'create_backup':
        try {
            if (!backupZipAvailable()) {
                jsonResponse(['error' => 'PHP zip extension is not enabled. Enable extension=zip in php.ini and restart the web server.'], 500);
            }

            $password = normalizeBackupPassword((string) ($_POST['backup_password'] ?? ''));
            validateBackupPassword($password);
            $scope = normalizeBackupScope((string) ($_POST['backup_scope'] ?? 'site'));

            if (($scope === 'database' || $scope === 'both') && !databaseBackupAvailable()) {
                jsonResponse(['error' => 'Database backup is unavailable. Check DB_DRIVER and MySQL/SQLite settings in config/local.php.'], 400);
            }

            $result = createBackupZip($scope, null, $password);
            jsonResponse([
                'status' => 'ok',
                'message' => backupScopeLabel($scope) . ' backup created. Use the same password to restore it.',
                'created' => $result,
                'backup' => backupAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('backup-admin create: ' . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 500);
        }
        break;

    case 'restore_backup':
        $confirm = sanitizeText($_POST['confirm'] ?? '', 20);
        if ($confirm !== 'RESTORE') {
            jsonResponse(['error' => 'Type RESTORE to confirm restore.'], 400);
        }

        if (empty($_FILES['backup_file']['tmp_name'])) {
            jsonResponse(['error' => 'Choose a backup ZIP file.'], 400);
        }

        $upload = $_FILES['backup_file'];
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'Backup upload failed.'], 400);
        }

        $name = strtolower((string) ($upload['name'] ?? ''));
        if (!str_ends_with($name, '.zip')) {
            jsonResponse(['error' => 'Backup file must be a .zip archive.'], 400);
        }

        $password = normalizeBackupPassword((string) ($_POST['backup_password'] ?? ''));

        try {
            readBackupManifest((string) $upload['tmp_name'], $password);
            $result = restoreSiteBackupZip((string) $upload['tmp_name'], $password);
            jsonResponse([
                'status' => 'ok',
                'message' => 'Backup restored (' . (int) $result['restored_files'] . ' files).',
                'restored' => $result,
                'backup' => backupAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('backup-admin restore: ' . $e->getMessage());
            jsonResponse(['error' => 'Restore failed. ' . $e->getMessage()], 500);
        }
        break;

    case 'reset_all':
        $confirm = sanitizeText($_POST['confirm'] ?? '', 20);
        if ($confirm !== 'RESET ALL') {
            jsonResponse(['error' => 'Type RESET ALL to confirm.'], 400);
        }

        $keepAdminAuth = !empty($_POST['keep_admin_auth']);
        $password = normalizeBackupPassword((string) ($_POST['backup_password'] ?? ''));

        try {
            validateBackupPassword($password);
            $result = resetAllSiteData($keepAdminAuth, $password);
            jsonResponse([
                'status' => 'ok',
                'message' => 'All website data has been reset. A password-protected backup was saved first.',
                'reset' => $result,
                'backup' => backupAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('backup-admin reset: ' . $e->getMessage());
            jsonResponse(['error' => 'Reset failed. ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
