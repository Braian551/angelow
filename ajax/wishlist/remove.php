<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'not_logged_in',
        'message' => 'Debes iniciar sesión'
    ]);
    exit;
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['product_id'] ?? null;
$userId = $_SESSION['user_id'];

// Validar datos
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

try {
    // Eliminar de la wishlist
    $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $delete->execute([
        ':user_id' => $userId,
        ':product_id' => $productId
    ]);

    if ($delete->rowCount() > 0) {
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
    echo json_encode([
        'success' => false, 
        'message' => 'Error al eliminar el producto de la lista de deseos'
    ]);
}
