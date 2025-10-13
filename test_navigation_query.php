<?php
// Simular ambiente web
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['DOCUMENT_ROOT'] = 'C:/laragon/www';

session_start();
$_SESSION['user_id'] = '6862b7448112f'; // Tu driver ID
$_SESSION['role'] = 'delivery';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';

$driverId = '6862b7448112f';
$deliveryId = 6; // Una de tus entregas activas

echo "=== TEST DE NAVEGACIÓN ===\n\n";
echo "Driver ID: $driverId\n";
echo "Delivery ID: $deliveryId\n\n";

try {
    echo "Ejecutando query...\n";
    
    $stmt = $conn->prepare("
        SELECT 
            od.id,
            od.order_id,
            od.delivery_status,
            od.current_lat,
            od.current_lng,
            od.destination_lat,
            od.destination_lng,
            o.order_number,
            o.shipping_address,
            o.shipping_city,
            o.shipping_state,
            o.shipping_zip,
            o.total,
            o.delivery_notes,
            CONCAT(u.name) AS customer_name,
            u.phone AS customer_phone
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        INNER JOIN users u ON o.user_id = u.id
        WHERE od.id = ? 
        AND od.driver_id = ?
        AND od.delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
    ");
    
    $stmt->execute([$deliveryId, $driverId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($delivery) {
        echo "✅ ENTREGA ENCONTRADA:\n";
        print_r($delivery);
        echo "\n\n✅ La navegación DEBERÍA funcionar\n";
    } else {
        echo "❌ No se encontró la entrega\n";
        
        // Verificar sin filtro de estado
        $stmt = $conn->prepare("
            SELECT id, driver_id, delivery_status
            FROM order_deliveries 
            WHERE id = ?
        ");
        $stmt->execute([$deliveryId]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check) {
            echo "Entrega existe pero:\n";
            echo "- Driver ID: {$check['driver_id']} (esperado: $driverId)\n";
            echo "- Estado: {$check['delivery_status']}\n";
        } else {
            echo "La entrega no existe en la base de datos\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR SQL: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}
