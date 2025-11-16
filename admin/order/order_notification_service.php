<?php
/**
 * Helper para centralizar la notificacion al cliente cuando una orden pasa a entregado.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../tienda/api/pay/invoice_pdf_helpers.php';
require_once __DIR__ . '/../../tienda/api/pay/send_invoice.php';

/**
 * Obtiene la informacion completa de la orden e items para armar el comprobante.
 *
 * @param PDO $conn
 * @param int $orderId
 * @return array{order: array, items: array}
 * @throws Exception
 */
function getOrderPayloadForInvoice(PDO $conn, int $orderId): array
{
    static $hasShippingMethodColumn = null;

    if ($hasShippingMethodColumn === null) {
        try {
            $colStmt = $conn->prepare("SHOW COLUMNS FROM `orders` LIKE 'shipping_method_id'");
            $colStmt->execute();
            $hasShippingMethodColumn = (bool) $colStmt->fetch();
        } catch (Throwable $e) {
            error_log('[ORDER_NOTIFY] No fue posible verificar shipping_method_id: ' . $e->getMessage());
            $hasShippingMethodColumn = false;
        }
    }

    $shippingSelect = $hasShippingMethodColumn
        ? ", sm.name AS shipping_method_name, sm.description AS shipping_description"
        : '';
    $shippingJoin = $hasShippingMethodColumn
        ? 'LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id'
        : '';

    $orderSql = "
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               pt.reference_number, pt.payment_proof, pt.created_at AS payment_date
               $shippingSelect
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN (
            SELECT t1.*
            FROM payment_transactions t1
            INNER JOIN (
                SELECT order_id, MAX(created_at) AS max_created
                FROM payment_transactions
                GROUP BY order_id
            ) latest ON latest.order_id = t1.order_id AND latest.max_created = t1.created_at
        ) pt ON pt.order_id = o.id
        $shippingJoin
        WHERE o.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($orderSql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Orden no encontrada');
    }

    $itemsSql = "
        SELECT 
            oi.*,
            p.name AS product_name,
            p.slug AS product_slug,
            COALESCE(vi.image_path, pi.image_path) AS primary_image,
            COALESCE(c.name, CONCAT('Variante ', pcv.id)) AS variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE oi.order_id = ?
    ";

    $stmt = $conn->prepare($itemsSql);
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        if (empty($item['primary_image'])) {
            $item['primary_image'] = 'images/default-product.jpg';
        }
    }
    unset($item);

    return ['order' => $order, 'items' => $items];
}

/**
 * Envia el comprobante PDF por correo al cliente.
 *
 * @param PDO $conn
 * @param int $orderId
 * @return array{ok: bool, message: string}
 */
function notifyOrderDelivered(PDO $conn, int $orderId): array
{
    try {
        $payload = getOrderPayloadForInvoice($conn, $orderId);
        $order = $payload['order'];
        $items = $payload['items'];

        if (empty($order['user_email'])) {
            return ['ok' => false, 'message' => 'La orden no tiene correo asociado'];
        }

        $pdfContent = generateInvoicePdfContent($order, $items);
        $pdfFilename = 'factura_' . ($order['order_number'] ?? $orderId) . '.pdf';

        $sent = sendInvoiceEmail($order, $items, $pdfContent, $pdfFilename);

        if (!$sent) {
            return ['ok' => false, 'message' => 'No se pudo enviar el correo'];
        }

        return ['ok' => true, 'message' => 'Correo enviado correctamente'];
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error al notificar entrega de orden ' . $orderId . ': ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}
