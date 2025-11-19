<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../layouts/headerproducts.php';

// Verificar que el usuario esté logueado
requireRole(['user', 'customer', 'admin']);

// Usar $conn de conexion.php
$pdo = $conn;
$user_id = $_SESSION['user_id'];

// Preferencias para filtrar notificaciones
$disabled_type_ids = [];
try {
    $prefs_stmt = $pdo->prepare("SELECT type_id, push_enabled FROM notification_preferences WHERE user_id = ?");
    $prefs_stmt->execute([$user_id]);
    while ($pref = $prefs_stmt->fetch(PDO::FETCH_ASSOC)) {
        if ((int) ($pref['push_enabled'] ?? 1) === 0) {
            $disabled_type_ids[] = (int) $pref['type_id'];
        }
    }
} catch (PDOException $e) {
    error_log('Error al obtener preferencias de notificación: ' . $e->getMessage());
}

// Obtener el total de notificaciones no leídas
try {
    $unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
    $unread_params = [$user_id];

    if ($disabled_type_ids) {
        $placeholders = implode(',', array_fill(0, count($disabled_type_ids), '?'));
        $unread_sql .= " AND type_id NOT IN ($placeholders)";
        $unread_params = array_merge($unread_params, $disabled_type_ids);
    }

    $stmt_unread = $pdo->prepare($unread_sql);
    $stmt_unread->execute($unread_params);
    $unread_count = $stmt_unread->fetch(PDO::FETCH_ASSOC)['unread'];
} catch (PDOException $e) {
    $unread_count = 0;
    error_log("Error al obtener notificaciones no leídas: " . $e->getMessage());
}

// Opcional: marcar todas como leídas al entrar. Se deja deshabilitado para que
// el usuario decida manualmente (botón "Marcar todas como leídas").
// Si quieres volver al comportamiento anterior, descomenta el siguiente bloque.
/*
if ($unread_count > 0) {
    try {
        $stmt_mark_read = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt_mark_read->execute([$user_id]);
        $unread_count = 0; // Actualizar el conteo localmente
    } catch (PDOException $e) {
        error_log("Error al marcar notificaciones como leídas: " . $e->getMessage());
    }
}
*/

// Filtros
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Construir query base
$sql = "SELECT n.*, nt.name as type_name, nt.description as type_description
        FROM notifications n
        LEFT JOIN notification_types nt ON n.type_id = nt.id
        WHERE n.user_id = ?";

$params = [$user_id];

// Aplicar filtro de lectura
if ($filter === 'unread') {
    $sql .= " AND n.is_read = 0";
} elseif ($filter === 'read') {
    $sql .= " AND n.is_read = 1";
}

// Aplicar filtro de tipo
if ($type_filter !== 'all') {
    $sql .= " AND n.related_entity_type = ?";
    $params[] = $type_filter;
}

if ($disabled_type_ids) {
    $placeholders = implode(',', array_fill(0, count($disabled_type_ids), '?'));
    $sql .= " AND n.type_id NOT IN ($placeholders)";
    $params = array_merge($params, $disabled_type_ids);
}

$sql .= " ORDER BY n.created_at DESC LIMIT 50";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
    error_log("Error al obtener notificaciones: " . $e->getMessage());
}

