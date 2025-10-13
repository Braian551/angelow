<?php
/**
 * API de Navegación para Sistema de Delivery
 * Maneja rutas, geocodificación, actualizaciones de ubicación y tracking en tiempo real
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar rol de delivery
try {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'delivery') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sin permisos']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de autenticación']);
    exit();
}

$driverId = $user['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ============================================
        // OBTENER RUTA OPTIMIZADA (OSRM)
        // ============================================
        case 'get_route':
            $startLat = floatval($_GET['start_lat'] ?? 0);
            $startLng = floatval($_GET['start_lng'] ?? 0);
            $endLat = floatval($_GET['end_lat'] ?? 0);
            $endLng = floatval($_GET['end_lng'] ?? 0);
            
            if (!$startLat || !$startLng || !$endLat || !$endLng) {
                throw new Exception('Coordenadas incompletas');
            }
            
            // Usar OSRM (Open Source Routing Machine) - GRATIS
            $osrmUrl = "https://router.project-osrm.org/route/v1/driving/{$startLng},{$startLat};{$endLng},{$endLat}";
            $osrmUrl .= "?overview=full&geometries=geojson&steps=true&alternatives=true";
            
            $ch = curl_init($osrmUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Error al obtener la ruta');
            }
            
            $routeData = json_decode($response, true);
            
            if (!isset($routeData['routes']) || empty($routeData['routes'])) {
                throw new Exception('No se encontró una ruta');
            }
            
            // Procesar la ruta principal
            $mainRoute = $routeData['routes'][0];
            $distanceKm = round($mainRoute['distance'] / 1000, 2);
            $durationSeconds = $mainRoute['duration'];
            
            // Extraer instrucciones paso a paso
            $steps = [];
            foreach ($mainRoute['legs'][0]['steps'] as $step) {
                $steps[] = [
                    'instruction' => $step['maneuver']['type'] ?? 'continue',
                    'name' => $step['name'] ?? 'Sin nombre',
                    'distance' => round($step['distance'], 0),
                    'duration' => round($step['duration'], 0),
                    'location' => $step['maneuver']['location'] ?? null
                ];
            }
            
            echo json_encode([
                'success' => true,
                'route' => [
                    'geometry' => $mainRoute['geometry'],
                    'distance_km' => $distanceKm,
                    'distance_meters' => $mainRoute['distance'],
                    'duration_seconds' => $durationSeconds,
                    'duration_minutes' => round($durationSeconds / 60, 1),
                    'steps' => $steps
                ],
                'alternatives' => array_slice($routeData['routes'], 1, 2) // Hasta 2 rutas alternativas
            ]);
            break;
        
        // ============================================
        // GEOCODIFICAR DIRECCIÓN (Nominatim - OpenStreetMap)
        // ============================================
        case 'geocode':
            $address = $_GET['address'] ?? '';
            
            if (empty($address)) {
                throw new Exception('Dirección no especificada');
            }
            
            // Usar Nominatim (OpenStreetMap) - GRATIS
            $nominatimUrl = "https://nominatim.openstreetmap.org/search";
            $nominatimUrl .= "?q=" . urlencode($address);
            $nominatimUrl .= "&format=json&limit=5&addressdetails=1";
            
            $ch = curl_init($nominatimUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'AngelowDeliveryApp/1.0');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Error al geocodificar');
            }
            
            $results = json_decode($response, true);
            
            echo json_encode([
                'success' => true,
                'results' => array_map(function($result) {
                    return [
                        'lat' => floatval($result['lat']),
                        'lng' => floatval($result['lon']),
                        'display_name' => $result['display_name'],
                        'address' => $result['address'] ?? []
                    ];
                }, $results)
            ]);
            break;
        
        // ============================================
        // GEOCODIFICACIÓN INVERSA (coordenadas -> dirección)
        // ============================================
        case 'reverse_geocode':
            $lat = floatval($_GET['lat'] ?? 0);
            $lng = floatval($_GET['lng'] ?? 0);
            
            if (!$lat || !$lng) {
                throw new Exception('Coordenadas incompletas');
            }
            
            $nominatimUrl = "https://nominatim.openstreetmap.org/reverse";
            $nominatimUrl .= "?lat={$lat}&lon={$lng}&format=json&addressdetails=1";
            
            $ch = curl_init($nominatimUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'AngelowDeliveryApp/1.0');
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            echo json_encode([
                'success' => true,
                'address' => $result['display_name'] ?? 'Dirección desconocida',
                'details' => $result['address'] ?? []
            ]);
            break;
        
        // ============================================
        // INICIAR NAVEGACIÓN
        // ============================================
        case 'start_navigation':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $startLat = floatval($data['start_lat'] ?? 0);
            $startLng = floatval($data['start_lng'] ?? 0);
            $destLat = floatval($data['dest_lat'] ?? 0);
            $destLng = floatval($data['dest_lng'] ?? 0);
            $routeJson = json_encode($data['route'] ?? []);
            $distanceKm = floatval($data['distance_km'] ?? 0);
            $durationSeconds = intval($data['duration_seconds'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('ID de entrega no válido');
            }
            
            // Verificar que la entrega existe y está en estado correcto
            $stmt = $conn->prepare("
                SELECT id, delivery_status 
                FROM order_deliveries 
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Entrega no encontrada o no pertenece al conductor');
            }
            
            if (!in_array($delivery['delivery_status'], ['driver_accepted', 'in_transit'])) {
                throw new Exception('La entrega no está en un estado válido para navegación. Estado actual: ' . $delivery['delivery_status']);
            }
            
            try {
                // Asegurar que driver_id sea string (VARCHAR en DB)
                $driverIdStr = strval($driverId);
                
                // Llamar al procedimiento almacenado
                $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
                $stmt->execute([
                    $deliveryId, $driverIdStr, 
                    $startLat, $startLng, 
                    $destLat, $destLng,
                    $routeJson, $distanceKm, $durationSeconds
                ]);
                
                // Cerrar el cursor del procedimiento
                $stmt->closeCursor();
                
                // Obtener el resultado
                $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
                
                if (!$result || $result['result'] !== 'SUCCESS') {
                    $errorMsg = $result['result'] ?? 'ERROR_DESCONOCIDO';
                    throw new Exception('Error al iniciar navegación: ' . $errorMsg);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Navegación iniciada correctamente',
                    'status' => 'in_transit'
                ]);
                
            } catch (PDOException $e) {
                error_log("Error en StartNavigation: " . $e->getMessage());
                throw new Exception('Error de base de datos al iniciar navegación: ' . $e->getMessage());
            }
            break;
        
        // ============================================
        // ACTUALIZAR UBICACIÓN EN TIEMPO REAL
        // ============================================
        case 'update_location':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $latitude = floatval($data['latitude'] ?? 0);
            $longitude = floatval($data['longitude'] ?? 0);
            $accuracy = floatval($data['accuracy'] ?? 0);
            $speed = floatval($data['speed'] ?? 0);
            $heading = floatval($data['heading'] ?? 0);
            $batteryLevel = intval($data['battery_level'] ?? 100);
            
            if (!$deliveryId || !$latitude || !$longitude) {
                throw new Exception('Datos de ubicación incompletos');
            }
            
            // Asegurar que driver_id sea string (VARCHAR en DB)
            $driverIdStr = strval($driverId);
            
            // Llamar al procedimiento almacenado
            $stmt = $conn->prepare("
                CALL UpdateDeliveryLocation(?, ?, ?, ?, ?, ?, ?, ?, @result)
            ");
            $stmt->execute([
                $deliveryId, $driverIdStr,
                $latitude, $longitude,
                $accuracy, $speed, $heading, $batteryLevel
            ]);
            
            // Cerrar el cursor del procedimiento
            $stmt->closeCursor();
            
            $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
            
            if ($result['result'] !== 'SUCCESS') {
                throw new Exception('Error al actualizar ubicación');
            }
            
            // Obtener información actualizada del delivery
            $stmt = $conn->prepare("
                SELECT 
                    distance_remaining,
                    eta_seconds,
                    delivery_status
                FROM order_deliveries
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverIdStr]);
            $deliveryInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ubicación actualizada',
                'distance_remaining' => $deliveryInfo['distance_remaining'],
                'eta_seconds' => $deliveryInfo['eta_seconds'],
                'status' => $deliveryInfo['delivery_status']
            ]);
            break;
        
        // ============================================
        // OBTENER INFORMACIÓN ACTUAL DE NAVEGACIÓN
        // ============================================
        case 'get_navigation_info':
            $deliveryId = intval($_GET['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('ID de entrega no válido');
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    od.id,
                    od.delivery_status,
                    od.current_lat,
                    od.current_lng,
                    od.destination_lat,
                    od.destination_lng,
                    od.distance_remaining,
                    od.eta_seconds,
                    od.route_distance,
                    od.route_duration,
                    od.last_location_update,
                    od.navigation_started_at,
                    od.navigation_route,
                    o.order_number,
                    o.shipping_address
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$info) {
                throw new Exception('Entrega no encontrada');
            }
            
            // Obtener últimas ubicaciones del tracking
            $stmt = $conn->prepare("
                SELECT 
                    latitude, longitude, speed, heading, 
                    recorded_at, is_moving
                FROM location_tracking
                WHERE delivery_id = ?
                ORDER BY recorded_at DESC
                LIMIT 10
            ");
            $stmt->execute([$deliveryId]);
            $recentLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'info' => $info,
                'recent_locations' => $recentLocations
            ]);
            break;
        
        // ============================================
        // PAUSAR NAVEGACIÓN
        // ============================================
        case 'pause_navigation':
            $data = json_decode(file_get_contents('php://input'), true);
            $deliveryId = intval($data['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('ID de entrega no válido');
            }
            
            // Verificar que la entrega pertenece al conductor
            $stmt = $conn->prepare("
                SELECT id, delivery_status
                FROM order_deliveries 
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Entrega no encontrada');
            }
            
            if ($delivery['delivery_status'] !== 'in_transit') {
                throw new Exception('La entrega no está en tránsito');
            }
            
            // Registrar evento de pausa
            $stmt = $conn->prepare("
                INSERT INTO navigation_events (
                    delivery_id, driver_id, event_type, 
                    latitude, longitude, event_data
                ) VALUES (?, ?, 'paused', NULL, NULL, '{}')
            ");
            $stmt->execute([$deliveryId, $driverId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Navegación pausada'
            ]);
            break;
        
        // ============================================
        // REANUDAR NAVEGACIÓN
        // ============================================
        case 'resume_navigation':
            $data = json_decode(file_get_contents('php://input'), true);
            $deliveryId = intval($data['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('ID de entrega no válido');
            }
            
            // Verificar que la entrega pertenece al conductor
            $stmt = $conn->prepare("
                SELECT id, delivery_status
                FROM order_deliveries 
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Entrega no encontrada');
            }
            
            if ($delivery['delivery_status'] !== 'in_transit') {
                throw new Exception('La entrega no está en tránsito');
            }
            
            // Registrar evento de reanudación
            $stmt = $conn->prepare("
                INSERT INTO navigation_events (
                    delivery_id, driver_id, event_type, 
                    latitude, longitude, event_data
                ) VALUES (?, ?, 'resumed', NULL, NULL, '{}')
            ");
            $stmt->execute([$deliveryId, $driverId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Navegación reanudada'
            ]);
            break;
        
        // ============================================
        // REGISTRAR EVENTO DE NAVEGACIÓN
        // ============================================
        case 'log_event':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $eventType = $data['event_type'] ?? '';
            $eventData = json_encode($data['event_data'] ?? []);
            $latitude = floatval($data['latitude'] ?? 0);
            $longitude = floatval($data['longitude'] ?? 0);
            
            if (!$deliveryId || !$eventType) {
                throw new Exception('Datos del evento incompletos');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO navigation_events (
                    delivery_id, driver_id, event_type, 
                    event_data, latitude, longitude
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $deliveryId, $driverId, $eventType,
                $eventData, $latitude, $longitude
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Evento registrado'
            ]);
            break;
        
        // ============================================
        // OBTENER HISTORIAL DE RUTA
        // ============================================
        case 'get_route_history':
            $deliveryId = intval($_GET['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('ID de entrega no válido');
            }
            
            // Verificar permisos
            $stmt = $conn->prepare("
                SELECT id FROM order_deliveries 
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Sin permisos');
            }
            
            // Obtener historial de ubicaciones
            $stmt = $conn->prepare("
                SELECT 
                    latitude, longitude, speed, heading,
                    accuracy, is_moving, recorded_at
                FROM location_tracking
                WHERE delivery_id = ?
                ORDER BY recorded_at ASC
            ");
            $stmt->execute([$deliveryId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener eventos
            $stmt = $conn->prepare("
                SELECT 
                    event_type, event_data, 
                    latitude, longitude, created_at
                FROM navigation_events
                WHERE delivery_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$deliveryId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'events' => $events
            ]);
            break;
        
        // ============================================
        // CALCULAR DISTANCIA ENTRE DOS PUNTOS
        // ============================================
        case 'calculate_distance':
            $lat1 = floatval($_GET['lat1'] ?? 0);
            $lng1 = floatval($_GET['lng1'] ?? 0);
            $lat2 = floatval($_GET['lat2'] ?? 0);
            $lng2 = floatval($_GET['lng2'] ?? 0);
            
            if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
                throw new Exception('Coordenadas incompletas');
            }
            
            // Usar la función de la base de datos
            $stmt = $conn->prepare("
                SELECT CalculateDistance(?, ?, ?, ?) as distance_km
            ");
            $stmt->execute([$lat1, $lng1, $lat2, $lng2]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'distance_km' => floatval($result['distance_km']),
                'distance_meters' => floatval($result['distance_km']) * 1000
            ]);
            break;
        
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    error_log("Error en navigation_api.php: " . $e->getMessage());
}
