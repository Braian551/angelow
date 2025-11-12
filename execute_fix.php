<?php
require_once 'conexion.php';

try {
    // Eliminar el procedimiento existente
    $conn->exec("DROP PROCEDURE IF EXISTS GetFilteredProducts");

    // Crear un procedimiento más simple que evite problemas de collation
    $procedureSql = "
CREATE PROCEDURE GetFilteredProducts (
    IN p_search_query VARCHAR(255),
    IN p_category_id INT,
    IN p_gender VARCHAR(10),
    IN p_min_price DECIMAL(10,2),
    IN p_max_price DECIMAL(10,2),
    IN p_sort_by VARCHAR(20),
    IN p_limit INT,
    IN p_offset INT,
    IN p_user_id VARCHAR(20)
)
BEGIN
    -- Consulta principal para obtener productos (versión simplificada)
    SELECT
        p.id,
        p.name,
        p.slug,
        p.description,
        p.price as min_price,
        p.price as max_price,
        p.gender,
        p.category_id,
        p.is_featured,
        p.created_at,
        p.primary_image,
        0 as is_favorite,
        0 as avg_rating,
        0 as review_count
    FROM
        products p
    WHERE
        p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR p.price >= p_min_price)
        AND (p_max_price IS NULL OR p.price <= p_max_price)
    ORDER BY
        CASE WHEN p_sort_by = 'price_asc' THEN p.price END ASC,
        CASE WHEN p_sort_by = 'price_desc' THEN p.price END DESC,
        CASE WHEN p_sort_by = 'name_asc' THEN p.name END ASC,
        CASE WHEN p_sort_by = 'name_desc' THEN p.name END DESC,
        CASE WHEN p_sort_by = 'popular' THEN p.is_featured END DESC,
        p.is_featured DESC,
        p.created_at DESC
    LIMIT p_limit OFFSET p_offset;

    -- Consulta para contar el total de productos
    SELECT COUNT(*) as total
    FROM products p
    WHERE p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR p.price >= p_min_price)
        AND (p_max_price IS NULL OR p.price <= p_max_price);
END";

    $conn->exec($procedureSql);

    echo "Procedimiento simplificado creado correctamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>