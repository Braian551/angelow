<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

// Limpiar cualquier salida previa
if (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Log para debug
error_log("productsearchadmin.php - Session ID: " . session_id());
error_log("productsearchadmin.php - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    error_log("productsearchadmin.php - No session user_id found");
    ob_end_clean();
    echo json_encode(['error' => 'Acceso no autorizado', 'session_id' => session_id()]);
    exit();
}

try {
    // Verificar permisos de administrador
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    error_log("productsearchadmin.php - User found: " . ($user ? 'YES' : 'NO'));
    error_log("productsearchadmin.php - User role: " . ($user ? $user['role'] : 'N/A'));
    
    if (!$user || $user['role'] !== 'admin') {
        ob_end_clean();
        echo json_encode([
            'error' => 'Permisos insuficientes',
            'user_id' => $_SESSION['user_id'],
            'role' => $user ? $user['role'] : 'no user found'
        ]);
        exit();
    }

    // Obtener y sanitizar parámetros
    $search = (isset($_GET['search']) && trim($_GET['search']) !== '') ? '%'.trim($_GET['search']).'%' : null;
    $category = (isset($_GET['category']) && $_GET['category'] !== '') ? (int)$_GET['category'] : null;
    $status = (isset($_GET['status']) && $_GET['status'] !== '') ? ($_GET['status'] === 'active' ? 1 : 0) : null;
    $gender = (isset($_GET['gender']) && trim($_GET['gender']) !== '') ? trim($_GET['gender']) : null;
    $order = isset($_GET['order']) ? $_GET['order'] : 'newest';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;
    
    // Log para debug
    error_log("productsearchadmin.php - Filters: search=" . ($search ?? 'null') . ", category=" . ($category ?? 'null') . ", status=" . ($status ?? 'null') . ", gender=" . ($gender ?? 'null'));

    // Construir consulta base con JOIN para obtener imágenes
    $sql = "SELECT 
              p.id, 
              p.name, 
              p.slug, 
              p.brand, 
              p.gender, 
              p.is_active,
              p.created_at,
              c.name AS category_name,
              (SELECT COUNT(*) FROM product_color_variants pcv WHERE pcv.product_id = p.id) AS variant_count,
              (SELECT COALESCE(SUM(psv.quantity), 0) 
               FROM product_color_variants pcv
               JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
               WHERE pcv.product_id = p.id) AS total_stock,
              (SELECT COALESCE(MIN(psv.price), 0) 
               FROM product_color_variants pcv
               JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
               WHERE pcv.product_id = p.id) AS min_price,
              (SELECT COALESCE(MAX(psv.price), 0) 
               FROM product_color_variants pcv
               JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
               WHERE pcv.product_id = p.id) AS max_price,
              (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY `order` LIMIT 1) AS primary_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE 1=1";

    $params = [];
    $types = [];

    // Aplicar filtros
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types[] = 's';
        $types[] = 's';
    }

    if ($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
        $types[] = 'i';
    }

    if ($status !== null) {
        $sql .= " AND p.is_active = ?";
        $params[] = $status;
        $types[] = 'i';
    }

    if ($gender) {
        $sql .= " AND p.gender = ?";
        $params[] = $gender;
        $types[] = 's';
    }

    // Ordenación
    switch ($order) {
        case 'name_asc': $sql .= " ORDER BY p.name ASC"; break;
        case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
        case 'price_asc': $sql .= " ORDER BY min_price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY max_price DESC"; break;
        case 'stock_asc': $sql .= " ORDER BY total_stock ASC"; break;
        case 'stock_desc': $sql .= " ORDER BY total_stock DESC"; break;
        default: $sql .= " ORDER BY p.created_at DESC";
    }

    // Paginación
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types[] = 'i';
    $types[] = 'i';

    // Consulta para contar total
    $countSql = "SELECT COUNT(*) FROM products p WHERE 1=1";
    $countParams = [];
    
    if ($search) {
        $countSql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
        $countParams[] = $search;
        $countParams[] = $search;
    }
    
    if ($category) {
        $countSql .= " AND p.category_id = ?";
        $countParams[] = $category;
    }
    
    if ($status !== null) {
        $countSql .= " AND p.is_active = ?";
        $countParams[] = $status;
    }
    
    if ($gender) {
        $countSql .= " AND p.gender = ?";
        $countParams[] = $gender;
    }

    // Ejecutar consultas
    error_log("productsearchadmin.php - SQL: " . $sql);
    error_log("productsearchadmin.php - Params count: " . count($params));
    
    $stmt = $conn->prepare($sql);
    
    // Vincular parámetros dinámicamente
    foreach ($params as $index => $param) {
        $paramType = $types[$index] === 'i' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($index + 1, $param, $paramType);
        error_log("productsearchadmin.php - Binding param " . ($index + 1) . ": " . $param . " (type: " . $types[$index] . ")");
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("productsearchadmin.php - Products found: " . count($products));

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();
    
    error_log("productsearchadmin.php - Total count: " . $total);

    // Formatear respuesta
    $response = [
        'success' => true,
        'products' => array_map(function($product) {
            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'brand' => $product['brand'],
                'gender' => $product['gender'],
                'is_active' => (bool)$product['is_active'],
                'category_name' => $product['category_name'] ?? 'Sin categoría',
                'variant_count' => (int)$product['variant_count'],
                'total_stock' => (int)$product['total_stock'],
                'min_price' => (float)$product['min_price'],
                'max_price' => (float)$product['max_price'],
                                'primary_image' => $product['primary_image'] ? BASE_URL . '/' . $product['primary_image'] : null,
                'created_at' => $product['created_at']
            ];
        }, $products),
        'meta' => [
            'total' => (int)$total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ]
    ];

    ob_end_clean();
    echo json_encode($response);
    exit();

} catch (PDOException $e) {
    error_log("Error en productsearchadmin.php: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'error' => 'Error en la base de datos',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    exit();
}
?>