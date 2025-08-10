<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../config.php';
// Forzar el tipo de contenido a JSON
header('Content-Type: application/json');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['terms' => []];

try {
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['user_id'])) {
        echo json_encode($response);
        exit;
    }

    $userId = $_SESSION['user_id'];
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare("
        SELECT DISTINCT LOWER(TRIM(search_term)) as search_term 
        FROM search_history 
        WHERE user_id = :user_id
        AND search_term IS NOT NULL
        AND search_term != ''
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->bindParam(':user_id', $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la consulta');
    }
    
    $terms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Filtrar términos vacíos y asegurar que sean strings
    $response['terms'] = array_filter($terms, function($term) {
        return is_string($term) && trim($term) !== '';
    });
    
} catch (Exception $e) {
    // Registrar el error pero continuar con respuesta vacía
    error_log('Error en get_search_history: ' . $e->getMessage());
}

// Enviar respuesta JSON
echo json_encode($response);
exit;