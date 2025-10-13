<?php
require_once 'conexion.php';

$driverId = '6862b7448112f';
$deliveryId = 9;

try {
    $stmt = $conn->prepare("
        SELECT 
            id, driver_id, delivery_status,
            navigation_started_at
        FROM order_deliveries 
        WHERE id = ?
    ");
    $stmt->execute([$deliveryId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Estado actual de la entrega:\n";
    echo "  ID: {$delivery['id']}\n";
    echo "  Driver ID: {$delivery['driver_id']}\n";
    echo "  Estado: {$delivery['delivery_status']}\n";
    echo "  Navegación iniciada: " . ($delivery['navigation_started_at'] ?: 'NULL') . "\n\n";
    
    // Verificar si cumple la condición del procedimiento
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM order_deliveries 
        WHERE id = ? 
        AND driver_id = ?
        AND delivery_status IN ('driver_accepted', 'in_transit')
    ");
    $stmt->execute([$deliveryId, $driverId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "¿Cumple condiciones del procedimiento? " . ($result['count'] > 0 ? 'SÍ' : 'NO') . "\n";
    
    // Verificar comparación exacta
    $stmt = $conn->prepare("SELECT driver_id FROM order_deliveries WHERE id = ?");
    $stmt->execute([$deliveryId]);
    $dbDriverId = $stmt->fetch(PDO::FETCH_COLUMN);
    
    echo "\nComparación de driver_id:\n";
    echo "  En BD: '{$dbDriverId}' (longitud: " . strlen($dbDriverId) . ")\n";
    echo "  Buscado: '{$driverId}' (longitud: " . strlen($driverId) . ")\n";
    echo "  ¿Son iguales? " . ($dbDriverId === $driverId ? 'SÍ' : 'NO') . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
