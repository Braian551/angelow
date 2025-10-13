<?php
require_once 'conexion.php';

try {
    // Ver todas las entregas
    $stmt = $conn->query("
        SELECT 
            od.id,
            od.driver_id,
            od.order_id,
            od.delivery_status,
            o.order_number
        FROM order_deliveries od
        LEFT JOIN orders o ON od.order_id = o.id
        ORDER BY od.id DESC
        LIMIT 10
    ");
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Entregas recientes:\n\n";
    foreach ($deliveries as $d) {
        echo "ID: {$d['id']}, Order: {$d['order_number']}, Driver: {$d['driver_id']}, Estado: {$d['delivery_status']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
