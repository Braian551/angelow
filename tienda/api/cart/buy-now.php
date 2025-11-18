<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$color_variant_id = $data['color_variant_id'] ?? null;
$size_variant_id = $data['size_variant_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

if (!$product_id || !$size_variant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Validate variant exists and belongs to product
    $stmt = $conn->prepare("SELECT psv.*, pcv.product_id FROM product_size_variants psv JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id WHERE psv.id = :size_variant_id");
    $stmt->execute([':size_variant_id' => $size_variant_id]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$variant) throw new Exception('Variante no encontrada');
    if ($variant['product_id'] != $product_id) throw new Exception('La variante no pertenece a este producto');

    // Check stock
    if ($variant['quantity'] < $quantity) throw new Exception('No hay suficiente stock disponible');

    // Create a new cart for the buy now flow (do not reuse existing cart)
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();

    $stmt = $conn->prepare('INSERT INTO carts (user_id, session_id, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$user_id, $user_id ? null : $session_id]);
    $cart_id = $conn->lastInsertId();

    // Insert cart item
    $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, color_variant_id, size_variant_id, quantity) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$cart_id, $product_id, $color_variant_id ?: null, $size_variant_id, $quantity]);

    // Store buy now cart id in session so checkout page can use it
    $_SESSION['buy_now_cart_id'] = $cart_id;

    echo json_encode(['success' => true, 'message' => 'Compra ahora lista', 'cart_id' => $cart_id]);
} catch (PDOException $e) {
    error_log('buy-now.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al procesar la solicitud']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>