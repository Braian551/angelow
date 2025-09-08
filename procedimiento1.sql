Procedimiento para busqueda y sugerencias: DELIMITER //

DROP PROCEDURE IF EXISTS SearchProductsAndTerms //
CREATE PROCEDURE SearchProductsAndTerms(
    IN p_search_term VARCHAR(255),
    IN p_user_id VARCHAR(20)
)
BEGIN
    -- Variables para manejar resultados
    DECLARE history_count INT;
    
    -- Resultados de productos coincidentes
    SELECT 
        p.id, 
        p.name, 
        p.slug, 
        pi.image_path
    FROM 
        products p
    LEFT JOIN 
        product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE 
        (p.name LIKE CONCAT('%', p_search_term, '%') OR p.description LIKE CONCAT('%', p_search_term, '%')) 
        AND p.is_active = 1
    LIMIT 5;
    
    -- Obtener términos de búsqueda del historial del usuario
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_history_terms AS
    SELECT DISTINCT search_term 
    FROM search_history 
    WHERE user_id = p_user_id
    AND search_term LIKE CONCAT(p_search_term, '%')
    AND search_term IS NOT NULL
    AND search_term != ''
    ORDER BY created_at DESC
    LIMIT 6;
    
    -- Contar cuántos términos hay en el historial
    SELECT COUNT(*) INTO history_count FROM temp_history_terms;
    
    -- Si no hay suficientes términos, buscar en nombres de productos
    IF history_count < 4 THEN
        SELECT DISTINCT name 
        FROM products 
        WHERE name LIKE CONCAT('%', p_search_term, '%') AND is_active = 1
        LIMIT 4;
    ELSE
        SELECT * FROM temp_history_terms LIMIT 4;
    END IF;
    
    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_history_terms;
END //

DELIMITER ;