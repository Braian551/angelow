<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

try {
    // Verificar que el usuario sea administrador
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit();
    }

    $userId = $_SESSION['user_id'];

    // Marcar todas las órdenes existentes como vistas
    $query = "
        INSERT INTO order_views (order_id, user_id, viewed_at)
        SELECT o.id, ?, NOW()
        FROM orders o
        LEFT JOIN order_views ov ON o.id = ov.order_id AND ov.user_id = ?
        WHERE ov.id IS NULL
        ON DUPLICATE KEY UPDATE viewed_at = NOW()
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId, $userId]);
    
    $affectedRows = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'Órdenes marcadas como vistas',
        'marked_count' => $affectedRows
    ]);

} catch (PDOException $e) {
    error_log("Error al marcar órdenes como vistas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud'
    ]);
}
