<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de delivery
requireRole('delivery');

// Función helper para mostrar tiempo transcurrido
function time_elapsed_string($datetime, $full = false) {
    if (!$datetime) return 'N/A';
    
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    } elseif ($diff->d > 0) {
        return $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'justo ahora';
    }
}

$driverId = $_SESSION['user_id'];

// Obtener órdenes asignadas a este transportista específico
try {
    $driverId = $_SESSION['user_id'];
    
    $query = "
        SELECT 
            od.id as delivery_id,
            od.delivery_status,
            od.assigned_at,
            od.accepted_at,
            od.started_at,
            o.id as order_id,
            o.order_number, 
            o.status, 
            o.total, 
            o.shipping_address, 
            o.shipping_city,
            o.delivery_notes, 
            o.created_at,
            CONCAT(u.name, ' (', u.phone, ')') as customer_info
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        INNER JOIN users u ON o.user_id = u.id
        WHERE od.driver_id = ?
        AND od.delivery_status IN ('driver_assigned', 'driver_accepted', 'in_transit', 'arrived')
        ORDER BY od.assigned_at DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // También obtener órdenes disponibles (sin asignar)
    $queryAvailable = "
        SELECT 
            NULL as delivery_id,
            NULL as delivery_status,
            NULL as assigned_at,
            NULL as accepted_at,
            NULL as started_at,
            o.id as order_id,
            o.order_number, 
            o.status, 
            o.total, 
            o.shipping_address, 
            o.shipping_city,
            o.delivery_notes, 
            o.created_at,
            CONCAT(u.name, ' (', u.phone, ')') as customer_info
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        WHERE o.status = 'shipped'
        AND o.payment_status = 'paid'
        AND NOT EXISTS (
            SELECT 1 FROM order_deliveries od2 
            WHERE od2.order_id = o.id 
            AND od2.delivery_status NOT IN ('rejected', 'cancelled')
        )
        ORDER BY o.created_at DESC
        LIMIT 10
    ";
    $stmtAvailable = $conn->prepare($queryAvailable);
    $stmtAvailable->execute();
    $availableOrders = $stmtAvailable->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener órdenes: " . $e->getMessage());
    $orders = [];
    $availableOrders = [];
}

// Obtener historial de entregas recientes de este transportista
try {
    $query = "
        SELECT 
            od.id as delivery_id,
            o.id as order_id,
            o.order_number, 
            o.total, 
            o.shipping_address,
            o.shipping_city,
            od.delivered_at,
            od.recipient_name,
            od.delivery_notes
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        WHERE od.driver_id = ?
        AND od.delivery_status = 'delivered'
        ORDER BY od.delivered_at DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $deliveryHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener historial: " . $e->getMessage());
    $deliveryHistory = [];
}

