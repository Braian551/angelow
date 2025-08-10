<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';

// Verificar si el usuario está logueado
$isLoggedIn = isset($_SESSION['user_id']);

// Obtener parámetros de búsqueda y filtrado
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$genderFilter = isset($_GET['gender']) ? $_GET['gender'] : '';
$priceMin = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$priceMax = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Productos por página
$offset = ($page - 1) * $limit;

// Obtener el ID del usuario para favoritos
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Construir consulta base con JOIN para favoritos si el usuario está logueado
$query = "SELECT p.*, pi.image_path as primary_image, 
          MIN(psv.price) as min_price,
          MAX(psv.price) as max_price";

if ($isLoggedIn) {
    $query .= ", (SELECT COUNT(*) FROM wishlist w WHERE w.user_id = :user_id AND w.product_id = p.id) as is_favorite";
}

$query .= " FROM products p
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
          LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
          WHERE p.is_active = 1";

$params = [];
$conditions = [];

// Aplicar filtros
if (!empty($searchQuery)) {
    $conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$searchQuery%";
}

if ($categoryFilter) {
    $conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $categoryFilter;
}

if (in_array($genderFilter, ['niño', 'niña', 'bebe', 'unisex'])) {
    $conditions[] = "p.gender = :gender";
    $params[':gender'] = $genderFilter;
}

if ($priceMin !== null) {
    $conditions[] = "psv.price >= :min_price";
    $params[':min_price'] = $priceMin;
}

if ($priceMax !== null) {
    $conditions[] = "psv.price <= :max_price";
    $params[':max_price'] = $priceMax;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Agrupar por producto
$query .= " GROUP BY p.id";

// Ordenar
switch ($sortBy) {
    case 'price_asc':
        $query .= " ORDER BY min_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY min_price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.is_featured DESC, p.name ASC";
        break;
    default:
        $query .= " ORDER BY p.is_featured DESC, p.created_at DESC";
        break;
}

// Consulta para contar el total de productos (para paginación)
$countQuery = "SELECT COUNT(DISTINCT p.id) as total 
               FROM products p
               LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
               LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
               WHERE p.is_active = 1" .
    (!empty($conditions) ? " AND " . implode(" AND ", $conditions) : "");

// Ejecutar consulta de conteo
try {
    $stmtCount = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        if ($key !== ':user_id') {
            $stmtCount->bindValue($key, $value);
        }
    }
    $stmtCount->execute();
    $totalProducts = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalProducts / $limit);
} catch (PDOException $e) {
    error_log("Error counting products: " . $e->getMessage());
    $totalProducts = 0;
    $totalPages = 1;
}

// Añadir límite y offset a la consulta principal
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

if ($isLoggedIn) {
    $params[':user_id'] = $userId;
}

