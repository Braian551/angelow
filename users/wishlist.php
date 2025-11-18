<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/../helpers/product_pricing.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Necesitamos `$userData` en el `asideuser.php` para mostrar el avatar del usuario
require_once __DIR__ . '/../layouts/functions.php';

$userId = $_SESSION['user_id'];

// Obtener productos de la lista de deseos con sus detalles
try {
    $query = "
        SELECT 
            p.id,
            p.name,
            p.slug,
            p.price,
            p.compare_price,
            p.is_featured,
            p.category_id,
            c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, `order` ASC LIMIT 1) as main_image,
            COALESCE(pr.avg_rating, 0) as avg_rating,
            COALESCE(pr.review_count, 0) as review_count,
            w.created_at as added_date
        FROM wishlist w
        INNER JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM product_reviews
            WHERE is_approved = 1
            GROUP BY product_id
        ) pr ON p.id = pr.product_id
        WHERE w.user_id = :user_id AND p.is_active = 1
        ORDER BY w.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $wishlistProducts = hydrateProductsPricing($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
    $wishlistProducts = [];
}

// Obtener categorías para referencia
$categories = [];
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Mi Lista de Deseos - Angelow</title>
    <meta name="description" content="Tu lista de productos favoritos en Angelow.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/productos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/wishlist.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificacion.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/user/alert_user.css">
</head>

<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <!-- Contenido principal -->
        <main class="user-main-content">
            <?php require_once __DIR__ . '/alertas/alert_user.php'; ?>

            <div class="wishlist-page-container">
      
        <!-- Header de la página -->
        <div class="wishlist-header">
            <h1><i class="fas fa-heart"></i> Mi Lista de Deseos</h1>
            <p>Guarda tus productos favoritos aquí</p>
        </div>

        <?php if (!empty($wishlistProducts)): ?>
            <!-- Acciones y Estadísticas -->
            <div class="wishlist-controls">
                <!-- Acciones (izquierda - mayor espacio) -->
                <div class="wishlist-actions">
                    <div class="total-products">
                        <p><?= count($wishlistProducts) ?> producto<?= count($wishlistProducts) != 1 ? 's' : '' ?> en tu lista</p>
                    </div>
                    <button class="clear-all-btn" id="clearAllWishlist">
                        <i class="fas fa-trash"></i>
                        Limpiar lista
                    </button>
                </div>

                <!-- Estadísticas (derecha - menos espacio) -->
                <div class="wishlist-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= count($wishlistProducts) ?></span>
                            <span class="stat-label">Producto<?= count($wishlistProducts) != 1 ? 's' : '' ?> guardado<?= count($wishlistProducts) != 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de productos -->
            <div class="products-grid">
                <?php foreach ($wishlistProducts as $product): 
                    $displayPrice = $product['display_price'] ?? $product['price'];
                    $comparePrice = $product['compare_price'] ?? null;
                    $hasDiscount = !empty($product['has_discount']) && $comparePrice !== null;
                ?>
                    <div class="product-card" data-product-id="<?= $product['id'] ?>">
                        <?php if (!empty($product['is_featured'])): ?>
                            <div class="product-badge">Destacado</div>
                        <?php endif; ?>


                        <!-- Botón de favoritos (siempre activo en esta página) -->
                        <button class="wishlist-btn active" aria-label="Quitar de favoritos" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-heart"></i>
                        </button>

                        <!-- Imagen del producto -->
                        <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="product-image loading">
                            <?php if ($product['main_image']): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['main_image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <img src="<?= BASE_URL ?>/images/default-product.jpg" 
                                     alt="Producto sin imagen">
                            <?php endif; ?>
                                <?php if ($hasDiscount && !empty($product['discount_percentage'])): ?>
                                    <div class="product-badge sale"><?= $product['discount_percentage'] ?>% OFF</div>
                                <?php endif; ?>
                        </a>

                        <!-- Información del producto -->
                        <div class="product-info">
                            <span class="product-category">
                                <?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?>
                            </span>

                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>

                            <!-- Valoración -->
                            <div class="product-rating">
                                <?php
                                    // Intentar usar avg_rating y review_count si están disponibles (agregados en la consulta)
                                    $avgRating = isset($product['avg_rating']) ? round($product['avg_rating'], 1) : 0;
                                    $reviewCount = isset($product['review_count']) ? $product['review_count'] : 0;

                                    $fullStars = floor($avgRating);
                                    $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                ?>
                                <div class="stars">
                                    <?php
                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }
                                    if ($hasHalfStar) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    }
                                    for ($i = 0; $i < $emptyStars; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span class="rating-count">(<?= $reviewCount ?>)</span>
                            </div>

                            <!-- Precio -->
                            <div class="product-price">
                                <span class="current-price">$<?= number_format($displayPrice, 0, ',', '.') ?></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="original-price">$<?= number_format($comparePrice, 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Botón de ver producto -->
                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="view-product-btn">
                                <i class="fas fa-eye"></i> Ver producto
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Lista vacía -->
            <div class="empty-wishlist">
                <div class="empty-wishlist-content">
                    <i class="fas fa-heart-broken"></i>
                    <h2>Tu lista de deseos está vacía</h2>
                    <p>Agrega productos a tu lista de deseos para guardarlos aquí</p>
                    <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">
                        <i class="fas fa-shopping-bag"></i>
                        Explorar productos
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <!-- Sistema de alertas y wishlist -->
    <script src="<?= BASE_URL ?>/js/user/alert_user.js"></script>
</body>

</html>
