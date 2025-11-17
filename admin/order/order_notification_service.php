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
               u.id AS user_id,
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

        // Registrar notificación en la tabla `notifications` para que el usuario vea el aviso
        try {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type_id, title, message, related_entity_type, related_entity_id, is_read, created_at) VALUES (?, 1, ?, ?, 'order', ?, 0, NOW())");
            $title = 'Pedido entregado #' . ($order['order_number'] ?? $orderId);
            $message = 'Tu pedido #' . ($order['order_number'] ?? $orderId) . ' ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.';
            $userId = $order['user_id'] ?? $order['user_id'];
            $stmt->execute([$userId, $title, $message, $orderId]);
        } catch (Throwable $e) {
            error_log('[ORDER_NOTIFY] Error al crear notificación: ' . $e->getMessage());
        }

        return ['ok' => true, 'message' => 'Correo enviado correctamente'];
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error al notificar entrega de orden ' . $orderId . ': ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Envia notificaciones y correo cuando una orden es cancelada.
 */
function notifyOrderCancelled(PDO $conn, int $orderId, string $initiator = 'system', bool $registerRefund = false): array
{
    try {
        $payload = getOrderPayloadForInvoice($conn, $orderId);
        $order = $payload['order'];
        $items = $payload['items'];

        if (empty($order['user_id'])) {
            return ['ok' => false, 'message' => 'La orden no tiene usuario asociado'];
        }

        $orderNumber = $order['order_number'] ?? $orderId;

        $title = 'Pedido cancelado #' . $orderNumber;
        $messageBase = $initiator === 'user'
            ? 'Hemos confirmado la cancelación del pedido #' . $orderNumber . ' que solicitaste.'
            : 'Tu pedido #' . $orderNumber . ' fue cancelado por el equipo de Angelow.';
        $message = $messageBase . ' El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.';

        if (!createNotification(
            $conn,
            (int) $order['user_id'],
            1,
            $title,
            $message,
            'order',
            $orderId
        )) {
            error_log('[ORDER_NOTIFY] No fue posible registrar la notificación de cancelación para la orden ' . $orderId);
        }

        $emailSent = sendOrderCancellationEmail($order, $items);

        if (!$emailSent) {
            return ['ok' => false, 'message' => 'El correo de cancelación no pudo enviarse'];
        }

        // Registrar reembolso en payment_transactions (solo rastro en DB). Si quieres integrar con el gateway,
        // esto debe llamarse con la información del gateway y después validar la respuesta del proveedor.
        $refundLogId = null;
        if ($registerRefund) {
            // Registrar solo si la orden tiene payment_status 'paid' o 'pending'
            $currentPaymentStatus = $order['payment_status'] ?? 'pending';
            if (in_array($currentPaymentStatus, ['paid', 'pending'])) {
                $refundLogId = createRefundRecord($conn, $orderId, (float) ($order['total'] ?? 0), null, null, null);
            }
        }

        $message = 'Cancelación notificada';
        if ($refundLogId) $message .= ' · Reembolso registrado en sistema (id: ' . $refundLogId . ')';
        return ['ok' => true, 'message' => $message];
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error al notificar cancelación de orden ' . $orderId . ': ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function notifyRefundCompleted(PDO $conn, int $orderId, array $options = []): array
{
    try {
        $payload = getOrderPayloadForInvoice($conn, $orderId);
        $order = $payload['order'];
        $items = $payload['items'];

        $amount = $options['amount'] ?? ($order['total'] ?? null);
        $reference = $options['reference'] ?? ($order['reference_number'] ?? null);
        $gateway = $options['gateway'] ?? null;
        $adminId = $options['admin_id'] ?? null;

        $refundRecordId = createRefundRecord($conn, $orderId, $amount, $reference, $gateway, $adminId);

        $emailSent = sendRefundConfirmationEmail($order, $items, [
            'amount' => $amount,
            'reference' => $reference,
            'payment_method' => $order['payment_method'] ?? 'Transferencia bancaria',
            'gateway' => $gateway ?? 'Angelow'
        ]);


        $notificationSent = createPaymentNotification($conn, $orderId, 'refunded');

        // También crear notificación de cambio de estado para que se muestre en el timeline
        $statusNotificationSent = createOrderStatusNotification($conn, $orderId, 'refunded');

        $messages = [];
        if ($refundRecordId) {
            $messages[] = 'Reembolso registrado (#' . $refundRecordId . ')';
        } else {
            $messages[] = 'Reembolso ya registrado previamente';
        }
        $messages[] = $emailSent ? 'Correo enviado' : 'Fallo al enviar correo';
        $messages[] = $notificationSent ? 'Notificación de pago creada' : 'Fallo al crear notificación de pago';
        $messages[] = $statusNotificationSent ? 'Notificación de estado creada' : 'Fallo al crear notificación de estado';

        $ok = ($emailSent || $notificationSent || $statusNotificationSent);

        // Log para ayudar a debugging si algo falló
        if (!$ok) {
            error_log('[ORDER_NOTIFY] notifyRefundCompleted: fallo al notificar reembolso para orden ' . $orderId . ': email=' . ($emailSent ? 'ok' : 'fail') . ', payment_notification=' . ($notificationSent ? 'ok' : 'fail') . ', status_notification=' . ($statusNotificationSent ? 'ok' : 'fail'));
        }

        return [
            'ok' => $ok,
            'message' => implode(' · ', $messages)
        ];
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error al notificar reembolso completado para orden ' . $orderId . ': ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}


/**
 * Crea un registro en payment_transactions para registrar el reembolso.
 * No hace la devolución en el gateway — solo deja rastro en la base de datos.
 * Retorna el ID del registro o null si no se creó.
 */
function createRefundRecord(PDO $conn, int $orderId, ?float $amount = null, ?string $reference = null, ?string $gateway = null, ?int $adminId = null): ?int
{
    try {
        // Evitar duplicados: revisar si ya existe un registro con amount negativo
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payment_transactions WHERE order_id = ? AND amount < 0");
        $stmt->execute([$orderId]);
        $exists = ((int)$stmt->fetchColumn()) > 0;
        if ($exists) return null;

        // Obtener usuario y monto si no se proporcionaron
        if ($amount === null) {
            $stmt = $conn->prepare("SELECT total, user_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $amount = isset($row['total']) ? (float)$row['total'] : 0.0;
            $userId = $row['user_id'] ?? null;
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $userId = $stmt->fetchColumn();
        }

        // Insertar registro negativo para indicar reembolso
        $refundAmount = -1 * abs((float)$amount);
        $adminNotes = 'Reembolso registrado';
        if ($gateway) $adminNotes .= ' vía ' . $gateway;
        if ($reference) $adminNotes .= ' · Ref: ' . $reference;

        $stmtIns = $conn->prepare("INSERT INTO payment_transactions (order_id, user_id, amount, reference_number, payment_proof, status, admin_notes, verified_by, verified_at, created_at) VALUES (?, ?, ?, ?, NULL, 'verified', ?, ?, NOW(), NOW())");
        $verifiedBy = $adminId ? (string)$adminId : 'system';
        $stmtIns->execute([$orderId, $userId, $refundAmount, $reference, $adminNotes, $verifiedBy]);
        $paymentId = (int)$conn->lastInsertId();

        // Registrar en histori de orden
        try {
            $stmtHist = $conn->prepare("INSERT INTO order_status_history (order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, 'refunded', 'payment_status', ?, 'refunded', ?, ?, ?, NOW())");
            $changedBy = $adminId ? $adminId : null;
            $changedByName = $changedBy ? (string)$changedBy : 'Sistema';
            $description = 'Reembolso de ' . number_format(abs($refundAmount), 0, ',', '.') . ' registrado en sistema. ' . ($reference ? 'Ref: ' . $reference : '');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'cli';
            $stmtHist->execute([$orderId, $changedBy, $changedByName, ($amount ? (string)$amount : null), $description, $ip, $ua]);
        } catch (Throwable $e) {
            error_log('[ORDER_NOTIFY] No fue posible crear historial de reembolso: ' . $e->getMessage());
        }

        return $paymentId;
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error al crear registro de reembolso: ' . $e->getMessage());
        return null;
    }
}

/**
 * Inserta una notificación genérica para un usuario.
 */
function createNotification(PDO $conn, $userId, int $typeId, string $title, string $message, string $relatedEntityType = 'order', ?int $relatedEntityId = null): bool
{
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type_id, title, message, related_entity_type, related_entity_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$userId, $typeId, $title, $message, $relatedEntityType, $relatedEntityId]);
        return true;
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error createNotification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Notifica al usuario sobre el nuevo estado de la orden (excepto 'delivered' que se maneja por separado).
 */
function createOrderStatusNotification(PDO $conn, int $orderId, string $newStatus): bool
{
    try {
        $stmt = $conn->prepare("SELECT user_id, order_number FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $ord = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ord) return false;

        $userId = $ord['user_id'];
        $orderNumber = $ord['order_number'];

        $labels = [
            'processing' => ['Pedido en proceso', "Tu pedido #$orderNumber está en proceso y pronto saldrá para entrega."],
            'shipped' => ['Tu envío está en camino', "Tu pedido #$orderNumber ha salido para entrega. Pronto lo recibirás."],
            'cancelled' => ['Pedido cancelado', "Tu pedido #$orderNumber ha sido cancelado. Iniciaremos el reembolso en las próximas horas."],
            'refunded' => ['Reembolso exitoso', "Confirmamos el reembolso de tu pedido #$orderNumber. Verás el dinero reflejado según los tiempos de tu banco."],
            'pending' => ['Pedido pendiente', "Tu pedido #$orderNumber está pendiente de confirmación."],
        ];

        if (!isset($labels[$newStatus])) return false;

        [$title, $message] = $labels[$newStatus];

        return createNotification($conn, $userId, 1, $title, $message, 'order', $orderId);
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error createOrderStatusNotification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Crear notificaciones específicas para cambios en el estado de pago.
 */
function createPaymentNotification(PDO $conn, int $orderId, string $newPaymentStatus): bool
{
    try {
        $stmt = $conn->prepare("SELECT user_id, order_number FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $ord = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ord) return false;

        $userId = $ord['user_id'];
        $orderNumber = $ord['order_number'];

        $labels = [
            'paid' => ['Pago aprobado', "Hemos recibido el pago de tu pedido #$orderNumber. Gracias."],
            'failed' => ['Pago rechazado', "Tu pago para el pedido #$orderNumber no fue aprobado. Revisa la referencia o contáctanos."],
            'refunded' => ['Reembolso exitoso', "Tu reembolso del pedido #$orderNumber fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas."],
        ];

        if (!isset($labels[$newPaymentStatus])) return false;

        [$title, $message] = $labels[$newPaymentStatus];
        return createNotification($conn, $userId, 1, $title, $message, 'order', $orderId);
    } catch (Throwable $e) {
        error_log('[ORDER_NOTIFY] Error createPaymentNotification: ' . $e->getMessage());
        return false;
    }
}
