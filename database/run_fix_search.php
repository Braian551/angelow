<?php
require_once __DIR__ . '/../conexion.php';

try {
    // Eliminar el procedimiento si existe
    $conn->exec("DROP PROCEDURE IF EXISTS SearchProductsAndTerms");
    
    // Crear el nuevo procedimiento
    $procedureSQL = "
    CREATE PROCEDURE SearchProductsAndTerms(
        IN search_term VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
        IN user_id VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    )
    BEGIN
        -- Primer conjunto de resultados: Productos coincidentes (máximo 5)
        SELECT 
            p.id,
            p.name,
            p.slug,
            COALESCE(pi.image_path, 'uploads/products/default-product.jpg') as image_path
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE (
            p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
            OR p.description LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
            OR p.brand LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
        )
        AND p.is_active = 1
        ORDER BY 
            CASE 
                WHEN p.name LIKE CONCAT(search_term, '%') COLLATE utf8mb4_general_ci THEN 1
                WHEN p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci THEN 2
                ELSE 3
            END,
            p.name
        LIMIT 5;

        -- Segundo conjunto de resultados: Términos de búsqueda sugeridos (nombres de productos)
        SELECT DISTINCT p.name as search_term_result
        FROM products p
        WHERE p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
        AND p.is_active = 1
        AND p.name IS NOT NULL
        AND p.name != ''
        LIMIT 6;
    END
    ";
    
    $conn->exec($procedureSQL);
    
    echo "✓ Procedimiento SearchProductsAndTerms creado exitosamente\n";
    
} catch (Exception $e) {
    echo "✗ Error al crear procedimiento: " . $e->getMessage() . "\n";
}
