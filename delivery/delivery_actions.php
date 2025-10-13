<?php
// Configuración estricta de errores
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar completamente el buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Debes iniciar sesión');
    }

    // Verificar rol de transportista
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'delivery') {
        throw new Exception('No tienes permisos de transportista');
    }

    // Obtener datos de la solicitud
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos');
    }

    if (!isset($data['action'])) {
        throw new Exception('Acción no especificada');
    }

    $action = $data['action'];
    $driverId = $user['id'];
    $driverName = $user['name'];

    // Iniciar transacción
    $conn->beginTransaction();

    switch ($action) {
        
        // AUTO-ASIGNARSE Y ACEPTAR UNA ORDEN DISPONIBLE
        case 'self_assign_order':
            if (!isset($data['order_id'])) {
                throw new Exception('ID de orden no especificado');
            }
            
            $orderId = (int)$data['order_id'];
            
            // Verificar que la orden existe y está disponible
            $stmt = $conn->prepare("
                SELECT o.id, o.order_number, o.status, o.payment_status, o.user_id
                FROM orders o
                WHERE o.id = ?
                AND o.status = 'shipped'
                AND o.payment_status = 'paid'
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Esta orden ya no está disponible');
            }
            
            // Verificar si ya existe una entrega activa
            $stmt = $conn->prepare("
                SELECT id FROM order_deliveries 
                WHERE order_id = ? 
                AND delivery_status NOT IN ('rejected', 'cancelled', 'failed')
            ");
            $stmt->execute([$orderId]);
            if ($stmt->fetch()) {
                throw new Exception('Esta orden ya está asignada');
            }
            
            // Obtener coordenadas de destino desde user_addresses
            $stmt = $conn->prepare("
                SELECT gps_latitude, gps_longitude 
                FROM user_addresses 
                WHERE user_id = ? AND is_default = 1 AND gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$order['user_id']]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $destLat = null;
            $destLng = null;
            
            if ($address && $address['gps_latitude'] && $address['gps_longitude']) {
                $destLat = floatval($address['gps_latitude']);
                $destLng = floatval($address['gps_longitude']);
            }
            
            // Crear nueva entrega directamente aceptada con coordenadas de destino
            $stmt = $conn->prepare("
                INSERT INTO order_deliveries 
                (order_id, driver_id, delivery_status, assigned_at, accepted_at, destination_lat, destination_lng, created_at, updated_at) 
                VALUES (?, ?, 'driver_accepted', NOW(), NOW(), ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$orderId, $driverId, $destLat, $destLng]);
            $deliveryId = $conn->lastInsertId();
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Orden aceptada exitosamente',
                'delivery_id' => $deliveryId,
                'order_number' => $order['order_number']
            ];
            break;

        // ACEPTAR ORDEN ASIGNADA
        case 'accept_order':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            
            // Verificar que la entrega existe y obtener user_id
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, o.order_number, o.user_id
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ?
            ");
            $stmt->execute([$deliveryId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta entrega no existe');
            }
            
            // Verificar permisos
            if ($delivery['driver_id'] && $delivery['driver_id'] != $driverId) {
                throw new Exception('Esta orden está asignada a otro transportista');
            }
            
            if ($delivery['delivery_status'] !== 'driver_assigned') {
                throw new Exception('Esta orden no puede ser aceptada. Estado: ' . $delivery['delivery_status']);
            }
            
            // Obtener coordenadas de destino desde user_addresses
            $stmt = $conn->prepare("
                SELECT gps_latitude, gps_longitude 
                FROM user_addresses 
                WHERE user_id = ? AND is_default = 1 AND gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$delivery['user_id']]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $destLat = null;
            $destLng = null;
            
            if ($address && $address['gps_latitude'] && $address['gps_longitude']) {
                $destLat = floatval($address['gps_latitude']);
                $destLng = floatval($address['gps_longitude']);
            }
            
            // Aceptar orden con coordenadas de destino
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'driver_accepted',
                    driver_id = ?,
                    accepted_at = NOW(),
                    destination_lat = ?,
                    destination_lng = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$driverId, $destLat, $destLng, $deliveryId]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Orden #' . $delivery['order_number'] . ' aceptada',
                'delivery_status' => 'driver_accepted'
            ];
            break;

        // INICIAR RECORRIDO
        case 'start_trip':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            $lat = isset($data['latitude']) ? floatval($data['latitude']) : null;
            $lng = isset($data['longitude']) ? floatval($data['longitude']) : null;
            
            // Verificar entrega y obtener información del usuario
            $stmt = $conn->prepare("
                SELECT 
                    od.id, 
                    od.driver_id, 
                    od.delivery_status, 
                    o.order_number,
                    o.user_id,
                    o.shipping_address
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta entrega no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'driver_accepted') {
                throw new Exception('Debes aceptar la orden primero');
            }
            
            // Obtener coordenadas de destino desde user_addresses (dirección por defecto)
            $stmt = $conn->prepare("
                SELECT gps_latitude, gps_longitude 
                FROM user_addresses 
                WHERE user_id = ? AND is_default = 1 AND gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$delivery['user_id']]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $destLat = null;
            $destLng = null;
            
            if ($address && $address['gps_latitude'] && $address['gps_longitude']) {
                $destLat = floatval($address['gps_latitude']);
                $destLng = floatval($address['gps_longitude']);
            }
            
            // Iniciar recorrido con coordenadas de destino
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'in_transit',
                    started_at = NOW(),
                    current_lat = ?,
                    current_lng = ?,
                    destination_lat = ?,
                    destination_lng = ?,
                    updated_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $destLat, $destLng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Recorrido iniciado para orden #' . $delivery['order_number'],
                'delivery_status' => 'in_transit',
                'delivery_id' => $deliveryId,
                'destination' => [
                    'lat' => $destLat,
                    'lng' => $destLng
                ]
            ];
            break;

        // MARCAR COMO LLEGADO
        case 'mark_arrived':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            $lat = isset($data['latitude']) ? floatval($data['latitude']) : null;
            $lng = isset($data['longitude']) ? floatval($data['longitude']) : null;
            
            // Verificar entrega
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta entrega no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'in_transit') {
                throw new Exception('Debes estar en tránsito primero');
            }
            
            // Marcar como llegado
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'arrived',
                    arrived_at = NOW(),
                    current_lat = ?,
                    current_lng = ?,
                    updated_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Has llegado al destino de orden #' . $delivery['order_number'],
                'delivery_status' => 'arrived'
            ];
            break;

        // COMPLETAR ENTREGA
        case 'complete_delivery':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            $recipientName = isset($data['recipient_name']) ? trim($data['recipient_name']) : null;
            $notes = isset($data['notes']) ? trim($data['notes']) : null;
            
            // Verificar entrega
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, od.order_id, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta entrega no está asignada a ti');
            }
            
            if (!in_array($delivery['delivery_status'], ['in_transit', 'arrived'])) {
                throw new Exception('La orden debe estar en tránsito o haber llegado');
            }
            
            // Completar entrega
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'delivered',
                    delivered_at = NOW(),
                    recipient_name = ?,
                    delivery_notes = ?,
                    updated_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$recipientName, $notes, $deliveryId, $driverId]);
            
            // Actualizar orden principal
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'delivered',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$delivery['order_id']]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Entrega completada para orden #' . $delivery['order_number'],
                'delivery_status' => 'delivered'
            ];
            break;

        // RECHAZAR ORDEN
        case 'reject_order':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            $reason = isset($data['reason']) ? trim($data['reason']) : 'No especificado';
            
            // Verificar entrega
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta entrega no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'driver_assigned') {
                throw new Exception('Solo puedes rechazar órdenes recién asignadas');
            }
            
            // Rechazar orden
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'rejected',
                    rejection_reason = ?,
                    cancelled_at = NOW(),
                    driver_id = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $deliveryId]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Orden #' . $delivery['order_number'] . ' rechazada',
                'delivery_status' => 'rejected'
            ];
            break;

        // ACTUALIZAR UBICACIÓN
        case 'update_location':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = (int)$data['delivery_id'];
            $lat = isset($data['latitude']) ? floatval($data['latitude']) : null;
            $lng = isset($data['longitude']) ? floatval($data['longitude']) : null;
            
            // Actualizar ubicación
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET current_lat = ?,
                    current_lng = ?,
                    updated_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Ubicación actualizada'
            ];
            break;

        default:
            throw new Exception('Acción no válida: ' . $action);
    }

    // Limpiar buffer y enviar respuesta
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log del error
    error_log("Error en delivery_actions: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Limpiar buffer y enviar error
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
