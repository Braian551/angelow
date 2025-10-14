<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de delivery
requireRole('delivery');

$driverId = $_SESSION['user_id'];

// DEBUG: Log para ver qué está pasando
error_log("Navigation.php - Driver ID: " . $driverId);
error_log("Navigation.php - Delivery ID solicitado: " . ($_GET['delivery_id'] ?? 'ninguno'));

// Obtener delivery_id de la URL
if (!isset($_GET['delivery_id']) || empty($_GET['delivery_id'])) {
    // Si no hay delivery_id, buscar la última entrega activa
    try {
        $stmt = $conn->prepare("
            SELECT id 
            FROM order_deliveries 
            WHERE driver_id = ? 
            AND delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
            ORDER BY started_at DESC, accepted_at DESC
            LIMIT 1
        ");
        $stmt->execute([$driverId]);
        $activeDelivery = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($activeDelivery) {
            header('Location: ' . BASE_URL . '/delivery/navigation.php?delivery_id=' . $activeDelivery['id']);
            exit();
        } else {
            // No hay entregas activas, redirigir al dashboard
            $_SESSION['error_message'] = 'No tienes ninguna entrega activa. Acepta e inicia una orden primero.';
            header('Location: ' . BASE_URL . '/delivery/dashboarddeli.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error buscando entrega activa: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/delivery/dashboarddeli.php');
        exit();
    }
}

$deliveryId = intval($_GET['delivery_id']);

// Verificar que el delivery pertenece al driver actual
try {
    $stmt = $conn->prepare("
        SELECT 
            od.id,
            od.order_id,
            od.delivery_status,
            od.current_lat,
            od.current_lng,
            od.destination_lat,
            od.destination_lng,
            o.order_number,
            o.shipping_address,
            o.shipping_city,
            o.total,
            o.delivery_notes,
            CONCAT(u.name) AS customer_name,
            u.phone AS customer_phone
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        INNER JOIN users u ON o.user_id = u.id
        WHERE od.id = ? 
        AND od.driver_id = ?
        AND od.delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
    ");
    $stmt->execute([$deliveryId, $driverId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$delivery) {
        // Verificar si existe pero no está en el estado correcto
        $stmt = $conn->prepare("
            SELECT delivery_status 
            FROM order_deliveries 
            WHERE id = ? AND driver_id = ?
        ");
        $stmt->execute([$deliveryId, $driverId]);
        $deliveryCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deliveryCheck) {
            $_SESSION['error_message'] = 'Esta entrega ya no está activa. Estado: ' . $deliveryCheck['delivery_status'];
        } else {
            $_SESSION['error_message'] = 'Esta entrega no te pertenece o no existe.';
        }
        
        header('Location: ' . BASE_URL . '/delivery/dashboarddeli.php');
        exit();
    }
    
    // Agregar valores por defecto para compatibilidad
    $delivery['shipping_state'] = '';
    $delivery['shipping_zip'] = '';
    
} catch (PDOException $e) {
    error_log("Error en navigation.php: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    $_SESSION['error_message'] = 'Error al cargar la navegación: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/delivery/dashboarddeli.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Navegación - Orden #<?= htmlspecialchars($delivery['order_number']) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/delivery/navigation.css">
</head>
<body>
    <!-- Header de navegación -->
    <div class="nav-header">
        <button class="btn-back" onclick="confirmExit()">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div class="nav-title">
            <h1>Orden #<?= htmlspecialchars($delivery['order_number']) ?></h1>
            <p id="nav-status">Preparando ruta...</p>
        </div>
        <button class="btn-menu" onclick="toggleMenu()">
            <i class="fas fa-ellipsis-v"></i>
        </button>
    </div>

    <!-- Mapa -->
    <div id="map"></div>

    <!-- Panel de información inferior -->
    <div class="nav-panel" id="nav-panel">
        <!-- Panel compacto (por defecto visible) -->
        <div class="panel-compact" id="panel-compact" style="display: block;">
            <div class="panel-handle" onclick="togglePanel()">
                <div class="handle-bar"></div>
            </div>
            
            <div class="compact-info">
                <div class="instruction-container">
                    <div class="instruction-icon" id="instruction-icon">
                        <i class="fas fa-location-arrow"></i>
                    </div>
                    <div class="instruction-text">
                        <div class="instruction-main" id="instruction-main">Calculando ruta...</div>
                        <div class="instruction-distance" id="instruction-distance">--</div>
                    </div>
                </div>
                
                <div class="eta-container">
                    <div class="eta-time" id="eta-time">--</div>
                    <div class="eta-label">min</div>
                </div>
            </div>
            
            <div class="stats-row">
                <div class="stat-item">
                    <i class="fas fa-route"></i>
                    <span id="distance-remaining">-- km</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span id="current-speed">-- km/h</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span id="arrival-time">--:--</span>
                </div>
            </div>
        </div>

        <!-- Panel expandido (oculto por defecto) -->
        <div class="panel-expanded" id="panel-expanded" style="display: none;">
            <div class="panel-handle" onclick="togglePanel()">
                <div class="handle-bar"></div>
            </div>
            
            <div class="expanded-header">
                <h2>Información del Pedido</h2>
                <button class="btn-close-panel" onclick="togglePanel()">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <div class="expanded-content">
                <div class="info-section">
                    <div class="info-label">
                        <i class="fas fa-user-circle"></i>
                        Cliente
                    </div>
                    <div class="info-value"><?= htmlspecialchars($delivery['customer_name']) ?></div>
                    <a href="tel:<?= htmlspecialchars($delivery['customer_phone']) ?>" class="btn-call">
                        <i class="fas fa-phone"></i> Llamar
                    </a>
                </div>
                
                <div class="info-section">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Dirección de entrega
                    </div>
                    <div class="info-value">
                        <?= htmlspecialchars($delivery['shipping_address']) ?><br>
                        <?= htmlspecialchars($delivery['shipping_city']) ?>, <?= htmlspecialchars($delivery['shipping_state']) ?>
                    </div>
                </div>
                
                <?php if (!empty($delivery['delivery_notes'])): ?>
                <div class="info-section">
                    <div class="info-label">
                        <i class="fas fa-sticky-note"></i>
                        Notas de entrega
                    </div>
                    <div class="info-value notes"><?= nl2br(htmlspecialchars($delivery['delivery_notes'])) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-section">
                    <div class="info-label">
                        <i class="fas fa-dollar-sign"></i>
                        Monto total
                    </div>
                    <div class="info-value price">
                        <?= number_format($delivery['total'], 0, ',', '.') ?> COP
                    </div>
                </div>
            </div>
            
            <div class="expanded-actions">
                <button class="btn-action btn-primary" id="btn-action-main" onclick="handleMainAction()">
                    <i class="fas fa-play-circle"></i>
                    <span id="btn-action-text">Iniciar Navegación</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Botones de acción flotantes -->
    <div class="floating-actions">
        <button class="fab" id="btn-center" onclick="centerOnDriver()" title="Centrar en mi ubicación">
            <i class="fas fa-crosshairs"></i>
        </button>
        
        <button class="fab" id="btn-voice" onclick="toggleVoice()" title="Instrucciones de voz">
            <i class="fas fa-volume-up"></i>
        </button>
        
        <button class="fab" id="btn-traffic" onclick="toggleTraffic()" title="Ver tráfico">
            <i class="fas fa-traffic-light"></i>
        </button>
    </div>

    <!-- Menú de opciones -->
    <div class="menu-overlay" id="menu-overlay" onclick="toggleMenu()"></div>
    <div class="menu-drawer" id="menu-drawer">
        <div class="menu-header">
            <h3>Opciones</h3>
            <button class="btn-close" onclick="toggleMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="menu-content">
            <button class="menu-item" onclick="recalculateRoute()">
                <i class="fas fa-route"></i>
                Recalcular ruta
            </button>
            <button class="menu-item" onclick="reportIssue()">
                <i class="fas fa-exclamation-triangle"></i>
                Reportar problema
            </button>
            <button class="menu-item" onclick="viewOrderDetails()">
                <i class="fas fa-info-circle"></i>
                Detalles del pedido
            </button>
            <button class="menu-item danger" onclick="cancelNavigation()">
                <i class="fas fa-times-circle"></i>
                Cancelar navegación
            </button>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="notification-container"></div>

    <!-- Datos del delivery (JSON) -->
    <script id="delivery-data" type="application/json">
        <?= json_encode([
            'delivery_id' => $delivery['id'],
            'order_id' => $delivery['order_id'],
            'order_number' => $delivery['order_number'],
            'status' => $delivery['delivery_status'],
            'destination' => [
                'lat' => floatval($delivery['destination_lat'] ?? 0),
                'lng' => floatval($delivery['destination_lng'] ?? 0),
                'address' => $delivery['shipping_address'],
                'city' => $delivery['shipping_city']
            ],
            'customer' => [
                'name' => $delivery['customer_name'],
                'phone' => $delivery['customer_phone']
            ],
            'driver_id' => $driverId
        ]) ?>
    </script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Routing Machine (OSRM) -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <!-- Voice Helper - Sistema de voz con múltiples motores -->
    <script src="<?= BASE_URL ?>/js/delivery/voice-helper.js"></script>
    
    <!-- Navigation Session Manager - Sistema de persistencia -->
    <script src="<?= BASE_URL ?>/js/delivery/navigation-session.js"></script>
    
    <!-- Custom Navigation JS -->
    <script src="<?= BASE_URL ?>/js/delivery/navigation.js"></script>
    <script src="<?= BASE_URL ?>/js/delivery/navigation_fix.js"></script>
    
    <!-- Navigation State Restore - Restaurar estado al cargar página -->
    <script src="<?= BASE_URL ?>/js/delivery/navigation-restore.js"></script>
</body>
</html>
