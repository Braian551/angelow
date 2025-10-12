<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de delivery
requireRole('delivery');

// Obtener información del usuario actual
try {
    $userId = $_SESSION['user_id'];
    $query = "SELECT id, name, email, image, phone, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/error.php');
    exit();
}

// Obtener órdenes asignadas al transportista (solo las que están en estado 'processing' o 'shipped')
try {
    $query = "
        SELECT o.id, o.order_number, o.status, o.total, o.shipping_address, o.shipping_city, 
               o.delivery_notes, o.created_at, 
               CONCAT(u.name, ' (', u.phone, ')') as customer_info
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('processing', 'shipped') 
        ORDER BY o.created_at DESC
        LIMIT 10
    ";
    $orders = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener órdenes: " . $e->getMessage());
    $orders = [];
}

// Obtener historial de entregas recientes (solo las entregadas)
try {
    $query = "
        SELECT o.id, o.order_number, o.status, o.total, o.shipping_address, 
               o.updated_at as delivered_at
        FROM orders o
        WHERE o.status = 'delivered'
        ORDER BY o.updated_at DESC
        LIMIT 5
    ";
    $deliveryHistory = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener historial: " . $e->getMessage());
    $deliveryHistory = [];
}

// Obtener estadísticas para el dashboard
try {
    // Entregas hoy
    $query = "SELECT COUNT(*) as count FROM orders 
              WHERE status = 'delivered' AND DATE(updated_at) = CURDATE()";
    $deliveriesToday = $conn->query($query)->fetch(PDO::FETCH_ASSOC)['count'];

    // Entregas totales
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'";
    $totalDeliveries = $conn->query($query)->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $deliveriesToday = 0;
    $totalDeliveries = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Transportista - Angelow</title>
    <meta name="description" content="Panel de control para transportistas de Angelow Ropa Infantil">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarddelivery.css">
</head>
<body>
    <div class="delivery-container">
        <!-- Sidebar -->
        <aside class="delivery-sidebar">
            <div class="profile-summary">
                <div class="avatar">
                    <img src="<?= BASE_URL ?>/<?= !empty($userData['image']) ? htmlspecialchars($userData['image']) : 'images/default-avatar.png' ?>" alt="Foto de perfil">
                </div>
                <div class="info">
                    <h3><?= htmlspecialchars($userData['name']) ?></h3>
                    <p>Transportista</p>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($userData['phone'] ?? 'No registrado') ?></p>
                </div>
            </div>

            <nav class="delivery-menu">
                <ul>
                    <li class="active">
                        <a href="<?= BASE_URL ?>/delivery/dashboarddeli.php">
                            <i class="fas fa-tachometer-alt"></i> Resumen
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/delivery/orders.php">
                            <i class="fas fa-shopping-bag"></i> Órdenes
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/delivery/history.php">
                            <i class="fas fa-history"></i> Historial
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/delivery/settings.php">
                            <i class="fas fa-user-cog"></i> Mi Cuenta
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="delivery-content">
            <header class="delivery-header">
                <h1>Panel de Transportista</h1>
                <div class="header-actions">
                    <div class="status-indicator">
                        <span class="status-dot online"></span>
                        <span>Disponible</span>
                    </div>
                    <button class="notifications-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge">2</span>
                    </button>
                </div>
            </header>

            <!-- Stats Summary -->
            <section class="stats-summary">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Entregas Hoy</h3>
                        <span class="stat-value"><?= $deliveriesToday ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pendientes</h3>
                        <span class="stat-value"><?= count($orders) ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Entregas Totales</h3>
                        <span class="stat-value"><?= $totalDeliveries ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Calificación</h3>
                        <span class="stat-value">4.8/5</span>
                    </div>
                </div>
            </section>

            <!-- Current Deliveries -->
            <section class="current-deliveries">
                <div class="section-header">
                    <h2>Órdenes para Entregar</h2>
                    <a href="<?= BASE_URL ?>/delivery/orders.php" class="view-all">Ver todas</a>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <i class="fas fa-box-open"></i>
                        <p>No hay órdenes asignadas actualmente</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <span class="order-number">#<?= htmlspecialchars($order['order_number']) ?></span>
                                    <span class="order-status <?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <div class="order-info">
                                        <p><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_info']) ?></p>
                                        <p><strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></p>
                                        <?php if (!empty($order['delivery_notes'])): ?>
                                            <p><strong>Notas:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-actions">
                                        <span class="order-total">$<?= number_format($order['total'], 2) ?></span>
                                        <?php if ($order['status'] === 'processing'): ?>
                                            <button class="btn start-delivery" data-order="<?= $order['id'] ?>">
                                                <i class="fas fa-truck"></i> Iniciar Entrega
                                            </button>
                                        <?php else: ?>
                                            <button class="btn complete-delivery" data-order="<?= $order['id'] ?>">
                                                <i class="fas fa-check"></i> Marcar Entregado
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Delivery History -->
            <section class="delivery-history">
                <div class="section-header">
                    <h2>Historial Reciente</h2>
                    <a href="<?= BASE_URL ?>/delivery/history.php" class="view-all">Ver todo</a>
                </div>

                <?php if (empty($deliveryHistory)): ?>
                    <div class="no-history">
                        <i class="fas fa-history"></i>
                        <p>No hay historial de entregas</p>
                    </div>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Dirección</th>
                                <th>Total</th>
                                <th>Fecha Entrega</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveryHistory as $delivery): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($delivery['order_number']) ?></td>
                                    <td><?= htmlspecialchars($delivery['shipping_address']) ?></td>
                                    <td>$<?= number_format($delivery['total'], 2) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($delivery['delivered_at'])) ?></td>
                                    <td><span class="status-badge delivered">Entregado</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botones de acción para entregas
        document.querySelectorAll('.start-delivery, .complete-delivery').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order');
                const action = this.classList.contains('start-delivery') ? 'start' : 'complete';
                
                fetch('<?= BASE_URL ?>/delivery/update_delivery.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo actualizar el estado'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            });
        });
    });
    </script>
</body>
</html>