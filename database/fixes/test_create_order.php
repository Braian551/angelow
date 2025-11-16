<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = '6861e06ddcf49';
$user_id = $_SESSION['user_id'];

try {
    $conn->beginTransaction();

    // Obtener carrito activo
    $stmt = $conn->prepare("SELECT c.id FROM carts c WHERE c.user_id = :user_id ORDER BY c.created_at DESC LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception('Carrito no encontrado para el usuario test');
    }

    $cart_id = $cart['id'];

    // Obtener items
    $stmt = $conn->prepare("SELECT ci.quantity, p.id as product_id, p.name as product_name, p.price as product_price, psv.price as variant_price, (COALESCE(psv.price, p.price) * ci.quantity) as item_total, ci.color_variant_id, ci.size_variant_id
        FROM cart_items ci JOIN products p ON ci.product_id = p.id LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id WHERE ci.cart_id = :cart_id");
    $stmt->execute([':cart_id' => $cart_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) throw new Exception('No hay items en el carrito para la prueba');

    $subtotal = 0;
    foreach ($items as $it) $subtotal += $it['item_total'];
    $shipping_cost = 10000; // sample
    $total = $subtotal + $shipping_cost;

    $order_number = 'TEST' . strtoupper(substr(uniqid(), -6));
    $shipping_address_id = null; // for test
    $shipping_address = 'Dirección de prueba';

    // Insertar la orden
    $stmt = $conn->prepare("INSERT INTO orders (order_number,user_id,status,subtotal,shipping_cost,total,payment_method,payment_status,shipping_address_id,shipping_address,shipping_city) VALUES (?, ?, 'pending', ?, ?, ?, 'transfer', 'pending', ?, ?, ?)");
    $stmt->execute([$order_number, $user_id, $subtotal, $shipping_cost, $total, $shipping_address_id, $shipping_address, 'Medellín']);

    $order_id = $conn->lastInsertId();

    echo "Orden creada: ID=$order_id número=$order_number\n";

    // Insertar items
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, color_variant_id, size_variant_id, product_name, variant_name, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $variant_name = '';
        $stmtItem->execute([$order_id, $it['product_id'], $it['color_variant_id'], $it['size_variant_id'], $it['product_name'], $variant_name, $it['variant_price'] ?? $it['product_price'], $it['quantity'], $it['item_total']]);
    }

    // Insertar transacción de pago
    $stmtT = $conn->prepare("INSERT INTO payment_transactions (order_id, user_id, amount, reference_number, payment_proof, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmtT->execute([$order_id, $user_id, $total, 'TESTREF', null]);

    $conn->commit();
    echo "Prueba de creación de orden completa.\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Error en prueba: " . $e->getMessage() . "\n";
}
