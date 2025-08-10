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
    // Verificar si el producto existe
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Producto no disponible");
    }
    
    // Verificar si ya está en la lista de deseos
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        echo json_encode([
            'success' => true,
            'message' => 'El producto ya está en tu lista de deseos'
        ]);
        exit;
    }
    
    // Añadir a la lista de deseos
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto añadido a tu lista de deseos'
    ]);
    
} catch (PDOException $e) {
    error_log("Error adding to wishlist: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al añadir a la lista de deseos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>