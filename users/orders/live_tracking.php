<?php
// Este archivo se usa via AJAX para actualizaciones en tiempo real
if (!isset($conn) || !isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) {
    die(json_encode(['success' => false, 'message' => 'ID inválido']));
}

try {
    // Obtener información de tracking en tiempo real
    $stmt = $conn->prepare("
        SELECT
            od.delivery_status,
            od.location_lat as current_lat,
            od.location_lng as current_lng,
            od.started_at,
            od.updated_at,
            od.estimated_arrival as estimated_delivery_time,
            u.name as driver_name,
            u.phone as driver_phone,
            ua.gps_latitude as destination_lat,
            ua.gps_longitude as destination_lng
        FROM order_deliveries od
        LEFT JOIN orders o ON o.id = od.order_id
        LEFT JOIN users u ON u.id = od.driver_id
        LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
        WHERE od.order_id = :order_id AND o.user_id = :user_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delivery) {
        die(json_encode(['success' => false, 'message' => 'Información de entrega no encontrada']));
    }

    // Verificar si el delivery está activo para tracking
    $isActive = in_array($delivery['delivery_status'], ['in_transit', 'out_for_delivery']);

    if (!$isActive) {
        die(json_encode([
            'success' => false,
            'message' => 'El delivery no está activo para tracking en tiempo real'
        ]));
    }

    // Calcular tiempo transcurrido desde la salida
    $timeElapsed = null;
    if ($delivery['started_at']) {
        $startTime = strtotime($delivery['started_at']);
        $currentTime = time();
        $timeElapsed = $currentTime - $startTime;
    }

    // Calcular distancia aproximada si hay coordenadas
    $distance = null;
    $eta = null;
    if ($delivery['current_lat'] && $delivery['current_lng'] && $delivery['destination_lat'] && $delivery['destination_lng']) {
        // Cálculo simple de distancia (fórmula de Haversine aproximada)
        $lat1 = deg2rad($delivery['current_lat']);
        $lon1 = deg2rad($delivery['current_lng']);
        $lat2 = deg2rad($delivery['destination_lat']);
        $lon2 = deg2rad($delivery['destination_lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = 6371 * $c; // Distancia en km

        // ETA estimado (asumiendo velocidad promedio de 30 km/h para delivery)
        if ($timeElapsed && $distance > 0) {
            $avgSpeed = 30; // km/h
            $timeRemaining = ($distance / $avgSpeed) * 3600; // en segundos
            $eta = $timeRemaining;
        }
    }

    // Formatear tiempo transcurrido
    $timeElapsedFormatted = null;
    if ($timeElapsed) {
        $hours = floor($timeElapsed / 3600);
        $minutes = floor(($timeElapsed % 3600) / 60);
        $timeElapsedFormatted = sprintf('%02d:%02d', $hours, $minutes);
    }

    // Formatear ETA
    $etaFormatted = null;
    if ($eta) {
        $hours = floor($eta / 3600);
        $minutes = floor(($eta % 3600) / 60);
        $etaFormatted = sprintf('%02d:%02d', $hours, $minutes);
    }

    echo json_encode([
        'success' => true,
        'tracking_data' => [
            'current_lat' => $delivery['current_lat'],
            'current_lng' => $delivery['current_lng'],
            'destination_lat' => $delivery['destination_lat'],
            'destination_lng' => $delivery['destination_lng'],
            'status' => $delivery['delivery_status'],
            'driver_name' => $delivery['driver_name'],
            'driver_phone' => $delivery['driver_phone'],
            'started_at' => $delivery['started_at'],
            'updated_at' => $delivery['updated_at'],
            'time_elapsed' => $timeElapsedFormatted,
            'distance_remaining' => $distance ? round($distance, 2) : null,
            'eta' => $etaFormatted,
            'estimated_delivery_time' => $delivery['estimated_delivery_time']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en live_tracking: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos de tracking']);
}
?>