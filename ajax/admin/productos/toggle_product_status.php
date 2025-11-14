<?php
/**
 * Endpoint AJAX para desactivar/activar productos
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $productId = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? 'deactivate'; // 'deactivate' or 'activate'

    if (!$productId || !is_numeric($productId)) {
        throw new Exception('ID de producto inválido');
    }

    // Inicializar controlador
    $controller = new ProductsController($conn);

    if ($action === 'activate') {
        // Activar producto
        $result = $controller->activateProduct($productId);
    } else {
        // Desactivar producto
        $result = $controller->deleteProduct($productId);
    }

    ob_end_clean();
    echo json_encode($result);
    exit();

} catch (Exception $e) {
    error_log("Error en toggle_product_status.php: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}