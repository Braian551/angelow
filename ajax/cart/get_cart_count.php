<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = [
    'success' => false,
    'count' => 0
];

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    // Obtener el carrito activo
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $session_id];
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";

    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    $itemCount = 0;

    if ($cart) {
        // Contar el número de variantes distintas (filas en cart_items)
        $itemsQuery = "SELECT SUM(quantity) as variant_count FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([':cart_id' => $cart['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $itemCount = $result['variant_count'] ?? 0;
    }

    $_SESSION['cart_count'] = $itemCount;
    
    $response = [
        'success' => true,
        'count' => $itemCount
    ];
} catch (PDOException $e) {
    error_log("Error al obtener el carrito: " . $e->getMessage());
    $response['error'] = 'Error al obtener el conteo del carrito';
}

echo json_encode($response);