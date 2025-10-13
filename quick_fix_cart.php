<?php
require_once 'conexion.php';

try {
    // Actualizar el carrito #5 para usar la nueva sesión del navegador
    $new_session_id = 'cpa8ar35bm1p3oi604d8b1qvfq'; // Tu sesión actual del navegador
    $cart_id = 5; // El carrito con items
    
    echo "Actualizando carrito #$cart_id con nueva sesión...\n";
    
    $stmt = $conn->prepare("
        UPDATE carts 
        SET session_id = :session_id,
            updated_at = NOW()
        WHERE id = :cart_id
    ");
    
    $stmt->execute([
        ':session_id' => $new_session_id,
        ':cart_id' => $cart_id
    ]);
    
    echo "✓ Carrito actualizado exitosamente!\n";
    echo "\nAhora recarga: http://localhost/angelow/tienda/cart.php\n";
    
    // Verificar
    $stmt = $conn->prepare("SELECT session_id FROM carts WHERE id = :cart_id");
    $stmt->execute([':cart_id' => $cart_id]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Session ID del carrito: {$updated['session_id']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
