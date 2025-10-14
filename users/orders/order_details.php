<?php
// Este archivo se usa como include o via AJAX
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado - Sesión no válida']));
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) {
    die(json_encode(['success' => false, 'message' => 'ID inválido']));
}

try {
    $stmt = $conn->prepare("
        SELECT o.*
        FROM orders o
        WHERE o.id = :id AND o.user_id = :user_id
    ");
    $stmt->execute([':id' => $orderId, ':user_id' => $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die(json_encode(['success' => false, 'message' => 'Orden no encontrada']));
    }

    // Items
    $stmtItems = $conn->prepare("
        SELECT oi.*
        FROM order_items oi
        WHERE oi.order_id = :order_id
    ");
    $stmtItems->execute([':order_id' => $orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Output HTML para modal
    ob_start();
    ?>
    <h2>Detalles de Orden #<?= $order['id'] ?></h2>
    <div class="order-summary">
        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        <p><strong>Estado:</strong> <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></p>
        <p><strong>Dirección de envío:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
        <p><strong>Total:</strong> $<?= number_format($order['total'], 0) ?></p>
    </div>
    <h3>Productos</h3>
    <div class="order-items-grid">
        <?php foreach ($items as $item): ?>
            <div class="order-item-card glass-effect">
                <img src="<?= BASE_URL ?>/images/productos/<?= htmlspecialchars($item['product_image'] ?? 'default-product.jpg') ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Producto') ?>">
                <div class="item-info">
                    <h4><?= htmlspecialchars($item['product_name'] ?? 'Producto') ?></h4>
                    <p>Cantidad: <?= $item['quantity'] ?></p>
                    <p>Precio: $<?= number_format($item['price'], 0) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
} catch (PDOException $e) {
    error_log("Error en order_details: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode(['success' => false, 'message' => 'Error al cargar detalles: ' . $e->getMessage()]);
}