<?php
/**
 * API Proxy para búsqueda de direcciones
 * Evita problemas de CORS al hacer peticiones a Nominatim
 */

// Configurar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Validar parámetros
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Parámetro "q" (query) es requerido'
        ]);
        exit;
    }

    $query = trim($_GET['q']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

    // Construir URL de Nominatim
    $params = [
        'format' => 'json',
        'q' => $query,
        'limit' => $limit,
        'countrycodes' => 'co',
        'addressdetails' => '1',
        'bounded' => '0',
        'accept-language' => 'es'
    ];
    
    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query($params);

    // Log para debugging
    error_log("Búsqueda GPS: Query='$query', URL='$url'");

    // Configurar contexto para la solicitud con timeout más largo
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Angelow-Address-App/1.0',
                'Accept: application/json',
                'Accept-Language: es,en'
            ],
            'timeout' => 15,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($opts);
    
    // Hacer la petición
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        throw new Exception('Error al conectar con el servicio: ' . ($error['message'] ?? 'Timeout o conexión rechazada'));
    }
    
    // Verificar el código de respuesta HTTP
    if (isset($http_response_header)) {
        $status_line = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $status = $match[1] ?? 0;
        
        if ($status >= 400) {
            throw new Exception("Error HTTP $status de Nominatim");
        }
    }
    
    // Decodificar JSON
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Respuesta inválida del servicio: ' . json_last_error_msg());
    }
    
    // Log exitoso
    error_log("Búsqueda GPS exitosa: " . count($data) . " resultados");
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'query' => $query
    ]);
    
} catch (Exception $e) {
    error_log("Error en search_address.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'query' => $_GET['q'] ?? ''
    ]);
}
