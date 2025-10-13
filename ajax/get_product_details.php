<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    echo json_encode(['error' => 'ID de producto no proporcionado']);
    exit;
}

try {
    // Obtener producto
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.brand,
            p.gender,
            pi.image_path
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.id = :id AND p.is_active = 1
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Producto no encontrado']);
        exit;
    }
    
    // Obtener variantes de color
    $stmt = $conn->prepare("
        SELECT 
            pcv.id,
            c.name,
            c.hex_code
        FROM product_color_variants pcv
        JOIN colors c ON pcv.color_id = c.id
        WHERE pcv.product_id = :product_id
    ");
    $stmt->execute([':product_id' => $product_id]);
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $product['colors'] = $colors;
    
    // Si hay colores, obtener las tallas del primer color
    if (count($colors) > 0) {
        $stmt = $conn->prepare("
            SELECT 
                psv.id,
                s.name,
                psv.price,
                psv.quantity as stock
            FROM product_size_variants psv
            JOIN sizes s ON psv.size_id = s.id
            WHERE psv.color_variant_id = :color_variant_id
        ");
        $stmt->execute([':color_variant_id' => $colors[0]['id']]);
        $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $product['sizes'] = $sizes;
    } else {
        $product['sizes'] = [];
    }
    
    echo json_encode($product);
    
} catch (Exception $e) {
    error_log("Error in get_product_details.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener detalles del producto']);
}
