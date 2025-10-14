<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/header2.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../auth/role_redirect.php';

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
        SELECT o.*, ua.address as shipping_address, ua.neighborhood
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
               COALESCE(vi.image_path, pi.image_path, 'uploads/productos/default-product.jpg') as product_image
        FROM order_items oi
        LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_primary = 1
        LEFT JOIN variant_images vi ON vi.color_variant_id = oi.color_variant_id AND vi.is_primary = 1
        WHERE oi.order_id = :order_id
        ORDER BY oi.id ASC
    ");
    $stmtItems->execute([':order_id' => $orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Obtener información de delivery si existe
    $stmtDelivery = $conn->prepare("
        SELECT od.*, u.name as driver_name, u.phone as driver_phone
        FROM order_deliveries od
        LEFT JOIN users u ON u.id = od.driver_id
        WHERE od.order_id = :order_id
    ");
    $stmtDelivery->execute([':order_id' => $orderId]);
    $delivery = $stmtDelivery->fetch(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orders.css">
    <style>
        .order-detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .order-detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .order-detail-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
        }

        .order-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .meta-item i {
            font-size: 1.2rem;
            width: 20px;
        }

        .order-detail-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .order-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .order-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h3 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 1.1rem;
        }

        .item-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .item-meta span {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #666;
        }

        .item-price {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .item-subtotal {
            font-weight: bold;
            color: #333;
            font-size: 1.2rem;
        }

        .summary-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row.total {
            border-bottom: none;
            border-top: 2px solid #667eea;
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
            padding-top: 15px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .driver-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 15px;
        }

        .driver-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .driver-info {
            flex: 1;
        }

        .driver-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffc107;
            font-size: 0.9rem;
        }

        .history-timeline {
            position: relative;
            padding-left: 30px;
        }

        .history-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .history-item {
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .history-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #38a169;
            color: white;
        }

        .btn-success:hover {
            background: #2f855a;
            transform: translateY(-2px);
        }

        .progress-container {
            margin: 20px 0;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #666;
        }

        @media (max-width: 1024px) {
            .order-detail-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .order-detail-header h1 {
                font-size: 2rem;
            }

            .order-meta-grid {
                grid-template-columns: 1fr;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                justify-content: center;
            }

            .driver-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <main class="user-main-content">
            <div class="order-detail-container">
                <!-- Header con información principal -->
                <div class="order-detail-header animate__animated animate__fadeIn">
                    <h1>Pedido #<?= htmlspecialchars($order['order_number']) ?></h1>
                    <p class="order-date">Realizado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($order['created_at'])) ?></p>
                    
                    <div class="order-meta-grid">
                        <div class="meta-item">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <div>Estado del Pedido</div>
                                <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                    <i class="fas fa-<?= getStatusIcon($order['status']) ?>"></i>
                                    <?= getStatusText($order['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="meta-item">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <div>Estado del Pago</div>
                                <span class="status-badge status-<?= $order['payment_status'] === 'paid' ? 'delivered' : 'pending' ?>">
                                    <i class="fas fa-<?= $order['payment_status'] === 'paid' ? 'check-circle' : 'clock' ?>"></i>
                                    <?= getPaymentStatusText($order['payment_status'] ?: 'pending') ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($delivery): ?>
                        <div class="meta-item">
                            <i class="fas fa-truck"></i>
                            <div>
                                <div>Estado de Entrega</div>
                                <span class="status-badge status-<?= htmlspecialchars($delivery['delivery_status']) ?>">
                                    <i class="fas fa-<?= getDeliveryStatusIcon($delivery['delivery_status']) ?>"></i>
                                    <?= getDeliveryStatusText($delivery['delivery_status']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <div>Total del Pedido</div>
                                <strong style="font-size: 1.2rem;">$<?= number_format($total, 0) ?></strong>
                            </div>
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
                <div class="order-detail-content">
                    <!-- Columna izquierda -->
                    <div>
                        <!-- Sección de productos -->
                        <div class="order-section animate__animated animate__fadeInLeft">
                            <h2><i class="fas fa-box-open"></i> Productos del Pedido</h2>
                            <?php if (empty($items)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open fa-3x"></i>
                                    <p>No hay productos en esta orden.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="order-item">
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['product_image']) ?>"
                                             alt="Producto"
                                             onerror="this.src='<?= BASE_URL ?>/uploads/productos/default-product.jpg'">
                                        <div class="order-item-details">
                                            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                            <div class="item-meta">
                                                <span><i class="fas fa-hashtag"></i> Cantidad: <?= $item['quantity'] ?></span>
                                                <?php if (isset($item['color_variant_id']) && $item['color_variant_id']): ?>
                                                    <span><i class="fas fa-palette"></i> Variante Color: <?= $item['color_variant_id'] ?></span>
                                                <?php endif; ?>
                                                <?php if (isset($item['size_variant_id']) && $item['size_variant_id']): ?>
                                                    <span><i class="fas fa-ruler"></i> Variante Talla: <?= $item['size_variant_id'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="item-price">$<?= number_format($item['price'], 0) ?> c/u</p>
                                        </div>
                                        <div class="item-subtotal">
                                            $<?= number_format($item['quantity'] * $item['price'], 0) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                        <div class="order-section animate__animated animate__fadeInRight">
                            <h2><i class="fas fa-receipt"></i> Resumen del Pedido</h2>
                            <div class="summary-grid">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span>$<?= number_format($subtotal, 0) ?></span>
                                </div>
                                
                                <?php if ($discount_amount > 0): ?>
                                <div class="summary-row" style="color: #38a169;">
                                    <span>Descuento:</span>
                                    <span>-$<?= number_format($discount_amount, 0) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="summary-row">
                                    <span>Envío:</span>
                                    <span>$<?= number_format($shipping_cost, 0) ?></span>
                                </div>
                                
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <span>$<?= number_format($total, 0) ?></span>
                                </div>
                            </div>

                            <!-- Información de pago -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                <h3><i class="fas fa-credit-card"></i> Información de Pago</h3>
                                <p><strong>Método:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'No especificado') ?></p>
                                <p><strong>Estado:</strong> 
                                    <span class="status-badge status-<?= $order['payment_status'] === 'paid' ? 'delivered' : 'pending' ?>">
                                        <?= getPaymentStatusText($order['payment_status'] ?: 'pending') ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Información de envío -->
                        <div class="order-section animate__animated animate__fadeInRight">
                            <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
                            <div class="shipping-info">
                                <p><strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                                <?php if ($order['neighborhood']): ?>
                                    <p><strong>Barrio:</strong> <?= htmlspecialchars($order['neighborhood']) ?></p>
                                <?php endif; ?>
                                <?php if ($order['delivery_notes']): ?>
                                    <p><strong>Notas:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información de delivery -->
                        <?php if ($delivery): ?>
                        <div class="order-section animate__animated animate__fadeInRight">
                            <h2><i class="fas fa-truck"></i> Información de Entrega</h2>
                            
                            <?php if ($delivery['driver_name']): ?>
                                <div class="driver-card">
                                    <div class="driver-photo" style="background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        <?= substr($delivery['driver_name'], 0, 1) ?>
                                    </div>
                                    
                                    <div class="driver-info">
                                        <h4><?= htmlspecialchars($delivery['driver_name']) ?></h4>
                                        <?php if ($delivery['driver_phone']): ?>
                                            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($delivery['driver_phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="delivery-timeline" style="margin-top: 20px;">
                                <?php if ($delivery['assigned_at']): ?>
                                    <p><strong>Asignado:</strong> <?= date('d/m/Y H:i', strtotime($delivery['assigned_at'])) ?></p>
                                <?php endif; ?>
                                <?php if ($delivery['started_at']): ?>
                                    <p><strong>Iniciado:</strong> <?= date('d/m/Y H:i', strtotime($delivery['started_at'])) ?></p>
                                <?php endif; ?>
                                <?php if ($delivery['delivered_at']): ?>
                                    <p><strong>Entregado:</strong> <?= date('d/m/Y H:i', strtotime($delivery['delivered_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Botones de acción -->
                        <div class="order-section animate__animated animate__fadeInRight">
                            <h2><i class="fas fa-cog"></i> Acciones</h2>
                            <div class="action-buttons">
                                <a href="orders.php" class="btn btn-secondary">
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

                                <?php if ($order['status'] === 'shipped' || ($delivery && in_array($delivery['delivery_status'], ['in_transit', 'out_for_delivery']))): ?>
                                    <a href="track_order.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-map-marked-alt"></i> Rastrear Envío
                                    </a>
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
function getStatusText($status) {
    $statuses = [
        'pending' => 'Pendiente',
        'processing' => 'En Proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'on_hold' => 'En Espera'
    ];
    return $statuses[$status] ?? ucfirst($status);
}

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

function getDeliveryStatusText($status) {
    $statuses = [
        'pending' => 'Pendiente',
        'driver_assigned' => 'Conductor Asignado',
        'driver_accepted' => 'Aceptado por Conductor',
        'in_transit' => 'En Tránsito',
        'out_for_delivery' => 'En Reparto',
        'arrived' => 'Ha Llegado',
        'delivered' => 'Entregado',
        'failed_delivery' => 'Entrega Fallida',
        'cancelled' => 'Cancelado'
    ];
    return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function getDeliveryStatusIcon($status) {
    $icons = [
        'pending' => 'clock',
        'driver_assigned' => 'user-check',
        'driver_accepted' => 'check-circle',
        'in_transit' => 'truck',
        'out_for_delivery' => 'shipping-fast',
        'arrived' => 'map-marker-alt',
        'delivered' => 'check-circle',
        'failed_delivery' => 'exclamation-triangle',
        'cancelled' => 'times-circle'
    ];
    return $icons[$status] ?? 'info-circle';
}

function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'cancelled' => 'Cancelado',
        'partially_refunded' => 'Parcialmente Reembolsado'
    ];
    return $statuses[$status] ?? ucfirst($status);
}
?>