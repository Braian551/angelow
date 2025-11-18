<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/../helpers/admin_header_widgets.php';

$baseUrl = rtrim(BASE_URL ?? '', '/');
$quickActions = getAdminQuickActions();
$searchEndpoint = $baseUrl . '/admin/api/dashboard/global_search.php';
$notificationsEndpoint = $baseUrl . '/admin/api/dashboard/notifications.php';
?>
<header
    class="admin-header"
    data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES) ?>"
    data-search-endpoint="<?= htmlspecialchars($searchEndpoint, ENT_QUOTES) ?>"
    data-notifications-endpoint="<?= htmlspecialchars($notificationsEndpoint, ENT_QUOTES) ?>"
>
    <div class="header-left">
        <button class="sidebar-toggle" type="button" aria-label="Abrir menu lateral">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
        <h2>Angelow</h2>
    </div>

    <div class="header-right">
        <div class="search-box" id="admin-search" role="search">
            <label class="sr-only" for="admin-search-input">Buscar en el panel</label>
            <input
                type="search"
                id="admin-search-input"
                placeholder="Buscar pedidos, clientes o modulos"
                autocomplete="off"
                spellcheck="false"
            >
            <button type="button" id="admin-search-btn" aria-label="Buscar">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
            <div class="search-results-panel" id="admin-search-results" aria-live="polite" hidden>
                <p class="dropdown-empty">Escribe al menos 2 letras para comenzar.</p>
            </div>
        </div>

        <div class="header-actions">
            <div class="header-action">
                <button
                    class="notification-btn"
                    id="admin-notification-btn"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span class="badge" data-notification-count hidden>0</span>
                    <span class="sr-only" data-notification-label>Sin notificaciones pendientes</span>
                </button>
                <div class="header-dropdown notifications-panel" id="admin-notifications-panel" role="menu" hidden>
                    <div class="dropdown-header">
                        <div>
                            <h4>Notificaciones</h4>
                            <p class="dropdown-subtitle">Eventos recientes del sistema</p>
                        </div>
                        <div class="dropdown-actions">
                            <button type="button" class="link-button" data-notification-refresh>Actualizar</button>
                            <button type="button" class="link-button" data-notification-mark-all>Marcar todo</button>
                        </div>
                    </div>
                    <div class="dropdown-body" data-notifications-container>
                        <p class="dropdown-empty">Cargando notificaciones...</p>
                    </div>
                </div>
            </div>

            <div class="header-action">
                <button
                    class="quick-action-btn"
                    id="admin-quick-action-btn"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span>Accion rapida</span>
                </button>
                <div class="header-dropdown quick-actions-panel" id="admin-quick-actions-panel" hidden>
                    <div class="dropdown-header">
                        <div>
                            <h4>Acciones rapidas</h4>
                            <p class="dropdown-subtitle">Atajo Ctrl + K</p>
                        </div>
                        <button type="button" class="link-button" data-quick-actions-close>Cerrar</button>
                    </div>
                    <div class="quick-actions-search">
                        <label class="sr-only" for="quick-actions-filter">Filtrar acciones</label>
                        <input type="search" id="quick-actions-filter" placeholder="Filtrar acciones" autocomplete="off">
                    </div>
                    <div class="dropdown-body">
                        <ul class="quick-actions-list" data-quick-actions-container>
                            <?php foreach ($quickActions as $action): ?>
                                <li class="quick-action-item" data-action-id="<?= htmlspecialchars($action['id'], ENT_QUOTES) ?>">
                                    <a href="<?= htmlspecialchars($baseUrl . $action['path'], ENT_QUOTES) ?>" class="quick-action-link">
                                        <span class="icon" aria-hidden="true"><i class="fas <?= htmlspecialchars($action['icon'], ENT_QUOTES) ?>"></i></span>
                                        <div>
                                            <strong><?= htmlspecialchars($action['label']) ?></strong>
                                            <p><?= htmlspecialchars($action['description']) ?></p>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<script id="admin-header-quick-actions-data" type="application/json">
<?= json_encode($quickActions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<script defer src="<?= htmlspecialchars($baseUrl, ENT_QUOTES) ?>/js/admin/header-widgets.js"></script>