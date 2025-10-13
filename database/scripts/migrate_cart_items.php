<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    echo "=== MIGRACIÓN DE ITEMS DEL CARRITO ===\n\n";
    
    $current_session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    echo "Session actual: $current_session_id\n";
    echo "User ID: " . ($user_id ?? 'NULL') . "\n\n";
    
    // Encontrar el carrito más reciente con items
    $stmt = $conn->query("
        SELECT 
            c.id,
            c.user_id,
            c.session_id,
            COUNT(ci.id) as item_count
        FROM carts c
        INNER JOIN cart_items ci ON c.id = ci.cart_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 1
    ");
    
    $sourceCart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCart) {
        echo "✗ No hay carritos con items para migrar\n";
        exit;
    }
    
    echo "Carrito origen encontrado:\n";
    echo "  ID: {$sourceCart['id']}\n";
    echo "  Items: {$sourceCart['item_count']}\n";
    echo "  User ID: " . ($sourceCart['user_id'] ?? 'NULL') . "\n";
    echo "  Session ID: " . ($sourceCart['session_id'] ?? 'NULL') . "\n\n";
    
    // Actualizar el session_id del carrito para que coincida con la sesión actual
    echo "Actualizando session_id del carrito...\n";
    
    $stmt = $conn->prepare("
        UPDATE carts 
        SET session_id = :new_session_id,
            user_id = :user_id,
            updated_at = NOW()
        WHERE id = :cart_id
    ");
    
    $stmt->execute([
        ':new_session_id' => $user_id ? null : $current_session_id,
        ':user_id' => $user_id,
        ':cart_id' => $sourceCart['id']
    ]);
    
    echo "✓ Carrito actualizado\n\n";
    
    // Verificar
    echo "Verificando...\n";
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $current_session_id];
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cart['id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "✓ Carrito encontrado con ID: {$cart['id']}\n";
        echo "✓ Items en el carrito: $count\n\n";
        echo "✓ Migración exitosa! Ahora puedes ir a:\n";
        echo "  http://localhost/angelow/tienda/cart.php\n";
    } else {
        echo "✗ Error: No se pudo encontrar el carrito después de la migración\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
