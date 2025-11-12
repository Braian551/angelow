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
        'message' => 'Debes iniciar sesión para agregar productos a tu lista de deseos'
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
    // Verificar que el producto existe y está activo
    $checkProduct = $conn->prepare("SELECT id FROM products WHERE id = :product_id AND is_active = 1");
    $checkProduct->execute([':product_id' => $productId]);
    
    if (!$checkProduct->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    // Verificar si ya está en la wishlist
    $checkWishlist = $conn->prepare("SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $checkWishlist->execute([
        ':user_id' => $userId,
        ':product_id' => $productId
    ]);

    if ($checkWishlist->fetch()) {
        echo json_encode([
            'success' => true, 
            'message' => 'El producto ya está en tu lista de deseos',
            'already_exists' => true
        ]);
        exit;
    }

    // Agregar a la wishlist
    $insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (:user_id, :product_id, NOW())");
    $insert->execute([
        ':user_id' => $userId,
        ':product_id' => $productId
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Producto agregado a tu lista de deseos'
    ]);

} catch (PDOException $e) {
    error_log("Error adding to wishlist: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al agregar el producto a la lista de deseos'
    ]);
}
