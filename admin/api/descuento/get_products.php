<?php
// admin/api/descuento/get_products.php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de permisos']);
    exit();
}

// Obtener parámetros de búsqueda
$search = $_POST['search'] ?? '';
$action = $_POST['action'] ?? '';

try {
    // Construir consulta base usando la estructura REAL de la base de datos
    $sql = "SELECT 
                p.id,
                p.name,
                p.price,
                p.is_active,
                p.gender,
                c.name as category_name,
                -- Calcular stock total sumando las variantes de tamaño
                COALESCE(SUM(psv.quantity), 0) as stock,
                -- Contar variantes de color
                COUNT(DISTINCT pcv.id) as color_variants,
                -- Contar variantes de tamaño
                COUNT(DISTINCT psv.id) as size_variants,
                -- Obtener la imagen principal del producto
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
            LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
            WHERE p.is_active = 1";

    $params = [];

    // Aplicar filtro de búsqueda
    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR c.name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }

    // Agrupar por producto para evitar duplicados
    $sql .= " GROUP BY p.id";
    $sql .= " ORDER BY p.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos para la respuesta
    $formattedProducts = [];
    foreach ($products as $product) {
        // Determinar la URL de la imagen
        $imageUrl = $product['main_image']
            ? BASE_URL . '/' . $product['main_image']
            : BASE_URL . '/images/placeholder-product.jpg';

        $formattedProducts[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'category' => $product['category_name'] ?? 'Sin categoría',
            'gender' => $product['gender'] ?? 'unisex',
            'price' => (float)$product['price'],
            'stock' => (int)$product['stock'],
            'color_variants' => (int)$product['color_variants'],
            'size_variants' => (int)$product['size_variants'],
            'status' => $product['is_active'] ? 'active' : 'inactive',
            'image' => $imageUrl
        ];
    }

    echo json_encode([
        'success' => true,
        'products' => $formattedProducts,
        'total' => count($formattedProducts)
    ]);
} catch (PDOException $e) {
    error_log("Error al obtener productos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar productos: ' . $e->getMessage()
    ]);
}