// Obtener productos
$products = [];
try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Obtener categorías para el filtro
$categories = [];
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Obtener valoraciones promedio para cada producto
$productRatings = [];
try {
    $ratingQuery = "SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count 
                   FROM product_reviews 
                   WHERE is_approved = 1 
                   GROUP BY product_id";
    $ratingStmt = $conn->query($ratingQuery);
    while ($row = $ratingStmt->fetch(PDO::FETCH_ASSOC)) {
        $productRatings[$row['product_id']] = [
            'avg_rating' => $row['avg_rating'],
            'review_count' => $row['review_count']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching product ratings: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $searchQuery ? "Resultados para: $searchQuery" : 'Productos - Angelow Ropa Infantil' ?></title>
    <meta name="description" content="Explora nuestra colección de ropa infantil. Encuentra los mejores productos para niños y niñas.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/productos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificacion.css">
    <style> .main-header{position: relative;} </style>
</head>

<body>
    <div class="products-page-container">
        <!-- Filtros avanzados (sidebar) -->
        <aside class="products-filters-sidebar">
            <div class="filter-header">
                <h3>Filtros</h3>
                <button class="clear-filters">Limpiar</button>
            </div>

            <!-- Filtro por categoría -->
            <div class="filter-group">
                <div class="filter-title" data-toggle="category-filter">
                    <h4>Categorías</h4>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="filter-options" id="category-filter">
                    <?php foreach ($categories as $category): ?>
                        <div class="filter-option">
                            <input type="radio" name="category" id="cat-<?= $category['id'] ?>"
                                value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'checked' : '' ?>>
                            <label for="cat-<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filtro por género -->
            <div class="filter-group">
                <div class="filter-title" data-toggle="gender-filter">
                    <h4>Género</h4>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="filter-options" id="gender-filter">
                    <div class="filter-option">
                        <input type="radio" name="gender" id="gender-all" value="" <?= empty($genderFilter) ? 'checked' : '' ?>>
                        <label for="gender-all">Todos</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="gender" id="gender-boy" value="niño" <?= $genderFilter == 'niño' ? 'checked' : '' ?>>
                        <label for="gender-boy">Niño</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="gender" id="gender-girl" value="niña" <?= $genderFilter == 'niña' ? 'checked' : '' ?>>
                        <label for="gender-girl">Niña</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="gender" id="gender-baby" value="bebe" <?= $genderFilter == 'bebe' ? 'checked' : '' ?>>
                        <label for="gender-baby">Bebé</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="gender" id="gender-unisex" value="unisex" <?= $genderFilter == 'unisex' ? 'checked' : '' ?>>
                        <label for="gender-unisex">Unisex</label>
                    </div>
                </div>
            </div>

            <!-- Filtro por precio -->
            <div class="filter-group">
                <div class="filter-title" data-toggle="price-filter">
                    <h4>Rango de precios</h4>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="filter-options" id="price-filter">
                    <div class="price-range-slider">
                        <input type="range" class="min-price" name="min_price" min="0" max="200000" value="<?= $priceMin ?? 0 ?>">
                        <input type="range" class="max-price" name="max_price" min="0" max="200000" value="<?= $priceMax ?? 200000 ?>">
                        <div class="price-values">
                            <span class="min-price-value">$<?= number_format($priceMin ?? 0, 0, ',', '.') ?></span>
                            <span class="max-price-value">$<?= number_format($priceMax ?? 200000, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón aplicar filtros -->
            <button class="apply-filters">Aplicar Filtros</button>
        </aside>

        <!-- Contenido principal -->
        <main class="products-main-content">
            <!-- Barra de herramientas (ordenamiento y vista) -->
            <div class="products-toolbar">
                <div class="total-products">
                    <p><?= $totalProducts ?> producto<?= $totalProducts != 1 ? 's' : '' ?> encontrado<?= $totalProducts != 1 ? 's' : '' ?></p>
                </div>

                <div class="sort-options">
                    <label for="sort">Ordenar por:</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Más recientes</option>
                        <option value="popular" <?= $sortBy == 'popular' ? 'selected' : '' ?>>Más populares</option>
                        <option value="price_asc" <?= $sortBy == 'price_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                        <option value="price_desc" <?= $sortBy == 'price_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                        <option value="name_asc" <?= $sortBy == 'name_asc' ? 'selected' : '' ?>>Nombre: A-Z</option>
                        <option value="name_desc" <?= $sortBy == 'name_desc' ? 'selected' : '' ?>>Nombre: Z-A</option>
                    </select>
                </div>
            </div>

            <!-- Listado de productos -->
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>No se encontraron productos con los filtros seleccionados.</p>
                        <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">Ver todos los productos</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product):
                        // Obtener información de valoración
                        $ratingInfo = $productRatings[$product['id']] ?? null;
                        $avgRating = $ratingInfo ? round($ratingInfo['avg_rating'], 1) : 0;
                        $reviewCount = $ratingInfo ? $ratingInfo['review_count'] : 0;

                        // Determinar precio a mostrar
                        $displayPrice = $product['min_price'] ?? ($product['price'] ?? 0);
                    ?>
                        <div class="product-card" data-product-id="<?= $product['id'] ?>">
                            <!-- Badge para productos destacados -->
                            <?php if ($product['is_featured']): ?>
                                <div class="product-badge">Destacado</div>
                            <?php endif; ?>

                            <!-- Botón de favoritos -->
                            <button class="wishlist-btn <?= isset($product['is_favorite']) && $product['is_favorite'] ? 'active' : '' ?>"
                                aria-label="Añadir a favoritos"
                                data-product-id="<?= $product['id'] ?>">
                                <i class="<?= isset($product['is_favorite']) && $product['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                            </button>

                            <!-- Imagen del producto -->
                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="product-image loading">
                                <?php if ($product['primary_image']): ?>
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['primary_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="<?= BASE_URL ?>/images/default-product.jpg" alt="Producto sin imagen">
                                <?php endif; ?>
                            </a>

                            <!-- Información del producto -->
                            <div class="product-info">
                                <span class="product-category">
                                    <?php
                                    $categoryName = 'Sin categoría';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $product['category_id']) {
                                            $categoryName = $cat['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($categoryName);
                                    ?>
                                </span>

                                <h3 class="product-title">
                                    <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                                </h3>

                                <!-- Valoración -->
                                <div class="product-rating">
                                    <div class="stars">
                                        <?php
                                        // Mostrar estrellas según la valoración promedio
                                        $fullStars = floor($avgRating);
                                        $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

                                        // Estrellas llenas
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }

                                        // Media estrella
                                        if ($hasHalfStar) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }

                                        // Estrellas vacías
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
                                    <?php if ($product['compare_price'] && $product['compare_price'] > $displayPrice): ?>
                                        <span class="original-price">$<?= number_format($product['compare_price'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Botón de ver producto -->
                                <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="view-product-btn">
                                    <i class="fas fa-eye"></i> Ver producto
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>/tienda/productos.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= BASE_URL ?>/tienda/productos.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= BASE_URL ?>/tienda/productos.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link next">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>

    <?php require_once __DIR__ . '/../js/tienda/productosjs.php'; ?>
</body>

</html>