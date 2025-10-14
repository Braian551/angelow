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
    // Obtener información de la orden y delivery
    $stmt = $conn->prepare("
        SELECT
            o.id,
            o.order_number,
            o.status as order_status,
            o.created_at,
            o.updated_at,
            od.delivery_status,
            od.started_at,
            od.delivered_at,
            od.location_lat as current_lat,
            od.location_lng as current_lng,
            od.driver_id,
            u.name as driver_name,
            u.phone as driver_phone,
            ua.address as shipping_address,
            ua.neighborhood,
            ua.gps_latitude as destination_lat,
            ua.gps_longitude as destination_lng
        FROM orders o
        LEFT JOIN order_deliveries od ON od.order_id = o.id
        LEFT JOIN users u ON u.id = od.driver_id
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

    // Determinar si hay tracking activo
    $tracking_active = false;
    if ($order['delivery_status'] === 'in_transit' || $order['delivery_status'] === 'out_for_delivery') {
        $tracking_active = true;
    }

} catch (PDOException $e) {
    error_log("Error al obtener información de rastreo: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al cargar la información de rastreo';
    header('Location: orders.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreo de Pedido #<?= htmlspecialchars($order['order_number']) ?> - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orders.css">
    <style>
        .tracking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .tracking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .tracking-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
        }

        .tracking-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .tracking-info, .tracking-map {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .tracking-info h2, .tracking-map h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .delivery-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detail-item i {
            color: #667eea;
            font-size: 1.2rem;
        }

        .detail-item div {
            flex: 1;
        }

        .detail-item strong {
            display: block;
            color: #333;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-in_transit { background: #cce5ff; color: #004085; }
        .status-out_for_delivery { background: #d1ecf1; color: #0c5460; }

        .delivery-timeline {
            margin-top: 30px;
        }

        .timeline-container {
            position: relative;
        }

        .timeline-progress-bar {
            background: #e9ecef;
            height: 4px;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .timeline-progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .timeline-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .timeline-step.completed .timeline-dot {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .timeline-step.active .timeline-dot {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3);
        }

        .timeline-label {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .timeline-step.completed .timeline-label {
            color: #333;
        }

        .timeline-step.active .timeline-label {
            color: #667eea;
            font-weight: bold;
        }

        .map-container {
            height: 400px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #dee2e6;
        }

        .map-placeholder {
            text-align: center;
            color: #666;
        }

        .map-placeholder i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        .tracking-coordinates {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 15px;
        }

        .tracking-controls {
            margin-top: 20px;
            text-align: center;
        }

        .btn-refresh {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 30px;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .no-tracking {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-tracking i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .tracking-content {
                grid-template-columns: 1fr;
            }

            .timeline-steps {
                flex-direction: column;
                gap: 20px;
            }

            .timeline-step {
                flex-direction: row;
                justify-content: flex-start;
                gap: 15px;
            }

            .timeline-dot {
                flex-shrink: 0;
            }
        }
    </style>
</head>
<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <main class="user-main-content">
            <div class="tracking-container">
                <!-- Header -->
                <div class="tracking-header animate__animated animate__fadeIn">
                    <h1><i class="fas fa-truck"></i> Rastreo de Pedido</h1>
                    <p>Pedido #<?= htmlspecialchars($order['order_number']) ?></p>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error animate__animated animate__fadeIn">
                        <?= $_SESSION['error_message'] ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Contenido -->
                <div class="tracking-content">
                    <?php if ($order['delivery_status']): ?>
                        <!-- Información de entrega -->
                        <div class="tracking-info animate__animated animate__fadeInLeft">
                            <h2><i class="fas fa-info-circle"></i> Información de Entrega</h2>
                            <div class="delivery-details">
                                <div class="detail-item">
                                    <i class="fas fa-truck"></i>
                                    <div>
                                        <strong>Estado:</strong>
                                        <span class="status-badge status-<?= htmlspecialchars($order['delivery_status']) ?>">
                                            <?= getDeliveryStatusText($order['delivery_status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($order['driver_name']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-user"></i>
                                    <div>
                                        <strong>Repartidor:</strong> <?= htmlspecialchars($order['driver_name']) ?>
                                        <?php if ($order['driver_phone']): ?>
                                        <br><small><i class="fas fa-phone"></i> <?= htmlspecialchars($order['driver_phone']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($order['started_at']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Salida:</strong> <?= date('d/m/Y H:i', strtotime($order['started_at'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($order['delivered_at']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <strong>Entregado:</strong> <?= date('d/m/Y H:i', strtotime($order['delivered_at'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address']) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline de entrega -->
                            <div class="delivery-timeline">
                                <h3>Estado de la Entrega</h3>
                                <?= renderDeliveryTimeline($order['delivery_status']) ?>
                            </div>
                        </div>

                        <!-- Mapa de tracking -->
                        <div class="tracking-map animate__animated animate__fadeInRight">
                            <h2><i class="fas fa-map-marked-alt"></i> Ubicación en Tiempo Real</h2>
                            <?php if ($tracking_active && $order['current_lat'] && $order['current_lng']): ?>
                            <div id="tracking-map-container" class="map-container">
                                <div class="map-placeholder">
                                    <i class="fas fa-map-marked-alt"></i>
                                    <h3>Mapa Interactivo</h3>
                                    <p>La ubicación del repartidor se actualizará en tiempo real</p>
                                    <div class="tracking-coordinates">
                                        Lat: <?= $order['current_lat'] ?>, Lng: <?= $order['current_lng'] ?>
                                    </div>
                                </div>
                            </div>
                            <div class="tracking-controls">
                                <button id="refresh-location" class="btn-refresh">
                                    <i class="fas fa-sync-alt"></i> Actualizar Ubicación
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="no-tracking">
                                <i class="fas fa-clock"></i>
                                <h3>Información de ubicación no disponible</h3>
                                <p>El pedido aún no ha salido o la ubicación no está disponible en este momento.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-tracking" style="grid-column: 1 / -1;">
                            <i class="fas fa-info-circle"></i>
                            <h3>Información de entrega no disponible</h3>
                            <p>Esta orden aún no ha sido asignada para entrega. Te notificaremos cuando tengamos más información.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Botón de volver -->
                <div style="text-align: center;">
                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Ver Detalles del Pedido
                    </a>
                </div>
            </div>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';

        <?php if ($tracking_active): ?>
        // Actualización en tiempo real cada 10 segundos
        setInterval(async () => {
            try {
                const response = await fetch(`${BASE_URL}/users/orders/live_tracking.php?id=<?= $orderId ?>`);
                const data = await response.json();

                if (data.success && data.tracking_data) {
                    updateTrackingDisplay(data.tracking_data);
                }
            } catch (error) {
                console.error('Error updating tracking:', error);
            }
        }, 10000);

        // Función para actualizar la información de tracking
        function updateTrackingDisplay(trackingData) {
            const coordinatesElement = document.querySelector('.tracking-coordinates');
            if (coordinatesElement && trackingData.current_lat && trackingData.current_lng) {
                coordinatesElement.textContent = `Lat: ${trackingData.current_lat}, Lng: ${trackingData.current_lng}`;
            }
        }

        // Botón de actualizar ubicación
        document.getElementById('refresh-location')?.addEventListener('click', async () => {
            const button = document.getElementById('refresh-location');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            button.disabled = true;

            try {
                const response = await fetch(`${BASE_URL}/users/orders/live_tracking.php?id=<?= $orderId ?>`);
                const data = await response.json();

                if (data.success && data.tracking_data) {
                    updateTrackingDisplay(data.tracking_data);
                    button.innerHTML = '<i class="fas fa-check"></i> Actualizado';
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 2000);
                }
            } catch (error) {
                console.error('Error refreshing location:', error);
                button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Funciones auxiliares
function getDeliveryStatusText($status) {
    $statuses = [
        'in_transit' => 'En Tránsito',
        'delivered' => 'Entregado',
        'pending' => 'Pendiente',
        'picked_up' => 'Recogido',
        'out_for_delivery' => 'En Reparto',
        'failed_delivery' => 'Entrega Fallida',
        'driver_assigned' => 'Conductor Asignado',
        'driver_accepted' => 'Aceptado por Conductor'
    ];
    return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function renderDeliveryTimeline($status) {
    $steps = [
        'pending' => ['icon' => 'fas fa-clock', 'label' => 'Pendiente'],
        'driver_assigned' => ['icon' => 'fas fa-user-check', 'label' => 'Asignado'],
        'driver_accepted' => ['icon' => 'fas fa-check-circle', 'label' => 'Aceptado'],
        'in_transit' => ['icon' => 'fas fa-truck', 'label' => 'En Tránsito'],
        'out_for_delivery' => ['icon' => 'fas fa-shipping-fast', 'label' => 'En Reparto'],
        'delivered' => ['icon' => 'fas fa-check-circle', 'label' => 'Entregado']
    ];

    $statusOrder = array_keys($steps);
    $currentIndex = array_search($status, $statusOrder);
    if ($currentIndex === false) $currentIndex = 0;

    $progress = (($currentIndex + 1) / count($steps)) * 100;

    $html = '<div class="timeline-container">';
    $html .= '<div class="timeline-progress-bar">';
    $html .= '<div class="timeline-progress-fill" style="width: ' . $progress . '%"></div>';
    $html .= '</div>';
    $html .= '<div class="timeline-steps">';

    foreach ($statusOrder as $index => $step) {
        $isCompleted = $index < $currentIndex;
        $isActive = $index === $currentIndex;
        $class = $isCompleted ? 'completed' : ($isActive ? 'active' : '');

        $html .= '<div class="timeline-step ' . $class . '">';
        $html .= '<div class="timeline-dot"><i class="' . $step['icon'] . '"></i></div>';
        $html .= '<span class="timeline-label">' . $step['label'] . '</span>';
        $html .= '</div>';
    }

    $html .= '</div></div>';
    return $html;
}
?>