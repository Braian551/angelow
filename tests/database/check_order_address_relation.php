<?php
require_once 'conexion.php';

echo "=== VERIFICANDO RELACION ORDERS - USER_ADDRESSES ===\n\n";

// Verificar si existe relación directa
$result = $conn->query("DESCRIBE orders");
$hasAddressId = false;
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    if (stripos($row['Field'], 'address') !== false) {
        echo "Campo en orders: " . $row['Field'] . " (" . $row['Type'] . ")\n";
        if ($row['Field'] == 'address_id' || $row['Field'] == 'user_address_id') {
            $hasAddressId = true;
        }
    }
}

if (!$hasAddressId) {
    echo "\n⚠️  La tabla orders NO tiene campo address_id o user_address_id\n";
    echo "Las ordenes solo tienen shipping_address (texto) sin coordenadas GPS\n\n";
}

echo "\n=== DATOS ORDEN 27 ===\n";
$result = $conn->query("
    SELECT 
        o.id, 
        o.order_number, 
        o.user_id, 
        o.shipping_address,
        o.shipping_city,
        ua.id as address_id,
        ua.gps_latitude, 
        ua.gps_longitude,
        ua.address as ua_address
    FROM orders o 
    LEFT JOIN user_addresses ua ON o.user_id = ua.user_id AND ua.is_default = 1 
    WHERE o.id = 27 
    LIMIT 1
");
$data = $result->fetch(PDO::FETCH_ASSOC);
if ($data) {
    print_r($data);
} else {
    echo "No encontrado\n";
}

echo "\n=== TODAS LAS DIRECCIONES DEL USUARIO ===\n";
if ($data) {
    $result = $conn->query("
        SELECT id, alias, address, gps_latitude, gps_longitude, is_default 
        FROM user_addresses 
        WHERE user_id = '{$data['user_id']}'
    ");
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "---\n";
    }
}
