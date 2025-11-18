<?php
// Obtener productos con información de inventario
function getProductsWithInventory($conn) {
        $sql = "SELECT p.id, p.name, p.is_active, 
                 c.name AS category_name,
                 COUNT(DISTINCT pcv.id) AS variant_count,
                 COALESCE(SUM(psv.quantity), 0) AS total_stock,
                 COALESCE(SUM(CASE WHEN psv.quantity <= 5 THEN 1 ELSE 0 END), 0) AS low_stock_count
             FROM products p
             JOIN categories c ON p.category_id = c.id
             LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
             LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
             GROUP BY p.id, p.name, p.is_active, c.name
             ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener productos con bajo stock
function getLowStockItems($conn, $threshold = 5) {
    $sql = "SELECT p.id AS product_id, p.name AS product_name,
                   c.name AS color_name, c.hex_code,
                   s.name AS size_name,
                   psv.id, psv.quantity, psv.sku
            FROM product_size_variants psv
            JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
            JOIN products p ON pcv.product_id = p.id
            LEFT JOIN colors c ON pcv.color_id = c.id
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE psv.quantity <= ? AND psv.is_active = 1
            ORDER BY psv.quantity ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$threshold]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener detalles de un producto específico
function getProductDetails($conn, $product_id) {
    $sql = "SELECT p.*, c.name AS category_name,
                   (SELECT SUM(psv.quantity) 
                    FROM product_size_variants psv
                    JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
                    WHERE pcv.product_id = p.id) AS total_stock,
                   (SELECT COUNT(*) 
                    FROM product_size_variants psv
                    JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
                    WHERE pcv.product_id = p.id AND psv.quantity <= 5) AS low_stock_count,
                   (SELECT COUNT(*) > 0 
                    FROM product_size_variants psv
                    JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
                    WHERE pcv.product_id = p.id AND psv.quantity <= 5) AS low_stock
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener variantes de un producto con información de stock
function getProductVariantsWithStock($conn, $product_id) {
    // Many installs don't store timestamps on size variants; fetch last stock movement from history instead
    $sql = "SELECT psv.id, psv.sku, psv.quantity,
                   (SELECT MAX(sh.created_at) FROM stock_history sh WHERE sh.variant_id = psv.id) AS last_updated,
                   c.name AS color_name, c.hex_code,
                   s.name AS size_name, s.description AS size_description
            FROM product_size_variants psv
            JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
            LEFT JOIN colors c ON pcv.color_id = c.id
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE pcv.product_id = ?
            ORDER BY color_name, size_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener historial de movimientos de stock
function getStockHistory($conn, $product_id, $limit = 20) {
    $sql = "SELECT sh.*, u.name AS user_name,
                   c.name AS color_name, s.name AS size_name
            FROM stock_history sh
            JOIN users u ON sh.user_id = u.id
            JOIN product_size_variants psv ON sh.variant_id = psv.id
            JOIN product_color_variants pcv ON psv.color_variant_id = pcv.id
            LEFT JOIN colors c ON pcv.color_id = c.id
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE pcv.product_id = ?
            ORDER BY sh.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$product_id, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener stock actual de una variante
function getCurrentStock($conn, $variant_id) {
    $stmt = $conn->prepare("SELECT quantity FROM product_size_variants WHERE id = ?");
    $stmt->execute([$variant_id]);
    return $stmt->fetchColumn();
}

// Calcular nueva cantidad según la operación
function calculateNewQuantity($current, $quantity, $operation) {
    switch ($operation) {
        case 'add': return $current + $quantity;
        case 'subtract': return max(0, $current - $quantity);
        case 'set': return $quantity;
        default: return $current;
    }
}

// Registrar movimiento en el historial de stock
function logStockMovement($conn, $data) {
    $sql = "INSERT INTO stock_history 
            (variant_id, user_id, previous_qty, new_qty, operation, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $data['variant_id'],
        $data['user_id'],
        $data['previous_qty'],
        $data['new_qty'],
        $data['operation'],
        $data['notes']
    ]);
}

// Transferir stock entre variantes
function transferStock($conn, $source_variant_id, $target_variant_id, $quantity, $user_id) {
    try {
        $conn->beginTransaction();
        
        // Verificar que no sean la misma variante
        if ($source_variant_id == $target_variant_id) {
            throw new Exception("No puedes transferir stock a la misma variante");
        }
        
        // Obtener stock actual de origen
        $source_current = getCurrentStock($conn, $source_variant_id);
        if ($source_current < $quantity) {
            throw new Exception("No hay suficiente stock para transferir");
        }
        
        // Obtener stock actual de destino
        $target_current = getCurrentStock($conn, $target_variant_id);
        
        // Actualizar stock origen (restar)
        $stmt = $conn->prepare("UPDATE product_size_variants SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $source_variant_id]);
        
        // Registrar movimiento origen
        logStockMovement($conn, [
            'variant_id' => $source_variant_id,
            'user_id' => $user_id,
            'previous_qty' => $source_current,
            'new_qty' => $source_current - $quantity,
            'operation' => 'transfer_out',
            'notes' => "Transferencia a variante #$target_variant_id"
        ]);
        
        // Actualizar stock destino (sumar)
        $stmt = $conn->prepare("UPDATE product_size_variants SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$quantity, $target_variant_id]);
        
        // Registrar movimiento destino
        logStockMovement($conn, [
            'variant_id' => $target_variant_id,
            'user_id' => $user_id,
            'previous_qty' => $target_current,
            'new_qty' => $target_current + $quantity,
            'operation' => 'transfer_in',
            'notes' => "Transferencia desde variante #$source_variant_id"
        ]);
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

// Obtener texto descriptivo de la operación
function getOperationText($operation) {
    $operations = [
        'add' => 'añadió',
        'subtract' => 'restó',
        'set' => 'estableció',
        'transfer_in' => 'recibió transferencia de',
        'transfer_out' => 'transfirió'
    ];
    return $operations[$operation] ?? $operation;
}