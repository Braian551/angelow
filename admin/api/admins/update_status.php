<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$input = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];
$userId = $input['user_id'] ?? null;
$action = $input['action'] ?? null;

if (!$userId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solicitud incompleta']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Administrador no encontrado']);
        exit;
    }

    $temporaryPassword = null;

    switch ($action) {
        case 'block':
            $conn->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?')->execute([$userId]);
            break;
        case 'unblock':
            $conn->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?')->execute([$userId]);
            break;
        case 'update_profile':
            $fields = [];
            $params = [];
            if (isset($input['name'])) {
                $fields[] = 'name = ?';
                $params[] = trim($input['name']);
            }
            if (isset($input['phone'])) {
                $fields[] = 'phone = ?';
                $params[] = trim($input['phone']);
            }
            if ($fields) {
                $params[] = $userId;
                $conn->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
            }
            // admin_profiles removed: do not save profile fields
            break;
        case 'reset_password':
            $temporaryPassword = bin2hex(random_bytes(5));
            $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
            $conn->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Accion no valida']);
            exit;
    }

    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.phone, u.image, u.created_at, u.last_access, u.is_blocked
        WHERE u.id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'item' => formatAdminRow($row),
        'temporary_password' => $temporaryPassword
    ]);
} catch (Throwable $e) {
    error_log('admins.update_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el administrador']);
}

function formatAdminRow(array $row): array {
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'image' => $row['image'],
        'created_at' => $row['created_at'],
        'last_access' => $row['last_access'],
        'is_blocked' => (bool) $row['is_blocked'],
        // Admin profiles removed
    ];
}
