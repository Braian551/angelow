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
    
    $query = "SELECT ci.id FROM cart_items ci
              JOIN carts c ON ci.cart_id = c.id
              WHERE ci.id = :item_id AND ";
    
    if ($user_id) {
        $query .= "c.user_id = :user_id";
        $params = [':item_id' => $item_id, ':user_id' => $user_id];
    } else {
        $query .= "c.session_id = :session_id";
        $params = [':item_id' => $item_id, ':session_id' => $session_id];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception("Item no encontrado en tu carrito");
    }
    
    // Eliminar el item
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    
    $response = [
        'success' => true,
        'message' => 'Producto eliminado del carrito'
    ];
} catch (PDOException $e) {
    $response['error'] = 'Error al eliminar del carrito: ' . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>