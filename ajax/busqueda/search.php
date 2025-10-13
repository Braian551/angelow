<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = [
    'suggestions' => [],
    'terms' => []
];

if (isset($_GET['term']) && !empty($_GET['term'])) {
    $searchTerm = trim($_GET['term']);
    $userId = $_SESSION['user_id'] ?? '';
    
    try {
        // Intentar con el procedimiento almacenado
        $stmt = $conn->prepare("CALL SearchProductsAndTerms(:term, :user_id)");
        $stmt->bindValue(':term', $searchTerm);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        // Obtener el primer conjunto de resultados (productos)
        $response['suggestions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Avanzar al siguiente conjunto de resultados (términos)
        $stmt->nextRowset();
        $terms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Filtrar términos válidos
        $response['terms'] = array_values(array_filter($terms, function($term) {
            return is_string($term) && trim($term) !== '';
        }));
        
        // Filtrar productos válidos
        $response['suggestions'] = array_values(array_filter($response['suggestions'], function($item) {
            return !empty($item['name']);
        }));
        
        // Cerrar el cursor para liberar recursos
        $stmt->closeCursor();
        
    } catch (PDOException $e) {
        error_log('Error en búsqueda con procedimiento: ' . $e->getMessage());
        // Fallback a la implementación directa si hay error
        $response = fallbackSearch($conn, $searchTerm, $userId);
    }
}

echo json_encode($response);
exit;

// Función de respaldo en caso de error con el procedimiento
function fallbackSearch($conn, $searchTerm, $userId) {
    $response = [
        'suggestions' => [],
        'terms' => []
    ];
    
    try {
        // Buscar productos coincidentes
        $stmt = $conn->prepare("
            SELECT 
                p.id, 
                p.name, 
                p.slug, 
                COALESCE(pi.image_path, 'uploads/products/default-product.jpg') as image_path
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE (
                p.name LIKE :term 
                OR p.description LIKE :term 
                OR p.brand LIKE :term
            )
            AND p.is_active = 1
            ORDER BY 
                CASE 
                    WHEN p.name LIKE :exact_start THEN 1
                    WHEN p.name LIKE :term THEN 2
                    ELSE 3
                END,
                p.name
            LIMIT 5
        ");
        $stmt->bindValue(':term', '%' . $searchTerm . '%');
        $stmt->bindValue(':exact_start', $searchTerm . '%');
        $stmt->execute();
        
        $response['suggestions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar términos de búsqueda del historial del usuario (si está logueado)
        $historyTerms = [];
        if (!empty($userId)) {
            $stmt = $conn->prepare("
                SELECT DISTINCT search_term 
                FROM search_history 
                WHERE user_id = :user_id
                AND search_term LIKE :term
                AND search_term IS NOT NULL
                AND search_term != ''
                ORDER BY created_at DESC
                LIMIT 3
            ");
            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':term', $searchTerm . '%');
            $stmt->execute();
            
            $historyTerms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        
        // Buscar en búsquedas populares
        $stmt = $conn->prepare("
            SELECT DISTINCT search_term 
            FROM popular_searches 
            WHERE search_term LIKE :term
            AND search_term IS NOT NULL
            AND search_term != ''
            ORDER BY search_count DESC
            LIMIT 3
        ");
        $stmt->bindValue(':term', '%' . $searchTerm . '%');
        $stmt->execute();
        
        $popularTerms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Si aún necesitamos más términos, buscar en nombres de productos
        $productTerms = [];
        if (count($historyTerms) + count($popularTerms) < 4) {
            $stmt = $conn->prepare("
                SELECT DISTINCT name 
                FROM products 
                WHERE name LIKE :term 
                AND is_active = 1
                AND name IS NOT NULL
                AND name != ''
                LIMIT 3
            ");
            $stmt->bindValue(':term', '%' . $searchTerm . '%');
            $stmt->execute();
            
            $productTerms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        
        // Combinar todos los términos
        $allTerms = array_merge($historyTerms, $popularTerms, $productTerms);
        $allTerms = array_unique(array_filter($allTerms, function($term) {
            return is_string($term) && trim($term) !== '';
        }));
        
        $response['terms'] = array_values(array_slice($allTerms, 0, 6));
        
    } catch (PDOException $e) {
        error_log('Error en fallbackSearch: ' . $e->getMessage());
    }
    
    return $response;
}
