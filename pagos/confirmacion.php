<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

$orderNumber = isset($_GET['order']) ? $_GET['order'] : (isset($_SESSION['order_success']) ? $_SESSION['order_success'] : null);
if (!$orderNumber) {
    header("Location: " . BASE_URL);
    exit();
}

// Obtener información del pedido
try {
    $orderStmt = $conn->prepare("
        SELECT o.*, pt.payment_method, pt.status as payment_status, 
               DATE_FORMAT(o.created_at, '%d/%m/%Y %H:%i') as order_date,
               u.name as user_name, u.email as user_email
        FROM orders o
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ?
        LIMIT 1
    ");
    $orderStmt->execute([$orderNumber]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Pedido no encontrado");
    }
    
    // Obtener items del pedido
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.slug as product_slug, pi.image_path as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . BASE_URL);
    exit();
}

// Limpiar sesión de éxito si existe
if (isset($_SESSION['order_success'])) {
    unset($_SESSION['order_success']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/confirmar-orden.css">
</head>
<body>
    <?php require_once __DIR__ . '/../layouts/header2.php'; ?>

    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <i class="fas fa-check-circle confirmation-success-icon"></i>
                <h1 class="confirmation-title">¡Pedido Confirmado!</h1>
                <p class="confirmation-message">Gracias por tu compra. Tu pedido ha sido recibido y está siendo procesado.</p>
                <div class="confirmation-order-number">
                    Número de pedido: <strong><?= htmlspecialchars($orderNumber) ?></strong>
                </div>
                <div class="confirmation-order-date">
                    Fecha: <?= $order['order_date'] ?>
                </div>
            </div>

            <div class="confirmation-details">
                <div class="confirmation-details-section">
                    <h3 class="confirmation-section-title">Resumen del Pedido</h3>
                    <div class="confirmation-items">
                        <?php foreach ($items as $item): ?>
                            <div class="confirmation-item">
                                <div class="confirmation-item-image">
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                </div>
                                <div class="confirmation-item-info">
                                    <h4 class="confirmation-item-title">
                                        <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $item['product_slug'] ?>">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </a>
                                    </h4>
                                    <?php if ($item['variant_name']): ?>
                                        <p class="confirmation-item-variant"><?= htmlspecialchars($item['variant_name']) ?></p>
                                    <?php endif; ?>
                                    <p class="confirmation-item-quantity">Cantidad: <?= $item['quantity'] ?></p>
                                </div>
                                <div class="confirmation-item-price">
                                    $<?= number_format($item['price'], 0, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="confirmation-details-section">
                    <h3 class="confirmation-section-title">Total del Pedido</h3>
                    <div class="confirmation-totals">
                        <div class="confirmation-total-row">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($order['subtotal'], 0, ',', '.') ?></span>
                        </div>
                        <div class="confirmation-total-row">
                            <span>Envío:</span>
                            <span>$<?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
                        </div>
                        <div class="confirmation-total-row confirmation-grand-total">
                            <span>Total:</span>
                            <span>$<?= number_format($order['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <div class="confirmation-details-section">
                    <h3 class="confirmation-section-title">Método de Pago</h3>
                    <div class="confirmation-payment-method">
                        <?php if ($order['payment_method'] === 'transferencia'): ?>
                            <i class="fas fa-university confirmation-payment-icon"></i>
                            <span class="confirmation-payment-name">Transferencia Bancaria</span>
                            <p class="confirmation-payment-description">Por favor envía el comprobante de pago a nuestro WhatsApp o correo electrónico.</p>
                        <?php elseif ($order['payment_method'] === 'contra_entrega'): ?>
                            <i class="fas fa-money-bill-wave confirmation-payment-icon"></i>
                            <span class="confirmation-payment-name">Pago Contra Entrega</span>
                            <p class="confirmation-payment-description">Pagarás cuando recibas tu pedido. Asegúrate de tener el dinero exacto.</p>
                            
                            <div class="confirmation-shipping-info">
                                <h4>Dirección de Entrega:</h4>
                                <p><?= htmlspecialchars($order['shipping_city']) ?></p>
                                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                                <?php if (!empty($order['delivery_notes'])): ?>
                                    <p><strong>Instrucciones:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="confirmation-details-section">
                    <h3 class="confirmation-section-title">Instrucciones Importantes</h3>
                    <div class="confirmation-instructions">
                        <?php if ($order['payment_method'] === 'transferencia'): ?>
                            <p class="confirmation-instruction-step"><strong>1.</strong> Realiza la transferencia a nuestra cuenta bancaria:</p>
                            <div class="confirmation-bank-info">
                                <p><strong>Banco:</strong> Bancolombia</p>
                                <p><strong>Tipo de Cuenta:</strong> Ahorros</p>
                                <p><strong>Número:</strong> 123-456789-00</p>
                                <p><strong>Titular:</strong> Angelow Ropa Infantil</p>
                                <p><strong>Valor:</strong> $<?= number_format($order['total'], 0, ',', '.') ?></p>
                            </div>
                            <p class="confirmation-instruction-step"><strong>2.</strong> Envía el comprobante de pago a nuestro WhatsApp <strong>+57 123 456 7890</strong> o al correo <strong>contacto@angelow.com</strong> con el número de tu pedido.</p>
                            <p class="confirmation-instruction-step"><strong>3.</strong> Una vez confirmemos el pago, procederemos a enviar tu pedido.</p>
                        <?php else: ?>
                            <p class="confirmation-instruction-step"><strong>1.</strong> Nos comunicaremos contigo para confirmar los detalles de entrega.</p>
                            <p class="confirmation-instruction-step"><strong>2.</strong> El pedido será enviado en un plazo de 1-3 días hábiles.</p>
                            <p class="confirmation-instruction-step"><strong>3.</strong> Paga en efectivo al momento de recibir tu pedido.</p>
                            <p class="confirmation-instruction-step"><strong>4.</strong> Ten el dinero exacto y una identificación a la mano.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="confirmation-actions">
                <a href="<?= BASE_URL ?>/users/orders.php" class="confirmation-action-btn confirmation-view-orders">
                    <i class="fas fa-shopping-bag"></i> Ver mis pedidos
                </a>
                   <a href="<?= BASE_URL ?>/pagos/generar_factura.php?order=<?= $orderNumber ?>" 
       class="confirmation-action-btn confirmation-download-invoice" target="_blank">
        <i class="fas fa-file-pdf"></i> Descargar Factura
    </a>
                <a href="<?= BASE_URL ?>/tienda/productos.php" class="confirmation-action-btn confirmation-continue-shopping">
                    <i class="fas fa-store"></i> Seguir comprando
                </a>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>