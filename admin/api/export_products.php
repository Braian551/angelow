<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Verificar permisos
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

try {
    // Obtener parámetros de filtrado (los mismos que en productsearch.php)
    $filters = [
        'search' => $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? null,
        'status' => $_GET['status'] ?? null,
        'gender' => $_GET['gender'] ?? null,
        'order' => $_GET['order'] ?? 'newest'
    ];

    // Construir consulta (similar a tu lógica actual pero para todos los registros)
    $query = "SELECT 
                p.id, p.name, p.description, p.brand, p.gender,
                p.price as base_price, p.is_active, p.created_at,
                c.name as category,
                COUNT(pv.id) as variants,
                SUM(pv.quantity) as total_stock
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN product_variants pv ON p.id = pv.product_id
              WHERE 1=1";

    if (!empty($filters['search'])) {
        $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    }
    if (!empty($filters['category'])) {
        $query .= " AND p.category_id = :category";
    }
    if ($filters['status'] === 'active') {
        $query .= " AND p.is_active = 1";
    } elseif ($filters['status'] === 'inactive') {
        $query .= " AND p.is_active = 0";
    }
    if (!empty($filters['gender'])) {
        $query .= " AND p.gender = :gender";
    }

    $query .= " GROUP BY p.id";

    // Ordenamiento
    switch ($filters['order']) {
        case 'name_asc': $query .= " ORDER BY p.name ASC"; break;
        case 'name_desc': $query .= " ORDER BY p.name DESC"; break;
        case 'price_asc': $query .= " ORDER BY p.price ASC"; break;
        case 'price_desc': $query .= " ORDER BY p.price DESC"; break;
        case 'stock_asc': $query .= " ORDER BY total_stock ASC"; break;
        case 'stock_desc': $query .= " ORDER BY total_stock DESC"; break;
        default: $query .= " ORDER BY p.created_at DESC"; break;
    }

    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($filters['search'])) {
        $stmt->bindValue(':search', '%' . $filters['search'] . '%');
    }
    if (!empty($filters['category'])) {
        $stmt->bindValue(':category', $filters['category'], PDO::PARAM_INT);
    }
    if (!empty($filters['gender'])) {
        $stmt->bindValue(':gender', $filters['gender']);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configurar headers para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=productos_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados CSV
    fputcsv($output, [
        'ID',
        'Nombre',
        'Descripción',
        'Marca',
        'Categoría',
        'Género',
        'Precio Base',
        'Variantes',
        'Stock Total',
        'Estado',
        'Fecha Creación'
    ], ';');
    
    // Datos
    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'],
            $product['name'],
            $product['description'],
            $product['brand'],
            $product['category'],
            $product['gender'],
            $product['base_price'],
            $product['variants'],
            $product['total_stock'],
            $product['is_active'] ? 'Activo' : 'Inactivo',
            $product['created_at']
        ], ';');
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Error al generar exportación: " . $e->getMessage());
}