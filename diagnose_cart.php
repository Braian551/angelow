<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    echo "=== DIAGNÓSTICO DEL CARRITO ===\n\n";
    
    // Verificar sesión
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    echo "User ID: " . ($user_id ?? 'NULL (usuario no logueado)') . "\n";
    echo "Session ID: $session_id\n\n";
    
    // Verificar todos los carritos
    echo "=== TODOS LOS CARRITOS ===\n";
    $stmt = $conn->query('SELECT id, user_id, session_id, created_at FROM carts ORDER BY created_at DESC');
    $carts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($carts as $cart) {
        echo "\nCarrito ID: {$cart['id']}\n";
        echo "  User ID: " . ($cart['user_id'] ?? 'NULL') . "\n";
        echo "  Session ID: " . ($cart['session_id'] ?? 'NULL') . "\n";
        echo "  Creado: {$cart['created_at']}\n";
        
        // Ver items de este carrito
        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute([':cart_id' => $cart['id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "  Items: $count\n";
    }
    
    // Buscar carrito del usuario actual
    echo "\n\n=== BUSCAR CARRITO ACTUAL ===\n";
    
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
        echo "Buscando por user_id: $user_id\n";
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $session_id];
        echo "Buscando por session_id: $session_id\n";
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";
    
    echo "Query: $cartQuery\n";
    
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart) {
        echo "\n✓ Carrito encontrado: ID {$cart['id']}\n";
        
        // Obtener items del carrito
        echo "\n=== ITEMS DEL CARRITO ===\n";
        $itemsQuery = "
            SELECT 
                ci.id as item_id,
                ci.quantity,
                ci.product_id,
                ci.color_variant_id,
                ci.size_variant_id,
                p.name as product_name,
                p.slug as product_slug,
                p.price as product_price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = :cart_id
        ";
        
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([':cart_id' => $cart['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($items) > 0) {
            foreach ($items as $item) {
                echo "\nItem ID: {$item['item_id']}\n";
                echo "  Producto: {$item['product_name']}\n";
                echo "  Cantidad: {$item['quantity']}\n";
                echo "  Product ID: {$item['product_id']}\n";
                echo "  Color Variant ID: " . ($item['color_variant_id'] ?? 'NULL') . "\n";
                echo "  Size Variant ID: " . ($item['size_variant_id'] ?? 'NULL') . "\n";
            }
        } else {
            echo "⚠ No hay items en este carrito\n";
        }
        
        // Probar la consulta completa de cart.php
        echo "\n\n=== PRUEBA DE CONSULTA COMPLETA ===\n";
        $fullQuery = "
            SELECT 
                ci.id as item_id,
                ci.quantity,
                p.id as product_id,
                p.name as product_name,
                p.slug as product_slug,
                p.price as product_price,
                COALESCE(vi.image_path, pi.image_path) as primary_image,
                c.name as color_name,
                s.name as size_name,
                pcv.id as color_variant_id,
                psv.id as size_variant_id,
                psv.price as variant_price,
                (COALESCE(psv.price, p.price) * ci.quantity) as item_total,
                psv.quantity as stock_available
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_color_variants pcv ON ci.color_variant_id = pcv.id
            LEFT JOIN colors c ON pcv.color_id = c.id
            LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
            LEFT JOIN sizes s ON psv.size_id = s.id
            LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
            WHERE ci.cart_id = :cart_id
            GROUP BY ci.id
        ";
        
        try {
            $stmt = $conn->prepare($fullQuery);
            $stmt->execute([':cart_id' => $cart['id']]);
            $fullItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Items encontrados con consulta completa: " . count($fullItems) . "\n";
            
            if (count($fullItems) > 0) {
                foreach ($fullItems as $item) {
                    echo "\n✓ Item completo:\n";
                    echo "  Producto: {$item['product_name']}\n";
                    echo "  Cantidad: {$item['quantity']}\n";
                    echo "  Imagen: " . ($item['primary_image'] ?? 'NULL') . "\n";
                    echo "  Color: " . ($item['color_name'] ?? 'NULL') . "\n";
                    echo "  Talla: " . ($item['size_name'] ?? 'NULL') . "\n";
                    echo "  Precio variante: " . ($item['variant_price'] ?? 'NULL') . "\n";
                    echo "  Stock: " . ($item['stock_available'] ?? 'NULL') . "\n";
                }
            }
        } catch (Exception $e) {
            echo "✗ Error en consulta completa: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "\n✗ No se encontró carrito para el usuario/sesión actual\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
