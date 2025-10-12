<?php
/**
 * API para obtener órdenes del transportista
 * Devuelve órdenes según diferentes categorías (tabs)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

$driverId = $_SESSION['user_id'];

// Obtener parámetros
$tab = $_GET['tab'] ?? 'available';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = min(50, max(1, intval($_GET['per_page'] ?? 12)));
$search = $_GET['search'] ?? '';

$offset = ($page - 1) * $perPage;

try {
    // ====================================
    // CONSTRUIR QUERY SEGÚN LA PESTAÑA
    // ====================================
    
    $baseQuery = "
        SELECT 
            o.id,
            o.order_number,
            o.total,
            o.shipping_address,
            o.shipping_city,
            o.delivery_notes,
            o.created_at,
            o.status as order_status,
            o.payment_status,
            CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            od.id as delivery_id,
            od.delivery_status,
            od.assigned_at,
            od.accepted_at,
            od.started_at,
            od.arrived_at,
            od.delivered_at
    ";
    
    $whereConditions = [];
    $params = [];
    
    switch ($tab) {
        case 'available':
            // Órdenes enviadas y pagadas SIN ASIGNAR a ningún transportista
            $query = "
                SELECT 
                    o.id,
                    o.order_number,
                    o.total,
                    o.shipping_address,
                    o.shipping_city,
                    o.delivery_notes,
                    o.created_at,
                    o.status as order_status,
                    o.payment_status,
                    CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as customer_name,
                    u.phone as customer_phone,
                    u.email as customer_email,
                    NULL as delivery_id,
                    NULL as delivery_status,
                    NULL as assigned_at,
                    NULL as accepted_at,
                    NULL as started_at,
                    NULL as arrived_at,
                    NULL as delivered_at
                FROM orders o
                INNER JOIN users u ON o.user_id = u.id
                WHERE o.status = 'shipped'
                AND o.payment_status = 'paid'
                AND NOT EXISTS (
                    SELECT 1 FROM order_deliveries od2 
                    WHERE od2.order_id = o.id 
                    AND od2.delivery_status NOT IN ('rejected', 'cancelled')
                )
            ";
            break;
            
        case 'assigned':
            // Órdenes asignadas a este transportista pero NO aceptadas aún
            $query = "
                $baseQuery
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN users u ON o.user_id = u.id
                WHERE od.driver_id = ?
                AND od.delivery_status = 'driver_assigned'
            ";
            $params[] = $driverId;
            break;
            
        case 'active':
            // Órdenes aceptadas y en proceso (aceptada, en tránsito, llegado)
            $query = "
                $baseQuery
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN users u ON o.user_id = u.id
                WHERE od.driver_id = ?
                AND od.delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
            ";
            $params[] = $driverId;
            break;
            
        case 'completed':
            // Órdenes completadas por este transportista
            $query = "
                $baseQuery
                FROM order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN users u ON o.user_id = u.id
                WHERE od.driver_id = ?
                AND od.delivery_status = 'delivered'
            ";
            $params[] = $driverId;
            break;
            
        default:
            throw new Exception('Pestaña no válida');
    }
    
    // Agregar búsqueda si existe
    if (!empty($search)) {
        $query .= " AND (
            o.order_number LIKE ?
            OR o.shipping_address LIKE ?
            OR o.shipping_city LIKE ?
            OR CONCAT(u.name, ' ', COALESCE(u.apellido, '')) LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Ordenar
    $query .= " ORDER BY ";
    if ($tab === 'completed') {
        $query .= "od.delivered_at DESC";
    } elseif ($tab === 'available') {
        $query .= "o.created_at DESC";
    } else {
        $query .= "od.assigned_at DESC";
    }
    
    // ====================================
    // CONTAR TOTAL DE REGISTROS
    // ====================================
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->execute($params);
    $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    // ====================================
    // OBTENER REGISTROS CON PAGINACIÓN
    // ====================================
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ====================================
    // OBTENER CONTADORES PARA TODAS LAS PESTAÑAS
    // ====================================
    
    // Disponibles (sin asignar)
    $stmtAvailable = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders o
        WHERE o.status = 'shipped'
        AND o.payment_status = 'paid'
        AND NOT EXISTS (
            SELECT 1 FROM order_deliveries od 
            WHERE od.order_id = o.id 
            AND od.delivery_status NOT IN ('rejected', 'cancelled')
        )
    ");
    $stmtAvailable->execute();
    $countAvailable = $stmtAvailable->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Asignadas a mí
    $stmtAssigned = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ?
        AND delivery_status = 'driver_assigned'
    ");
    $stmtAssigned->execute([$driverId]);
    $countAssigned = $stmtAssigned->fetch(PDO::FETCH_ASSOC)['count'];
    
    // En proceso
    $stmtActive = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ?
        AND delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
    ");
    $stmtActive->execute([$driverId]);
    $countActive = $stmtActive->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Completadas
    $stmtCompleted = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ?
        AND delivery_status = 'delivered'
    ");
    $stmtCompleted->execute([$driverId]);
    $countCompleted = $stmtCompleted->fetch(PDO::FETCH_ASSOC)['count'];
    
    // ====================================
    // RESPUESTA
    // ====================================
    $totalPages = ceil($total / $perPage);
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'meta' => [
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ],
        'counts' => [
            'available' => (int)$countAvailable,
            'assigned' => (int)$countAssigned,
            'active' => (int)$countActive,
            'completed' => (int)$countCompleted
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get_orders.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener órdenes',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
