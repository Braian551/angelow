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
        CONCAT(u.name) AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        u.identification_number,
        u.identification_type,
        u.address,
        u.neighborhood,
        u.address_details
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
    
} catch (PDOException $e) {
    error_log("Error al obtener detalles de la orden: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar los detalles de la orden'];
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/verdetailorden.css">
  
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
                            <?php if ($order['shipping_address'] || $order['address']): ?>
                                <p><?= nl2br(htmlspecialchars($order['shipping_address'] ?: $order['address'])) ?></p>
                                <p><strong>Ciudad:</strong> <?= $order['shipping_city'] ?: 'Medellín' ?></p>
                                <p><strong>Barrio:</strong> <?= $order['neighborhood'] ?: 'N/A' ?></p>
                                <p><strong>Detalles:</strong> <?= $order['address_details'] ?: 'N/A' ?></p>
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
                                                <label>Método:</label>
                                                <span><?= ucfirst(str_replace('_', ' ', $tx['payment_method'])) ?></span>
                                            </div>
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
                                            <?php if ($tx['payment_method'] === 'transferencia'): ?>
                                                <div class="info-group">
                                                    <label>Banco:</label>
                                                    <span><?= $tx['bank_name'] ?: 'N/A' ?></span>
                                                </div>
                                                <div class="info-group">
                                                    <label>N° Cuenta:</label>
                                                    <span><?= $tx['account_number'] ?: 'N/A' ?></span>
                                                </div>
                                                <div class="info-group">
                                                    <label>Tipo Cuenta:</label>
                                                    <span><?= $tx['account_type'] === 'ahorros' ? 'Ahorros' : 'Corriente' ?></span>
                                                </div>
                                                <div class="info-group">
                                                    <label>Titular:</label>
                                                    <span><?= $tx['account_holder'] ?: 'N/A' ?></span>
                                                </div>
                                                <div class="info-group">
                                                    <label>Referencia:</label>
                                                    <span><?= $tx['reference_number'] ?: 'N/A' ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($tx['payment_proof']): ?>
                                            <div class="proof-image-container">
                                                <h5>Comprobante de Pago:</h5>
                                                <p><?= $tx['notes'] ? nl2br(htmlspecialchars($tx['notes'])) : 'Sin notas adicionales' ?></p>
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
                            <h3>Historial de la Orden</h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-point"></div>
                                    <div class="timeline-content">
                                        <h4>Orden creada</h4>
                                        <p><?= formatDate($order['created_at']) ?></p>
                                    </div>
                                </div>
                                <?php if ($order['updated_at'] && $order['updated_at'] != $order['created_at']): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-point"></div>
                                        <div class="timeline-content">
                                            <h4>Última actualización</h4>
                                            <p><?= formatDate($order['updated_at']) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
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
        // Función para cambiar el estado de la orden
        function changeOrderStatus(orderId) {
            const newStatus = prompt('Ingrese el nuevo estado de la orden (pending, processing, shipped, delivered, cancelled, refunded):');
            
            if (newStatus && ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'].includes(newStatus)) {
                fetch(`${BASE_URL}/admin/order/update_status.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_ids: [orderId],
                        new_status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Estado de la orden actualizado correctamente', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Error al actualizar estado');
                    }
                })
                .catch(error => {
                    showAlert(error.message, 'error');
                });
            } else if (newStatus !== null) {
                showAlert('Estado no válido', 'error');
            }
        }

        // Funciones para el modal de imagen
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = imageSrc;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera de la imagen
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>