// Obtener estadísticas personalizadas del transportista
try {
    // Entregas hoy de este transportista
    $query = "SELECT COUNT(*) as count FROM order_deliveries 
              WHERE driver_id = ? AND delivery_status = 'delivered' 
              AND DATE(delivered_at) = CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $deliveriesToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Entregas totales de este transportista
    $query = "SELECT COUNT(*) as count FROM order_deliveries 
              WHERE driver_id = ? AND delivery_status = 'delivered'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $totalDeliveries = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Obtener estadísticas avanzadas
    $query = "SELECT * FROM driver_statistics WHERE driver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $driverStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no existen estadísticas, crear registro
    if (!$driverStats) {
        $stmt = $conn->prepare("INSERT INTO driver_statistics (driver_id) VALUES (?)");
        $stmt->execute([$driverId]);
        $driverStats = [
            'average_rating' => 0,
            'acceptance_rate' => 100,
            'completion_rate' => 100
        ];
    }
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $deliveriesToday = 0;
    $totalDeliveries = 0;
    $driverStats = [
        'average_rating' => 0,
        'acceptance_rate' => 100,
        'completion_rate' => 100
    ];
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
        <?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

        <!-- Main Content -->
        <main class="delivery-content">
            <!-- Header -->
            <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>

            <!-- Stats Summary -->
            <section class="stats-summary">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Entregas Hoy</h3>
                        <span class="stat-value"><?= $deliveriesToday ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pendientes</h3>
                        <span class="stat-value"><?= count($orders) ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
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
                        <span class="stat-value"><?= number_format($driverStats['average_rating'], 1) ?>/5</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tasa de Aceptación</h3>
                        <span class="stat-value"><?= number_format($driverStats['acceptance_rate'], 0) ?>%</span>
                    </div>
                </div>
            </section>

            <!-- Available Orders Section -->
            <?php if (!empty($availableOrders)): ?>
            <section class="current-deliveries available-section">
                <div class="section-header">
                    <h2><i class="fas fa-gift"></i> Órdenes Disponibles para Aceptar</h2>
                    <a href="<?= BASE_URL ?>/delivery/orders.php" class="view-all">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="orders-list">
                    <?php foreach ($availableOrders as $order): ?>
                        <div class="order-card available" data-order-id="<?= $order['order_id'] ?>">
                            <div class="order-header">
                                <span class="order-number"><i class="fas fa-hashtag"></i><?= htmlspecialchars($order['order_number']) ?></span>
                                <span class="order-status available"><i class="fas fa-sparkles"></i> Disponible</span>
                            </div>
                            <div class="order-body">
                                <div class="order-info">
                                    <p><i class="fas fa-user"></i><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_info']) ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i><strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></p>
                                    <?php if (!empty($order['delivery_notes'])): ?>
                                        <p><i class="fas fa-sticky-note"></i><strong>Notas:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                    <?php endif; ?>
                                    <p class="order-time"><i class="fas fa-clock"></i> Creada hace <?= time_elapsed_string($order['created_at']) ?></p>
                                </div>
                                <div class="order-actions">
                                    <span class="order-total"><i class="fas fa-dollar-sign"></i><?= number_format($order['total'], 2) ?></span>
                                    <button class="btn btn-success accept-available-order" data-order-id="<?= $order['order_id'] ?>" data-order-number="<?= htmlspecialchars($order['order_number']) ?>">
                                        <i class="fas fa-hand-pointer"></i> Quiero esta orden
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Current Deliveries -->
            <section class="current-deliveries">
                <div class="section-header">
                    <h2><i class="fas fa-shipping-fast"></i> Mis Órdenes en Proceso</h2>
                    <a href="<?= BASE_URL ?>/delivery/orders.php?tab=active" class="view-all">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No hay órdenes en proceso actualmente</p>
                        <?php if (!empty($availableOrders)): ?>
                            <a href="<?= BASE_URL ?>/delivery/orders.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-search-plus"></i> Ver órdenes disponibles
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card" data-delivery-id="<?= $order['delivery_id'] ?>">
                                <div class="order-header">
                                    <span class="order-number"><i class="fas fa-hashtag"></i><?= htmlspecialchars($order['order_number']) ?></span>
                                    <span class="order-status <?= $order['delivery_status'] ?>">
                                        <?php
                                        $statusLabels = [
                                            'driver_assigned' => '<i class="fas fa-user-check"></i> Asignada',
                                            'driver_accepted' => '<i class="fas fa-thumbs-up"></i> Aceptada',
                                            'in_transit' => '<i class="fas fa-truck-moving"></i> En Tránsito',
                                            'arrived' => '<i class="fas fa-map-pin"></i> En Destino'
                                        ];
                                        echo $statusLabels[$order['delivery_status']] ?? $order['delivery_status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <div class="order-info">
                                        <p><i class="fas fa-user-circle"></i><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_info']) ?></p>
                                        <p><i class="fas fa-map-marked-alt"></i><strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></p>
                                        <?php if (!empty($order['delivery_notes'])): ?>
                                            <p><i class="fas fa-comment-dots"></i><strong>Notas:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($order['delivery_status'] === 'driver_assigned'): ?>
                                            <p class="order-time"><i class="fas fa-bell"></i> Asignada hace <?= time_elapsed_string($order['assigned_at']) ?></p>
                                        <?php elseif ($order['delivery_status'] === 'driver_accepted'): ?>
                                            <p class="order-time"><i class="fas fa-check-circle"></i> Aceptada hace <?= time_elapsed_string($order['accepted_at']) ?></p>
                                        <?php elseif ($order['delivery_status'] === 'in_transit'): ?>
                                            <p class="order-time"><i class="fas fa-route"></i> En ruta desde hace <?= time_elapsed_string($order['started_at']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-actions">
                                        <span class="order-total"><i class="fas fa-money-bill-wave"></i><?= number_format($order['total'], 2) ?></span>
                                        
                                        <?php if ($order['delivery_status'] === 'driver_assigned'): ?>
                                            <!-- Orden recién asignada - mostrar botones de aceptar/rechazar -->
                                            <div class="btn-group">
                                                <button class="btn btn-success accept-order" data-delivery-id="<?= $order['delivery_id'] ?>">
                                                    <i class="fas fa-check"></i> Aceptar
                                                </button>
                                                <button class="btn btn-danger reject-order" data-delivery-id="<?= $order['delivery_id'] ?>">
                                                    <i class="fas fa-times"></i> Rechazar
                                                </button>
                                            </div>
                                        <?php elseif ($order['delivery_status'] === 'driver_accepted'): ?>
                                            <!-- Orden aceptada - mostrar botón de iniciar recorrido -->
                                            <button class="btn btn-primary start-trip" data-delivery-id="<?= $order['delivery_id'] ?>">
                                                <i class="fas fa-play-circle"></i> Iniciar Recorrido
                                            </button>
                                        <?php elseif ($order['delivery_status'] === 'in_transit'): ?>
                                            <!-- En tránsito - mostrar botón de llegada -->
                                            <button class="btn btn-info mark-arrived" data-delivery-id="<?= $order['delivery_id'] ?>">
                                                <i class="fas fa-flag-checkered"></i> He Llegado
                                            </button>
                                        <?php elseif ($order['delivery_status'] === 'arrived'): ?>
                                            <!-- Llegado - mostrar botón de entrega completada -->
                                            <button class="btn btn-success complete-delivery" data-delivery-id="<?= $order['delivery_id'] ?>">
                                                <i class="fas fa-check-double"></i> Entrega Completada
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
                    <h2><i class="fas fa-history"></i> Historial Reciente</h2>
                    <a href="<?= BASE_URL ?>/delivery/history.php" class="view-all">
                        Ver todo <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($deliveryHistory)): ?>
                    <div class="no-history">
                        <i class="fas fa-archive"></i>
                        <p>No hay historial de entregas</p>
                    </div>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Orden</th>
                                <th><i class="fas fa-map-marker-alt"></i> Dirección</th>
                                <th><i class="fas fa-dollar-sign"></i> Total</th>
                                <th><i class="fas fa-calendar-alt"></i> Fecha Entrega</th>
                                <th><i class="fas fa-info-circle"></i> Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveryHistory as $delivery): ?>
                                <tr>
                                    <td><i class="fas fa-hashtag"></i><?= htmlspecialchars($delivery['order_number']) ?></td>
                                    <td><?= htmlspecialchars($delivery['shipping_address']) ?></td>
                                    <td><strong>$<?= number_format($delivery['total'], 2) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($delivery['delivered_at'])) ?></td>
                                    <td><span class="status-badge delivered"><i class="fas fa-check"></i> Entregado</span></td>
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
        const BASE_URL = '<?= BASE_URL ?>';
        
        // ====================================
        // FUNCIÓN PARA REALIZAR ACCIONES
        // ====================================
        function performDeliveryAction(action, deliveryId, additionalData = {}) {
            const data = {
                action: action,
                delivery_id: deliveryId,
                ...additionalData
            };
            
            // Obtener ubicación si está disponible
            if (navigator.geolocation && ['start_trip', 'mark_arrived', 'update_location'].includes(action)) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    data.latitude = position.coords.latitude;
                    data.longitude = position.coords.longitude;
                    sendDeliveryRequest(data);
                }, function(error) {
                    console.warn('No se pudo obtener la ubicación:', error);
                    sendDeliveryRequest(data);
                });
            } else {
                sendDeliveryRequest(data);
            }
        }
        
        function sendDeliveryRequest(data) {
            fetch(BASE_URL + '/delivery/delivery_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + (result.message || 'No se pudo procesar la acción'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'error');
            });
        }
        
        // ====================================
        // ACEPTAR ORDEN DISPONIBLE (SIN ASIGNAR)
        // ====================================
        document.querySelectorAll('.accept-available-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const orderNumber = this.getAttribute('data-order-number');
                
                if (confirm(`¿Deseas aceptar la orden #${orderNumber}?\n\nSe te asignará automáticamente.`)) {
                    // Primero asignar la orden al transportista
                    fetch(BASE_URL + '/delivery/delivery_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'self_assign_order',
                            order_id: orderId
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showNotification('¡Orden aceptada exitosamente!', 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification('Error: ' + (result.message || 'No se pudo aceptar la orden'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al procesar la solicitud', 'error');
                    });
                }
            });
        });
        
        // ====================================
        // ACEPTAR ORDEN ASIGNADA
        // ====================================
        document.querySelectorAll('.accept-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const deliveryId = this.getAttribute('data-delivery-id');
                
                if (confirm('¿Deseas aceptar esta orden de entrega?')) {
                    performDeliveryAction('accept_order', deliveryId);
                }
            });
        });
        
        // ====================================
        // RECHAZAR ORDEN
        // ====================================
        document.querySelectorAll('.reject-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const deliveryId = this.getAttribute('data-delivery-id');
                
                const reason = prompt('¿Por qué rechazas esta orden? (opcional)');
                if (reason !== null) {
                    performDeliveryAction('reject_order', deliveryId, { reason: reason || 'Sin razón especificada' });
                }
            });
        });
        
        // ====================================
        // INICIAR RECORRIDO
        // ====================================
        document.querySelectorAll('.start-trip').forEach(btn => {
            btn.addEventListener('click', function() {
                const deliveryId = this.getAttribute('data-delivery-id');
                
                if (confirm('¿Iniciar el recorrido hacia el cliente?')) {
                    performDeliveryAction('start_trip', deliveryId);
                }
            });
        });
        
        // ====================================
        // MARCAR COMO LLEGADO
        // ====================================
        document.querySelectorAll('.mark-arrived').forEach(btn => {
            btn.addEventListener('click', function() {
                const deliveryId = this.getAttribute('data-delivery-id');
                
                if (confirm('¿Has llegado al destino?')) {
                    performDeliveryAction('mark_arrived', deliveryId);
                }
            });
        });
        
        // ====================================
        // COMPLETAR ENTREGA
        // ====================================
        document.querySelectorAll('.complete-delivery').forEach(btn => {
            btn.addEventListener('click', function() {
                const deliveryId = this.getAttribute('data-delivery-id');
                
                // Modal para confirmar entrega
                const recipientName = prompt('¿Nombre de quien recibió el pedido?');
                if (recipientName !== null && recipientName.trim() !== '') {
                    const notes = prompt('Notas adicionales (opcional)');
                    
                    performDeliveryAction('complete_delivery', deliveryId, {
                        recipient_name: recipientName,
                        notes: notes || ''
                    });
                } else if (recipientName !== null) {
                    alert('Debes ingresar el nombre de quien recibió el pedido');
                }
            });
        });
        
        // ====================================
        // SISTEMA DE NOTIFICACIONES
        // ====================================
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Agregar estilos inline si no existen en CSS
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#4caf50' : '#f44336'};
                color: white;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Eliminar después de 3 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Auto-actualizar cada 30 segundos para ver nuevas órdenes
        setInterval(() => {
            // Solo recargar si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                console.log('Actualizando órdenes...');
                // Aquí podrías hacer una llamada AJAX en lugar de recargar
                // location.reload();
            }
        }, 30000);
    });
    </script>
    
    <style>
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-success {
        background: #4A90E2;
        color: white;
    }
    
    .btn-success:hover {
        background: #2E5C8A;
    }
    
    .btn-danger {
        background: rgba(74, 144, 226, 0.2);
        color: #2E5C8A;
        border: 1px solid rgba(74, 144, 226, 0.3);
    }
    
    .btn-danger:hover {
        background: rgba(74, 144, 226, 0.3);
    }
    
    .btn-primary {
        background: #4A90E2;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2E5C8A;
    }
    
    .btn-info {
        background: #7AB8F5;
        color: white;
    }
    
    .btn-info:hover {
        background: #4A90E2;
    }
    
    .order-time {
        color: #4A6A82;
        font-size: 13px;
        margin-top: 8px;
    }
    
    .order-time i {
        margin-right: 5px;
        color: #4A90E2;
    }
    
    .order-status.driver_assigned {
        background: rgba(74, 144, 226, 0.1);
        color: #2E5C8A;
    }
    
    .order-status.driver_accepted {
        background: rgba(74, 144, 226, 0.2);
        color: #4A90E2;
    }
    
    .order-status.in_transit {
        background: rgba(74, 144, 226, 0.3);
        color: #2E5C8A;
    }
    
    .order-status.arrived {
        background: rgba(74, 144, 226, 0.5);
        color: white;
    }
    
    .order-status.available {
        background: #4A90E2;
        color: white;
    }
    
    .available-section {
        background: rgba(74, 144, 226, 0.1);
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 3rem;
        border: 2px solid rgba(74, 144, 226, 0.3);
    }
    
    .available-section .section-header h2 {
        color: #4A90E2;
    }
    
    .order-card.available {
        border: 2px solid rgba(74, 144, 226, 0.3);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.15);
    }
    
    .order-card.available:hover {
        box-shadow: 0 6px 20px rgba(74, 144, 226, 0.25);
        border-color: #4A90E2;
    }
    </style>
</body>
</html>