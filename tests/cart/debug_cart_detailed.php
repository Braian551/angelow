<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<pre>";
echo "=== DEBUG CART.PHP ===\n\n";

// Obtener el carrito del usuario
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

echo "1. INFORMACIÓN DE SESIÓN:\n";
echo "   Session ID: $session_id\n";
echo "   User ID: " . ($user_id ? $user_id : 'NULL (no logueado)') . "\n\n";

try {
    // Obtener el carrito activo (MISMA CONSULTA QUE cart.php)
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
        echo "2. BUSCANDO CARRITO POR:\n";
        echo "   Tipo: user_id\n";
        echo "   Valor: $user_id\n\n";
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $session_id];
        echo "2. BUSCANDO CARRITO POR:\n";
        echo "   Tipo: session_id\n";
        echo "   Valor: $session_id\n\n";
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";
    
    echo "   Query completa: $cartQuery\n\n";

    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        echo "3. ✓ CARRITO ENCONTRADO:\n";
        echo "   Cart ID: {$cart['id']}\n\n";
        
        // Verificar items con consulta simple
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cart['id']]);
        $simpleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "4. ITEMS EN CARRITO (consulta simple):\n";
        echo "   Total: $simpleCount\n\n";
        
        if ($simpleCount > 0) {
            // Ver los items básicos
            echo "5. ITEMS BÁSICOS:\n";
            $stmt = $conn->prepare("
                SELECT ci.id, ci.product_id, ci.quantity, ci.color_variant_id, ci.size_variant_id,
                       p.name as product_name
                FROM cart_items ci
                LEFT JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id
            ");
            $stmt->execute([':cart_id' => $cart['id']]);
            $basicItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($basicItems as $item) {
                echo "   - Item #{$item['id']}: {$item['product_name']} (Qty: {$item['quantity']})\n";
                echo "     Product ID: {$item['product_id']}\n";
                echo "     Color Variant ID: " . ($item['color_variant_id'] ?? 'NULL') . "\n";
                echo "     Size Variant ID: " . ($item['size_variant_id'] ?? 'NULL') . "\n\n";
            }
            
            // Ahora la consulta completa de cart.php
            echo "6. CONSULTA COMPLETA (como en cart.php):\n";
            $itemsQuery = "
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
                $stmt = $conn->prepare($itemsQuery);
                $stmt->execute([':cart_id' => $cart['id']]);
                $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "   Resultados: " . count($cartItems) . " items\n\n";
                
                if (count($cartItems) > 0) {
                    echo "   ✓ ITEMS COMPLETOS:\n";
                    foreach ($cartItems as $item) {
                        echo "   - {$item['product_name']}\n";
                        echo "     Cantidad: {$item['quantity']}\n";
                        echo "     Imagen: " . ($item['primary_image'] ?? 'NULL') . "\n";
                        echo "     Color: " . ($item['color_name'] ?? 'NULL') . "\n";
                        echo "     Talla: " . ($item['size_name'] ?? 'NULL') . "\n";
                        echo "     Precio: $" . number_format($item['variant_price'] ?? $item['product_price'], 0) . "\n";
                        echo "     Total: $" . number_format($item['item_total'], 0) . "\n\n";
                    }
                    
                    echo "7. VERIFICACIÓN EN cart.php:\n";
                    echo "   La variable \$cartItems debería tener " . count($cartItems) . " items\n";
                    echo "   La condición empty(\$cartItems) debería ser: " . (empty($cartItems) ? 'TRUE (VACÍO)' : 'FALSE (CON ITEMS)') . "\n\n";
                    
                    if (empty($cartItems)) {
                        echo "   ✗ PROBLEMA: cartItems está vacío aunque hay items en la BD!\n";
                    } else {
                        echo "   ✓ TODO CORRECTO: cartItems tiene datos\n\n";
                        echo "   Si aún ves 'carrito vacío', el problema está en:\n";
                        echo "   - La lógica de cart.php después de obtener los items\n";
                        echo "   - O hay una redirección/recarga que pierde la sesión\n";
                    }
                } else {
                    echo "   ✗ PROBLEMA: La consulta completa no devuelve resultados\n";
                    echo "   Posibles causas:\n";
                    echo "   - Los JOINs están filtrando los items\n";
                    echo "   - Hay un problema con GROUP BY\n";
                }
                
            } catch (Exception $e) {
                echo "   ✗ ERROR en consulta completa: {$e->getMessage()}\n";
            }
            
        } else {
            echo "5. ✗ NO HAY ITEMS EN EL CARRITO\n";
            echo "   El carrito existe pero está vacío\n";
        }
        
    } else {
        echo "3. ✗ NO SE ENCONTRÓ CARRITO\n\n";
        echo "4. TODOS LOS CARRITOS EN LA BD:\n";
        $stmt = $conn->query("
            SELECT id, user_id, session_id, created_at,
                   (SELECT COUNT(*) FROM cart_items WHERE cart_id = carts.id) as items
            FROM carts 
            ORDER BY created_at DESC
        ");
        $allCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allCarts as $c) {
            echo "   - Cart #{$c['id']}: {$c['items']} items\n";
            echo "     User ID: " . ($c['user_id'] ?? 'NULL') . "\n";
            echo "     Session ID: " . ($c['session_id'] ?? 'NULL') . "\n";
            echo "     Creado: {$c['created_at']}\n\n";
        }
        
        echo "5. SOLUCIÓN:\n";
        echo "   Abre: http://localhost/angelow/fix_cart_session.php\n";
        echo "   Y haz clic en 'Migrar Carrito'\n";
    }
    
} catch (PDOException $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
}

echo "</pre>";
?>
