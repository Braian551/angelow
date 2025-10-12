<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit();
}

// Verificar que el usuario sea un transportista
try {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'delivery') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos de transportista']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de autenticación: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de autenticación']);
    exit();
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit();
}

$action = $data['action'];
$driverId = $user['id'];
$driverName = $user['name'];

// Función para obtener IP del usuario
function getRealUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }
    
    return $ip;
}

try {
    $conn->beginTransaction();
    
    // Establecer variables de sesión MySQL para los triggers
    $userIp = getRealUserIP();
    $conn->exec("SET @current_user_id = " . $conn->quote($driverId));
    $conn->exec("SET @current_user_name = " . $conn->quote($driverName));
    $conn->exec("SET @current_user_ip = " . $conn->quote($userIp));
    
    switch ($action) {
        
        // ============================================
        // AUTO-ASIGNARSE Y ACEPTAR UNA ORDEN DISPONIBLE
        // ============================================
        case 'self_assign_order':
            if (!isset($data['order_id'])) {
                throw new Exception('ID de orden no especificado');
            }
            
            $orderId = $data['order_id'];
            
            // Verificar que la orden existe y está disponible
            $stmt = $conn->prepare("
                SELECT o.id, o.order_number, o.status, o.payment_status
                FROM orders o
                WHERE o.id = ?
                AND o.status = 'shipped'
                AND o.payment_status = 'paid'
                AND NOT EXISTS (
                    SELECT 1 FROM order_deliveries od 
                    WHERE od.order_id = o.id 
                    AND od.delivery_status NOT IN ('rejected', 'cancelled')
                )
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Esta orden ya no está disponible');
            }
            
            // Llamar al procedimiento almacenado para asignar
            $stmt = $conn->prepare("CALL AssignOrderToDriver(?, ?, @result)");
            $stmt->execute([$orderId, $driverId]);
            
            // Obtener el resultado
            $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
            
            if ($result['result'] !== 'SUCCESS') {
                throw new Exception('No se pudo asignar la orden: ' . $result['result']);
            }
            
            // Obtener el ID de la entrega recién creada
            $stmt = $conn->prepare("
                SELECT id FROM order_deliveries 
                WHERE order_id = ? AND driver_id = ?
                ORDER BY assigned_at DESC LIMIT 1
            ");
            $stmt->execute([$orderId, $driverId]);
            $deliveryId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Inmediatamente aceptar la orden
            $stmt = $conn->prepare("CALL DriverAcceptOrder(?, ?, @result)");
            $stmt->execute([$deliveryId, $driverId]);
            
            $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
            
            if ($result['result'] !== 'SUCCESS') {
                throw new Exception('Orden asignada pero no se pudo aceptar: ' . $result['result']);
            }
            
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Orden aceptada exitosamente',
                'delivery_id' => $deliveryId,
                'order_number' => $order['order_number']
            ]);
            break;
        
        // ============================================
        // ACEPTAR ORDEN
        // ============================================
        case 'accept_order':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            
            // Verificar que la entrega está asignada a este transportista
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, od.order_id, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta orden no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'driver_assigned') {
                throw new Exception('Esta orden no está en estado válido para aceptar');
            }
            
            // Aceptar la orden
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'driver_accepted',
                    accepted_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Orden #' . $delivery['order_number'] . ' aceptada correctamente',
                'delivery_status' => 'driver_accepted'
            ]);
            break;
        
        // ============================================
        // RECHAZAR ORDEN
        // ============================================
        case 'reject_order':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            $reason = isset($data['reason']) ? $data['reason'] : 'No especificado';
            
            // Verificar que la entrega está asignada a este transportista
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, od.order_id, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta orden no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'driver_assigned') {
                throw new Exception('Esta orden no está en estado válido para rechazar');
            }
            
            // Rechazar la orden
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'rejected',
                    rejection_reason = ?,
                    cancelled_at = NOW(),
                    driver_id = NULL
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$reason, $deliveryId, $driverId]);
            
            // Volver a poner la orden en awaiting_driver
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'awaiting_driver',
                    assigned_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$deliveryId]);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Orden #' . $delivery['order_number'] . ' rechazada',
                'delivery_status' => 'rejected'
            ]);
            break;
        
        // ============================================
        // INICIAR RECORRIDO
        // ============================================
        case 'start_trip':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            $lat = isset($data['latitude']) ? $data['latitude'] : null;
            $lng = isset($data['longitude']) ? $data['longitude'] : null;
            
            // Verificar que la orden fue aceptada
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta orden no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'driver_accepted') {
                throw new Exception('Debes aceptar la orden primero');
            }
            
            // Iniciar recorrido
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'in_transit',
                    started_at = NOW(),
                    location_lat = ?,
                    location_lng = ?
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Recorrido iniciado para orden #' . $delivery['order_number'],
                'delivery_status' => 'in_transit'
            ]);
            break;
        
        // ============================================
        // MARCAR COMO LLEGADO
        // ============================================
        case 'mark_arrived':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            $lat = isset($data['latitude']) ? $data['latitude'] : null;
            $lng = isset($data['longitude']) ? $data['longitude'] : null;
            
            // Verificar que está en tránsito
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta orden no está asignada a ti');
            }
            
            if ($delivery['delivery_status'] !== 'in_transit') {
                throw new Exception('Debes estar en tránsito para marcar como llegado');
            }
            
            // Marcar como llegado
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'arrived',
                    arrived_at = NOW(),
                    location_lat = ?,
                    location_lng = ?
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Has llegado al destino de la orden #' . $delivery['order_number'],
                'delivery_status' => 'arrived'
            ]);
            break;
        
        // ============================================
        // COMPLETAR ENTREGA
        // ============================================
        case 'complete_delivery':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            $recipientName = isset($data['recipient_name']) ? $data['recipient_name'] : null;
            $notes = isset($data['notes']) ? $data['notes'] : null;
            $photo = isset($data['photo']) ? $data['photo'] : null;
            
            // Verificar que está en estado válido para completar
            $stmt = $conn->prepare("
                SELECT od.id, od.driver_id, od.delivery_status, od.order_id, o.order_number
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE od.id = ? AND od.driver_id = ?
            ");
            $stmt->execute([$deliveryId, $driverId]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$delivery) {
                throw new Exception('Esta orden no está asignada a ti');
            }
            
            if (!in_array($delivery['delivery_status'], ['in_transit', 'arrived'])) {
                throw new Exception('La orden debe estar en tránsito o haber llegado para completar la entrega');
            }
            
            // Completar entrega
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET delivery_status = 'delivered',
                    delivered_at = NOW(),
                    recipient_name = ?,
                    delivery_notes = ?,
                    delivery_photo = ?
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$recipientName, $notes, $photo, $deliveryId, $driverId]);
            
            // Actualizar orden principal (el trigger lo hará automáticamente)
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Entrega completada exitosamente para orden #' . $delivery['order_number'],
                'delivery_status' => 'delivered'
            ]);
            break;
        
        // ============================================
        // ACTUALIZAR UBICACIÓN
        // ============================================
        case 'update_location':
            if (!isset($data['delivery_id'])) {
                throw new Exception('ID de entrega no especificado');
            }
            
            $deliveryId = $data['delivery_id'];
            $lat = isset($data['latitude']) ? $data['latitude'] : null;
            $lng = isset($data['longitude']) ? $data['longitude'] : null;
            
            // Actualizar ubicación
            $stmt = $conn->prepare("
                UPDATE order_deliveries 
                SET location_lat = ?,
                    location_lng = ?,
                    updated_at = NOW()
                WHERE id = ? AND driver_id = ?
            ");
            $stmt->execute([$lat, $lng, $deliveryId, $driverId]);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Ubicación actualizada'
            ]);
            break;
        
        // ============================================
        // OBTENER ÓRDENES DISPONIBLES
        // ============================================
        case 'get_available_orders':
            $stmt = $conn->prepare("
                SELECT * FROM v_orders_awaiting_driver
                ORDER BY created_at ASC
                LIMIT 10
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
            break;
        
        // ============================================
        // OBTENER MIS ENTREGAS ACTIVAS
        // ============================================
        case 'get_my_deliveries':
            $stmt = $conn->prepare("
                SELECT 
                    od.id as delivery_id,
                    od.delivery_status,
                    od.assigned_at,
                    od.accepted_at,
                    od.started_at,
                    od.estimated_arrival,
                    od.location_lat,
                    od.location_lng,
                    o.id as order_id,
                    o.order_number,
                    o.total,
                    o.shipping_address,
                    o.shipping_city,
                    o.shipping_state,
                    o.delivery_notes,
                    CONCAT(u.name, ' - ', u.phone) as customer_info
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN users u ON o.user_id = u.id
                WHERE od.driver_id = ?
                AND od.delivery_status IN ('driver_assigned', 'driver_accepted', 'in_transit', 'arrived')
                ORDER BY od.assigned_at DESC
            ");
            $stmt->execute([$driverId]);
            $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'deliveries' => $deliveries
            ]);
            break;
        
        // ============================================
        // OBTENER ESTADÍSTICAS
        // ============================================
        case 'get_statistics':
            $stmt = $conn->prepare("
                SELECT * FROM driver_statistics WHERE driver_id = ?
            ");
            $stmt->execute([$driverId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stats) {
                // Crear registro de estadísticas si no existe
                $stmt = $conn->prepare("
                    INSERT INTO driver_statistics (driver_id) VALUES (?)
                ");
                $stmt->execute([$driverId]);
                
                $stmt = $conn->prepare("
                    SELECT * FROM driver_statistics WHERE driver_id = ?
                ");
                $stmt->execute([$driverId]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
        
        default:
            throw new Exception('Acción no válida');
    }
    
    // Limpiar variables de sesión MySQL
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = NULL");
    $conn->exec("SET @current_user_ip = NULL");
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Limpiar variables de sesión MySQL
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = NULL");
    $conn->exec("SET @current_user_ip = NULL");
    
    error_log("Error en delivery_actions: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
