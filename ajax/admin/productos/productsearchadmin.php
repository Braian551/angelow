<?php
ob_start();
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

try {
    // Verificar permisos de administrador
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['error' => 'Permisos insuficientes']);
        exit();
    }

    // Obtener y sanitizar parámetros
    $search = isset($_GET['search']) ? '%'.trim($_GET['search']).'%' : null;
    $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $status = isset($_GET['status']) ? ($_GET['status'] === 'active' ? 1 : 0) : null;
    $gender = isset($_GET['gender']) ? $_GET['gender'] : null;
    $order = isset($_GET['order']) ? $_GET['order'] : 'newest';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;

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
    $types = '';

    // Aplicar filtros
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }

    if ($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }

    if ($status !== null) {
        $sql .= " AND p.is_active = ?";
        $params[] = $status;
        $types .= 'i';
    }

    if ($gender) {
        $sql .= " AND p.gender = ?";
        $params[] = $gender;
        $types .= 's';
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
    $types .= 'ii';

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
    $stmt = $conn->prepare($sql);
    
    // Vincular parámetros dinámicamente
    foreach ($params as $index => $param) {
        $paramType = $types[$index] === 'i' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($index + 1, $param, $paramType);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();

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

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error en productsearchadmin.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error en la base de datos',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
ob_end_flush();
?>