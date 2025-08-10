<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = isset($data['product_id']) ? intval($data['product_id']) : null;

if (!$productId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto no proporcionado']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para usar la lista de deseos']);
    exit;
}

try {
    // Eliminar de la lista de deseos
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    
    $rowsAffected = $stmt->rowCount();
    
    if ($rowsAffected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado de tu lista de deseos'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'El producto no estaba en tu lista de deseos'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error removing from wishlist: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar de la lista de deseos: ' . $e->getMessage()]);
}
?>