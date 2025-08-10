
<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

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
$item_id = $data['item_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

// Validación adicional
if (!$item_id || $quantity < 1 || !is_numeric($quantity)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    // Verificar que el item pertenece al carrito del usuario/sesión actual
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    $query = "SELECT ci.id, ci.size_variant_id, ci.quantity as current_quantity 
              FROM cart_items ci
              JOIN carts c ON ci.cart_id = c.id
              WHERE ci.id = :item_id AND ";
    
    if ($user_id) {
        $query .= "c.user_id = :user_id";
        $params = [':item_id' => $item_id, ':user_id' => $user_id];
    } else {
        $query .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':item_id' => $item_id, ':session_id' => $session_id];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception("Item no encontrado en tu carrito");
    }
    
    // Verificar disponibilidad del stock en product_size_variants
    $max_quantity = null;
    if ($item['size_variant_id']) {
        $stmt = $conn->prepare("SELECT quantity FROM product_size_variants WHERE id = :size_variant_id");
        $stmt->execute([':size_variant_id' => $item['size_variant_id']]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variant) {
            throw new Exception("Variante de producto no encontrada");
        }
        
        $max_quantity = (int)$variant['quantity'];
        if ($max_quantity < $quantity) {
            throw new Exception("No hay suficiente stock disponible. Máximo disponible: " . $max_quantity);
        }
    }
    
    // Actualizar cantidad
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE id = :id");
    $updateResult = $stmt->execute([':quantity' => $quantity, ':id' => $item_id]);
    
    if (!$updateResult) {
        throw new Exception("Error al actualizar la cantidad en la base de datos");
    }
    
    // Obtener información actualizada del item
    $stmt = $conn->prepare("
        SELECT 
            ci.quantity,
            COALESCE(psv.price, p.price) as item_price,
            (COALESCE(psv.price, p.price) * ci.quantity) as item_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
        WHERE ci.id = :item_id
    ");
    $stmt->execute([':item_id' => $item_id]);
    $updatedItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular el nuevo total del carrito
    $stmt = $conn->prepare("
        SELECT SUM(COALESCE(psv.price, p.price) * ci.quantity) as cart_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
        WHERE ci.cart_id = (SELECT cart_id FROM cart_items WHERE id = :item_id LIMIT 1)
    ");
    $stmt->execute([':item_id' => $item_id]);
    $cartTotal = $stmt->fetch(PDO::FETCH_ASSOC)['cart_total'] ?? 0;
    
    $response = [
        'success' => true,
        'message' => 'Cantidad actualizada correctamente',
        'item_id' => $item_id,
        'new_quantity' => $quantity,
        'max_quantity' => $max_quantity,
        'item_price' => $updatedItem['item_price'] ?? 0,
        'item_total' => $updatedItem['item_total'] ?? 0,
        'cart_total' => $cartTotal
    ];
    
} catch (PDOException $e) {
    error_log("PDO Error in update-quantity.php: " . $e->getMessage());
    $response['error'] = 'Error al actualizar el carrito: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General Error in update-quantity.php: " . $e->getMessage());
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>