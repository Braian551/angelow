<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    echo "=== DIAGNÓSTICO COMPLETO ===\n\n";
    
    // 1. Verificar sesión actual
    echo "1. SESIÓN ACTUAL:\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   User ID en sesión: " . ($_SESSION['user_id'] ?? 'NULL') . "\n\n";
    
    // 2. Ver todos los carritos con items
    echo "2. TODOS LOS CARRITOS CON ITEMS:\n";
    $stmt = $conn->query("
        SELECT 
            c.id as cart_id,
            c.user_id,
            c.session_id,
            c.created_at,
            COUNT(ci.id) as item_count
        FROM carts c
        LEFT JOIN cart_items ci ON c.id = ci.cart_id
        GROUP BY c.id
        HAVING item_count > 0
        ORDER BY c.created_at DESC
    ");
    
    $carts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($carts as $cart) {
        echo "\n   Carrito ID: {$cart['cart_id']}\n";
        echo "   User ID: " . ($cart['user_id'] ?? 'NULL') . "\n";
        echo "   Session ID: " . ($cart['session_id'] ?? 'NULL') . "\n";
        echo "   Items: {$cart['item_count']}\n";
        echo "   Creado: {$cart['created_at']}\n";
    }
    
    // 3. Verificar qué carrito encuentra el código actual
    echo "\n\n3. CARRITO QUE ENCUENTRA EL CÓDIGO:\n";
    
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
        echo "   Buscando por user_id: $user_id\n";
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $session_id];
        echo "   Buscando por session_id: $session_id\n";
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $foundCart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($foundCart) {
        echo "   ✓ Carrito encontrado: ID {$foundCart['id']}\n";
        
        // Ver items
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $foundCart['id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Items en este carrito: $count\n";
    } else {
        echo "   ✗ No se encontró carrito\n";
    }
    
    // 4. Solución propuesta
    echo "\n\n4. SOLUCIÓN:\n";
    
    if (!$foundCart && count($carts) > 0) {
        echo "   Hay carritos con items pero no coinciden con tu sesión actual.\n";
        echo "   Opciones:\n";
        echo "   A) Migrar items del carrito más reciente a tu sesión actual\n";
        echo "   B) Iniciar sesión con el usuario correcto\n";
        echo "   C) Usar el session_id del carrito existente\n\n";
        
        echo "   ¿Quieres migrar los items del carrito más reciente a tu sesión actual? (s/n)\n";
        echo "   Ejecuta: php migrate_cart_items.php\n";
    } else if ($foundCart && count($carts) > 1) {
        echo "   Tienes múltiples carritos. Puedes consolidarlos.\n";
        echo "   Ejecuta: php consolidate_carts.php\n";
    } else if ($foundCart) {
        echo "   ✓ Todo parece estar correcto.\n";
        echo "   El problema podría estar en la consulta SQL de cart.php\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
