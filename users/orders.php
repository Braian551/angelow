<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar rol de usuario
requireRole(['user', 'customer']);

// Obtener órdenes del usuario con paginación y datos más completos
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Órdenes por página
$offset = ($page - 1) * $limit;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;

$orders = []; // Inicializar variable

try {
    $where = "WHERE o.user_id = :user_id";
    $params = [':user_id' => $_SESSION['user_id']];
    
    if ($statusFilter) {
        $where .= " AND o.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    // Conteo total para paginación
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM orders o $where");
    $stmtCount->execute($params);
    $totalOrders = $stmtCount->fetchColumn();
    $totalPages = ceil($totalOrders / $limit);
    
    // Órdenes con información más completa
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_status,
            o.total,
            o.created_at,
            o.updated_at,
            o.shipping_address,
            COUNT(oi.id) as items_count,
            SUM(oi.quantity * oi.price) as items_total
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_STR);
    if ($statusFilter) {
        $stmt->bindValue(':status', $params[':status'], PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener órdenes: " . $e->getMessage());
    $orders = [];
    $_SESSION['error_message'] = "Error al cargar órdenes. Intenta más tarde.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orders.css">
</head>
<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>
        
        <main class="user-main-content">
            <div class="dashboard-header">
                <h1>Mis Pedidos</h1>
                <p>Revisa el estado de tus compras. El equipo de Angelow actualizará tus pedidos cuando sean enviados o entregados.</p>
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

            <!-- Estadísticas rápidas -->
            <div class="orders-stats">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $totalOrders ?></h3>
                        <p>Total Pedidos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= getOrderCountByStatus('pending') ?></h3>
                        <p>Pendientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon shipped">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= getOrderCountByStatus('shipped') ?></h3>
                        <p>Enviados</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon delivered">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= getOrderCountByStatus('delivered') ?></h3>
                        <p>Entregados</p>
                    </div>
                </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="orders-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="order-search" placeholder="Buscar por número de pedido...">
                </div>
                <div class="filters">
                    <select id="status-filter">
                        <option value="">Todos los estados</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>En proceso</option>
                        <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Entregado</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                    <button id="apply-filter" class="btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </div>

            <!-- Lista de órdenes -->
            <div class="orders-container">
                <?php if (empty($orders)): ?>
                    <div class="no-orders animate__animated animate__fadeIn">
                        <i class="fas fa-box-open fa-4x"></i>
                        <h3>No tienes pedidos aún</h3>
                        <p>¡Descubre nuestros productos y realiza tu primera compra!</p>
                        <a href="<?= BASE_URL ?>" class="btn-primary">
                            <i class="fas fa-shopping-bag"></i> Ir a la tienda
                        </a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card glass-effect animate__animated animate__fadeInUp" 
                                 data-order-id="<?= $order['id'] ?>" 
                                 data-order-number="<?= htmlspecialchars($order['order_number']) ?>">
                                <div class="order-header">
                                    <div class="order-title">
                                        <h3>Pedido #<?= htmlspecialchars($order['order_number']) ?></h3>
                                        <span class="order-date">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                            <?= getStatusText($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    <div class="order-info-grid">
                                        <div class="info-item">
                                            <i class="fas fa-boxes"></i>
                                            <span><?= $order['items_count'] ?> producto(s)</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>$<?= number_format($order['total'] ?: $order['items_total'], 0) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-credit-card"></i>
                                            <span class="payment-status payment-<?= $order['payment_status'] ?>">
                                                <?php if ($order['status'] === 'cancelled'): ?>
                                                    <?= getRefundStatusText($order['payment_status'] ?? 'pending') ?>
                                                <?php else: ?>
                                                    <?= getPaymentStatusText($order['payment_status'] ?: 'pending') ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($order['shipping_address']): ?>
                                    <div class="delivery-address">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($order['shipping_address']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                    <?php if ($order['status'] === 'cancelled'): ?>
                                <div class="order-status-note" style="margin-top:12px; padding:12px; border-radius:12px; background:#fff3cd; color:#7c5700; display:flex; gap:10px; align-items:flex-start;">
                                    <i class="fas fa-money-bill-wave" style="margin-top:2px;"></i>
                                    <div>
                                        <strong>Reembolso en proceso.</strong>
                                        <div style="font-size:0.9rem; line-height:1.4;">
                                            Te enviaremos el reembolso con el mismo método de pago. Estado del reembolso: <?= getRefundStatusText($order['payment_status'] ?? 'pending') ?>.
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="order-actions">
                                    <button class="btn-view-details" data-order-id="<?= $order['id'] ?>">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </button>
                                    
                                    <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                        <button class="btn-cancel" data-order-id="<?= $order['id'] ?>">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <button class="btn-reorder" data-order-id="<?= $order['id'] ?>">
                                            <i class="fas fa-redo"></i> Volver a Pedir
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Timeline de estado -->
                                <div class="order-timeline">
                                    <?= renderOrderTimeline($order['status']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>" class="pagination-prev">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>" 
                                   class="<?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>" class="pagination-next">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script src="<?= BASE_URL ?>/js/user/orders.js"></script>
</body>
</html>

<?php
// Funciones auxiliares
function getOrderCountByStatus($status) {
    global $conn, $_SESSION;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = ?");
        $stmt->execute([$_SESSION['user_id'], $status]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting orders: " . $e->getMessage());
        return 0;
    }
}

// Se usa `layouts/functions.php` para las traducciones de estados.

function renderOrderTimeline($status) {
    if ($status === 'cancelled') {
        return '<div class="order-timeline-modern"><span class="status-badge status-cancelled"><i class="fas fa-ban"></i> Pedido cancelado</span></div>';
    }

    // Mostrar timeline especial en caso de reembolso
    if ($status === 'refunded' || $status === 'partially_refunded') {
        $label = $status === 'refunded' ? 'Pedido reembolsado' : 'Parcialmente reembolsado';
        return '<div class="order-timeline-modern"><span class="status-badge status-refunded"><i class="fas fa-undo"></i> ' . $label . '</span></div>';
    }

    $steps = [
        'pending' => ['icon' => 'fas fa-clock', 'label' => 'Pendiente'],
        'processing' => ['icon' => 'fas fa-cog', 'label' => 'En Proceso'],
        'shipped' => ['icon' => 'fas fa-truck', 'label' => 'Enviado'],
        'delivered' => ['icon' => 'fas fa-check-circle', 'label' => 'Entregado']
    ];
    
    // Calcular el progreso
    $statusOrder = array_keys($steps);
    $currentIndex = array_search($status, $statusOrder);
    
    if ($currentIndex === false) {
        $currentIndex = 0;
    }
    
    $progress = (($currentIndex + 1) / count($steps)) * 100;
    
    $html = '<div class="order-timeline-modern">';
    $html .= '<div class="timeline-progress-bar">';
    $html .= '<div class="timeline-progress-fill" style="width: ' . $progress . '%"></div>';
    $html .= '</div>';
    $html .= '<div class="timeline-steps-container">';
    
    foreach ($statusOrder as $index => $step) {
        $isCompleted = $index < $currentIndex;
        $isActive = $index === $currentIndex;
        $statusClass = $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending');
        
        $html .= '<div class="timeline-step-modern ' . $statusClass . '">';
        $html .= '<div class="timeline-dot-modern">';
        $html .= '<i class="' . $steps[$step]['icon'] . '"></i>';
        $html .= '</div>';
        $html .= '<span class="timeline-label-modern">' . $steps[$step]['label'] . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>