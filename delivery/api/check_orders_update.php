<?php
/**
 * API para verificar si hay actualizaciones en las órdenes
 * Devuelve solo un hash para comparación, evitando cargar datos innecesarios
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

ob_clean();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? $_SESSION['user_role'] ?? null) !== 'delivery') {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$driverId = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'available';

try {
    // Construir query según pestaña para obtener IDs y timestamps
    $query = "";
    $params = [];
    
    switch ($tab) {
        case 'available':
            $query = "
                SELECT o.id, o.updated_at
                FROM orders o
                WHERE o.status = 'shipped'
                AND o.payment_status = 'paid'
                AND NOT EXISTS (
                    SELECT 1 FROM order_deliveries od2 
                    WHERE od2.order_id = o.id 
                    AND od2.delivery_status NOT IN ('rejected', 'cancelled')
                )
                ORDER BY o.created_at DESC
            ";
            break;
            
        case 'assigned':
            $query = "
                SELECT od.id, od.updated_at
                FROM order_deliveries od
                WHERE od.driver_id = ?
                AND od.delivery_status = 'driver_assigned'
                ORDER BY od.assigned_at DESC
            ";
            $params[] = $driverId;
            break;
            
        case 'active':
            $query = "
                SELECT od.id, od.updated_at
                FROM order_deliveries od
                WHERE od.driver_id = ?
                AND od.delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
                ORDER BY od.assigned_at DESC
            ";
            $params[] = $driverId;
            break;
            
        case 'completed':
            $query = "
                SELECT od.id, od.updated_at
                FROM order_deliveries od
                WHERE od.driver_id = ?
                AND od.delivery_status = 'delivered'
                ORDER BY od.delivered_at DESC
                LIMIT 50
            ";
            $params[] = $driverId;
            break;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear hash de los datos
    $hashData = json_encode($records);
    $hash = md5($hashData);
    
    // Obtener contadores
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
    
    $stmtAssigned = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ? AND delivery_status = 'driver_assigned'
    ");
    $stmtAssigned->execute([$driverId]);
    $countAssigned = $stmtAssigned->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmtActive = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ? AND delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
    ");
    $stmtActive->execute([$driverId]);
    $countActive = $stmtActive->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmtCompleted = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE driver_id = ? AND delivery_status = 'delivered'
    ");
    $stmtCompleted->execute([$driverId]);
    $countCompleted = $stmtCompleted->fetch(PDO::FETCH_ASSOC)['count'];
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'hash' => $hash,
        'count' => count($records),
        'counts' => [
            'available' => (int)$countAvailable,
            'assigned' => (int)$countAssigned,
            'active' => (int)$countActive,
            'completed' => (int)$countCompleted
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en check_orders_update.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al verificar actualizaciones'
    ]);
}

ob_end_flush();
exit;
