<?php
// Obtener la URL actual sin parámetros GET
$current_url = strtok($_SERVER['REQUEST_URI'], '?');
$current_url = str_replace(BASE_URL, '', $current_url);

// Definir las URLs del menú de delivery
$menu_items = [
    'dashboard' => '/delivery/dashboarddeli.php',
    'orders' => '/delivery/orders.php',
    'history' => '/delivery/history.php',
    'settings' => '/delivery/settings.php'
];

// Función para verificar si el ítem del menú está activo
function isDeliveryMenuItemActive($item_url, $current_url)
{
    return strpos($current_url, $item_url) !== false;
}

// Obtener información del usuario actual si no está ya cargada
if (!isset($userData)) {
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
}

// Verificar si hay una entrega activa en tránsito para habilitar navegación
$activeDelivery = null;
try {
    $stmt = $conn->prepare("
        SELECT id, delivery_status 
        FROM order_deliveries 
        WHERE driver_id = ? 
        AND delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
        ORDER BY started_at DESC, accepted_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activeDelivery = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al verificar entrega activa: " . $e->getMessage());
}
?>

<!-- Sidebar de Delivery -->
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
            <li class="<?= isDeliveryMenuItemActive($menu_items['dashboard'], $current_url) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?><?= $menu_items['dashboard'] ?>">
                    <i class="fas fa-tachometer-alt"></i> Resumen
                </a>
            </li>
            <li class="<?= isDeliveryMenuItemActive($menu_items['orders'], $current_url) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?><?= $menu_items['orders'] ?>">
                    <i class="fas fa-shopping-bag"></i> Órdenes
                </a>
            </li>
            <li class="<?= strpos($current_url, '/delivery/navigation.php') !== false ? 'active' : '' ?>">
                <?php if ($activeDelivery && in_array($activeDelivery['delivery_status'], ['driver_accepted', 'in_transit', 'arrived'])): ?>
                    <a href="<?= BASE_URL ?>/delivery/navigation.php?delivery_id=<?= $activeDelivery['id'] ?>">
                        <i class="fas fa-map-marked-alt"></i> Navegación
                        <?php if ($activeDelivery['delivery_status'] === 'in_transit'): ?>
                            <span style="display:inline-block;width:8px;height:8px;background:#4caf50;border-radius:50%;margin-left:5px;"></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/delivery/dashboarddeli.php" style="opacity: 0.6;">
                        <i class="fas fa-map-marked-alt"></i> Navegación
                    </a>
                <?php endif; ?>
            </li>
            <li class="<?= isDeliveryMenuItemActive($menu_items['history'], $current_url) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?><?= $menu_items['history'] ?>">
                    <i class="fas fa-history"></i> Historial
                </a>
            </li>
            <li class="<?= isDeliveryMenuItemActive($menu_items['settings'], $current_url) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?><?= $menu_items['settings'] ?>">
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
