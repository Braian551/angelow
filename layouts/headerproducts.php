<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../settings/site_settings.php';

// Aplicar control de acceso basado en roles
enforceRoleAccess();

// Obtener configuraciones del sitio
$siteSettings = fetch_site_settings($conn);
$logoUrl = !empty($siteSettings['brand_logo']) ? BASE_URL . '/' . $siteSettings['brand_logo'] : BASE_URL . '/images/logo2.png';
$storeName = $siteSettings['store_name'] ?? 'Angelow - Ropa Infantil';

// Obtener el conteo del carrito si no está en sesión
if (!isset($_SESSION['cart_count'])) {
    require_once __DIR__ . '/../config.php';
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();

    try {
        $cartQuery = "SELECT c.id FROM carts c WHERE ";
        if ($user_id) {
            $cartQuery .= "c.user_id = :user_id";
            $params = [':user_id' => $user_id];
        } else {
            $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
            $params = [':session_id' => $session_id];
        }
        $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";

        $stmt = $conn->prepare($cartQuery);
        $stmt->execute($params);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        $itemCount = 0;

        if ($cart) {
            $itemsQuery = "SELECT SUM(quantity) as total_items FROM cart_items WHERE cart_id = :cart_id";
            $stmt = $conn->prepare($itemsQuery);
            $stmt->execute([':cart_id' => $cart['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $itemCount = $result['total_items'] ?? 0;
        }

        $_SESSION['cart_count'] = $itemCount;
    } catch (PDOException $e) {
        $_SESSION['cart_count'] = 0;
        error_log("Error al obtener conteo del carrito: " . $e->getMessage());
    }
}

// Obtener el término de búsqueda actual si existe
$currentSearch = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/user/alert_user.css">
<header class="main-header">
    <div class="header-container">
        <!-- Logo -->
        <div class="content-logo2">
            <a href="<?= BASE_URL ?>/index.html">
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($storeName) ?>" width="100">
            </a>
        </div>

        <div class="search-bar">
            <form action="<?= BASE_URL ?>/tienda/productos.php" method="get" class="search-form">
                <input type="text" name="search" id="header-search" placeholder="Buscar productos..." autocomplete="off" value="<?= $currentSearch ?>">
                <button type="submit" aria-label="Buscar">
                    <i class="fas fa-search"></i>
                </button>
                <div class="search-results" id="search-results"></div>
            </form>
        </div>

        <!-- Iconos de navegación -->
        <div class="header-icons">
            <a href="<?= BASE_URL ?>/users/dashboarduser.php" aria-label="Mi cuenta">
                <i class="fas fa-user"></i>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>/users/wishlist.php" aria-label="Favoritos">
                    <i class="fas fa-heart"></i>
                </a>
            <?php else: ?>
                <a href="#" onclick="showLoginRequired(event, 'favoritos')" aria-label="Favoritos">
                    <i class="fas fa-heart"></i>
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/tienda/pagos/cart.php" aria-label="Carrito" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                    <span class="cart-count"><?= $_SESSION['cart_count'] ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Navegación principal -->
    <nav class="main-nav">
        <ul>
            <li><a href="<?= BASE_URL ?>/index.php">Inicio</a></li>
            <li class="mega-menu">
                <a href="<?= BASE_URL ?>/tienda/productos.php?gender=niña">Niñas</a>
            </li>
            <li class="mega-menu">
                <a href="<?= BASE_URL ?>/tienda/productos.php?gender=niño">Niños</a>
            </li>
            <li><a href="<?= BASE_URL ?>/tienda/productos.php?gender=bebe">Bebés</a></li>
            <li><a href="<?= BASE_URL ?>/tienda/productos.php?offers=1">Ofertas</a></li>
            <li><a href="<?= BASE_URL ?>/colecciones.php">Colecciones</a></li>
        </ul>
    </nav>
</header>

<?php require_once __DIR__ . '/../js/header/headerprjs.php' ?>