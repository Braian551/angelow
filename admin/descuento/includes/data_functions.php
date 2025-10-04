<?php
// admin/descuento/includes/data_functions.php

// Obtener todos los códigos de descuento con información de sus tipos específicos
function obtenerCodigosDescuento($conn)
{
    $sql = "SELECT dc.*, dt.name as discount_type_name,
               (SELECT COUNT(*) FROM discount_code_products WHERE discount_code_id = dc.id) as product_count,
               (SELECT COUNT(*) FROM discount_code_usage WHERE discount_code_id = dc.id) as used_count,
               pd.percentage, pd.max_discount_amount,
               fd.amount, fd.min_order_amount,
               fs.shipping_method_id
            FROM discount_codes dc
            JOIN discount_types dt ON dc.discount_type_id = dt.id
            LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id
            LEFT JOIN fixed_amount_discounts fd ON dc.id = fd.discount_code_id
            LEFT JOIN free_shipping_discounts fs ON dc.id = fs.discount_code_id
            ORDER BY dc.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener un código específico por ID
function obtenerCodigoPorId($conn, $id)
{
    $sql = "SELECT dc.*, dt.name as discount_type_name,
               pd.percentage, pd.max_discount_amount,
               fd.amount, fd.min_order_amount,
               fs.shipping_method_id,
               (SELECT GROUP_CONCAT(product_id) FROM discount_code_products WHERE discount_code_id = dc.id) as product_ids
            FROM discount_codes dc
            JOIN discount_types dt ON dc.discount_type_id = dt.id
            LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id
            LEFT JOIN fixed_amount_discounts fd ON dc.id = fd.discount_code_id
            LEFT JOIN free_shipping_discounts fs ON dc.id = fs.discount_code_id
            WHERE dc.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener productos para asignar a descuentos
function obtenerProductos($conn)
{
    $sql = "SELECT id, name FROM products WHERE is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener usuarios clientes
function obtenerUsuarios($conn)
{
    $sql = "SELECT id, name, email, phone FROM users WHERE role = 'customer' AND id IS NOT NULL ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener tipos de descuento
function obtenerTiposDescuento($conn)
{
    $sql = "SELECT * FROM discount_types WHERE is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener métodos de envío
function obtenerMetodosEnvio($conn)
{
    try {
        // Verificar si la tabla existe antes de consultarla
        $stmt = $conn->prepare("SHOW TABLES LIKE 'shipping_methods'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            // Si la tabla no existe, retornar array vacío
            return [];
        }
        
        $sql = "SELECT id, name FROM shipping_methods WHERE is_active = 1 ORDER BY name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener métodos de envío: " . $e->getMessage());
        return []; // Retornar array vacío si hay error
    }
}
?>