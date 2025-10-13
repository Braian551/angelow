<?php
require_once 'conexion.php';

echo "=== ACTUALIZANDO COORDENADAS DE DESTINO PARA ORDEN 27 ===\n\n";

// Obtener información de la orden
$stmt = $conn->prepare("
    SELECT 
        od.id as delivery_id,
        od.order_id,
        od.destination_lat,
        od.destination_lng,
        o.user_id,
        o.order_number
    FROM order_deliveries od
    INNER JOIN orders o ON od.order_id = o.id
    WHERE od.order_id = 27 AND od.delivery_status = 'in_transit'
");
$stmt->execute();
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    echo "❌ No se encontró entrega activa para la orden 27\n";
    exit(1);
}

echo "📦 Delivery ID: {$delivery['delivery_id']}\n";
echo "📦 Order ID: {$delivery['order_id']}\n";
echo "📦 Order Number: {$delivery['order_number']}\n";
echo "📍 Coordenadas actuales: LAT={$delivery['destination_lat']}, LNG={$delivery['destination_lng']}\n\n";

// Obtener coordenadas desde user_addresses
$stmt = $conn->prepare("
    SELECT gps_latitude, gps_longitude, address, alias
    FROM user_addresses 
    WHERE user_id = ? AND is_default = 1
    LIMIT 1
");
$stmt->execute([$delivery['user_id']]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$address || !$address['gps_latitude'] || !$address['gps_longitude']) {
    echo "❌ No se encontraron coordenadas GPS en la dirección por defecto del usuario\n";
    exit(1);
}

echo "🏠 Dirección encontrada: {$address['alias']} - {$address['address']}\n";
echo "📍 Coordenadas GPS: LAT={$address['gps_latitude']}, LNG={$address['gps_longitude']}\n\n";

// Actualizar coordenadas de destino
$stmt = $conn->prepare("
    UPDATE order_deliveries 
    SET destination_lat = ?,
        destination_lng = ?,
        updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([
    floatval($address['gps_latitude']),
    floatval($address['gps_longitude']),
    $delivery['delivery_id']
]);

echo "✅ Coordenadas de destino actualizadas exitosamente!\n\n";

// Verificar actualización
$stmt = $conn->prepare("
    SELECT destination_lat, destination_lng 
    FROM order_deliveries 
    WHERE id = ?
");
$stmt->execute([$delivery['delivery_id']]);
$updated = $stmt->fetch(PDO::FETCH_ASSOC);

echo "📍 Nuevas coordenadas en BD: LAT={$updated['destination_lat']}, LNG={$updated['destination_lng']}\n";
echo "\n✅ Actualización completada. Ahora recarga la página de navegación.\n";
