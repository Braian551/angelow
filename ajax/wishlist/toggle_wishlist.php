<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Soporta application/json y application/x-www-form-urlencoded
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    // Intentar parsing de urlencoded
    parse_str($raw, $input);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in', 'message' => 'Debes iniciar sesión para agregar productos a favoritos']);
    exit;
}

$productId = isset($input['product_id']) ? intval($input['product_id']) : null;
$userId = $_SESSION['user_id'];

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

try {
    // Verificar si ya está en la wishlist
    $checkWishlist = $conn->prepare("SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $checkWishlist->execute([':user_id' => $userId, ':product_id' => $productId]);

    if ($checkWishlist->fetch()) {
        // Eliminar
        $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $delete->execute([':user_id' => $userId, ':product_id' => $productId]);

        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Producto eliminado de favoritos']);
        exit;
    } else {
        // Insertar
        $insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (:user_id, :product_id, NOW())");
        $insert->execute([':user_id' => $userId, ':product_id' => $productId]);

        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Producto agregado a favoritos']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Error toggling wishlist: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
