<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No tienes permisos para acceder a esta área'];
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al verificar permisos. Por favor intenta nuevamente.'];
    header("Refresh:0");
    exit();
}

// Obtener ID de la orden
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Orden no especificada'];
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit();
}

$orderId = intval($_GET['id']);

// Obtener información de la orden
try {
    // Consulta para obtener los detalles principales de la orden
    $orderQuery = "SELECT 
        o.*, 
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        u.identification_number,
        u.identification_type
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?";
    
    $stmt = $conn->prepare($orderQuery);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'La orden no existe'];
        header("Location: " . BASE_URL . "/admin/orders.php");
        exit();
    }
    
    // Consulta para obtener los items de la orden
    $itemsQuery = "SELECT 
        oi.*,
        p.slug AS product_slug,
        pi.image_path AS product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener las transacciones de pago
    $transactionsQuery = "SELECT * FROM payment_transactions WHERE order_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($transactionsQuery);
    $stmt->execute([$orderId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener el historial de cambios
    $historyQuery = "SELECT 
        osh.*,
        u.name as changed_by_full_name,
        u.role as changed_by_role,
        u.email as changed_by_email
    FROM order_status_history osh
    LEFT JOIN users u ON osh.changed_by = u.id
    WHERE osh.order_id = ?
    ORDER BY osh.created_at DESC, osh.id DESC";
    $stmt = $conn->prepare($historyQuery);
    $stmt->execute([$orderId]);
    $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener detalles de la orden: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar los detalles de la orden: ' . $e->getMessage()];
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit();
}

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}

// Funciones de ayuda para formatear datos
function formatDate($dateString) {
    if (!$dateString) return 'N/A';
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}

function formatCurrency($amount) {
    if ($amount === null) return 'N/A';
    return '$' . number_format($amount, 0, ',', '.');
}

