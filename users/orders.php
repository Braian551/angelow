<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/header2.php';
require_once __DIR__ . '/../layouts/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Función para obtener los pedidos del usuario
function getUserOrders($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                o.id, 
                o.order_number, 
                o.status, 
                o.total, 
                o.payment_method, 
                o.payment_status,
                o.created_at,
                o.updated_at,
                COUNT(oi.id) as item_count
            FROM 
                orders o
            LEFT JOIN 
                order_items oi ON o.id = oi.order_id
            WHERE 
                o.user_id = ?
            GROUP BY 
                o.id
            ORDER BY 
                o.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener pedidos: " . $e->getMessage());
        return [];
    }
}

// Función para obtener detalles de un pedido específico
function getOrderDetails($conn, $order_id, $user_id) {
    try {
        // Verificar que el pedido pertenece al usuario
        $stmt = $conn->prepare("
            SELECT id FROM orders 
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$order_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }

        // Obtener información del pedido
        $stmt = $conn->prepare("
            SELECT 
                o.*,
                pt.reference_number,
                pt.bank_name,
                pt.account_number,
                pt.account_type,
                pt.account_holder,
                pt.payment_proof,
                pt.notes as payment_notes,
                pt.status as payment_status,
                pt.created_at as payment_date
            FROM 
                orders o
            LEFT JOIN 
                payment_transactions pt ON o.id = pt.order_id
            WHERE 
                o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener items del pedido
        $stmt = $conn->prepare("
            SELECT 
                oi.*,
                pi.image_path as product_image
            FROM 
                order_items oi
            LEFT JOIN 
                product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
            WHERE 
                oi.order_id = ?
            GROUP BY
                oi.id
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'order' => $order,
            'items' => $items
        ];
    } catch (PDOException $e) {
        error_log("Error al obtener detalles del pedido: " . $e->getMessage());
        return false;
    }
}

// Obtener todos los pedidos del usuario
$orders = getUserOrders($conn, $user_id);

// Ver si se está solicitando ver un pedido específico
$orderDetails = null;
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $orderDetails = getOrderDetails($conn, $order_id, $user_id);
    
    if ($orderDetails === false) {
        header("Location: " . BASE_URL . "/users/orders.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($orderDetails) ? 'Detalles del Pedido' : 'Mis Pedidos' ?> - Angelow</title>
    <meta name="description" content="Gestiona y revisa tus pedidos en Angelow Ropa Infantil">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/ordersuser.css">
</head>

<body>
    <div class="user-dashboard-container">
        <!-- Sidebar de navegación -->
     <?php
require_once __DIR__ . '/../layouts/asideuser.php';
  ?>

        <!-- Contenido principal -->
        <main class="user-main-content">
            <?php if (isset($orderDetails)): ?>
                <!-- Vista de detalles de un pedido específico -->
                <div class="order-details-container">
                    <div class="order-header">
                        <a href="<?= BASE_URL ?>/users/orders.php" class="back-to-orders">
                            <i class="fas fa-arrow-left"></i> Volver a mis pedidos
                        </a>
                        <h1>Detalles del Pedido #<?= htmlspecialchars($orderDetails['order']['order_number']) ?></h1>
                        <div class="order-status-badge <?= $orderDetails['order']['status'] ?>">
                            <?= ucfirst(htmlspecialchars($orderDetails['order']['status'])) ?>
                        </div>
                    </div>

                    <div class="order-timeline">
                        <div class="timeline-step <?= $orderDetails['order']['status'] === 'pending' ? 'active' : 'completed' ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="timeline-label">Pedido realizado</div>
                            <div class="timeline-date"><?= date('d M Y, H:i', strtotime($orderDetails['order']['created_at'])) ?></div>
                        </div>
                        
                        <div class="timeline-step <?= in_array($orderDetails['order']['status'], ['processing', 'shipped', 'delivered']) ? 'completed' : ($orderDetails['order']['status'] === 'pending' ? 'pending' : '') ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="timeline-label">Procesando</div>
                            <?php if ($orderDetails['order']['status'] !== 'pending'): ?>
                                <div class="timeline-date"><?= date('d M Y, H:i', strtotime($orderDetails['order']['updated_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="timeline-step <?= in_array($orderDetails['order']['status'], ['shipped', 'delivered']) ? 'completed' : ($orderDetails['order']['status'] === 'processing' ? 'active' : '') ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="timeline-label">Enviado</div>
                            <?php if ($orderDetails['order']['status'] === 'shipped' || $orderDetails['order']['status'] === 'delivered'): ?>
                                <div class="timeline-date"><?= date('d M Y, H:i', strtotime($orderDetails['order']['updated_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="timeline-step <?= $orderDetails['order']['status'] === 'delivered' ? 'completed' : ($orderDetails['order']['status'] === 'shipped' ? 'active' : '') ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="timeline-label">Entregado</div>
                            <?php if ($orderDetails['order']['status'] === 'delivered'): ?>
                                <div class="timeline-date"><?= date('d M Y, H:i', strtotime($orderDetails['order']['updated_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-summary-grid">
                        <div class="order-items-section">
                            <h2>Productos</h2>
                            <div class="order-items-list">
                                <?php foreach ($orderDetails['items'] as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['product_image'] ?? 'images/default-product.jpg') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        </div>
                                        <div class="item-details">
                                            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                            <?php if ($item['variant_name']): ?>
                                                <p class="item-variant"><?= htmlspecialchars($item['variant_name']) ?></p>
                                            <?php endif; ?>
                                            <p class="item-price">$<?= number_format($item['price'], 0, ',', '.') ?> x <?= $item['quantity'] ?></p>
                                        </div>
                                        <div class="item-total">
                                            $<?= number_format($item['total'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="order-info-section">
                            <div class="order-info-card">
                                <h2>Resumen del Pedido</h2>
                                <div class="info-row">
                                    <span>Subtotal:</span>
                                    <span>$<?= number_format($orderDetails['order']['subtotal'], 0, ',', '.') ?></span>
                                </div>
                                <div class="info-row">
                                    <span>Envío:</span>
                                    <span>$<?= number_format($orderDetails['order']['shipping_cost'], 0, ',', '.') ?></span>
                                </div>
                                <div class="info-row">
                                    <span>Impuestos:</span>
                                    <span>$<?= number_format($orderDetails['order']['tax'], 0, ',', '.') ?></span>
                                </div>
                                <div class="info-row total">
                                    <span>Total:</span>
                                    <span>$<?= number_format($orderDetails['order']['total'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="order-info-card">
                                <h2>Información de Pago</h2>
                                <div class="info-row">
                                    <span>Método:</span>
                                    <span>
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $orderDetails['order']['payment_method']))) ?>
                                        <?php if ($orderDetails['order']['payment_status'] === 'paid'): ?>
                                            <span class="payment-status paid">(Pagado)</span>
                                        <?php elseif ($orderDetails['order']['payment_status'] === 'pending'): ?>
                                            <span class="payment-status pending">(Pendiente)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if ($orderDetails['order']['payment_method'] === 'transferencia' && !empty($orderDetails['order']['bank_name'])): ?>
                                    <div class="info-row">
                                        <span>Banco:</span>
                                        <span><?= htmlspecialchars($orderDetails['order']['bank_name']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span>Tipo de cuenta:</span>
                                        <span><?= htmlspecialchars(ucfirst($orderDetails['order']['account_type'])) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span>Número de cuenta:</span>
                                        <span><?= htmlspecialchars($orderDetails['order']['account_number']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span>Titular:</span>
                                        <span><?= htmlspecialchars($orderDetails['order']['account_holder']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($orderDetails['order']['payment_proof'])): ?>
                                    <div class="info-row">
                                        <span>Comprobante:</span>
                                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($orderDetails['order']['payment_proof']) ?>" target="_blank" class="view-proof">
                                            Ver comprobante <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($orderDetails['order']['payment_notes'])): ?>
                                    <div class="info-row notes">
                                        <span>Notas:</span>
                                        <p><?= htmlspecialchars($orderDetails['order']['payment_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-info-card">
                                <h2>Dirección de Envío</h2>
                                <p><?= nl2br(htmlspecialchars($orderDetails['order']['shipping_address'])) ?></p>
                                <p><strong>Ciudad:</strong> <?= htmlspecialchars($orderDetails['order']['shipping_city']) ?></p>
                                <?php if (!empty($orderDetails['order']['delivery_notes'])): ?>
                                    <p><strong>Notas de entrega:</strong> <?= htmlspecialchars($orderDetails['order']['delivery_notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Vista de lista de todos los pedidos -->
                <div class="orders-list-container">
                    <h1>Mis Pedidos</h1>
                    
                    <?php if (count($orders) > 0): ?>
                        <div class="orders-table-container">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Fecha</th>
                                        <th>Productos</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Pago</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td><?= $order['item_count'] ?></td>
                                            <td>$<?= number_format($order['total'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="status-badge <?= $order['status'] ?>">
                                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="payment-status <?= $order['payment_status'] ?>">
                                                    <?= ucfirst(htmlspecialchars($order['payment_status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/users/orders.php?order_id=<?= $order['id'] ?>" class="view-order">
                                                    Ver detalle <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-box-open"></i>
                            <h2>Aún no has realizado ningún pedido</h2>
                            <p>Cuando realices un pedido, aparecerá aquí.</p>
                            <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">Ir a la tienda</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>
    <script src="<?= BASE_URL ?>/js/orderuser.js"></script>
    <script>
        // JavaScript para mejorar la experiencia de usuario
        document.addEventListener('DOMContentLoaded', function() {
            // Función para confirmar cancelación de pedido
            const cancelButtons = document.querySelectorAll('.cancel-order');
            if (cancelButtons) {
                cancelButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        if (!confirm('¿Estás seguro de que deseas cancelar este pedido?')) {
                            e.preventDefault();
                        }
                    });
                });
            }
            
            // Función para copiar datos de transferencia
            const copyButtons = document.querySelectorAll('.copy-payment-info');
            if (copyButtons) {
                copyButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const textToCopy = this.getAttribute('data-copy');
                        navigator.clipboard.writeText(textToCopy).then(() => {
                            const originalText = this.innerHTML;
                            this.innerHTML = '<i class="fas fa-check"></i> Copiado';
                            setTimeout(() => {
                                this.innerHTML = originalText;
                            }, 2000);
                        });
                    });
                });
            }
        });
    </script>
</body>
</html>