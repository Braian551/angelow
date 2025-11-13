<?php
/**
 * Endpoint AJAX para búsqueda y filtrado de productos (Admin)
 * Refactorizado para usar el controlador
 */

session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';
require_once __DIR__ . '/../../../admin/api/productos/ProductsController.php';

// Limpiar cualquier salida previa
if (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación y rol
requireRole('admin');

try {
    // Inicializar controlador
    $controller = new ProductsController($conn);
    
    // Obtener parámetros
    $filters = [
        'search' => $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? '',
        'status' => $_GET['status'] ?? '',
        'gender' => $_GET['gender'] ?? '',
        'order' => $_GET['order'] ?? 'newest'
    ];
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 12;
    
    // Obtener productos usando el controlador
    $result = $controller->getProducts($filters, $page, $perPage);
    
    // Formatear imágenes con BASE_URL
    if ($result['success'] && !empty($result['products'])) {
        foreach ($result['products'] as &$product) {
            if (!empty($product['primary_image']) && strpos($product['primary_image'], 'http') !== 0) {
                $product['primary_image'] = BASE_URL . '/' . $product['primary_image'];
            }
        }
    }
    
    ob_end_clean();
    echo json_encode($result);
    exit();

} catch (Exception $e) {
    error_log("Error en productsearchadmin.php: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud',
        'products' => [],
        'meta' => ['total' => 0, 'page' => 1, 'perPage' => 12, 'totalPages' => 0]
    ]);
    exit();
}