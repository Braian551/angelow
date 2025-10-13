<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    echo "=== AGREGAR PRODUCTOS DE PRUEBA AL CARRITO ===\n\n";
    echo "Session ID: $session_id\n";
    echo "User ID: " . ($user_id ?? 'NULL') . "\n\n";
    
    // Crear o encontrar carrito
    $cartQuery = "SELECT id FROM carts WHERE ";
    if ($user_id) {
        $cartQuery .= "user_id = :user_id";
        $params = [':user_id' => $user_id];
    } else {
        $cartQuery .= "session_id = :session_id AND user_id IS NULL";
        $params = [':session_id' => $session_id];
    }
    $cartQuery .= " ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        // Crear nuevo carrito
        echo "Creando nuevo carrito...\n";
        $stmt = $conn->prepare("INSERT INTO carts (user_id, session_id, created_at) VALUES (:user_id, :session_id, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':session_id' => $user_id ? null : $session_id
        ]);
        $cart_id = $conn->lastInsertId();
        echo "✓ Carrito creado con ID: $cart_id\n\n";
    } else {
        $cart_id = $cart['id'];
        echo "✓ Carrito existente encontrado: ID $cart_id\n\n";
    }
    
    // Obtener producto de prueba
    $stmt = $conn->query("SELECT id, name FROM products WHERE is_active = 1 LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "✗ No hay productos activos en la base de datos\n";
        exit;
    }
    
    echo "Usando producto: {$product['name']} (ID: {$product['id']})\n\n";
    
    // Obtener variantes del producto
    $stmt = $conn->prepare("
        SELECT pcv.id as color_variant_id, c.name as color_name
        FROM product_color_variants pcv
        LEFT JOIN colors c ON pcv.color_id = c.id
        WHERE pcv.product_id = :product_id
        LIMIT 1
    ");
    $stmt->execute([':product_id' => $product['id']]);
    $colorVariant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colorVariant) {
        echo "⚠ Producto sin variantes de color, agregando sin variante...\n";
        $color_variant_id = null;
    } else {
        $color_variant_id = $colorVariant['color_variant_id'];
        echo "Variante de color: {$colorVariant['color_name']} (ID: $color_variant_id)\n";
    }
    
    // Obtener variante de tamaño
    $size_variant_id = null;
    if ($color_variant_id) {
        $stmt = $conn->prepare("
            SELECT psv.id as size_variant_id, s.name as size_name
            FROM product_size_variants psv
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE psv.color_variant_id = :color_variant_id
            LIMIT 1
        ");
        $stmt->execute([':color_variant_id' => $color_variant_id]);
        $sizeVariant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sizeVariant) {
            $size_variant_id = $sizeVariant['size_variant_id'];
            echo "Variante de tamaño: {$sizeVariant['size_name']} (ID: $size_variant_id)\n";
        }
    }
    
    echo "\n";
    
    // Verificar si ya existe este item
    $checkQuery = "SELECT id FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
    if ($color_variant_id) {
        $checkQuery .= " AND color_variant_id = :color_variant_id";
    } else {
        $checkQuery .= " AND color_variant_id IS NULL";
    }
    if ($size_variant_id) {
        $checkQuery .= " AND size_variant_id = :size_variant_id";
    } else {
        $checkQuery .= " AND size_variant_id IS NULL";
    }
    
    $stmt = $conn->prepare($checkQuery);
    $checkParams = [
        ':cart_id' => $cart_id,
        ':product_id' => $product['id']
    ];
    if ($color_variant_id) {
        $checkParams[':color_variant_id'] = $color_variant_id;
    }
    if ($size_variant_id) {
        $checkParams[':size_variant_id'] = $size_variant_id;
    }
    $stmt->execute($checkParams);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingItem) {
        echo "⚠ Este producto ya está en el carrito (Item ID: {$existingItem['id']})\n";
        echo "Actualizando cantidad...\n";
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = :id");
        $stmt->execute([':id' => $existingItem['id']]);
        echo "✓ Cantidad actualizada\n";
    } else {
        // Agregar item al carrito
        echo "Agregando item al carrito...\n";
        $stmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, product_id, color_variant_id, size_variant_id, quantity, created_at)
            VALUES (:cart_id, :product_id, :color_variant_id, :size_variant_id, 1, NOW())
        ");
        $stmt->execute([
            ':cart_id' => $cart_id,
            ':product_id' => $product['id'],
            ':color_variant_id' => $color_variant_id,
            ':size_variant_id' => $size_variant_id
        ]);
        $item_id = $conn->lastInsertId();
        echo "✓ Item agregado con ID: $item_id\n";
    }
    
    // Verificar items en el carrito
    echo "\n=== ITEMS EN EL CARRITO ===\n";
    $stmt = $conn->prepare("
        SELECT 
            ci.id,
            ci.quantity,
            p.name as product_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = :cart_id
    ");
    $stmt->execute([':cart_id' => $cart_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        echo "- {$item['product_name']} x{$item['quantity']}\n";
    }
    
    echo "\n✓ Total de items: " . count($items) . "\n";
    echo "\n✓ Ahora puedes ir a http://localhost/angelow/tienda/cart.php\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
