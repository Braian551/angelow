<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../tienda/pagos/helpers/shipping_helpers.php';

// Verificar rol de usuario
requireRole(['user', 'customer']);

// Obtener ID de la orden desde la URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    $_SESSION['error_message'] = 'ID de orden no válido';
    header('Location: orders.php');
    exit;
}

try {
    // Obtener información completa de la orden
    $stmt = $conn->prepare(" 
        SELECT o.*, ua.address as shipping_address, ua.neighborhood,
               ua.delivery_instructions as address_delivery_instructions
        FROM orders o
        LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
        WHERE o.id = :id AND o.user_id = :user_id
    ");
    $stmt->execute([':id' => $orderId, ':user_id' => $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error_message'] = 'Orden no encontrada';
        header('Location: orders.php');
        exit;
    }

    // Obtener items de la orden
    $stmtItems = $conn->prepare(" 
        SELECT oi.*, 
               COALESCE(vi.image_path, pi.image_path, 'uploads/productos/default-product.jpg') as product_image,
               pcv.id AS color_variant_id_join,
               c.name AS color_name,
               c.hex_code AS color_hex,
               psv.id AS size_variant_id_join,
               s.name AS size_name
        FROM order_items oi
        LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_primary = 1
        LEFT JOIN variant_images vi ON vi.color_variant_id = oi.color_variant_id AND vi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        LEFT JOIN product_size_variants psv ON oi.size_variant_id = psv.id
        LEFT JOIN sizes s ON psv.size_id = s.id
        WHERE oi.order_id = :order_id
        ORDER BY oi.id ASC
    ");
    $stmtItems->execute([':order_id' => $orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Ya no se manejan repartidores asociados desde el frontend
    $delivery = null;

    // Determinar si es recogida en tienda
    $isStorePickup = isStorePickupMethod($order);
    $storePickupAddress = $isStorePickup ? getStorePickupAddress() : null;

    // Inicializar variables para evitar errores
    $history = [];
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['quantity'] * $item['price'];
    }

    $shipping_cost = $order['shipping_cost'] ?? 5000;
    $discount_amount = 0; // Simplificado, sin descuentos complejos por ahora
    $total = $subtotal + $shipping_cost - $discount_amount;

} catch (PDOException $e) {
    error_log("Error al obtener detalles de orden: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al cargar los detalles de la orden';
    header('Location: orders.php');
    exit;
}

// Función para calcular descuento - simplificada
function calculateDiscountAmount($order, $subtotal) {
    // Por ahora, solo usar el descuento almacenado en la orden
    return $order['discount_amount'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Pedido #<?= htmlspecialchars($order['order_number']) ?> - Angelow</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/user/orders/order_detail.css">
</head>
<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <main class="user-main-content">
            <div class="order-details-container">
                <!-- Header con información principal -->
                <div class="order-details-header animate__animated animate__fadeIn">
                    <div class="order-details-title">
                        <h1>Pedido #<?= htmlspecialchars($order['order_number']) ?></h1>
                        <div class="order-details-meta">
                            <p class="order-date"><i class="fas fa-calendar-alt"></i> Realizado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="order-status-overview">
                        <div class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                            <i class="fas fa-<?= getStatusIcon($order['status']) ?>"></i>
                            <?= getStatusText($order['status']) ?>
                        </div>
                        <div class="status-badge status-<?= $order['payment_status'] === 'paid' ? 'delivered' : ($order['status'] === 'cancelled' ? 'cancelled' : 'pending') ?>">
                            <i class="fas fa-check-circle"></i>
                            <?php if ($order['status'] === 'cancelled'): ?>
                                <?= getRefundStatusText($order['payment_status'] ?? 'pending') ?>
                            <?php else: ?>
                                <?= getPaymentStatusText($order['payment_status'] ?: 'pending') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error animate__animated animate__fadeIn">
                        <?= $_SESSION['error_message'] ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success animate__animated animate__fadeIn">
                        <?= $_SESSION['success_message'] ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Contenido principal -->
                <div class="order-details-grid">
                    <!-- Columna izquierda -->
                    <div>
                        <!-- Sección de productos -->
                        <div class="order-items-section">
                            <div class="panel-header">
                                <i class="fas fa-box-open"></i>
                                <h2>Productos del Pedido</h2>
                            </div>
                            <?php if (empty($items)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open fa-3x"></i>
                                    <p>No hay productos en esta orden.</p>
                                </div>
                            <?php else: ?>
                                <div class="order-items-grid">
                                    <?php foreach ($items as $item): ?>
                                        <div class="order-item-card">
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['product_image']) ?>"
                                                 alt="Producto"
                                                 onerror="this.src='<?= BASE_URL ?>/uploads/productos/default-product.jpg'">
                                            <div class="item-info">
                                                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                                <div class="item-meta">
                                                    <span><i class="fas fa-hashtag"></i> Cantidad: <?= $item['quantity'] ?></span>

                                                    <?php if (!empty($item['variant_name'])): // variant_name stored at checkout ?>
                                                        <span class="variant-summary"><i class="fas fa-tags"></i> <?= htmlspecialchars($item['variant_name']) ?></span>
                                                    <?php else: ?>
                                                        <?php if (!empty($item['color_name'])): ?>
                                                            <span class="variant-color"><i class="fas fa-palette"></i>
                                                                <span class="color-swatch" style="background-color: <?= htmlspecialchars($item['color_hex'] ?: '#cccccc') ?>"></span>
                                                                <?= htmlspecialchars($item['color_name']) ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($item['size_name'])): ?>
                                                            <span class="variant-size"><i class="fas fa-ruler"></i> <?= htmlspecialchars($item['size_name']) ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="item-price-unit">$<?= number_format($item['price'], 0) ?> c/u</p>
                                            </div>
                                            <div class="item-total-price">
                                                $<?= number_format($item['quantity'] * $item['price'], 0) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                        <!-- Historial de estados - temporalmente deshabilitado -->
                        <!-- <div class="order-section animate__animated animate__fadeInLeft">
                            <h2><i class="fas fa-history"></i> Historial del Pedido</h2>
                            <p>Historial no disponible en este momento.</p>
                        </div> -->
                    </div>

                    <!-- Columna derecha -->
                    <div>
                        <!-- Resumen de la orden -->
                        <div class="order-summary">
                            <div class="panel-header">
                                <i class="fas fa-receipt"></i>
                                <h2>Resumen del Pedido</h2>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Subtotal:</span>
                                <span class="summary-value">$<?= number_format($subtotal, 0) ?></span>
                            </div>
                            
                            <?php if ($discount_amount > 0): ?>
                            <div class="summary-row" style="color: #38a169;">
                                <span class="summary-label">Descuento:</span>
                                <span class="summary-value">-$<?= number_format($discount_amount, 0) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-row">
                                <span class="summary-label">Envío:</span>
                                <span class="summary-value">$<?= number_format($shipping_cost, 0) ?></span>
                            </div>
                            
                            <div class="summary-row summary-total">
                                <span class="summary-label">Total:</span>
                                <span class="summary-value">$<?= number_format($total, 0) ?></span>
                            </div>

                            <!-- Información de pago -->
                            <div class="order-payment-info">
                                <div class="payment-header">
                                    <h3><i class="fas fa-credit-card"></i> Información de Pago</h3>
                                </div>
                                <div class="payment-content">
                                    <div class="payment-row">
                                        <div class="payment-label">Método:</div>
                                        <div class="payment-value"><span class="payment-method-text"><?= translatePaymentMethod($order['payment_method'] ?? null) ?></span></div>
                                    </div>
                                    <div class="payment-row">
                                        <div class="payment-label">Estado:</div>
                                        <div class="payment-value">
                                            <span class="status-badge payment-badge status-<?= $order['payment_status'] === 'paid' ? 'delivered' : ($order['status'] === 'cancelled' ? 'cancelled' : 'pending') ?> payment-status payment-<?= $order['payment_status'] ?: 'pending' ?>">
                                                <i class="fas fa-<?= $order['payment_status'] === 'paid' ? 'check-circle' : 'clock' ?>"></i>
                                                <?php if ($order['status'] === 'cancelled'): ?>
                                                    <?= getRefundStatusText($order['payment_status'] ?? 'pending') ?>
                                                <?php else: ?>
                                                    <?= getPaymentStatusText($order['payment_status'] ?: 'pending') ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de envío -->
                        <div class="shipping-info">
                            <div class="panel-header">
                                <i class="fas fa-map-marker-alt"></i>
                                <h2><?= $isStorePickup ? 'Punto de Recogida' : 'Dirección de Envío' ?></h2>
                            </div>
                            <div class="delivery-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>
                                    <strong>Dirección:</strong> <?= htmlspecialchars($isStorePickup ? $storePickupAddress : $order['shipping_address']) ?><br>
                                    <?php if ($order['neighborhood']): ?>
                                        <strong>Barrio:</strong> <?= htmlspecialchars($order['neighborhood']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($order['address_delivery_instructions'])): ?>
                                        <strong>Instrucciones de envío:</strong> <?= htmlspecialchars($order['address_delivery_instructions']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($order['notes'])): ?>
                                        <strong>Notas:</strong> <?= htmlspecialchars($order['notes']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>


                        <!-- Botones de acción -->
                        <div class="order-actions">
                            <a href="orders.php" class="back-button">
                                <i class="fas fa-arrow-left"></i> Volver a Pedidos
                            </a>

                            <?php if ($order['status'] === 'delivered'): ?>
                                <button class="btn btn-success" onclick="reorderItems(<?= $order['id'] ?>)">
                                    <i class="fas fa-redo"></i> Volver a Pedir
                                </button>
                                <button class="btn btn-primary" onclick="downloadInvoice(<?= $order['id'] ?>)">
                                    <i class="fas fa-download"></i> Descargar Factura
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                <button class="btn btn-danger" onclick="cancelOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-times"></i> Cancelar Pedido
                                </button>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'delivered'): ?>
                                <button class="btn btn-primary" onclick="rateOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-star"></i> Calificar Pedido
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';

        // Función para volver a pedir
        function reorderItems(orderId) {
            if (confirm('¿Estás seguro de que quieres volver a pedir estos productos? Se agregarán a tu carrito.')) {
                showLoading('Agregando productos al carrito...');
                
                fetch(`${BASE_URL}/users/ajax/ajax_reorder.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Productos agregados al carrito exitosamente', 'success');
                        if (data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1500);
                        }
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error al procesar la solicitud', 'error');
                });
            }
        }

        // Función para cancelar orden
        function cancelOrder(orderId) {
            const reason = prompt('Por favor, indica el motivo de la cancelación:');
            if (reason === null) return;
            
            if (reason.trim() === '') {
                alert('Debes proporcionar un motivo para cancelar el pedido.');
                return;
            }

            if (confirm('¿Estás seguro de que quieres cancelar este pedido? Esta acción no se puede deshacer.')) {
                showLoading('Cancelando pedido...');
                
                fetch(`${BASE_URL}/users/ajax/ajax_cancel_order.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${orderId}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Error al cancelar el pedido', 'error');
                });
            }
        }

        // Función para descargar factura
        function downloadInvoice(orderId) {
            window.open(`${BASE_URL}/users/orders/invoice.php?id=${orderId}`, '_blank');
        }

        // Función para calificar pedido
        function rateOrder(orderId) {
            const rating = prompt('Califica tu pedido del 1 al 5 estrellas:');
            if (rating === null) return;
            
            const ratingNum = parseInt(rating);
            if (isNaN(ratingNum) || ratingNum < 1 || ratingNum > 5) {
                alert('Por favor ingresa un número válido entre 1 y 5.');
                return;
            }

            const comment = prompt('Comentario adicional (opcional):');
            
            showLoading('Enviando calificación...');
            
            fetch(`${BASE_URL}/users/ajax/ajax_rate_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&rating=${ratingNum}&comment=${encodeURIComponent(comment || '')}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('¡Gracias por tu calificación!', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al enviar la calificación', 'error');
            });
        }

        // Utilidades de UI
        function showLoading(message = 'Cargando...') {
            // Implementar overlay de carga
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.querySelector('.loading-overlay');
            if (overlay) overlay.remove();
        }

        function showNotification(message, type = 'info') {
            // Implementar notificación toast
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.classList.add('notification-show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.add('notification-hide');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>

<?php
// Funciones auxiliares mejoradas

function getStatusIcon($status) {
    $icons = [
        'pending' => 'clock',
        'processing' => 'cog',
        'shipped' => 'shipping-fast',
        'delivered' => 'check-circle',
        'cancelled' => 'times-circle',
        'refunded' => 'undo',
        'on_hold' => 'pause-circle'
    ];
    return $icons[$status] ?? 'info-circle';
}


// Preferimos usar funciones centralizadas en `layouts/functions.php` para traducciones
?>