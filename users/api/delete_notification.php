<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

// Usar $conn de conexion.php
$pdo = $conn;

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de notificación requerido']);
    exit;
}

$notification_id = $data['notification_id'];

try {
    // Verificar que la notificación pertenezca al usuario
    $stmt_check = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$notification_id, $user_id]);
    
    if ($stmt_check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Notificación no encontrada']);
        exit;
    }

    // Eliminar notificación
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Notificación eliminada'
    ]);

} catch (PDOException $e) {
    error_log("Error al eliminar notificación: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar notificación'
    ]);
}