// Obtener tipos de notificación para el filtro
try {
    $stmt_types = $pdo->query("SELECT DISTINCT related_entity_type FROM notifications WHERE user_id = '$user_id' AND related_entity_type IS NOT NULL");
    $notification_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $notification_types = [];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Angelow</title>
    <meta name="description" content="Gestiona tus notificaciones en Angelow">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/user/notifications.css">
</head>

<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <!-- Contenido principal -->
        <main class="user-main-content notifications-page">
            <div class="notifications-header">
                <h1><i class="fas fa-bell"></i> Mis Notificaciones</h1>
                <p>Mantente al día con tus pedidos y actualizaciones</p>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="notifications-stats">
                <div class="stat-card">
                    <i class="fas fa-envelope"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= count($notifications) ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                </div>
                <div class="stat-card unread">
                    <i class="fas fa-envelope-open"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= $unread_count ?></span>
                        <span class="stat-label">Sin leer</span>
                    </div>
                </div>
                <div class="stat-card read">
                    <i class="fas fa-check-double"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= count($notifications) ?></span>
                        <span class="stat-label">Leídas</span>
                    </div>
                </div>
            </div>

            <!-- Filtros y acciones -->
            <div class="notifications-toolbar">
                <div class="filters">
                    <select id="filter-status" class="filter-select" onchange="applyFilters()">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Todas</option>
                        <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>No leídas</option>
                        <option value="read" <?= $filter === 'read' ? 'selected' : '' ?>>Leídas</option>
                    </select>

                    <select id="filter-type" class="filter-select" onchange="applyFilters()">
                        <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>Todos los tipos</option>
                        <option value="order" <?= $type_filter === 'order' ? 'selected' : '' ?>>Pedidos</option>
                        <option value="product" <?= $type_filter === 'product' ? 'selected' : '' ?>>Productos</option>
                        <option value="promotion" <?= $type_filter === 'promotion' ? 'selected' : '' ?>>Promociones</option>
                        <option value="system" <?= $type_filter === 'system' ? 'selected' : '' ?>>Sistema</option>
                        <option value="account" <?= $type_filter === 'account' ? 'selected' : '' ?>>Cuenta</option>
                    </select>
                </div>

                <div class="actions">
                    <button id="btn-mark-all" class="btn-action" onclick="markAllAsRead()" title="Marcar todas como leídas" aria-label="Marcar todas como leídas">
                        <i class="fas fa-check-double"></i>
                        Marcar todas como leídas
                    </button>
                </div>
            </div>

            <!-- Lista de notificaciones -->
            <div class="notifications-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= ((int) ($notification['is_read'] ?? 0) === 1) ? 'read' : 'unread' ?>" data-id="<?= $notification['id'] ?>" data-type="<?= htmlspecialchars($notification['related_entity_type'] ?? '') ?>">
                            <div class="notification-icon <?= $notification['related_entity_type'] ?? 'system' ?>">
                                <?php
                                $icon = 'fa-bell';
                                switch ($notification['related_entity_type']) {
                                    case 'order':
                                        $icon = 'fa-shopping-bag';
                                        break;
                                    case 'product':
                                        $icon = 'fa-tag';
                                        break;
                                    case 'promotion':
                                        $icon = 'fa-gift';
                                        break;
                                    case 'account':
                                        $icon = 'fa-user';
                                        break;
                                    case 'system':
                                        $icon = 'fa-info-circle';
                                        break;
                                }
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </div>

                            <div class="notification-content" onclick="viewNotification(<?= $notification['id'] ?>, '<?= $notification['related_entity_type'] ?>', <?= $notification['related_entity_id'] ?? 'null' ?>)">
                                <div class="notification-header">
                                    <h3>
                                        <?= htmlspecialchars($notification['title']) ?>
                                        <?php if (!empty($notification['type_name']) || !empty($notification['related_entity_type'])): ?>
                                            <span class="notification-type"><?= htmlspecialchars($notification['type_name'] ?? ucfirst($notification['related_entity_type'])) ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="notification-time">
                                        <i class="far fa-clock"></i>
                                        <?= formatTimeAgo($notification['created_at']) ?>
                                    </span>
                                </div>
                                <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                
                                <?php if ($notification['related_entity_type'] === 'order' && $notification['related_entity_id']): ?>
                                    <a href="<?= BASE_URL ?>/users/order_detail.php?id=<?= $notification['related_entity_id'] ?>" class="notification-link">
                                        Ver pedido <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                                <div class="notification-actions">
                                <button class="btn-delete" onclick="deleteNotification(<?= $notification['id'] ?>)" title="Eliminar" aria-label="Eliminar notificación">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No tienes notificaciones</h3>
                        <p>Cuando tengas actualizaciones importantes, aparecerán aquí</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/notifications.js"></script>
    <script>
        function applyFilters() {
            const status = document.getElementById('filter-status').value;
            const type = document.getElementById('filter-type').value;
            window.location.href = `<?= BASE_URL ?>/users/notifications.php?filter=${status}&type=${type}`;
        }

        function markAsRead(notificationId) {
            fetch('<?= BASE_URL ?>/users/api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        const markReadBtn = item.querySelector('.btn-mark-read');
                        if (markReadBtn) markReadBtn.remove();
                        updateUnreadCount();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteNotification(notificationId) {
            if (!confirm('¿Eliminar esta notificación?')) return;

            fetch('<?= BASE_URL ?>/users/api/delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    item.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => item.remove(), 300);
                    updateUnreadCount();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function viewNotification(notificationId, type, entityId) {
            // No necesitamos marcar como leída ya que todas se marcan automáticamente al cargar

            if (type === 'order' && entityId) {
                window.location.href = `<?= BASE_URL ?>/users/order_detail.php?id=${entityId}`;
            }
        }

        function updateUnreadCount() {
            // Todas las notificaciones ya están marcadas como leídas al cargar la página
            // Esta función se mantiene por compatibilidad con acciones futuras
            const totalItems = document.querySelectorAll('.notification-item').length;
            document.querySelector('.stat-card.unread .stat-number').textContent = '0';
            document.querySelector('.stat-card.read .stat-number').textContent = totalItems;
        }

        function markAllAsRead() {
            if (!confirm('¿Marcar todas las notificaciones como leídas?')) return;

            fetch('<?= BASE_URL ?>/users/api/mark_all_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                    });
                    updateUnreadCount();
                    alert('Todas las notificaciones fueron marcadas como leídas.');
                } else {
                    alert(data.message || 'No se pudieron marcar todas como leídas');
                }
            })
            .catch(err => console.error('Error:', err));
        }
    </script>
</body>

</html>

<?php
// Función helper para formatear tiempo
function formatTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Hace ' . $diff . ' segundo' . ($diff != 1 ? 's' : '');
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return 'Hace ' . $mins . ' minuto' . ($mins != 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Hace ' . $hours . ' hora' . ($hours != 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'Hace ' . $days . ' día' . ($days != 1 ? 's' : '');
    } else {
        return date('d/m/Y H:i', $timestamp);
    }
}
?>
