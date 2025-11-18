<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/../helpers/product_pricing.php';

// Verificar que el usuario tenga rol de user o customer
requireRole(['user', 'customer']);

$userId = $_SESSION['user_id'];

// Mostrar advertencia si el correo de bienvenida falló
if (isset($_SESSION['register_warning'])) {
    $warning_message = $_SESSION['register_warning'];
    unset($_SESSION['register_warning']);
    includeFromRoot('alertas/alerta1.php');
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert(".json_encode($warning_message).", 'info'); });</script>";
}

// ====== OBTENER ESTADÍSTICAS DEL USUARIO ======
$stats = [
    'orders_count' => 0,
    'addresses_count' => 0,
    'wishlist_count' => 0
];

try {
    // Contar pedidos
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['orders_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Contar direcciones
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $stats['addresses_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Contar favoritos
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['wishlist_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
}

// ====== OBTENER PEDIDOS RECIENTES (últimos 3) ======
$recentOrders = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.total AS total,
            o.status,
            o.created_at,
            COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent orders: " . $e->getMessage());
}

// ====== SISTEMA DE RECOMENDACIONES INTELIGENTE ======
$recommendedProducts = [];
try {
    // Consulta simplificada pero efectiva
    $stmt = $conn->prepare("
        SELECT DISTINCT
            p.id,
            p.name,
            p.slug,
            p.price,
            p.compare_price,
            p.is_featured,
            p.category_id,
            c.name as category_name,
            pi.image_path as primary_image,
            COALESCE(pr.avg_rating, 0) as avg_rating,
            COALESCE(pr.review_count, 0) as review_count,
            (CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END) as is_favorite,
            p.created_at
        FROM products p
        INNER JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN (
            SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM product_reviews
            WHERE is_approved = 1
            GROUP BY product_id
        ) pr ON p.id = pr.product_id
        LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = ?
        WHERE p.is_active = 1
        AND p.id NOT IN (
            -- Excluir productos ya en wishlist
            SELECT product_id FROM wishlist WHERE user_id = ?
        )
        ORDER BY
            -- Priorizar productos mejor valorados
            COALESCE(pr.avg_rating, 0) DESC,
            -- Luego productos más nuevos
            p.created_at DESC
        LIMIT 6
    ");

    $stmt->execute([$userId, $userId]);
    $recommendedProducts = hydrateProductsPricing($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Si no hay suficientes productos, agregar algunos aleatorios
    if (count($recommendedProducts) < 6) {
        $existingIds = array_column($recommendedProducts, 'id');
        $placeholders = !empty($existingIds) ? str_repeat('?,', count($existingIds) - 1) . '?' : '';

        $query = "
            SELECT DISTINCT
                p.id,
                p.name,
                p.slug,
                p.price,
                p.compare_price,
                p.is_featured,
                p.category_id,
                c.name as category_name,
                pi.image_path as primary_image,
                COALESCE(pr.avg_rating, 0) as avg_rating,
                COALESCE(pr.review_count, 0) as review_count,
                (CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END) as is_favorite
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN (
                SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                FROM product_reviews
                WHERE is_approved = 1
                GROUP BY product_id
            ) pr ON p.id = pr.product_id
            LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = ?
            WHERE p.is_active = 1
        ";

        if (!empty($existingIds)) {
            $query .= " AND p.id NOT IN ($placeholders)";
        }

        $limitToAdd = 6 - count($recommendedProducts);

        // Avoid binding LIMIT as a parameter (some MySQL versions don't accept it)
        $query .= " ORDER BY RAND() LIMIT " . intval($limitToAdd);

        $stmt = $conn->prepare($query);
        $params = array_merge([$userId], $existingIds);
        $stmt->execute($params);

        $additionalProducts = hydrateProductsPricing($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));
        $recommendedProducts = array_merge($recommendedProducts, $additionalProducts);
    }

} catch (PDOException $e) {
    error_log("Error fetching recommendations: " . $e->getMessage());
    // Fallback: mostrar productos básicos
    try {
        $stmt = $conn->prepare("
            SELECT
                p.id, p.name, p.slug, p.price, p.compare_price, p.is_featured,
                c.name as category_name,
                pi.image_path as primary_image,
                0 as avg_rating, 0 as review_count, 0 as is_favorite
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $recommendedProducts = hydrateProductsPricing($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $fallbackError) {
        error_log("Fallback error: " . $fallbackError->getMessage());
        $recommendedProducts = [];
    }
}

// Obtener categorías para el renderizado
$categories = [];
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

?>

<!-- El resto de tu HTML permanece igual -->

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Angelow</title>
    <meta name="description" content="Panel de control de tu cuenta en Angelow Ropa Infantil. Gestiona tus pedidos, direcciones y preferencias.">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Preconexión para mejorar performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/productos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificacion.css">
</head>

<body>
    <div class="user-dashboard-container">
    
  <?php
require_once __DIR__ . '/../layouts/asideuser.php';
  ?>

        <!-- Contenido principal -->
        <main class="user-main-content">
            <div class="dashboard-header">
                <h1>Bienvenido a tu cuenta</h1>
                <p>Aquí puedes gestionar tus pedidos, direcciones y preferencias.</p>
            </div>

            <!-- Resumen rápido -->
            <section class="dashboard-summary">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Pedidos</h3>
                        <p><?= $stats['orders_count'] ?> pedido<?= $stats['orders_count'] != 1 ? 's' : '' ?> realizado<?= $stats['orders_count'] != 1 ? 's' : '' ?></p>
                        <a href="<?= BASE_URL ?>/users/orders.php">Ver historial</a>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Direcciones</h3>
                        <p><?= $stats['addresses_count'] ?> dirección<?= $stats['addresses_count'] != 1 ? 'es' : '' ?> guardada<?= $stats['addresses_count'] != 1 ? 's' : '' ?></p>
                        <a href="<?= BASE_URL ?>/users/addresses.php">Gestionar direcciones</a>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Favoritos</h3>
                        <p><?= $stats['wishlist_count'] ?> producto<?= $stats['wishlist_count'] != 1 ? 's' : '' ?> guardado<?= $stats['wishlist_count'] != 1 ? 's' : '' ?></p>
                        <a href="<?= BASE_URL ?>/users/wishlist.php">Ver favoritos</a>
                    </div>
                </div>
            </section>

            <!-- Pedidos recientes -->
            <section class="recent-orders">
                <div class="section-header">
                    <h2>Pedidos recientes</h2>
                    <a href="<?= BASE_URL ?>/users/orders.php" class="view-all">Ver todos</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="no-orders">
                        <i class="fas fa-box-open"></i>
                        <p>Aún no has realizado ningún pedido</p>
                        <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">Ir a la tienda</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($recentOrders as $order):
                            $statusClass = strtolower($order['status']);
                            // Usar la función genérica para traducir estados consistentemente
                            $statusLabel = function_exists('getStatusText') ? getStatusText($order['status']) : ucfirst($order['status']);
                        ?>
                            <div class="order-card glass-effect animate__animated animate__fadeInUp">
                                <div class="order-header">
                                        <div class="order-title">
                                            <h3>Pedido #<?= htmlspecialchars($order['order_number']) ?></h3>
                                            <span class="order-date"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?= $statusClass ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </div>
                                    </div>
                                <div class="order-details">
                                    <div class="order-info">
                                        <i class="fas fa-box"></i>
                                        <span><?= $order['items_count'] ?> producto<?= $order['items_count'] != 1 ? 's' : '' ?></span>
                                    </div>
                                    <div class="order-total">
                                        <strong>$<?= number_format($order['total'], 0, ',', '.') ?></strong>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <a href="<?= BASE_URL ?>/users/order_detail.php?id=<?= $order['id'] ?>" class="btn-view-order">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recomendaciones personalizadas -->
            <section class="personalized-recommendations">
                <div class="section-header">
                    <h2>Recomendaciones para ti</h2>
                    <p class="section-subtitle">Productos seleccionados especialmente para ti</p>
                </div>

                <div class="recommendations-grid" id="recommendations-grid">
                    <?php if (!empty($recommendedProducts)): ?>
                        <?php foreach ($recommendedProducts as $product):
                            $displayPrice = $product['display_price'] ?? $product['price'];
                            $comparePrice = $product['compare_price'] ?? null;
                            $hasDiscount = !empty($product['has_discount']) && $comparePrice !== null;
                            $discountPercentage = $hasDiscount ? ($product['discount_percentage'] ?? 0) : 0;
                        ?>
                            <?php
                                // Asegurar is_favorite y ratings aunque la consulta pueda no traerlos
                                if (!isset($product['is_favorite'])) {
                                    try {
                                        $favStmt = $conn->prepare('SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
                                        $favStmt->execute([$userId, $product['id']]);
                                        $product['is_favorite'] = $favStmt->fetch() ? 1 : 0;
                                    } catch (PDOException $e) {
                                        $product['is_favorite'] = 0;
                                    }
                                }

                                if (!isset($product['avg_rating']) || !isset($product['review_count'])) {
                                    try {
                                        $ratingStmt = $conn->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE product_id = ? AND is_approved = 1');
                                        $ratingStmt->execute([$product['id']]);
                                        $ratingRow = $ratingStmt->fetch(PDO::FETCH_ASSOC);
                                        $product['avg_rating'] = $ratingRow['avg_rating'] ?: 0;
                                        $product['review_count'] = $ratingRow['review_count'] ?: 0;
                                    } catch (PDOException $e) {
                                        $product['avg_rating'] = 0;
                                        $product['review_count'] = 0;
                                    }
                                }
                                // Recalcular valores usados para mostrar estrellas
                                $avgRating = isset($product['avg_rating']) ? round($product['avg_rating'], 1) : 0;
                                $reviewCount = isset($product['review_count']) ? $product['review_count'] : 0;
                            ?>

                            <div class="product-card" data-product-id="<?= $product['id'] ?>"
                                 data-avg-rating="<?= htmlspecialchars(round($product['avg_rating'],1)) ?>"
                                 data-review-count="<?= htmlspecialchars($product['review_count'] ?? 0) ?>"
                                 data-is-favorite="<?= htmlspecialchars($product['is_favorite'] ?? 0) ?>">
                                <!-- Badge para productos destacados / descuentos -->
                                <?php if (!empty($product['is_featured'])): ?>
                                    <div class="product-badge">Destacado</div>
                                <?php endif; ?>
                                <!-- Badge de venta: se muestra dentro del área de imagen para no tapar el badge 'Destacado' -->

                                <!-- Botón de favoritos -->
                                <button class="wishlist-btn <?= isset($product['is_favorite']) && $product['is_favorite'] ? 'active' : '' ?>"
                                    aria-label="Añadir a favoritos"
                                    data-product-id="<?= $product['id'] ?>">
                                    <i class="<?= isset($product['is_favorite']) && $product['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                                </button>

                                <!-- Imagen del producto -->
                                <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="product-image loading">
                                    <?php if ($product['primary_image']): ?>
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['primary_image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <img src="<?= BASE_URL ?>/images/default-product.jpg" 
                                             alt="Producto sin imagen"
                                             loading="lazy">
                                    <?php endif; ?>
                                    <?php if ($hasDiscount && $discountPercentage > 0): ?>
                                        <div class="product-badge sale"><?= $discountPercentage ?>% OFF</div>
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
                                        <div class="stars">
                                            <?php
                                            $fullStars = floor($avgRating);
                                            $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                            $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

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
                    <?php else: ?>
                        <!-- Shimmer loading placeholders -->
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="product-card shimmer">
                                <div class="shimmer-wishlist"></div>
                                <div class="shimmer-image"></div>
                                <div class="shimmer-info">
                                    <div class="shimmer-category"></div>
                                    <div class="shimmer-title"></div>
                                    <div class="shimmer-title"></div>
                                    <div class="shimmer-rating"></div>
                                    <div class="shimmer-price"></div>
                                    <div class="shimmer-button"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>
    <?php require_once __DIR__ . '/../js/user/dashboarduserjs.php'; ?>
</body>

</html>