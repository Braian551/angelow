Procedimiento para mostrar productos: DELIMITER //

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
    -- Consulta principal para obtener productos
    SELECT 
        p.id, 
        p.name, 
        p.slug, 
        p.description,
        p.gender,
        p.category_id,
        p.is_featured,
        p.created_at,
        pi.image_path as primary_image,
        MIN(psv.price) as min_price,
        MAX(psv.price) as max_price,
        IFNULL((SELECT COUNT(*) FROM wishlist w WHERE w.user_id = p_user_id AND w.product_id = p.id), 0) as is_favorite,
        IFNULL((SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as avg_rating,
        IFNULL((SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as review_count
    FROM 
        products p
    LEFT JOIN 
        product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN 
        product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN 
        product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE 
        p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price)
    GROUP BY 
        p.id
    ORDER BY
        CASE WHEN p_sort_by = 'price_asc' THEN MIN(psv.price) END ASC,
        CASE WHEN p_sort_by = 'price_desc' THEN MIN(psv.price) END DESC,
        CASE WHEN p_sort_by = 'name_asc' THEN p.name END ASC,
        CASE WHEN p_sort_by = 'name_desc' THEN p.name END DESC,
        CASE WHEN p_sort_by = 'popular' THEN p.is_featured END DESC,
        p.is_featured DESC,
        p.created_at DESC
    LIMIT p_limit OFFSET p_offset;

    -- Consulta para contar el total de productos
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price);
END//

DELIMITER ;