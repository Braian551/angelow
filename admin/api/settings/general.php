<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';
require_once __DIR__ . '/../../../settings/site_settings.php';


header('Content-Type: application/json');

requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $settings = fetch_site_settings($conn);
        echo json_encode([
            'success' => true,
            'settings' => $settings,
            'definitions' => site_settings_definitions()
        ]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
        exit;
    }

    $input = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];

    if (!empty($_FILES['brand_logo']['tmp_name'])) {
        $logoPath = handleLogoUpload($_FILES['brand_logo']);
        if ($logoPath) {
            $input['brand_logo'] = $logoPath;
        }
    }

    save_site_settings($conn, $input, $_SESSION['user_id'] ?? null);
    $settings = fetch_site_settings($conn);

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} catch (Throwable $e) {
    error_log('settings.general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la configuracion']);
}

function handleLogoUpload(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return null;
    }

    $dir = __DIR__ . '/../../../uploads/settings';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'brand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'uploads/settings/' . $filename;
}
