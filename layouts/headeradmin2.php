<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

// Obtener información del usuario desde la base de datos
try {
    $userId = $_SESSION['user_id'];
    $query = "SELECT id, name, email, image, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }

    // Verificar si el usuario es administrador
    if ($userData['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/users/formuser.php');
        exit();
    }

    // Contar órdenes nuevas (no vistas por este admin)
    $newOrdersQuery = "
        SELECT COUNT(*) as new_orders
        FROM orders o
        LEFT JOIN order_views ov ON o.id = ov.order_id AND ov.user_id = ?
        WHERE ov.id IS NULL
    ";
    $stmtOrders = $conn->prepare($newOrdersQuery);
    $stmtOrders->execute([$userId]);
    $newOrdersCount = $stmtOrders->fetch(PDO::FETCH_ASSOC)['new_orders'];
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/error.php');
    exit();
}
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>/images/logo.png" alt="Angelow Logo" class="admin-logo">
        <h1>Panel</h1>
        <button class="close-sidebar">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item active">
                <a href="<?= BASE_URL ?>/admin/dashboardadmin.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-tshirt"></i>
                    <span>Productos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/products.php">Todos los productos</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/subproducto.php">Agregar nuevo</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/categoria/categories_list.php">Categorías</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/colecciones/collections_list.php">Colecciones</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/tallas/sizes.php">Tallas</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/inventario/inventory.php">Inventario</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/orders.php">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Órdenes</span>
                    <?php if ($newOrdersCount > 0): ?>
                        <span class="badge" id="orders-badge"><?= $newOrdersCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/clientes/index.php">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-star"></i>
                    <span>Reseñas</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/resenas/index.php">Reseñas</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/resenas/preguntas.php">Preguntas</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/pagos/define_pay.php">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Pagos</span>
                </a>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-truck"></i>
                    <span>Envíos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/envio/shipping_rules.php">Reglas por precio</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/envio/define_shipping.php">Definir envíos</a></li>
                </ul>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-percentage"></i>
                    <span>Descuentos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/descuento/bulk_discounts.php">Descuentos por cantidad</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/descuento/generate_codes.php">Códigos de Descuento</a></li>
                </ul>
            </li>



            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/announcements/list.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Anuncios</span>
                </a>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Informes</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/informes/ventas.php">Ventas</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/informes/productos.php">Productos populares</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/informes/clientes.php">Clientes recurrentes</a></li>
                </ul>
            </li>

            <li class="nav-item with-submenu">
                <div class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </div>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/sliders/sliders_list.php">Sliders</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/settings/general.php">General</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/admins/index.php">
                    <i class="fas fa-user-shield"></i>
                    <span>Administradores</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-profile">
            <?php $adminAvatar = !empty($userData['image']) ? (function_exists('normalizeUserImagePath') ? normalizeUserImagePath($userData['image']) : $userData['image']) : 'images/default-avatar.png'; ?>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($adminAvatar) ?>" alt="Foto de perfil" class="profile-avatar">
            <div class="profile-info">
                <span class="profile-name"><?= htmlspecialchars($userData['name'] ?? 'Administrador') ?></span>
                <span class="profile-email"><?= htmlspecialchars($userData['email'] ?? '') ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Función para marcar el ítem activo según la página actual
        function setActiveMenuItem() {
            const currentPath = window.location.pathname.replace(/\/index\.php$/i, '');

            // Resetear todos los activos
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            const toPath = (href) => {
                try {
                    const url = new URL(href, window.location.origin);
                    return url.pathname.replace(/\/index\.php$/i, '');
                } catch (e) {
                    return href.replace(/\/index\.php$/i, '');
                }
            };

            // Buscar coincidencia en enlaces del submenú
            document.querySelectorAll('.submenu a').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    const linkPath = toPath(href);
                    // Si el path actual esta en el mismo directorio del link entonces marcar activo
                    if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                        const parentMenu = link.closest('.with-submenu');
                        if (parentMenu) {
                            parentMenu.classList.add('active');
                            const submenu = parentMenu.querySelector('.submenu');
                            if (submenu) submenu.style.display = 'block';
                            const toggle = parentMenu.querySelector('.submenu-toggle');
                            if (toggle) toggle.style.transform = 'rotate(180deg)';
                        }
                    }
                }
            });

            // Buscar coincidencia en enlaces principales (sin submenú)
            document.querySelectorAll('.nav-menu > li > a').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    const linkPath = toPath(href);
                    if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                        const parentItem = link.closest('li');
                        if (parentItem) {
                            parentItem.classList.add('active');
                        }
                    }
                }
            });
        }

        // 2. Función para manejar TODOS los submenús al hacer clic en el li completo
        function setupSubmenus() {
            // Manejar clic en los elementos li con submenú
            document.querySelectorAll('.with-submenu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    // Si se hizo clic en un enlace del submenú, permitir navegación
                    if (e.target.closest('.submenu a')) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();

                    const submenu = this.querySelector('.submenu');
                    const toggle = this.querySelector('.submenu-toggle');

                    // Cerrar otros submenús abiertos
                    document.querySelectorAll('.with-submenu').forEach(otherMenu => {
                        if (otherMenu !== this) {
                            otherMenu.classList.remove('active');
                            const otherSubmenu = otherMenu.querySelector('.submenu');
                            if (otherSubmenu) otherSubmenu.style.display = 'none';
                            const otherToggle = otherMenu.querySelector('.submenu-toggle');
                            if (otherToggle) otherToggle.style.transform = 'rotate(0deg)';
                        }
                    });

                    // Alternar el submenú actual
                    this.classList.toggle('active');
                    submenu.style.display = this.classList.contains('active') ? 'block' : 'none';
                    if (toggle) {
                        toggle.style.transform = this.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });
            });
        }

        // 3. Función para manejar el colapso del sidebar (responsive)
        function setupSidebarCollapse() {
            const sidebar = document.querySelector('.admin-sidebar');
            const closeBtn = document.querySelector('.close-sidebar');

            if (!sidebar || !closeBtn) return;

            // Manejar el botón de cerrar
            closeBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });

            // Manejar el comportamiento responsive
            function handleResize() {
                const isMobile = window.innerWidth <= 768;

                if (isMobile) {
                    sidebar.classList.add('mobile');
                } else {
                    sidebar.classList.remove('mobile');
                    sidebar.classList.remove('collapsed');
                }
            }

            // Ejecutar al cargar y redimensionar
            handleResize();
            window.addEventListener('resize', handleResize);
        }

        // 4. Función para manejar clics fuera del sidebar en móvil
        function setupMobileInteractions() {
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.main-content');

            if (!sidebar) return;

            // Cerrar sidebar al hacer clic fuera en móvil
            document.addEventListener('click', function(e) {
                const isMobile = window.innerWidth <= 768;

                if (isMobile && !sidebar.contains(e.target) && sidebar.classList.contains('mobile')) {
                    sidebar.classList.add('collapsed');
                }
            });
        }

        // Inicializar todas las funciones
        setActiveMenuItem();
        setupSubmenus();
        setupSidebarCollapse();
        setupMobileInteractions();
    });
</script>

<!-- Script para el badge de órdenes -->
<script src="<?= BASE_URL ?>/js/admin/orders-badge.js"></script>