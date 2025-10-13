<?php
/**
 * API de Geocodificación
 * Convierte coordenadas GPS en direcciones legibles (reverse geocoding)
 * Usa Nominatim (OpenStreetMap)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config.php';

// Validar parámetros
if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Parámetros lat y lng son requeridos'
    ]);
    exit;
}

$lat = floatval($_GET['lat']);
$lng = floatval($_GET['lng']);

// Validar rango de coordenadas
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Coordenadas inválidas'
    ]);
    exit;
}

// Llamar a la API de Nominatim (OpenStreetMap)
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

// Configurar contexto para la solicitud
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Angelow-Delivery-App/1.0',
            'Accept: application/json'
        ],
        'timeout' => 10
    ]
]);

try {
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Error al conectar con el servicio de geocodificación');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['address'])) {
        throw new Exception('No se pudo obtener la dirección para estas coordenadas');
    }
    
    // Extraer información de la dirección
    $address = $data['address'];
    
    // Construir dirección formateada para Colombia
    $street = '';
    $streetNumber = '';
    $neighborhood = '';
    $city = '';
    $state = '';
    
    // Intentar construir la calle (formato colombiano)
    if (isset($address['road'])) {
        $street = $address['road'];
    } elseif (isset($address['street'])) {
        $street = $address['street'];
    } elseif (isset($address['pedestrian'])) {
        $street = $address['pedestrian'];
    }
    
    // Número de calle
    if (isset($address['house_number'])) {
        $streetNumber = $address['house_number'];
    }
    
    // Barrio
    if (isset($address['suburb'])) {
        $neighborhood = $address['suburb'];
    } elseif (isset($address['neighbourhood'])) {
        $neighborhood = $address['neighbourhood'];
    } elseif (isset($address['residential'])) {
        $neighborhood = $address['residential'];
    } elseif (isset($address['quarter'])) {
        $neighborhood = $address['quarter'];
    }
    
    // Ciudad
    if (isset($address['city'])) {
        $city = $address['city'];
    } elseif (isset($address['town'])) {
        $city = $address['town'];
    } elseif (isset($address['municipality'])) {
        $city = $address['municipality'];
    } elseif (isset($address['county'])) {
        $city = $address['county'];
    }
    
    // Estado/Departamento
    if (isset($address['state'])) {
        $state = $address['state'];
    } elseif (isset($address['region'])) {
        $state = $address['region'];
    }
    
    // Construir dirección completa
    $fullAddress = $street;
    if ($streetNumber) {
        $fullAddress .= ' ' . $streetNumber;
    }
    
    // Si no hay dirección específica, usar el display_name
    if (empty($fullAddress)) {
        $fullAddress = $data['display_name'] ?? 'Dirección no disponible';
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'formatted_address' => $fullAddress,
            'street' => $street,
            'street_number' => $streetNumber,
            'neighborhood' => $neighborhood ?: ($city ?: 'Sin barrio'),
            'city' => $city ?: 'Sin ciudad',
            'state' => $state ?: '',
            'country' => $address['country'] ?? 'Colombia',
            'postal_code' => $address['postcode'] ?? '',
            'lat' => $lat,
            'lng' => $lng,
            'display_name' => $data['display_name'] ?? $fullAddress,
            'raw_address' => $address
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en geocoding: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
