<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

// Usar $conn de conexion.php
$pdo = $conn;

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Obtener conteo de notificaciones no leídas
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);

} catch (PDOException $e) {
    error_log("Error al obtener conteo de notificaciones: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'count' => 0
    ]);
}
