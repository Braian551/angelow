<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Obtener parámetros de la solicitud
$params = json_decode(file_get_contents('php://input'), true);

// Validar y sanitizar parámetros
$searchQuery = isset($params['search']) ? trim($params['search']) : '';
$categoryFilter = isset($params['category']) ? intval($params['category']) : null;
$genderFilter = isset($params['gender']) ? $params['gender'] : '';
$priceMin = isset($params['min_price']) ? floatval($params['min_price']) : null;
$priceMax = isset($params['max_price']) ? floatval($params['max_price']) : null;
$sortBy = isset($params['sort']) ? $params['sort'] : 'newest';
$page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Verificar si el usuario está logueado
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Construir consulta base
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

// Consulta para contar el total de productos
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

// Preparar respuesta
$response = [
    'success' => true,
    'products' => $products,
    'totalProducts' => $totalProducts,
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'categories' => $categories,
    'productRatings' => $productRatings
];

echo json_encode($response);
?>