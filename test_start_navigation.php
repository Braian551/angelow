<?php
/**
 * Script de prueba para el procedimiento StartNavigation
 */

require_once 'conexion.php';

// Simular datos de sesión
$_SESSION['user_id'] = '6862b7448112f'; // Ajustar según tu ID de conductor

$driverId = $_SESSION['user_id'];

try {
    // Buscar una entrega activa
    $stmt = $conn->prepare("
        SELECT id, order_id, destination_lat, destination_lng
        FROM order_deliveries 
        WHERE driver_id = ? 
        AND delivery_status IN ('driver_accepted', 'in_transit')
        ORDER BY accepted_at DESC
        LIMIT 1
    ");
    $stmt->execute([$driverId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$delivery) {
        echo "No hay entregas activas para el conductor {$driverId}\n";
        echo "Por favor, crea o acepta una entrega primero.\n";
        exit;
    }
    
    echo "Entrega encontrada: ID {$delivery['id']}, Order ID {$delivery['order_id']}\n\n";
    
    // Datos de prueba
    $deliveryId = $delivery['id'];
    $startLat = 6.252786;
    $startLng = -75.538400;
    $destLat = floatval($delivery['destination_lat']);
    $destLng = floatval($delivery['destination_lng']);
    $routeJson = json_encode([
        'distance_km' => 5.5,
        'duration_seconds' => 900,
        'geometry' => []
    ]);
    $distanceKm = 5.5;
    $durationSeconds = 900;
    
    echo "Intentando iniciar navegación...\n";
    echo "  - Delivery ID: {$deliveryId}\n";
    echo "  - Driver ID: {$driverId} (tipo: " . gettype($driverId) . ")\n";
    echo "  - Desde: {$startLat}, {$startLng}\n";
    echo "  - Hacia: {$destLat}, {$destLng}\n\n";
    
    // Convertir driver_id a string
    $driverIdStr = strval($driverId);
    
    // Llamar al procedimiento
    $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
    $stmt->execute([
        $deliveryId, $driverIdStr,
        $startLat, $startLng,
        $destLat, $destLng,
        $routeJson, $distanceKm, $durationSeconds
    ]);
    
    // Cerrar cursor
    $stmt->closeCursor();
    
    // Obtener resultado
    $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
    
    echo "Resultado del procedimiento: " . $result['result'] . "\n\n";
    
    if ($result['result'] === 'SUCCESS') {
        echo "✓ Navegación iniciada correctamente!\n\n";
        
        // Verificar los datos actualizados
        $stmt = $conn->prepare("
            SELECT 
                delivery_status, 
                navigation_started_at,
                route_distance,
                route_duration,
                distance_remaining,
                eta_seconds
            FROM order_deliveries
            WHERE id = ?
        ");
        $stmt->execute([$deliveryId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Datos actualizados:\n";
        echo "  - Estado: {$updated['delivery_status']}\n";
        echo "  - Navegación iniciada: {$updated['navigation_started_at']}\n";
        echo "  - Distancia ruta: {$updated['route_distance']} km\n";
        echo "  - Duración ruta: {$updated['route_duration']} segundos\n";
        echo "  - Distancia restante: {$updated['distance_remaining']} km\n";
        echo "  - ETA: {$updated['eta_seconds']} segundos\n";
        
    } else {
        echo "✗ Error: " . $result['result'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error de base de datos:\n";
    echo "  Mensaje: " . $e->getMessage() . "\n";
    echo "  Código: " . $e->getCode() . "\n";
}
