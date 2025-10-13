<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Cart</title></head><body><pre>";
echo "=== DEBUGGING CART.PHP LINE BY LINE ===\n\n";

try {
    echo "1. Session Info:\n";
    echo "   Session ID: $session_id\n";
    echo "   User ID: " . ($user_id ?? 'NULL') . "\n\n";
    
    // Paso 1: Buscar carrito
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
    
    echo "2. Cart Search:\n";
    if ($cart) {
        echo "   ✓ Cart found: ID {$cart['id']}\n\n";
    } else {
        echo "   ✗ No cart found\n";
        echo "   </pre></body></html>";
        exit;
    }
    
    // Paso 2: Contar items simples
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute([':cart_id' => $cart['id']]);
    $simpleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "3. Simple Count:\n";
    echo "   Items in cart_items: $simpleCount\n\n";
    
    if ($simpleCount == 0) {
        echo "   ✗ No items in database\n";
        echo "   </pre></body></html>";
        exit;
    }
    
    // Paso 3: Query sin GROUP BY
    echo "4. Query WITHOUT GROUP BY:\n";
    $itemsQuerySimple = "
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
    ";
    
    $stmt = $conn->prepare($itemsQuerySimple);
    $stmt->execute([':cart_id' => $cart['id']]);
    $itemsNoGroup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Results WITHOUT GROUP BY: " . count($itemsNoGroup) . " rows\n\n";
    
    if (count($itemsNoGroup) > 0) {
        foreach ($itemsNoGroup as $idx => $item) {
            echo "   Row " . ($idx + 1) . ":\n";
            echo "     - Product: {$item['product_name']}\n";
            echo "     - Quantity: {$item['quantity']}\n";
            echo "     - Image: " . ($item['primary_image'] ?? 'NULL') . "\n";
            echo "     - Color: " . ($item['color_name'] ?? 'NULL') . "\n";
            echo "     - Size: " . ($item['size_name'] ?? 'NULL') . "\n\n";
        }
    }
    
    // Paso 4: Query con GROUP BY (como en cart.php)
    echo "5. Query WITH GROUP BY (as in cart.php):\n";
    $itemsQueryGroup = "
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
    
    $stmt = $conn->prepare($itemsQueryGroup);
    $stmt->execute([':cart_id' => $cart['id']]);
    $itemsWithGroup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Results WITH GROUP BY: " . count($itemsWithGroup) . " rows\n\n";
    
    if (count($itemsWithGroup) > 0) {
        foreach ($itemsWithGroup as $idx => $item) {
            echo "   Row " . ($idx + 1) . ":\n";
            echo "     - Product: {$item['product_name']}\n";
            echo "     - Quantity: {$item['quantity']}\n";
            echo "     - Image: " . ($item['primary_image'] ?? 'NULL') . "\n";
            echo "     - Color: " . ($item['color_name'] ?? 'NULL') . "\n";
            echo "     - Size: " . ($item['size_name'] ?? 'NULL') . "\n\n";
        }
    } else {
        echo "   ✗ GROUP BY is removing all rows!\n\n";
    }
    
    // Paso 5: Verificar si empty() es el problema
    echo "6. PHP empty() check:\n";
    $cartItems = $itemsWithGroup;
    echo "   count(\$cartItems) = " . count($cartItems) . "\n";
    echo "   empty(\$cartItems) = " . (empty($cartItems) ? 'TRUE' : 'FALSE') . "\n\n";
    
    if (empty($cartItems)) {
        echo "   ✗ empty() returns TRUE - Cart will show as empty!\n";
        echo "   This is the problem in cart.php line ~113\n";
    } else {
        echo "   ✓ empty() returns FALSE - Cart should show items\n";
    }
    
} catch (PDOException $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    echo "   SQL State: {$e->getCode()}\n";
}

echo "</pre></body></html>";
?>
