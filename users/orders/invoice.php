<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../tienda/api/pay/invoice_pdf_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) {
    http_response_code(400);
    echo 'ID de orden no válido';
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo 'Usuario no autenticado';
        exit;
    }

    // Obtener orden y validar que pertenezca al usuario
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':id' => $orderId, ':user_id' => $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo 'Orden no encontrada';
        exit;
    }

    // Obtener items de la orden
    $stmtItems = $conn->prepare("SELECT oi.*, p.name as product_name, p.slug as product_slug,
        COALESCE(vi.image_path, pi.image_path) as primary_image,
        COALESCE(oi.variant_name, CONCAT(
            IFNULL(c.name, ''),
            IF(oi.size_variant_id IS NOT NULL AND oi.size_variant_id != 0, CONCAT(' - ', IFNULL(s.name, '')), '')
        )) as variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        LEFT JOIN product_size_variants psv ON oi.size_variant_id = psv.id
        LEFT JOIN sizes s ON psv.size_id = s.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE oi.order_id = :order_id ORDER BY oi.id ASC");

    $stmtItems->execute([':order_id' => $orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Delegar la generación y envío del PDF al helper
    streamInvoicePdfDownload($order, $items);

} catch (Exception $e) {
    error_log('Error al descargar factura: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar la factura';
    exit;
}

?>
