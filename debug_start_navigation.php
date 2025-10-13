<?php
require_once 'conexion.php';

$driverId = '6862b7448112f';
$deliveryId = 9;

try {
    // Activar mostrar errores de MySQL
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Verificando entrega...\n";
    $stmt = $conn->prepare("
        SELECT id, driver_id, delivery_status 
        FROM order_deliveries 
        WHERE id = ? AND driver_id = ?
    ");
    $stmt->execute([$deliveryId, $driverId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$delivery) {
        echo "✗ Entrega no encontrada\n";
        exit;
    }
    
    echo "✓ Entrega encontrada: ID {$delivery['id']}, Estado: {$delivery['delivery_status']}\n\n";
    
    // Verificar que el estado es válido
    if (!in_array($delivery['delivery_status'], ['driver_accepted', 'in_transit'])) {
        echo "✗ Estado no válido para iniciar navegación: {$delivery['delivery_status']}\n";
        echo "  Estados válidos: driver_accepted, in_transit\n";
        exit;
    }
    
    echo "✓ Estado válido para navegación\n\n";
    
    // Probar la inserción en navigation_events directamente
    echo "Probando inserción en navigation_events...\n";
    $stmt = $conn->prepare("
        INSERT INTO navigation_events (
            delivery_id, driver_id, event_type, 
            latitude, longitude, event_data
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $eventData = json_encode([
        'distance_km' => 5.5,
        'duration_seconds' => 900
    ]);
    
    $stmt->execute([
        $deliveryId, 
        $driverId, 
        'navigation_started',
        6.252786, 
        -75.538400,
        $eventData
    ]);
    
    echo "✓ Inserción exitosa en navigation_events (ID: " . $conn->lastInsertId() . ")\n\n";
    
    // Limpiar el evento de prueba
    $conn->exec("DELETE FROM navigation_events WHERE id = " . $conn->lastInsertId());
    
    // Ahora probar el UPDATE en order_deliveries
    echo "Probando UPDATE en order_deliveries...\n";
    $stmt = $conn->prepare("
        UPDATE order_deliveries
        SET 
            navigation_started_at = NOW(),
            current_lat = ?,
            current_lng = ?,
            destination_lat = ?,
            destination_lng = ?,
            navigation_route = ?,
            route_distance = ?,
            route_duration = ?,
            distance_remaining = ?,
            eta_seconds = ?,
            last_location_update = NOW(),
            delivery_status = 'in_transit'
        WHERE id = ?
    ");
    
    $routeJson = json_encode([
        'distance_km' => 5.5,
        'duration_seconds' => 900,
        'geometry' => []
    ]);
    
    $stmt->execute([
        6.252786, -75.538400,
        6.25617528, -75.55546772,
        $routeJson,
        5.5, 900, 5.5, 900,
        $deliveryId
    ]);
    
    echo "✓ UPDATE exitoso en order_deliveries (filas afectadas: " . $stmt->rowCount() . ")\n\n";
    
    // Ahora intentar el procedimiento completo
    echo "Llamando al procedimiento StartNavigation...\n";
    $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
    $stmt->execute([
        $deliveryId, $driverId,
        6.252786, -75.538400,
        6.25617528, -75.55546772,
        $routeJson, 5.5, 900
    ]);
    
    $stmt->closeCursor();
    
    $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
    
    echo "Resultado: " . $result['result'] . "\n";
    
    if ($result['result'] === 'SUCCESS') {
        echo "✓ Procedimiento ejecutado correctamente!\n";
    } else {
        echo "✗ Procedimiento falló: " . $result['result'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ Error de PDO:\n";
    echo "  Mensaje: " . $e->getMessage() . "\n";
    echo "  Código: " . $e->getCode() . "\n";
    echo "  Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
