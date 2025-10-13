<?php
require_once 'conexion.php';

try {
    echo "=== VERIFICACIÓN DE DATOS ===\n\n";
    
    // Verificar productos
    $stmt = $conn->query('SELECT COUNT(*) as count FROM products WHERE is_active = 1');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Productos activos: $count\n";
    
    // Verificar imágenes de productos
    $stmt = $conn->query('SELECT COUNT(*) as count FROM product_images');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Imágenes de productos: $count\n";
    
    // Verificar variantes de color
    $stmt = $conn->query('SELECT COUNT(*) as count FROM product_color_variants');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Variantes de color: $count\n";
    
    // Verificar variantes de tamaño
    $stmt = $conn->query('SELECT COUNT(*) as count FROM product_size_variants');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Variantes de tamaño: $count\n";
    
    // Verificar imágenes de variantes
    $stmt = $conn->query('SELECT COUNT(*) as count FROM variant_images');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Imágenes de variantes: $count\n";
    
    // Verificar carritos
    $stmt = $conn->query('SELECT COUNT(*) as count FROM carts');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Carritos: $count\n";
    
    // Verificar items en carritos
    $stmt = $conn->query('SELECT COUNT(*) as count FROM cart_items');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Items en carritos: $count\n";
    
    echo "\n=== MUESTRA DE PRODUCTOS CON IMÁGENES ===\n\n";
    
    $stmt = $conn->query('
        SELECT 
            p.id, 
            p.name, 
            p.slug,
            pi.image_path,
            pi.is_primary
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        WHERE p.is_active = 1
        LIMIT 5
    ');
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        echo "ID: {$product['id']} - {$product['name']}\n";
        echo "  Slug: {$product['slug']}\n";
        echo "  Imagen: " . ($product['image_path'] ?? 'Sin imagen') . "\n";
        echo "  Primaria: " . ($product['is_primary'] ? 'Sí' : 'No') . "\n\n";
    }
    
    echo "\n=== VERIFICACIÓN DE PROCEDIMIENTO ===\n\n";
    
    try {
        $stmt = $conn->prepare("CALL SearchProductsAndTerms(:term, :user_id)");
        $stmt->bindValue(':term', 'camiseta');
        $stmt->bindValue(':user_id', '');
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Procedimiento SearchProductsAndTerms funcional\n";
        echo "  Productos encontrados: " . count($products) . "\n";
        
        $stmt->closeCursor();
        
    } catch (Exception $e) {
        echo "✗ Error en procedimiento: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
