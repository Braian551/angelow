<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $sessionId = session_id();
    
    if ($userId) {
        $stmt = $conn->prepare("
            SELECT ci.product_id 
            FROM carts c
            JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $conn->prepare("
            SELECT ci.product_id 
            FROM carts c
            JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.session_id = ? AND c.user_id IS NULL
        ");
        $stmt->execute([$sessionId]);
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el carrito: ' . $e->getMessage()
    ]);
}
?>