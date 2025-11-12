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
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // Llamar al procedimiento almacenado GetFilteredProducts
    $stmt = $conn->prepare("CALL GetFilteredProducts(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $searchQuery, PDO::PARAM_STR);
    $stmt->bindValue(2, $categoryFilter, $categoryFilter !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(3, $genderFilter, $genderFilter !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(4, $priceMin, $priceMin !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(5, $priceMax, $priceMax !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(6, $sortBy, PDO::PARAM_STR);
    $stmt->bindValue(7, $limit, PDO::PARAM_INT);
    $stmt->bindValue(8, $offset, PDO::PARAM_INT);
    $stmt->bindValue(9, $userId, $userId !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();

    // Obtener los productos
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el conteo total (segundo conjunto de resultados)
    $stmt->nextRowset();
    $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalProducts = $totalResult['total'];
    $totalPages = ceil($totalProducts / $limit);

    // Cerrar el cursor
    $stmt->closeCursor();

} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalPages = 1;
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
    'products' => array_map(function($product) {
        // Asegurar que is_favorite sea un integer
        $product['is_favorite'] = (int)$product['is_favorite'];
        return $product;
    }, $products),
    'totalProducts' => $totalProducts,
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'categories' => $categories,
    'productRatings' => $productRatings
];

echo json_encode($response);
?>