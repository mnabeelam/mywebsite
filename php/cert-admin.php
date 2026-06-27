<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/cert-services.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminPermission('content.view');
    jsonResponse([
        'status' => 'ok',
        'certifications' => certAdminSummary(),
    ]);
}

requirePost();
requireCsrfFromRequest();
requireSameOrigin();
requireAdminPermission('content.edit');
rateLimit('cert_admin', 20, 3600);

$action = sanitizeText($_POST['action'] ?? '', 40);

switch ($action) {
    case 'save_cert':
        try {
            $upload = null;
            if (!empty($_FILES['certificate']['tmp_name'])) {
                $upload = $_FILES['certificate'];
            }

            $input = [
                'id' => $_POST['id'] ?? '',
                'title' => $_POST['title'] ?? '',
                'issuer' => $_POST['issuer'] ?? '',
                'year' => $_POST['year'] ?? '',
                'description' => $_POST['description'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? 0,
                'active' => !empty($_POST['active']),
            ];

            if ($upload !== null) {
                require_once __DIR__ . '/lib/cert-parser.php';
                try {
                    $extracted = extractCertFieldsFromUpload($upload);
                    $input = mergeCertInputWithExtracted($input, $extracted);
                } catch (InvalidArgumentException $e) {
                    if (trim((string) ($input['title'] ?? '')) === '') {
                        throw $e;
                    }
                }
            }

            $cert = saveCertification($input, $upload);

            jsonResponse([
                'status' => 'ok',
                'message' => 'Certification saved.',
                'certification' => $cert,
                'certifications' => certAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('cert-admin save: ' . $e->getMessage());
            jsonResponse(['error' => 'Could not save certification.'], 500);
        }
        break;

    case 'delete_cert':
        $id = sanitizeText($_POST['id'] ?? '', 40);
        if ($id === '' || !deleteCertification($id)) {
            jsonResponse(['error' => 'Certification could not be deleted.'], 400);
        }
        jsonResponse([
            'status' => 'ok',
            'message' => 'Certification deleted.',
            'certifications' => certAdminSummary(),
        ]);
        break;

    case 'extract_cert':
        try {
            require_once __DIR__ . '/lib/cert-parser.php';
            $file = certUploadFromRequest();
            $extracted = extractCertFieldsFromUpload($file);

            jsonResponse([
                'status' => 'ok',
                'message' => (string) ($extracted['message'] ?? 'Fields auto-filled from the certificate file. Review them before saving.'),
                'extracted' => [
                    'title' => (string) ($extracted['title'] ?? ''),
                    'issuer' => (string) ($extracted['issuer'] ?? ''),
                    'year' => (string) ($extracted['year'] ?? ''),
                    'description' => (string) ($extracted['description'] ?? ''),
                ],
                'source' => (string) ($extracted['source'] ?? 'unknown'),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('cert-admin extract: ' . $e->getMessage());
            jsonResponse(['error' => 'Could not read the certificate file.'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
