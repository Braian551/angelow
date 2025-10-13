<?php
require_once 'conexion.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     ANÁLISIS DE REDUNDANCIA: ORDERS vs USER_ADDRESSES        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// 1. Estructura actual de ORDERS
echo "1️⃣  ESTRUCTURA ACTUAL DE ORDERS (campos de dirección):\n";
echo str_repeat("─", 80) . "\n";
$result = $conn->query('DESCRIBE orders');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    if(stripos($row['Field'], 'address') !== false || 
       stripos($row['Field'], 'shipping') !== false || 
       stripos($row['Field'], 'billing') !== false) {
        printf("   %-25s | %-30s | NULL: %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null']
        );
    }
}

// 2. Estructura de USER_ADDRESSES
echo "\n2️⃣  ESTRUCTURA DE USER_ADDRESSES:\n";
echo str_repeat("─", 80) . "\n";
$result = $conn->query('DESCRIBE user_addresses');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    printf("   %-25s | %-30s\n", $row['Field'], $row['Type']);
}

// 3. Análisis de datos actuales
echo "\n3️⃣  ANÁLISIS DE DATOS ACTUALES:\n";
echo str_repeat("─", 80) . "\n";

$result = $conn->query("
    SELECT COUNT(*) as total_orders,
           SUM(CASE WHEN shipping_address IS NOT NULL THEN 1 ELSE 0 END) as with_shipping,
           SUM(CASE WHEN billing_address IS NOT NULL THEN 1 ELSE 0 END) as with_billing
    FROM orders
");
$stats = $result->fetch(PDO::FETCH_ASSOC);
echo "   Total de órdenes: {$stats['total_orders']}\n";
echo "   Con shipping_address: {$stats['with_shipping']}\n";
echo "   Con billing_address: {$stats['with_billing']}\n";

// 4. Verificar si existe relación
echo "\n4️⃣  ¿EXISTE CAMPO DE RELACIÓN?\n";
echo str_repeat("─", 80) . "\n";
$result = $conn->query("SHOW COLUMNS FROM orders LIKE '%address_id%'");
if($result->rowCount() > 0) {
    echo "   ✅ SÍ existe campo address_id en orders\n";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} else {
    echo "   ❌ NO existe campo address_id en orders\n";
    echo "   📝 Hay REDUNDANCIA: Los datos de dirección están duplicados\n";
}

// 5. Ejemplo de redundancia
echo "\n5️⃣  EJEMPLO DE REDUNDANCIA (Orden 27):\n";
echo str_repeat("─", 80) . "\n";
$result = $conn->query("
    SELECT 
        o.id,
        o.order_number,
        o.user_id,
        o.shipping_address,
        o.shipping_city,
        ua.id as address_id,
        ua.address as ua_address,
        ua.neighborhood as ua_neighborhood,
        ua.gps_latitude,
        ua.gps_longitude
    FROM orders o
    LEFT JOIN user_addresses ua ON o.user_id = ua.user_id AND ua.is_default = 1
    WHERE o.id = 27
");
$order = $result->fetch(PDO::FETCH_ASSOC);
if($order) {
    echo "   📦 DATOS EN ORDERS:\n";
    echo "      - shipping_address: {$order['shipping_address']}\n";
    echo "      - shipping_city: {$order['shipping_city']}\n";
    echo "      - GPS: NO (no tiene campos)\n\n";
    
    echo "   🏠 DATOS EN USER_ADDRESSES:\n";
    echo "      - address_id: {$order['address_id']}\n";
    echo "      - address: {$order['ua_address']}\n";
    echo "      - neighborhood: {$order['ua_neighborhood']}\n";
    echo "      - GPS: LAT={$order['gps_latitude']}, LNG={$order['gps_longitude']}\n";
}

// 6. Propuesta de solución
echo "\n6️⃣  PROBLEMAS IDENTIFICADOS:\n";
echo str_repeat("─", 80) . "\n";
echo "   ❌ orders.shipping_address (TEXT) duplica info de user_addresses\n";
echo "   ❌ orders.shipping_city (VARCHAR) duplica info de user_addresses\n";
echo "   ❌ orders.billing_address (TEXT) sin relación clara\n";
echo "   ❌ NO hay relación FK entre orders y user_addresses\n";
echo "   ❌ Cambios en user_addresses NO se reflejan en orders antiguas\n";
echo "   ❌ GPS solo en user_addresses, no accesible desde orders\n";

echo "\n7️⃣  SOLUCIÓN PROPUESTA:\n";
echo str_repeat("─", 80) . "\n";
echo "   ✅ Agregar: orders.shipping_address_id (FK → user_addresses.id)\n";
echo "   ✅ Agregar: orders.billing_address_id (FK → user_addresses.id)\n";
echo "   ✅ MANTENER: orders.shipping_address (snapshot histórico)\n";
echo "   ✅ MANTENER: orders.shipping_city (snapshot histórico)\n";
echo "   ✅ Migración: Relacionar órdenes existentes con sus direcciones\n";
echo "\n   💡 VENTAJAS:\n";
echo "      - Dirección original preservada (histórico)\n";
echo "      - Acceso a GPS mediante FK\n";
echo "      - Datos actualizables sin perder historial\n";
echo "      - Coherencia entre admin/orders.php y user/addresses\n";

echo "\n" . str_repeat("═", 80) . "\n";
