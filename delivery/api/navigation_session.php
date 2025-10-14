<?php
/**
 * API de Gestión de Sesiones de Navegación
 * Maneja el estado persistente de navegación para delivery
 * 
 * Endpoints:
 * - GET    /get-state       -> Obtener estado actual
 * - POST   /start           -> Iniciar navegación
 * - POST   /pause           -> Pausar navegación
 * - POST   /resume          -> Reanudar navegación
 * - POST   /update-location -> Actualizar ubicación
 * - POST   /save-route      -> Guardar datos de ruta
 * - POST   /complete        -> Completar navegación
 * - POST   /cancel          -> Cancelar navegación
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso no autorizado'
    ]);
    exit();
}

$driverId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ==================================================
        // OBTENER ESTADO ACTUAL
        // ==================================================
        case 'get-state':
            $deliveryId = intval($_GET['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $stmt = $conn->prepare("CALL GetNavigationState(?, ?)");
            $stmt->execute([$deliveryId, $driverId]);
            $state = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($state) {
                // Decodificar JSON fields
                if ($state['route_data']) {
                    $state['route_data'] = json_decode($state['route_data'], true);
                }
                if ($state['device_info']) {
                    $state['device_info'] = json_decode($state['device_info'], true);
                }
                
                echo json_encode([
                    'success' => true,
                    'state' => $state,
                    'has_active_session' => in_array($state['session_status'], ['idle', 'navigating', 'paused'])
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'state' => null,
                    'has_active_session' => false,
                    'message' => 'No hay sesión activa'
                ]);
            }
            break;
            
        // ==================================================
        // INICIAR NAVEGACIÓN
        // ==================================================
        case 'start':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $lat = floatval($data['lat'] ?? 0);
            $lng = floatval($data['lng'] ?? 0);
            $deviceInfo = json_encode($data['device_info'] ?? []);
            
            if (!$deliveryId || !$lat || !$lng) {
                throw new Exception('Datos incompletos');
            }
            
            $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?)");
            $stmt->execute([$deliveryId, $driverId, $lat, $lng, $deviceInfo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            // También actualizar el estado de la entrega a in_transit si está en driver_accepted
            $updateStmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'in_transit',
                    started_at = NOW(),
                    location_lat = ?,
                    location_lng = ?
                WHERE id = ? 
                AND driver_id = ?
                AND delivery_status = 'driver_accepted'
            ");
            $updateStmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            echo json_encode([
                'success' => true,
                'message' => $result['message'] ?? 'Navegación iniciada',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ==================================================
        // PAUSAR NAVEGACIÓN
        // ==================================================
        case 'pause':
            $data = json_decode(file_get_contents('php://input'), true);
            $deliveryId = intval($data['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $stmt = $conn->prepare("CALL PauseNavigation(?, ?)");
            $stmt->execute([$deliveryId, $driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            echo json_encode([
                'success' => $result['status'] === 'success',
                'message' => $result['message']
            ]);
            break;
            
        // ==================================================
        // REANUDAR NAVEGACIÓN
        // ==================================================
        case 'resume':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $lat = floatval($data['lat'] ?? 0);
            $lng = floatval($data['lng'] ?? 0);
            
            if (!$deliveryId || !$lat || !$lng) {
                throw new Exception('Datos incompletos');
            }
            
            // Reanudar es similar a iniciar
            $deviceInfo = json_encode($data['device_info'] ?? []);
            $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?)");
            $stmt->execute([$deliveryId, $driverId, $lat, $lng, $deviceInfo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            echo json_encode([
                'success' => true,
                'message' => 'Navegación reanudada'
            ]);
            break;
            
        // ==================================================
        // ACTUALIZAR UBICACIÓN
        // ==================================================
        case 'update-location':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $lat = floatval($data['lat'] ?? 0);
            $lng = floatval($data['lng'] ?? 0);
            $speed = floatval($data['speed'] ?? 0);
            $distanceRemaining = floatval($data['distance_remaining'] ?? 0);
            $etaSeconds = intval($data['eta_seconds'] ?? 0);
            $batteryLevel = intval($data['battery_level'] ?? 100);
            
            if (!$deliveryId || !$lat || !$lng) {
                throw new Exception('Datos incompletos');
            }
            
            $stmt = $conn->prepare("
                CALL UpdateNavigationLocation(?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $deliveryId, 
                $driverId, 
                $lat, 
                $lng, 
                $speed, 
                $distanceRemaining, 
                $etaSeconds,
                $batteryLevel
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            echo json_encode([
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ==================================================
        // GUARDAR DATOS DE RUTA
        // ==================================================
        case 'save-route':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $routeData = json_encode($data['route_data'] ?? []);
            $totalDistance = floatval($data['total_distance'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $stmt = $conn->prepare("CALL SaveRouteData(?, ?, ?, ?)");
            $stmt->execute([$deliveryId, $driverId, $routeData, $totalDistance]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            echo json_encode([
                'success' => true,
                'message' => 'Ruta guardada'
            ]);
            break;
            
        // ==================================================
        // COMPLETAR NAVEGACIÓN
        // ==================================================
        case 'complete':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $totalDistance = floatval($data['total_distance'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $stmt = $conn->prepare("CALL CompleteNavigation(?, ?, ?)");
            $stmt->execute([$deliveryId, $driverId, $totalDistance]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            echo json_encode([
                'success' => true,
                'message' => $result['message']
            ]);
            break;
            
        // ==================================================
        // CANCELAR NAVEGACIÓN
        // ==================================================
        case 'cancel':
            $data = json_decode(file_get_contents('php://input'), true);
            $deliveryId = intval($data['delivery_id'] ?? 0);
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $stmt = $conn->prepare("
                UPDATE delivery_navigation_sessions
                SET 
                    session_status = 'cancelled',
                    navigation_cancelled_at = NOW()
                WHERE delivery_id = ? 
                AND driver_id = ?
                AND session_status IN ('idle', 'navigating', 'paused')
            ");
            $stmt->execute([$deliveryId, $driverId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Navegación cancelada'
            ]);
            break;
            
        // ==================================================
        // ACTUALIZAR CONFIGURACIÓN
        // ==================================================
        case 'update-settings':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $deliveryId = intval($data['delivery_id'] ?? 0);
            $voiceEnabled = isset($data['voice_enabled']) ? intval($data['voice_enabled']) : null;
            $trafficVisible = isset($data['traffic_visible']) ? intval($data['traffic_visible']) : null;
            
            if (!$deliveryId) {
                throw new Exception('delivery_id es requerido');
            }
            
            $updates = [];
            $params = [];
            
            if ($voiceEnabled !== null) {
                $updates[] = "voice_enabled = ?";
                $params[] = $voiceEnabled;
            }
            
            if ($trafficVisible !== null) {
                $updates[] = "traffic_visible = ?";
                $params[] = $trafficVisible;
            }
            
            if (empty($updates)) {
                throw new Exception('No hay configuraciones para actualizar');
            }
            
            $params[] = $deliveryId;
            $params[] = $driverId;
            
            $stmt = $conn->prepare("
                UPDATE delivery_navigation_sessions
                SET " . implode(', ', $updates) . "
                WHERE delivery_id = ? AND driver_id = ?
            ");
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada'
            ]);
            break;
            
        // ==================================================
        // ACCIÓN NO VÁLIDA
        // ==================================================
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (PDOException $e) {
    error_log("Error en navigation_session.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos',
        'message' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