function getStatusBadge($status) {
    $statuses = [
        'pending' => ['label' => 'Pendiente', 'class' => 'badge-warning'],
        'processing' => ['label' => 'En proceso', 'class' => 'badge-info'],
        'shipped' => ['label' => 'Enviado', 'class' => 'badge-primary'],
        'delivered' => ['label' => 'Entregado', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        'refunded' => ['label' => 'Reembolsado', 'class' => 'badge-secondary']
    ];
    
    $statusInfo = $statuses[$status] ?? ['label' => $status, 'class' => 'badge-light'];
    return '<span class="badge ' . $statusInfo['class'] . '">' . $statusInfo['label'] . '</span>';
}

function getPaymentStatusBadge($status) {
    $statuses = [
        'pending' => ['label' => 'Pendiente', 'class' => 'badge-warning'],
        'paid' => ['label' => 'Pagado', 'class' => 'badge-success'],
        'failed' => ['label' => 'Fallido', 'class' => 'badge-danger'],
        'refunded' => ['label' => 'Reembolsado', 'class' => 'badge-secondary']
    ];
    
    $statusInfo = $statuses[$status] ?? ['label' => $status, 'class' => 'badge-light'];
    return '<span class="badge ' . $statusInfo['class'] . '">' . $statusInfo['label'] . '</span>';
}

function getIdentificationType($type) {
    $types = [
        'cc' => 'Cédula de Ciudadanía',
        'ce' => 'Cédula de Extranjería',
        'ti' => 'Tarjeta de Identidad',
        'pasaporte' => 'Pasaporte'
    ];
    return $types[$type] ?? $type;
}

function getChangeTypeIcon($changeType) {
    $icons = [
        'status' => 'fas fa-info-circle',
        'payment_status' => 'fas fa-credit-card',
        'shipping' => 'fas fa-truck',
        'address' => 'fas fa-map-marker-alt',
        'notes' => 'fas fa-sticky-note',
        'created' => 'fas fa-plus-circle',
        'cancelled' => 'fas fa-ban',
        'refunded' => 'fas fa-undo',
        'items' => 'fas fa-shopping-cart',
        'other' => 'fas fa-edit'
    ];
    return $icons[$changeType] ?? 'fas fa-edit';
}

function getChangeTypeColor($changeType) {
    $colors = [
        'status' => '#0077b6',
        'payment_status' => '#10b981',
        'shipping' => '#f59e0b',
        'address' => '#8b5cf6',
        'notes' => '#6366f1',
        'created' => '#22c55e',
        'cancelled' => '#ef4444',
        'refunded' => '#f97316',
        'items' => '#ec4899',
        'other' => '#64748b'
    ];
    return $colors[$changeType] ?? '#64748b';
}

function getRoleBadge($role) {
    $roles = [
        'admin' => ['label' => 'Administrador', 'class' => 'badge-admin'],
        'customer' => ['label' => 'Cliente', 'class' => 'badge-customer'],
        'delivery' => ['label' => 'Repartidor', 'class' => 'badge-delivery'],
        'system' => ['label' => 'Sistema', 'class' => 'badge-system']
    ];
    
    $roleInfo = $roles[$role] ?? ['label' => ucfirst($role), 'class' => 'badge-light'];
    return '<span class="role-badge ' . $roleInfo['class'] . '">' . $roleInfo['label'] . '</span>';
}

function translateValue($value, $field = '') {
    // Traducir estados de orden
    $orderStatuses = [
        'pending' => 'Pendiente',
        'processing' => 'En Proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado'
    ];
    
    // Traducir estados de pago
    $paymentStatuses = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'partial' => 'Pago Parcial'
    ];
    
    // Traducir métodos de pago
    $paymentMethods = [
        'bank_transfer' => 'Transferencia Bancaria',
        'cash' => 'Efectivo',
        'credit_card' => 'Tarjeta de Crédito',
        'debit_card' => 'Tarjeta de Débito',
        'paypal' => 'PayPal',
        'mercadopago' => 'Mercado Pago',
        'other' => 'Otro'
    ];
    
    // Traducir tipos de cambio
    $changeTypes = [
        'status' => 'Estado de Orden',
        'payment_status' => 'Estado de Pago',
        'shipping' => 'Envío',
        'address' => 'Dirección',
        'notes' => 'Notas',
        'created' => 'Creación',
        'cancelled' => 'Cancelación',
        'refunded' => 'Reembolso',
        'items' => 'Productos',
        'other' => 'Otro'
    ];
    
    // Si no hay valor, retornar N/A
    if (empty($value) || $value === null) {
        return 'N/A';
    }
    
    // Convertir a minúsculas para comparación
    $valueLower = strtolower(trim($value));
    
    // Intentar traducir según el campo
    if ($field === 'status' || $field === 'order_status') {
        return $orderStatuses[$valueLower] ?? ucfirst($value);
    } elseif ($field === 'payment_status') {
        return $paymentStatuses[$valueLower] ?? ucfirst($value);
    } elseif ($field === 'payment_method') {
        return $paymentMethods[$valueLower] ?? ucfirst(str_replace('_', ' ', $value));
    } elseif ($field === 'change_type') {
        return $changeTypes[$valueLower] ?? ucfirst($value);
    }
    
    // Intentar todas las traducciones si no se especificó campo
    if (isset($orderStatuses[$valueLower])) {
        return $orderStatuses[$valueLower];
    } elseif (isset($paymentStatuses[$valueLower])) {
        return $paymentStatuses[$valueLower];
    } elseif (isset($paymentMethods[$valueLower])) {
        return $paymentMethods[$valueLower];
    } elseif (isset($changeTypes[$valueLower])) {
        return $changeTypes[$valueLower];
    }
    
    // Si no se encontró traducción, retornar el valor capitalizado
    return ucfirst(str_replace('_', ' ', $value));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Orden #<?= $order['order_number'] ?> - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/detail.css">
  
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-shopping-bag"></i> Detalle de Orden #<?= $order['order_number'] ?>
                        <div class="order-status-badge">
                            <?= getStatusBadge($order['status']) ?>
                        </div>
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                        <a href="<?= BASE_URL ?>/admin/orders.php">Órdenes</a> / 
                        <span>Detalle</span>
                    </div>
                </div>

                <div class="order-detail-container">
                    <!-- Resumen de la orden -->
                    <div class="order-summary-card">
                        <div class="card-header">
                            <h3>Resumen de la Orden</h3>
                            <div class="order-actions">
                                <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                                <a href="<?= BASE_URL ?>/admin/order/edit.php?id=<?= $orderId ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button onclick="changeOrderStatus(<?= $orderId ?>)" class="btn btn-info">
                                    <i class="fas fa-sync-alt"></i> Cambiar Estado
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="order-info-grid">
                                <div class="info-group">
                                    <label>Número de Orden:</label>
                                    <span><?= $order['order_number'] ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Fecha:</label>
                                    <span><?= formatDate($order['created_at']) ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Cliente:</label>
                                    <span><?= $order['user_name'] ?: 'Cliente no registrado' ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Email:</label>
                                    <span><?= $order['user_email'] ?: 'N/A' ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Teléfono:</label>
                                    <span><?= $order['user_phone'] ?: 'N/A' ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Documento:</label>
                                    <span><?= getIdentificationType($order['identification_type']) ?>: <?= $order['identification_number'] ?: 'N/A' ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Estado:</label>
                                    <span><?= getStatusBadge($order['status']) ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Método de Pago:</label>
                                    <span><?= $order['payment_method'] ? ucfirst(str_replace('_', ' ', $order['payment_method'])) : 'N/A' ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Estado de Pago:</label>
                                    <span><?= getPaymentStatusBadge($order['payment_status']) ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Subtotal:</label>
                                    <span><?= formatCurrency($order['subtotal']) ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Envío:</label>
                                    <span><?= formatCurrency($order['shipping_cost']) ?></span>
                                </div>
                                <div class="info-group">
                                    <label>Impuestos:</label>
                                    <span><?= formatCurrency($order['tax']) ?></span>
                                </div>
                                <div class="info-group total">
                                    <label>Total:</label>
                                    <span><?= formatCurrency($order['total']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección de envío -->
                    <div class="order-address-card">
                        <div class="card-header">
                            <h3>Dirección de Envío</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($order['shipping_address']): ?>
                                <p><strong>Dirección:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                                <?php if ($order['shipping_city']): ?>
                                    <p><strong>Ciudad:</strong> <?= htmlspecialchars($order['shipping_city']) ?></p>
                                <?php endif; ?>
                                <?php if ($order['delivery_notes']): ?>
                                    <div class="delivery-notes">
                                        <h4>Notas de Entrega:</h4>
                                        <p><?= nl2br(htmlspecialchars($order['delivery_notes'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No se ha proporcionado dirección de envío</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Productos de la orden -->
                    <div class="order-products-card">
                        <div class="card-header">
                            <h3>Productos</h3>
                        </div>
                        <div class="card-body">
                            <table class="order-products-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Variante</th>
                                        <th>Precio Unitario</th>
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <?php if ($item['product_image']): ?>
                                                        <img src="<?= BASE_URL . '/' . $item['product_image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="product-thumbnail">
                                                    <?php endif; ?>
                                                    <div>
                                                        <a href="<?= BASE_URL ?>/producto/<?= $item['product_slug'] ?>" target="_blank">
                                                            <?= htmlspecialchars($item['product_name']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $item['variant_name'] ?: 'N/A' ?></td>
                                            <td><?= formatCurrency($item['price']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= formatCurrency($item['total']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                        <td><?= formatCurrency($order['subtotal']) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Envío:</strong></td>
                                        <td><?= formatCurrency($order['shipping_cost']) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Impuestos:</strong></td>
                                        <td><?= formatCurrency($order['tax']) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                        <td><?= formatCurrency($order['total']) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Transacciones de pago -->
                    <?php if (!empty($transactions)): ?>
                        <div class="order-payments-card">
                            <div class="card-header">
                                <h3>Transacciones de Pago</h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($transactions as $tx): ?>
                                    <div class="payment-details">
                                        <h4>Transacción #<?= $tx['id'] ?></h4>
                                        <div class="payment-details-grid">
                                            <div class="info-group">
                                                <label>Monto:</label>
                                                <span><?= formatCurrency($tx['amount']) ?></span>
                                            </div>
                                            <div class="info-group">
                                                <label>Estado:</label>
                                                <span><?= getPaymentStatusBadge($tx['status']) ?></span>
                                            </div>
                                            <div class="info-group">
                                                <label>Fecha:</label>
                                                <span><?= formatDate($tx['created_at']) ?></span>
                                            </div>
                                            <?php if ($tx['reference_number']): ?>
                                                <div class="info-group">
                                                    <label>N° de Referencia:</label>
                                                    <span><?= htmlspecialchars($tx['reference_number']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($tx['verified_by']): ?>
                                                <div class="info-group">
                                                    <label>Verificado por:</label>
                                                    <span><?= htmlspecialchars($tx['verified_by']) ?></span>
                                                </div>
                                                <div class="info-group">
                                                    <label>Fecha de verificación:</label>
                                                    <span><?= formatDate($tx['verified_at']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($tx['admin_notes']): ?>
                                                <div class="info-group" style="grid-column: 1 / -1;">
                                                    <label>Notas del administrador:</label>
                                                    <span><?= nl2br(htmlspecialchars($tx['admin_notes'])) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($tx['payment_proof']): ?>
                                            <div class="proof-image-container">
                                                <h5>Comprobante de Pago:</h5>
                                                <img src="<?= BASE_URL . '/' . $tx['payment_proof'] ?>" alt="Comprobante de pago" class="proof-image" onclick="openModal('<?= BASE_URL . '/' . $tx['payment_proof'] ?>')">
                                                <p class="text-muted">Haz clic en la imagen para ampliar</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Historial de cambios de estado -->
                    <div class="order-history-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-history"></i> 
                                Historial de Cambios
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orderHistory)): ?>
                                <div class="timeline">
                                    <?php foreach ($orderHistory as $history): ?>
                                        <div class="timeline-item" data-change-type="<?= $history['change_type'] ?>">
                                            <div class="timeline-point" style="background: <?= getChangeTypeColor($history['change_type']) ?>;">
                                                <i class="<?= getChangeTypeIcon($history['change_type']) ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <h4><?= htmlspecialchars($history['description']) ?></h4>
                                                    <span class="timeline-date">
                                                        <i class="fas fa-clock"></i>
                                                        <?= formatDate($history['created_at']) ?>
                                                    </span>
                                                </div>
                                                <div class="timeline-details">
                                                    <div class="timeline-user">
                                                        <i class="fas fa-user"></i>
                                                        <span><?= htmlspecialchars($history['changed_by_name'] ?: 'Sistema') ?></span>
                                                        <?php if ($history['changed_by_role']): ?>
                                                            <?= getRoleBadge($history['changed_by_role']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($history['old_value'] && $history['new_value']): ?>
                                                        <div class="timeline-change-values">
                                                            <div class="change-value old-value">
                                                                <span class="value-label">Anterior:</span>
                                                                <span class="value-content"><?= htmlspecialchars(translateValue($history['old_value'], $history['field_changed'])) ?></span>
                                                            </div>
                                                            <i class="fas fa-arrow-right change-arrow"></i>
                                                            <div class="change-value new-value">
                                                                <span class="value-label">Nuevo:</span>
                                                                <span class="value-content"><?= htmlspecialchars(translateValue($history['new_value'], $history['field_changed'])) ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($history['ip_address']): ?>
                                                        <div class="timeline-metadata">
                                                            <i class="fas fa-network-wired"></i>
                                                            <span>IP: <?= htmlspecialchars($history['ip_address']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-history">
                                    <i class="fas fa-history"></i>
                                    <p>No hay historial de cambios para esta orden</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para imagen del comprobante -->
    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImage" class="modal-image">
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
        // Función para cambiar el estado de la orden con modal moderno
        function changeOrderStatus(orderId) {
            // Crear el modal dinámicamente
            const modalHTML = `
                <div class="modal" id="status-modal" style="display: flex; align-items: center; justify-content: center; padding: 20px;">
                    <div class="status-modal-content" style="max-width: 520px; width: 100%; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-radius: 20px; border: 1px solid rgba(0, 119, 182, 0.18); box-shadow: 0 8px 32px rgba(0, 119, 182, 0.15), 0 20px 60px rgba(0, 0, 0, 0.15); position: relative; animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);">
                        <div class="card-header" style="padding: 2rem 4.5rem 2rem 2.5rem; background: rgba(255, 255, 255, 0.5); border-bottom: 1px solid rgba(0, 119, 182, 0.1); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-radius: 20px 20px 0 0; position: relative;">
                            <span class="close-modal" onclick="closeStatusModal()" style="cursor: pointer; font-size: 0; color: #64748b; position: absolute; top: 20px; right: 20px; width: 38px; height: 38px; border-radius: 50%; background: rgba(248, 250, 252, 0.8); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); transition: all 0.3s; z-index: 10; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(100, 116, 139, 0.2);"></span>
                            <h3 style="margin: 0; font-size: 1.8rem; color: #334155; font-weight: 600; line-height: 1.2; display: flex; align-items: center; gap: 0.8rem; position: relative; z-index: 1;">
                                <i class="fas fa-sync-alt" style="color: #0077b6; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: rgba(0, 119, 182, 0.12); border-radius: 10px; padding: 8px; flex-shrink: 0;"></i>
                                <span>Cambiar Estado</span>
                            </h3>
                        </div>
                        <div class="card-body" style="padding: 2.5rem; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-radius: 0 0 20px 20px;">
                            <div style="margin-bottom: 1.8rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.3rem; font-weight: 600; color: #475569; margin-bottom: 0.8rem;">
                                    <i class="fas fa-tag" style="color: #0077b6;"></i>
                                    Nuevo Estado
                                </label>
                                <select id="new-status" style="width: 100%; height: 50px; padding: 0 1.4rem; border: 2px solid rgba(0, 119, 182, 0.15); border-radius: 12px; font-size: 15px; color: #334155; background: rgba(255, 255, 255, 0.95); cursor: pointer; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transition: all 0.3s; outline: none;">
                                    <option value="pending">Pendiente</option>
                                    <option value="processing">En proceso</option>
                                    <option value="shipped">Enviado</option>
                                    <option value="delivered">Entregado</option>
                                    <option value="cancelled">Cancelado</option>
                                    <option value="refunded">Reembolsado</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 2rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.3rem; font-weight: 600; color: #475569; margin-bottom: 0.8rem;">
                                    <i class="fas fa-sticky-note" style="color: #0077b6;"></i>
                                    Notas (Opcional)
                                </label>
                                <textarea id="status-notes" rows="3" style="width: 100%; padding: 1.2rem; border: 2px solid rgba(0, 119, 182, 0.15); border-radius: 12px; font-size: 14.5px; color: #334155; background: rgba(255, 255, 255, 0.95); font-family: inherit; resize: vertical; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transition: all 0.3s; outline: none; line-height: 1.5;" placeholder="Agrega notas sobre el cambio de estado..."></textarea>
                            </div>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <button onclick="closeStatusModal()" style="flex: 1; min-width: 140px; padding: 1rem 1.8rem; border-radius: 12px; font-size: 1.4rem; font-weight: 600; border: 2px solid rgba(0, 119, 182, 0.15); background: rgba(248, 250, 252, 0.85); color: #64748b; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button onclick="confirmStatusChange(${orderId})" style="flex: 1; min-width: 140px; padding: 1rem 1.8rem; border-radius: 12px; font-size: 1.4rem; font-weight: 600; border: 2px solid rgba(0, 119, 182, 0.3); background: rgba(0, 119, 182, 0.9); color: white; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                                    <i class="fas fa-check"></i> Confirmar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Cerrar al hacer clic fuera
            const modal = document.getElementById('status-modal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeStatusModal();
                }
            });
        }
        
        function closeStatusModal() {
            const modal = document.getElementById('status-modal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => modal.remove(), 300);
            }
        }
        
        function confirmStatusChange(orderId) {
            const newStatus = document.getElementById('new-status').value;
            const notes = document.getElementById('status-notes').value;
            
            if (!newStatus) {
                showAlert('Por favor selecciona un estado', 'warning');
                return;
            }
            
            fetch('<?= BASE_URL ?>/admin/order/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_ids: [orderId],
                    new_status: newStatus,
                    notes: notes || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Estado de la orden actualizado correctamente', 'success');
                    closeStatusModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Error al actualizar estado');
                }
            })
            .catch(error => {
                showAlert(error.message, 'error');
            });
        }

        // Funciones para el modal de imagen
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = imageSrc;
            modalImg.classList.remove('zoomed');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'none';
            modalImg.classList.remove('zoomed');
            document.body.style.overflow = '';
        }

        // Sistema de zoom y desplazamiento mejorado y sin bugs
        (function() {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalContent = document.querySelector('#imageModal .modal-content');
            
            let isZoomed = false;
            let isDragging = false;
            let dragStarted = false;
            let startX = 0, startY = 0;
            let currentX = 0, currentY = 0;
            let clickTimeout = null;

            // Función para aplicar zoom
            function applyZoom() {
                if (isZoomed) return;
                
                isZoomed = true;
                modalImage.classList.add('zoomed');
                modalImage.style.cursor = 'grab';
                currentX = 0;
                currentY = 0;
                updateImagePosition();
            }

            // Función para quitar zoom
            function removeZoom() {
                if (!isZoomed) return;
                
                isZoomed = false;
                isDragging = false;
                dragStarted = false;
                modalImage.classList.remove('zoomed');
                modalImage.style.cursor = 'zoom-in';
                modalImage.style.transform = '';
                currentX = 0;
                currentY = 0;
            }

            // Actualizar posición de la imagen
            function updateImagePosition() {
                if (!isZoomed) return;
                
                // Usar translate + scale para mejor rendimiento
                modalImage.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) scale(1.8)`;
            }

            // Manejar mousedown
            modalImage.addEventListener('mousedown', function(e) {
                if (e.button !== 0) return; // Solo botón izquierdo
                
                e.preventDefault();
                
                if (isZoomed) {
                    isDragging = true;
                    dragStarted = false;
                    startX = e.clientX - currentX;
                    startY = e.clientY - currentY;
                    modalImage.style.cursor = 'grabbing';
                }
            });

            // Manejar mousemove global
            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                
                e.preventDefault();
                
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                // Si se movió más de 3px, es un drag real
                if (!dragStarted && (Math.abs(deltaX - currentX) > 3 || Math.abs(deltaY - currentY) > 3)) {
                    dragStarted = true;
                }
                
                // Actualizar posición
                currentX = deltaX;
                currentY = deltaY;
                updateImagePosition();
            });

            // Manejar mouseup global
            document.addEventListener('mouseup', function(e) {
                if (!isDragging && !isZoomed && e.target === modalImage) {
                    // Click para hacer zoom
                    clearTimeout(clickTimeout);
                    clickTimeout = setTimeout(() => {
                        applyZoom();
                    }, 10);
                } else if (isDragging) {
                    // Terminar drag
                    const wasDragging = dragStarted;
                    isDragging = false;
                    
                    if (isZoomed) {
                        modalImage.style.cursor = 'grab';
                        
                        // Si NO se arrastró (click simple mientras está en zoom), hacer zoom out
                        if (!wasDragging) {
                            clearTimeout(clickTimeout);
                            clickTimeout = setTimeout(() => {
                                removeZoom();
                            }, 10);
                        }
                    }
                    
                    dragStarted = false;
                }
            });

            // Prevenir comportamientos por defecto
            modalImage.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            modalImage.addEventListener('contextmenu', function(e) {
                if (isZoomed) {
                    e.preventDefault();
                }
            });

            // Doble click para zoom/unzoom
            modalImage.addEventListener('dblclick', function(e) {
                e.preventDefault();
                e.stopPropagation();
                clearTimeout(clickTimeout);
                
                if (isZoomed) {
                    removeZoom();
                } else {
                    applyZoom();
                }
            });

            // Tecla ESC para salir del zoom
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isZoomed) {
                    removeZoom();
                }
            });

            // Limpiar estado cuando se cierra el modal
            window.closeModalOriginal = window.closeModal;
            window.closeModal = function() {
                removeZoom();
                if (window.closeModalOriginal) {
                    window.closeModalOriginal();
                } else {
                    const modal = document.getElementById('imageModal');
                    const modalImg = document.getElementById('modalImage');
                    modal.style.display = 'none';
                    modalImg.classList.remove('zoomed');
                    document.body.style.overflow = '';
                }
            };
        })();

        // Cerrar modal al hacer clic fuera de la imagen
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const imageModal = document.getElementById('imageModal');
                const statusModal = document.getElementById('status-modal');
                if (imageModal && imageModal.style.display === 'block') {
                    closeModal();
                }
                if (statusModal) {
                    closeStatusModal();
                }
            }
        });
        
        // Animación de scroll suave
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Añadir animación de carga completada
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
    </script>
    
    <style>
        /* Animación adicional para el modal de estado */
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        /* Estilos específicos para el modal de estado */
        #status-modal {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        #status-modal .status-modal-content {
            position: relative;
            margin: 0;
            top: auto;
            transform: none;
        }
        
        /* Sobrescribir el h3::before para este modal */
        #status-modal .card-header h3::before {
            display: none !important;
        }
        
        /* Estilos específicos para el botón de cerrar del modal de estado */
        #status-modal .close-modal {
            position: absolute !important;
            top: 20px !important;
            right: 20px !important;
            width: 38px !important;
            height: 38px !important;
            background: rgba(248, 250, 252, 0.8) !important;
            border: 2px solid rgba(100, 116, 139, 0.2) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        #status-modal .close-modal::before {
            content: '×' !important;
            font-size: 32px !important;
            color: #64748b !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
            line-height: 0 !important;
            margin: 0 !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
        }
        
        #status-modal .close-modal:hover {
            background: rgba(220, 38, 38, 0.2) !important;
            border-color: rgba(239, 68, 68, 0.5) !important;
            transform: rotate(90deg) scale(1.05) !important;
        }
        
        #status-modal .close-modal:hover::before {
            color: #ef4444 !important;
            transform: translate(-50%, -50%) !important;
        }
        
        #status-modal .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 119, 182, 0.02);
            pointer-events: none;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Hover effects para botones en el modal */
        .modal button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 119, 182, 0.15);
        }
        
        .modal button:active {
            transform: translateY(0);
        }
        
        .modal select:hover,
        .modal textarea:hover {
            border-color: rgba(0, 119, 182, 0.3);
            background: rgba(255, 255, 255, 1);
        }
        
        .modal select:focus,
        .modal textarea:focus {
            border-color: rgba(0, 119, 182, 0.5);
            box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        /* Responsive para el modal */
        @media (max-width: 600px) {
            #status-modal .status-modal-content {
                max-width: 90% !important;
            }
            
            #status-modal .card-header {
                padding: 1.8rem 4rem 1.8rem 2rem !important;
            }
            
            #status-modal h3 {
                font-size: 1.6rem !important;
            }
            
            #status-modal h3 i {
                width: 32px !important;
                height: 32px !important;
            }
            
            #status-modal .close-modal {
                width: 34px !important;
                height: 34px !important;
                top: 16px !important;
                right: 16px !important;
            }
            
            #status-modal .close-modal span {
                font-size: 28px !important;
            }
            
            #status-modal .card-body {
                padding: 2rem !important;
            }
            
            #status-modal button {
                font-size: 1.3rem !important;
                padding: 0.85rem 1.4rem !important;
            }
            
            #status-modal select {
                height: 48px !important;
                font-size: 14px !important;
            }
        }
        
        @media (max-width: 400px) {
            #status-modal .status-modal-content {
                max-width: 95% !important;
            }
            
            #status-modal .card-body {
                padding: 1.5rem !important;
            }
            
            #status-modal .card-header {
                padding: 1.5rem 3.5rem 1.5rem 1.8rem !important;
            }
            
            #status-modal h3 {
                font-size: 1.5rem !important;
                gap: 0.6rem !important;
            }
            
            #status-modal h3 i {
                width: 30px !important;
                height: 30px !important;
            }
        }
        
        /* Efecto de carga inicial */
        body:not(.loaded) .order-detail-container {
            opacity: 0;
        }
        
        body.loaded .order-detail-container {
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        
        /* Mejora de sombras y glassmorphism */
        .modal {
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
        }
        
        /* Animaciones suaves para cards */
        @keyframes cardPulse {
            0%, 100% {
                box-shadow: 0 8px 32px rgba(0, 119, 182, 0.08);
            }
            50% {
                box-shadow: 0 12px 40px rgba(0, 119, 182, 0.12);
            }
        }
    </style>
</body>
</html>