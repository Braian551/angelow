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
$name = trim($input['name'] ?? '');
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = trim($input['phone'] ?? '');
$jobTitle = null; // admin_profiles removed
$department = null;
$responsibilities = null;
$emergencyContact = null;

if ($name === '' || !$email) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nombre y correo son obligatorios']);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $temporaryPassword = null;

    if ($existing) {
        $userId = $existing['id'];
        $update = $conn->prepare("UPDATE users SET name = ?, phone = ?, role = 'admin' WHERE id = ?");
        $update->execute([$name, $phone ?: null, $userId]);
    } else {
        $userId = function_exists('create_unique_id') ? create_unique_id() : bin2hex(random_bytes(10));
        $temporaryPassword = bin2hex(random_bytes(5));
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (id, name, email, phone, password, role, is_blocked, created_at) VALUES (?, ?, ?, ?, ?, 'admin', 0, NOW())");
        $insert->execute([$userId, $name, $email, $phone ?: null, $passwordHash]);
    }

    // Admin profile service removed — no additional profile stored

    $conn->commit();

    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.phone, u.image, u.created_at, u.last_access, u.is_blocked,
            -- admin_profiles removed, profile fields are no longer returned
        FROM users u
        WHERE u.id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'item' => formatAdminRow($row),
        'temporary_password' => $temporaryPassword
    ]);
} catch (Throwable $e) {
    $conn->rollBack();
    error_log('admins.create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear el administrador']);
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
        // admin_profiles removed: no profile fields returned
    ];
}
