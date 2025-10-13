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
$item_id = $data['item_id'] ?? null;

if (!$item_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de item no proporcionado']);
    exit;
}

try {
    // Verificar que el item pertenece al carrito del usuario/sesión actual
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    $query = "SELECT ci.id, ci.cart_id FROM cart_items ci
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
    
    $cart_id = $item['cart_id'];
    
    // Eliminar el item
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    
    // Calcular el nuevo total del carrito
    $stmt = $conn->prepare("
        SELECT SUM(COALESCE(psv.price, p.price) * ci.quantity) as cart_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
        WHERE ci.cart_id = :cart_id
    ");
    $stmt->execute([':cart_id' => $cart_id]);
    $cartTotal = $stmt->fetch(PDO::FETCH_ASSOC)['cart_total'] ?? 0;
    
    $response = [
        'success' => true,
        'message' => 'Producto eliminado del carrito',
        'cart_total' => $cartTotal
    ];
} catch (PDOException $e) {
    $response['error'] = 'Error al eliminar del carrito: ' . $e->getMessage();
    error_log("Error en remove-cart.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Error en remove-cart.php: " . $e->getMessage());
}

echo json_encode($response);
?>