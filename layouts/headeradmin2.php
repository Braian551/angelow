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
                <a href="#productos">
                    <i class="fas fa-tshirt"></i>
                    <span>Productos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/products.php">Todos los productos</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/subproducto.php">Agregar nuevo</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/categoria/categories_list.php">Categorías</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/tallas/sizes.php">Tallas</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/inventario/inventory.php">Inventario</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/orders.php">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Órdenes</span>
                    <span class="badge">15</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#clientes">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#reseñas">
                    <i class="fas fa-star"></i>
                    <span>Reseñas</span>
                </a>
            </li>

            <!-- Nueva sección de Configuraciones de Envíos -->
            <li class="nav-item with-submenu">
                <a href="#envios">
                    <i class="fas fa-truck"></i>
                    <span>Envíos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/envio/shipping_rules.php">Reglas por precio</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/envio/definirenvi.php">Definir envíos</a></li>
                </ul>
            </li>

            <li class="nav-item with-submenu">
                <a href="#descuento">
                    <i class="fas fa-percentage"></i>
                    <span>Descuentos</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/descuento/bulk_discounts.php">Descuentos por cantidad</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/descuento/generate_codes.php">Códigos de Descuento</a></li>
                </ul>
            </li>

            <li class="nav-item with-submenu">
                <a href="#noticias">
                    <i class="fas fa-newspaper"></i>
                    <span>Noticias</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= BASE_URL ?>/admin/news/news_list.php">Todas las noticias</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/news/add_news.php">Agregar noticia</a></li>
                </ul>
            </li>

            <li class="nav-item with-submenu">
                <a href="#informes">
                    <i class="fas fa-chart-line"></i>
                    <span>Informes</span>
                    <i class="fas fa-chevron-down submenu-toggle"></i>
                </a>
                <ul class="submenu">
                    <li><a href="#ventas">Ventas</a></li>
                    <li><a href="#productos-populares">Productos populares</a></li>
                    <li><a href="#clientes-recurrentes">Clientes recurrentes</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="#configuracion">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#administradores">
                    <i class="fas fa-user-shield"></i>
                    <span>Administradores</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-profile">
            <img src="<?= BASE_URL ?>/<?php echo !empty($userData['image']) ? htmlspecialchars($userData['image']) : 'images/default-avatar.png'; ?>" alt="Foto de perfil" class="profile-avatar">
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
            const currentPath = window.location.pathname;
            const currentPage = currentPath.split('/').pop();

            // Resetear todos los activos
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Buscar coincidencia en todos los niveles
            document.querySelectorAll('.nav-menu a').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    if (currentPath.includes(href) || currentPage === href.split('/').pop()) {
                        // Marcar el ítem como activo
                        const parentItem = link.closest('li');
                        if (parentItem) {
                            parentItem.classList.add('active');

                            // Si está en un submenú, abrir el menú padre
                            const parentMenu = parentItem.closest('.with-submenu');
                            if (parentMenu) {
                                parentMenu.classList.add('active');
                                const submenu = parentMenu.querySelector('.submenu');
                                if (submenu) submenu.style.display = 'block';
                                const toggle = parentMenu.querySelector('.submenu-toggle');
                                if (toggle) toggle.style.transform = 'rotate(180deg)';
                            }
                        }
                    }
                }
            });
        }

        // 2. Función mejorada para manejar TODOS los submenús al hacer clic en el icono
        function setupSubmenus() {
            // Manejar clic en los iconos de submenú
            document.querySelectorAll('.submenu-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const menu = this.closest('.with-submenu');
                    const submenu = menu.querySelector('.submenu');

                    // Cerrar otros submenús abiertos
                    document.querySelectorAll('.with-submenu').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.classList.remove('active');
                            const otherSubmenu = otherMenu.querySelector('.submenu');
                            if (otherSubmenu) otherSubmenu.style.display = 'none';
                            const otherToggle = otherMenu.querySelector('.submenu-toggle');
                            if (otherToggle) otherToggle.style.transform = 'rotate(0deg)';
                        }
                    });

                    // Alternar el submenú actual
                    menu.classList.toggle('active');
                    submenu.style.display = menu.classList.contains('active') ? 'block' : 'none';
                    this.style.transform = menu.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
                });
            });

            // Manejar clic en los enlaces principales (para navegación)
            document.querySelectorAll('.with-submenu > a').forEach(link => {
                if (link.getAttribute('href') !== '#') {
                    link.addEventListener('click', function(e) {
                        // Solo si no se hizo clic en el toggle
                        if (!e.target.classList.contains('submenu-toggle')) {
                            // Cerrar todos los submenús al navegar
                            document.querySelectorAll('.with-submenu').forEach(menu => {
                                menu.classList.remove('active');
                                const submenu = menu.querySelector('.submenu');
                                if (submenu) submenu.style.display = 'none';
                                const toggle = menu.querySelector('.submenu-toggle');
                                if (toggle) toggle.style.transform = 'rotate(0deg)';
                            });
                        }
                    });
                }
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