<?php
require_once __DIR__ . '/conexion.php';

echo "=== USUARIOS DELIVERY ===\n\n";

$stmt = $conn->query("SELECT id, name, role FROM users WHERE role = 'delivery' LIMIT 10");
while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$u['id']} - Nombre: {$u['name']} - Rol: {$u['role']}\n";
}

echo "\n=== ÓRDENES DISPONIBLES ===\n\n";

$stmt = $conn->query("
    SELECT o.id, o.order_number, o.status, o.payment_status
    FROM orders o
    WHERE o.status = 'shipped'
    AND o.payment_status = 'paid'
    LIMIT 5
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($orders)) {
    echo "❌ No hay órdenes disponibles\n";
} else {
    foreach ($orders as $order) {
        echo "ID: {$order['id']} - {$order['order_number']} - {$order['status']}/{$order['payment_status']}\n";
    }
}
