<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

try {
    // Verificar permisos de administrador
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
        exit();
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
        exit();
    }

    $productId = (int)$_GET['id'];

    // Obtener información básica del producto
    $productStmt = $conn->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit();
    }

    // Obtener imágenes del producto
    $imagesStmt = $conn->prepare("
        SELECT * FROM product_images 
        WHERE product_id = ?
        ORDER BY `order`
    ");
    $imagesStmt->execute([$productId]);
    $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener variantes de color
    $colorVariantsStmt = $conn->prepare("
        SELECT pcv.id, pcv.is_default, col.name AS color_name, col.hex_code
        FROM product_color_variants pcv
        LEFT JOIN colors col ON pcv.color_id = col.id
        WHERE pcv.product_id = ?
    ");
    $colorVariantsStmt->execute([$productId]);
    $colorVariants = $colorVariantsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las variantes (color + talla)
    $variants = [];
    $totalStock = 0;
    $minPrice = PHP_FLOAT_MAX;
    $maxPrice = 0;

    foreach ($colorVariants as $colorVariant) {
        $sizeVariantsStmt = $conn->prepare("
            SELECT psv.*, s.name AS size_name
            FROM product_size_variants psv
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE psv.color_variant_id = ?
        ");
        $sizeVariantsStmt->execute([$colorVariant['id']]);
        $sizeVariants = $sizeVariantsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sizeVariants as $variant) {
            $variants[] = [
                'id' => $variant['id'],
                'color_id' => $colorVariant['id'],
                'color_name' => $colorVariant['color_name'],
                'size_id' => $variant['size_id'],
                'size_name' => $variant['size_name'],
                'price' => $variant['price'],
                'quantity' => $variant['quantity'],
                'is_active' => $variant['is_active']
            ];

            $totalStock += $variant['quantity'];
            $minPrice = min($minPrice, $variant['price']);
            $maxPrice = max($maxPrice, $variant['price']);
        }
    }

    echo json_encode([
        'success' => true,
        'product' => $product,
        'images' => $images,
        'variants' => $variants,
        'total_stock' => $totalStock,
        'min_price' => $minPrice === PHP_FLOAT_MAX ? 0 : $minPrice,
        'max_price' => $maxPrice
    ]);

} catch (PDOException $e) {
    error_log("Error en get_product_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>