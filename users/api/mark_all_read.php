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

try {
    // Marcar todas como leídas
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);

    $affected = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'Todas las notificaciones marcadas como leídas',
        'affected' => $affected
    ]);

} catch (PDOException $e) {
    error_log("Error al marcar todas como leídas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar notificaciones'
    ]);
}
