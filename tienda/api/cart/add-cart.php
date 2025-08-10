<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$color_variant_id = $data['color_variant_id'] ?? null;
$size_variant_id = $data['size_variant_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

if (!$product_id || !$size_variant_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que el size_variant_id existe y obtener sus datos
    $stmt = $conn->prepare("
        SELECT psv.*, s.name as size_name, pcv.product_id, pcv.id as color_variant_id,
               c.name as color_name, pcv.color_id
        FROM product_size_variants psv
        JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
        LEFT JOIN sizes s ON psv.size_id = s.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        WHERE psv.id = :size_variant_id
    ");
    $stmt->execute([':size_variant_id' => $size_variant_id]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$variant) {
        throw new Exception("Variante de tamaño no encontrada");
    }
    
    // Verificar que la variante pertenece al producto
    if ($variant['product_id'] != $product_id) {
        throw new Exception("La variante de tamaño no pertenece a este producto");
    }
    
    // Si se proporcionó color_variant_id, verificar que coincide
    if ($color_variant_id && $variant['color_variant_id'] != $color_variant_id) {
        throw new Exception("La variante de color no coincide con la variante de tamaño seleccionada");
    }
    
    // Verificar stock
    if ($variant['quantity'] < $quantity) {
        throw new Exception("No hay suficiente stock disponible");
    }
    
    // Obtener o crear carrito
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    $cartQuery = "SELECT id FROM carts WHERE ";
    if ($user_id) {
        $cartQuery .= "user_id = :user_id";
        $params = [':user_id' => $user_id];
    } else {
        $cartQuery .= "session_id = :session_id";
        $params = [':session_id' => $session_id];
    }
    $cartQuery .= " ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        // Crear nuevo carrito
        $stmt = $conn->prepare("INSERT INTO carts (user_id, session_id) VALUES (:user_id, :session_id)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':session_id' => $user_id ? null : $session_id
        ]);
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }
    
    // Verificar si el item ya existe en el carrito
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items 
                            WHERE cart_id = :cart_id 
                            AND product_id = :product_id 
                            AND color_variant_id " . ($color_variant_id ? "= :color_variant_id" : "IS NULL") . "
                            AND size_variant_id = :size_variant_id");
    
    $params = [
        ':cart_id' => $cart_id,
        ':product_id' => $product_id,
        ':size_variant_id' => $size_variant_id
    ];
    
    if ($color_variant_id) {
        $params[':color_variant_id'] = $color_variant_id;
    }
    
    $stmt->execute($params);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingItem) {
        // Verificar stock para la nueva cantidad total
        $newQuantity = $existingItem['quantity'] + $quantity;
        if ($variant['quantity'] < $newQuantity) {
            throw new Exception("No hay suficiente stock disponible para la cantidad solicitada");
        }
        
        // Actualizar cantidad si ya existe
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
        $stmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $existingItem['id']
        ]);
    } else {
        // Añadir nuevo item al carrito
        $stmt = $conn->prepare("INSERT INTO cart_items 
                                (cart_id, product_id, color_variant_id, size_variant_id, quantity) 
                                VALUES 
                                (:cart_id, :product_id, :color_variant_id, :size_variant_id, :quantity)");
        $stmt->execute([
            ':cart_id' => $cart_id,
            ':product_id' => $product_id,
            ':color_variant_id' => $color_variant_id ?: null,
            ':size_variant_id' => $size_variant_id,
            ':quantity' => $quantity
        ]);
    }
    
    $response = [
        'success' => true,
        'message' => 'Producto añadido al carrito',
        'cart_id' => $cart_id,
        'variant_info' => [
            'size' => $variant['size_name'],
            'color' => $variant['color_name'] ?? 'N/A',
            'price' => $variant['price']
        ]
    ];
} catch (PDOException $e) {
    $response['error'] = 'Error al actualizar el carrito: ' . $e->getMessage();
    error_log("Error en add-cart.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Error en add-cart.php: " . $e->getMessage());
}

echo json_encode($response);
?>