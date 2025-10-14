<?php
// Este archivo se usa como include o via AJAX
if (!isset($conn) || !isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) {
    die(json_encode(['success' => false, 'message' => 'ID inválido']));
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
        die(json_encode(['success' => false, 'message' => 'Orden no encontrada']));
    }

    // Determinar si hay tracking activo
    $tracking_active = false;
    if ($order['delivery_status'] === 'in_transit' || $order['delivery_status'] === 'out_for_delivery') {
        $tracking_active = true;
    }

    // Generar HTML para el modal de tracking
    ob_start();
    ?>
    <div class="tracking-header">
        <h2>Rastreo de Orden #<?= htmlspecialchars($order['order_number']) ?></h2>
        <div class="order-info">
            <p><strong>Estado de la orden:</strong>
                <span class="status-badge status-<?= htmlspecialchars($order['order_status']) ?>">
                    <?= getStatusText($order['order_status']) ?>
                </span>
            </p>
            <p><strong>Fecha del pedido:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>
    </div>

    <div class="tracking-content">
        <?php if ($order['delivery_status']): ?>
            <div class="delivery-info">
                <h3>Información de Entrega</h3>
                <div class="delivery-details">
                    <div class="detail-item">
                        <i class="fas fa-truck"></i>
                        <div>
                            <strong>Estado:</strong>
                            <span class="delivery-status status-<?= htmlspecialchars($order['delivery_status']) ?>">
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
                            <strong>Dirección:</strong> <?= htmlspecialchars($order['shipping_address'] . ', ' . $order['neighborhood']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline de entrega -->
            <div class="delivery-timeline">
                <h3>Estado de la Entrega</h3>
                <?= renderDeliveryTimeline($order['delivery_status']) ?>
            </div>

            <!-- Mapa de tracking (si está en tránsito) -->
            <?php if ($tracking_active && $order['current_lat'] && $order['current_lng']): ?>
            <div class="tracking-map">
                <h3>Ubicación en Tiempo Real</h3>
                <div id="tracking-map-container" class="map-container">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>Cargando mapa...</p>
                        <div class="tracking-coordinates">
                            Lat: <?= $order['current_lat'] ?>, Lng: <?= $order['current_lng'] ?>
                        </div>
                    </div>
                </div>
                <div class="tracking-controls">
                    <button id="refresh-location" class="btn-secondary">
                        <i class="fas fa-sync-alt"></i> Actualizar Ubicación
                    </button>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-delivery-info">
                <i class="fas fa-info-circle"></i>
                <h3>Información de entrega no disponible</h3>
                <p>Esta orden aún no ha sido asignada para entrega. Te notificaremos cuando tengamos más información.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_clean();
    echo json_encode([
        'success' => true,
        'html' => $html,
        'tracking_active' => $tracking_active,
        'tracking_data' => $tracking_active ? [
            'current_lat' => $order['current_lat'],
            'current_lng' => $order['current_lng'],
            'destination_lat' => $order['destination_lat'],
            'destination_lng' => $order['destination_lng'],
            'driver_name' => $order['driver_name']
        ] : null
    ]);
} catch (PDOException $e) {
    error_log("Error en track_order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar información de rastreo']);
}

// Funciones auxiliares
function getStatusText($status) {
    $statuses = [
        'pending' => 'Pendiente',
        'processing' => 'En Proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado'
    ];
    return $statuses[$status] ?? ucfirst($status);
}

function getDeliveryStatusText($status) {
    $statuses = [
        'pending' => 'Pendiente',
        'assigned' => 'Asignado',
        'driver_assigned' => 'Conductor Asignado',
        'driver_accepted' => 'Aceptado por Conductor',
        'in_transit' => 'En Tránsito',
        'out_for_delivery' => 'En Reparto',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'failed_delivery' => 'Entrega Fallida'
    ];
    return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function renderDeliveryTimeline($status) {
    $steps = [
        'pending' => ['icon' => 'fas fa-clock', 'label' => 'Pendiente'],
        'assigned' => ['icon' => 'fas fa-user-check', 'label' => 'Asignado'],
        'driver_assigned' => ['icon' => 'fas fa-user-check', 'label' => 'Conductor Asignado'],
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
    $html .= '<div class="timeline-progress-bar"><div class="timeline-progress-fill" style="width: ' . $progress . '%"></div></div>';
    $html .= '<div class="timeline-steps">';

    foreach ($statusOrder as $index => $step) {
        $isCompleted = $index < $currentIndex;
        $isActive = $index === $currentIndex;
        $class = $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending');

        $html .= '<div class="timeline-step ' . $class . '">';
        $html .= '<div class="timeline-dot"><i class="' . $step['icon'] . '"></i></div>';
        $html .= '<span class="timeline-label">' . $step['label'] . '</span>';
        $html .= '</div>';
    }

    $html .= '</div></div>';
    return $html;
}
?>