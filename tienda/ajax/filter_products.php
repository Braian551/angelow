<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../helpers/product_pricing.php';

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
$showOffersOnly = isset($params['offers']) && $params['offers'] === '1';
$collectionFilter = isset($params['collection']) ? intval($params['collection']) : null;
$page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Verificar si el usuario está logueado
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // Llamar al procedimiento almacenado GetFilteredProducts
    $stmt = $conn->prepare("CALL GetFilteredProducts(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $searchQuery, PDO::PARAM_STR);
    $stmt->bindValue(2, $categoryFilter, $categoryFilter !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(3, $genderFilter, $genderFilter !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(4, $priceMin, $priceMin !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(5, $priceMax, $priceMax !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(6, $sortBy, PDO::PARAM_STR);
    $stmt->bindValue(7, $limit, PDO::PARAM_INT);
    $stmt->bindValue(8, $offset, PDO::PARAM_INT);
    $stmt->bindValue(9, $userId, $userId !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(10, $showOffersOnly, PDO::PARAM_INT);
    $stmt->bindValue(11, $collectionFilter, $collectionFilter !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->execute();

    // Obtener los productos
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el conteo total (segundo conjunto de resultados)
    $totalProducts = 0;
    $totalPages = 1;
    
    // Consumir todos los rowsets para evitar error 2014
    do {
        if ($stmt->columnCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $totalProducts = (int)$row['total'];
                $totalPages = max(1, (int) ceil($totalProducts / $limit));
            }
        }
    } while ($stmt->nextRowset());

    // Cerrar el cursor explícitamente
    $stmt->closeCursor();

} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $response = [
        'success' => false,
        'error' => "Database Error: " . $e->getMessage()
    ];
    echo json_encode($response);
    exit;
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

// Normalizar información de precios para cada producto
$products = hydrateProductsPricing($conn, $products);

// Convertir flags a tipos consistentes
$products = array_map(function($product) {
    $product['is_favorite'] = isset($product['is_favorite']) ? (int) $product['is_favorite'] : 0;
    return $product;
}, $products);

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