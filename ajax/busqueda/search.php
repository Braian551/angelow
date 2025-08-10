<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

$response = [
    'suggestions' => [],
    'terms' => []
];

if (isset($_GET['term']) && !empty($_GET['term'])) {
    $searchTerm = trim($_GET['term']);
    $userId = $_SESSION['user_id'] ?? '';
    
    try {
        // Llamar al procedimiento almacenado
        $stmt = $conn->prepare("CALL SearchProductsAndTerms(:term, :user_id)");
        $stmt->bindValue(':term', $searchTerm);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        // Obtener el primer conjunto de resultados (productos)
        $response['suggestions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Avanzar al siguiente conjunto de resultados (términos)
        $stmt->nextRowset();
        $response['terms'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Filtrar resultados vacíos
        $response['suggestions'] = array_filter($response['suggestions'], function($item) {
            return !empty($item['name']);
        });
        
        $response['terms'] = array_filter($response['terms'], function($term) {
            return is_string($term) && trim($term) !== '';
        });
        
    } catch (PDOException $e) {
        error_log('Error en búsqueda: ' . $e->getMessage());
        // Fallback a la implementación original si hay error con el procedimiento
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
            SELECT p.id, p.name, p.slug, pi.image_path
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1
            LIMIT 5
        ");
        $stmt->bindValue(':term', '%' . $searchTerm . '%');
        $stmt->execute();
        
        $response['suggestions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar términos de búsqueda populares
        $stmt = $conn->prepare("
            SELECT DISTINCT search_term 
            FROM search_history 
            WHERE user_id = :user_id
            AND search_term LIKE :term
            AND search_term IS NOT NULL
            AND search_term != ''
            ORDER BY created_at DESC
            LIMIT 6
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':term', $searchTerm . '%');
        $stmt->execute();
        
        $historyTerms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Si no hay suficientes términos, buscar en nombres de productos
        if (count($historyTerms) < 4) {
            $stmt = $conn->prepare("
                SELECT DISTINCT name 
                FROM products 
                WHERE name LIKE :term AND is_active = 1
                LIMIT 4
            ");
            $stmt->bindValue(':term', '%' . $searchTerm . '%');
            $stmt->execute();
            
            $productTerms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $allTerms = array_merge($historyTerms, $productTerms);
            $allTerms = array_unique(array_filter($allTerms, function($term) {
                return is_string($term) && trim($term) !== '';
            }));
            
            $response['terms'] = array_slice($allTerms, 0, 4);
        } else {
            $response['terms'] = array_filter($historyTerms, function($term) {
                return is_string($term) && trim($term) !== '';
            });
        }
        
    } catch (PDOException $e) {
        error_log('Error en fallbackSearch: ' . $e->getMessage());
    }
    
    return $response;
}