<?php
// tienda/api/pay/send_invoice.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../pagos/helpers/shipping_helpers.php';
require_once __DIR__ . '/invoice_pdf_helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function invoiceBuildEmailImageSrc(?string $path): string
{
    if (function_exists('invoiceImageToBase64')) {
        return invoiceImageToBase64($path);
    }

    if (empty($path)) {
        return BASE_URL . '/images/default-product.jpg';
    }

    if (preg_match('/^https?:/i', $path)) {
        return $path;
    }

    $normalized = ltrim($path, '/');
    if (strpos($normalized, 'uploads/') === false && strpos($normalized, 'images/') !== 0) {
        $normalized = 'uploads/productos/' . basename($normalized);
    }

    return BASE_URL . '/' . $normalized;
}

/**
 * Intenta embeber la imagen en el correo usando PHPMailer y devuelve la CID.
 * Si no es posible, devuelve una URL pública o base64 (según invoiceImageToBase64).
 */
function invoiceEmbedImageForMail(PHPMailer $mail, ?string $path): string
{
    // Primero preferimos la ruta absoluta conocida (misma lógica del PDF)
    if (function_exists('invoiceAbsoluteImagePath')) {
        $absolute = invoiceAbsoluteImagePath($path);
    } else {
        // Fallback: construir la ruta física
        $relative = ltrim($path ?? 'images/default-product.jpg', '/');
        $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $relative;
    }

    // Añadir imagen embebida si existe
    if ($absolute && file_exists($absolute) && is_readable($absolute)) {
        $embedId = 'product_' . md5($absolute . microtime(true));
        try {
            $mail->addEmbeddedImage($absolute, $embedId, basename($absolute));
            return 'cid:' . $embedId;
        } catch (Exception $e) {
            error_log('[INVOICE_EMAIL] No fue posible embeber imagen en email: ' . $e->getMessage());
            // Seguir con otro método
        }
    }

    // Si no podemos embeber, preferimos base64 como el PDF o la URL pública
    if (function_exists('invoiceImageToBase64')) {
        $b64 = invoiceImageToBase64($path);
        if (!empty($b64)) return $b64;
    }

    // Fallback final: URL pública
    return invoiceBuildEmailImageSrc($path);
}

function sendInvoiceEmail(array $order, array $orderItems, ?string $pdfContent = null, ?string $pdfFilename = null): bool
{
    if (empty($order['user_email'])) {
        error_log('[INVOICE_EMAIL] Orden sin email: ' . ($order['order_number'] ?? 'N/A'));
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        // If the application is in debug mode, enable PHPMailer SMTP debug and forward output to error_log
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2; // client and server messages
            $mail->Debugoutput = function($str, $level) {
                error_log('[INVOICE_EMAIL][SMTP_DEBUG] ' . trim($str));
            };
        }
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('facturacion@angelow.com', 'Angelow Facturación');
        $mail->addAddress($order['user_email'], $order['user_name'] ?? 'Cliente Angelow');

        $logoPath = realpath(__DIR__ . '/../../../images/logo2.png');
        $logoEmbedId = 'invoice_logo';
        $logoUrl = BASE_URL . '/images/logo2.png';
        if ($logoPath && file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, $logoEmbedId);
            $logoUrl = 'cid:' . $logoEmbedId;
        }

        $itemsHtml = '';
        foreach ($orderItems as $item) {
            // Intentamos embeber la imagen como CID (más fiable en clientes de correo)
            $imageUrl = invoiceEmbedImageForMail($mail, $item['primary_image'] ?? null);
            $itemsHtml .= '<tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:12px 8px; width:64px;"><img src="' . htmlspecialchars($imageUrl) . '" style="width:48px; height:48px; border-radius:8px; object-fit:cover;" alt="Producto"></td>
                <td style="padding:12px 8px;">
                    <strong style="color:#0f172a;">' . htmlspecialchars($item['product_name']) . '</strong>' .
                    (!empty($item['variant_name']) ? '<br><small style="color:#475569;">' . htmlspecialchars($item['variant_name']) . '</small>' : '') . '
                </td>
                <td style="padding:12px 8px; text-align:center; color:#0f172a;">' . (int) $item['quantity'] . '</td>
                <td style="padding:12px 8px; text-align:right; color:#0f172a; font-weight:600;">$' . number_format($item['total'], 0, ',', '.') . '</td>
            </tr>';
        }

        $subtotal = (float) ($order['subtotal'] ?? array_sum(array_column($orderItems, 'total')));
        $discount = (float) ($order['discount_amount'] ?? 0);
        $shipping = (float) ($order['shipping_cost'] ?? 0);
        $total = (float) ($order['total'] ?? ($subtotal - $discount + $shipping));

        $mail->isHTML(true);
        $mail->Subject = 'Factura electrónica #' . htmlspecialchars($order['order_number'] ?? 'N/A');
        $mail->Body = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Angelow</title>
    <style>
        body { font-family: "Inter", Arial, sans-serif; background-color: #f6f7fb; margin: 0; padding: 24px; color: #0f172a; }
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); border: 1px solid #e5e7eb; }
        .header { background: linear-gradient(135deg, #003f74, #0b6bbd); color: #ffffff; padding: 36px 32px; text-align: center; }
        .header img { max-height: 56px; margin-bottom: 18px; }
        .subtitle { color: rgba(255,255,255,0.85); font-size: 15px; margin: 8px 0 0 0; }
        .content { padding: 32px; }
        .card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; background: #fdfdfd; }
        .card h3 { margin: 0 0 18px 0; color: #003f74; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-line { display: flex; justify-content: space-between; margin-bottom: 12px; color: #475569; font-size: 14px; }
        .detail-label { font-weight: 600; color: #0f172a; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .items-table tr:nth-child(even) { background: #f8fafc; }
        .items-table td { padding: 14px 10px; font-size: 14px; color: #0f172a; vertical-align: middle; border-bottom: 1px solid #e2e8f0; }
        .totals { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #475569; }
        .totals-row strong { color: #003f74; }
        .footer { text-align: center; color: #94a3b8; padding: 24px; font-size: 13px; background: #f8fafc; }
        .btn { display: inline-block; padding: 12px 20px; background: #0b6bbd; color: #fff; border-radius: 999px; text-decoration: none; font-weight: 600; margin-top: 20px; box-shadow: 0 10px 20px rgba(11,107,189,0.25); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="' . $logoUrl . '" alt="Angelow">
            <h1>Factura electrónica disponible</h1>
            <p class="subtitle">Gracias por completar tu pedido con Angelow. Adjuntamos tu factura digital.</p>
        </div>
        <div class="content">
            <div class="card">
                <h3>Resumen de la factura</h3>
                <div class="detail-line"><span class="detail-label">Número de factura:</span><span>FAC-' . htmlspecialchars($order['order_number'] ?? 'N/A') . '</span></div>
                <div class="detail-line"><span class="detail-label">Fecha de emisión:</span><span>' . date('d/m/Y H:i') . '</span></div>
                <div class="detail-line"><span class="detail-label">Cliente:</span><span>' . htmlspecialchars($order['user_name'] ?? 'Cliente Angelow') . '</span></div>
                <div class="detail-line"><span class="detail-label">Correo:</span><span>' . htmlspecialchars($order['user_email']) . '</span></div>
            </div>
            <div class="card">
                <h3>Detalle de productos</h3>
                <table class="items-table">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="text-align:left;padding:12px;border-bottom:1px solid #e2e8f0;">Imagen</th>
                            <th style="text-align:left;padding:12px;border-bottom:1px solid #e2e8f0;">Producto</th>
                            <th style="text-align:center;padding:12px;border-bottom:1px solid #e2e8f0;">Cant.</th>
                            <th style="text-align:right;padding:12px;border-bottom:1px solid #e2e8f0;">Total</th>
                        </tr>
                    </thead>
                    <tbody>' . $itemsHtml . '</tbody>
                </table>
                <div class="totals">
                    <div class="totals-row"><span>Subtotal:</span><span>$' . number_format($subtotal, 0, ',', '.') . '</span></div>
                    ' . ($discount > 0 ? '<div class="totals-row"><span>Descuento:</span><span>-$' . number_format($discount, 0, ',', '.') . '</span></div>' : '') . '
                    <div class="totals-row"><span>Envío:</span><span>$' . number_format($shipping, 0, ',', '.') . '</span></div>
                    <div class="totals-row" style="font-size:16px; font-weight:700;"><span>Total pagado:</span><span>$' . number_format($total, 0, ',', '.') . '</span></div>
                </div>
            </div>
            <p style="color:#475569; font-size:14px;">Conserva este correo como soporte de tu compra. Si necesitas actualizar tus datos de facturación o tienes inquietudes, escríbenos a <strong>facturacion@angelow.com</strong>.</p>
            <a class="btn" href="' . BASE_URL . '/tienda/pedidos.php">Ver historial de pedidos</a>
        </div>
        <div class="footer">
            © ' . date('Y') . ' Angelow Ropa Infantil · Documento generado automáticamente tras confirmar la entrega.
        </div>
    </div>
</body>
</html>';

        if (!empty($pdfContent) && !empty($pdfFilename)) {
            $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[INVOICE_EMAIL] Error: ' . $e->getMessage());
        return false;
    }
}

function sendOrderCancellationEmail(array $order, array $orderItems): bool
{
    if (empty($order['user_email'])) {
        error_log('[INVOICE_EMAIL] Orden sin email para cancelación: ' . ($order['order_number'] ?? 'N/A'));
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log('[INVOICE_EMAIL][SMTP_DEBUG] ' . trim($str));
            };
        }
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('soporte@angelow.com', 'Angelow Atención al Cliente');
        $mail->addAddress($order['user_email'], $order['user_name'] ?? 'Cliente Angelow');

        $logoPath = realpath(__DIR__ . '/../../../images/logo2.png');
        $logoEmbedId = 'cancellation_logo';
        $logoUrl = BASE_URL . '/images/logo2.png';
        if ($logoPath && file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, $logoEmbedId);
            $logoUrl = 'cid:' . $logoEmbedId;
        }

        $itemsHtml = '';
        foreach ($orderItems as $item) {
            $imageUrl = invoiceEmbedImageForMail($mail, $item['primary_image'] ?? null);
            $itemsHtml .= '<tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:12px 8px; width:64px;"><img src="' . htmlspecialchars($imageUrl) . '" style="width:48px; height:48px; border-radius:8px; object-fit:cover;" alt="Producto"></td>
                <td style="padding:12px 8px; color:#0f172a;">
                    <div style="font-weight:600; margin-bottom:4px;">' . htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Producto Angelow') . '</div>
                    <div style="color:#64748b; font-size:13px;">Cantidad: ' . (int) ($item['quantity'] ?? 1) . '</div>
                </td>
                <td style="padding:12px 8px; text-align:right; color:#0f172a; font-weight:600;">$' . number_format($item['total'] ?? (($item['quantity'] ?? 1) * ($item['price'] ?? 0)), 0, ',', '.') . '</td>
            </tr>';
        }

        if ($itemsHtml === '') {
            $itemsHtml = '<tr><td colspan="3" style="padding:18px; text-align:center; color:#64748b;">No fue posible cargar el detalle de los productos.</td></tr>';
        }

        $subtotal = $order['subtotal'] ?? array_sum(array_column($orderItems, 'total'));
        $discount = $order['discount_amount'] ?? 0;
        $shipping = $order['shipping_cost'] ?? 0;
        $total = $order['total'] ?? ($subtotal - $discount + $shipping);

        $paymentStatus = invoiceTranslatePaymentStatus($order['payment_status'] ?? 'pending');
        $orderNumber = $order['order_number'] ?? $order['id'] ?? 'N/A';

        $mail->isHTML(true);
        $mail->Subject = 'Cancelación del pedido #' . htmlspecialchars($orderNumber) . ' - Reembolso en proceso';
        $mail->Body = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelación de pedido Angelow</title>
    <style>
        body { font-family: "Inter", Arial, sans-serif; background-color: #f6f7fb; margin: 0; padding: 24px; color: #0f172a; }
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); border: 1px solid #e5e7eb; }
        .header { background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #ffffff; padding: 36px 32px; text-align: center; }
        .header img { max-height: 56px; margin-bottom: 18px; }
        .subtitle { color: rgba(255,255,255,0.85); font-size: 15px; margin: 8px 0 0 0; }
        .content { padding: 32px; }
        .card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; background: #fdfdfd; }
        .card h3 { margin: 0 0 18px 0; color: #0f172a; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-line { display: flex; justify-content: space-between; margin-bottom: 12px; color: #475569; font-size: 14px; }
        .detail-label { font-weight: 600; color: #0f172a; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .items-table tr:nth-child(even) { background: #f8fafc; }
        .items-table td { padding: 14px 10px; font-size: 14px; color: #0f172a; vertical-align: middle; border-bottom: 1px solid #e2e8f0; }
        .totals { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #475569; }
        .totals-row strong { color: #0f172a; }
        .footer { text-align: center; color: #94a3b8; padding: 24px; font-size: 13px; background: #f8fafc; }
        .btn { display: inline-block; padding: 12px 20px; background: #1d4ed8; color: #fff; border-radius: 999px; text-decoration: none; font-weight: 600; margin-top: 20px; box-shadow: 0 10px 20px rgba(29,78,216,0.25); }
        .highlight { display: inline-block; padding: 8px 14px; border-radius: 10px; background: rgba(30,64,175,0.08); color: #1e40af; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="' . $logoUrl . '" alt="Angelow">
            <h1>Confirmamos la cancelación del pedido</h1>
            <p class="subtitle">El reembolso se está procesando con el mismo método de pago utilizado.</p>
        </div>
        <div class="content">
            <div class="card">
                <h3>Resumen de la cancelación</h3>
                <div class="detail-line"><span class="detail-label">Pedido:</span><span>#' . htmlspecialchars($orderNumber) . '</span></div>
                <div class="detail-line"><span class="detail-label">Fecha de cancelación:</span><span>' . date('d/m/Y H:i') . '</span></div>
                <div class="detail-line"><span class="detail-label">Estado del reembolso:</span><span>' . htmlspecialchars($paymentStatus) . '</span></div>
            </div>
            <div class="card">
                <h3>Productos del pedido</h3>
                <table class="items-table">
                    <tbody>' . $itemsHtml . '</tbody>
                </table>
                <div class="totals">
                    <div class="totals-row"><span>Subtotal:</span><span>$' . number_format($subtotal, 0, ',', '.') . '</span></div>
                    <div class="totals-row"><span>Descuentos:</span><span>-$' . number_format($discount, 0, ',', '.') . '</span></div>
                    <div class="totals-row"><span>Envío:</span><span>$' . number_format($shipping, 0, ',', '.') . '</span></div>
                    <div class="totals-row"><strong>Total pagado:</strong><strong>$' . number_format($total, 0, ',', '.') . '</strong></div>
                </div>
            </div>
            <p style="color:#475569; font-size:14px; line-height:1.6;">El reembolso puede tardar entre <span class="highlight">3 y 7 días hábiles</span> dependiendo de tu entidad financiera. Si el cobro todavía estaba pendiente, verás la liberación automática en las próximas horas.</p>
            <p style="color:#475569; font-size:14px; line-height:1.6;">Para cualquier inquietud adicional escríbenos a <strong>soporte@angelow.com</strong> o envíanos un mensaje por WhatsApp.</p>
            <a class="btn" href="' . BASE_URL . '/users/orders.php">Revisar mis pedidos</a>
        </div>
        <div class="footer">
            © ' . date('Y') . ' Angelow Ropa Infantil · Mensaje generado automáticamente.
        </div>
    </div>
</body>
</html>';

        $mail->send();
        error_log('[INVOICE_EMAIL] Cancellation email sent successfully to ' . $order['user_email'] . ' for order ' . ($order['order_number'] ?? $order['id']));
        return true;
    } catch (Exception $e) {
        error_log('[INVOICE_EMAIL] Error cancelación (PHPMailer): ' . $e->getMessage());

        // Fallback: intentar enviar un email simple con mail() si PHPMailer falla
        try {
            $to = $order['user_email'];
            $subject = 'Cancelación del pedido #' . ($order['order_number'] ?? $order['id']) . ' - Reembolso en proceso';
            $plaintext = "Hola " . ($order['user_name'] ?? "") . ",\n\n" .
                "Tu pedido #" . ($order['order_number'] ?? $order['id']) . " fue cancelado. El reembolso se procesará con el mismo método de pago.\n" .
                "Monto: $" . number_format($order['total'] ?? 0, 0, ',', '.') . "\n\n" .
                "Por favor contacta soporte si tienes dudas.\n\n" .
                "--\nAngelow";

            $headers = 'From: soporte@angelow.com' . "\r\n" .
                       'Reply-To: soporte@angelow.com' . "\r\n" .
                       'X-Mailer: PHP/' . phpversion();

            $sent = @mail($to, $subject, $plaintext, $headers);
            if ($sent) {
                error_log('[INVOICE_EMAIL] Fallback mail() success for cancellation to ' . $to . ' (order ' . ($order['order_number'] ?? $order['id']) . ')');
                return true;
            }
            error_log('[INVOICE_EMAIL] Fallback mail() failed for cancellation to ' . $to . ' (order ' . ($order['order_number'] ?? $order['id']) . ')');
        } catch (Throwable $e2) {
            error_log('[INVOICE_EMAIL] Fallback mail() exception for cancellation: ' . $e2->getMessage());
        }
        return false;
    }
}

function sendRefundConfirmationEmail(array $order, array $orderItems, array $metadata = []): bool
{
    if (empty($order['user_email'])) {
        error_log('[INVOICE_EMAIL] Orden sin email para confirmación de reembolso: ' . ($order['order_number'] ?? 'N/A'));
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log('[INVOICE_EMAIL][SMTP_DEBUG] ' . trim($str));
            };
        }
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('soporte@angelow.com', 'Angelow Pagos');
        $mail->addAddress($order['user_email'], $order['user_name'] ?? 'Cliente Angelow');

        $logoPath = realpath(__DIR__ . '/../../../images/logo2.png');
        $logoEmbedId = 'refund_logo';
        $logoUrl = BASE_URL . '/images/logo2.png';
        if ($logoPath && file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, $logoEmbedId);
            $logoUrl = 'cid:' . $logoEmbedId;
        }

        $refundAmount = $metadata['amount'] ?? $order['total'] ?? array_sum(array_column($orderItems, 'total'));
        $paymentMethod = $metadata['payment_method'] ?? ($order['payment_method'] ?? 'Transferencia bancaria');
        $reference = $metadata['reference'] ?? ($order['reference_number'] ?? 'N/A');
        $gateway = $metadata['gateway'] ?? 'Angelow';
        $orderNumber = $order['order_number'] ?? $order['id'] ?? 'N/A';

        $itemsHtml = '';
        foreach ($orderItems as $item) {
            $imageUrl = invoiceEmbedImageForMail($mail, $item['primary_image'] ?? null);
            $itemsHtml .= '<tr style="border-bottom:1px solid #eef2ff;">
                <td style="padding:12px 8px; width:60px;"><img src="' . htmlspecialchars($imageUrl) . '" style="width:48px; height:48px; border-radius:8px; object-fit:cover; border:1px solid #e2e8f0;"></td>
                <td style="padding:12px 8px;">
                    <div style="font-weight:600; color:#0f172a;">' . htmlspecialchars($item['product_name'] ?? 'Producto Angelow') . '</div>
                    <div style="color:#64748b; font-size:13px;">Cantidad: ' . (int) ($item['quantity'] ?? 1) . '</div>
                </td>
                <td style="padding:12px 8px; text-align:right; font-weight:600; color:#0f172a;">$' . number_format($item['total'] ?? (($item['quantity'] ?? 1) * ($item['price'] ?? 0)), 0, ',', '.') . '</td>
            </tr>';
        }
        if ($itemsHtml === '') {
            $itemsHtml = '<tr><td colspan="3" style="padding:16px; text-align:center; color:#64748b;">No fue posible mostrar los productos de este pedido.</td></tr>';
        }

        $mail->isHTML(true);
        $mail->Subject = 'Reembolso exitoso · Pedido #' . htmlspecialchars($orderNumber);
        $mail->Body = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reembolso Angelow</title>
    <style>
        body { font-family: "Inter", Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 24px; color: #0f172a; }
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 25px 60px rgba(15,23,42,0.08); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #2968c8, #1e4d9c); color: white; padding: 40px 32px; text-align: center; }
        .header img { max-height: 56px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 26px; }
        .content { padding: 32px; }
        .highlight-card { background: #f0f7ff; border: 1px solid #cbdffd; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .highlight-card h3 { margin: 0 0 12px 0; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; font-size: 14px; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 16px; }
        .detail-box { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        .detail-label { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .detail-value { font-size: 18px; font-weight: 700; color: #0f172a; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items-table td { padding: 12px 10px; font-size: 14px; }
        .items-table tr:nth-child(even) { background: #f8fafc; }
        .badge { display: inline-block; padding: 6px 14px; border-radius: 999px; background: rgba(16,185,129,0.15); color: #0f9d58; font-weight: 600; font-size: 13px; }
        .footer { text-align: center; padding: 28px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        .support { background: linear-gradient(135deg,#fff,#f0f7ff); border: 1px solid #d0e2ff; border-radius: 14px; padding: 20px; margin-top: 24px; text-align: center; }
        .support a { color: #1e4d9c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="' . $logoUrl . '" alt="Angelow">
            <h1>Reembolso exitoso</h1>
            <p style="opacity:0.9; font-size:15px;">Confirmamos que devolvimos tu dinero con el mismo método de pago que usaste.</p>
            <div style="margin-top:14px;"><span class="badge">Pedido #' . htmlspecialchars($orderNumber) . '</span></div>
        </div>
        <div class="content">
            <div class="highlight-card">
                <h3>Resumen del reembolso</h3>
                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="detail-label">Monto devuelto</div>
                        <div class="detail-value">$' . number_format((float) $refundAmount, 0, ',', '.') . '</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Método</div>
                        <div class="detail-value" style="font-size:16px;">' . htmlspecialchars(ucfirst($paymentMethod)) . '</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Referencia</div>
                        <div class="detail-value" style="font-size:16px;">' . htmlspecialchars($reference) . '</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Procesado por</div>
                        <div class="detail-value" style="font-size:16px;">' . htmlspecialchars($gateway) . '</div>
                    </div>
                </div>
                <p style="margin-top:16px; color:#475569; font-size:14px; line-height:1.6;">Dependiendo de tu banco, el reembolso puede visualizarse en tu extracto entre <strong>24 y 72 horas</strong>. Conserva este correo como comprobante.</p>
            </div>
            <h3 style="color:#1f2937; font-size:16px; margin-bottom:12px;">Productos asociados</h3>
            <table class="items-table">
                <tbody>' . $itemsHtml . '</tbody>
            </table>
            <div class="support">
                <p style="margin:0 0 10px 0; font-size:15px; color:#1f2937; font-weight:600;">¿Necesitas soporte adicional?</p>
                <p style="margin:0; color:#475569;">Escríbenos a <strong>soporte@angelow.com</strong> o responde este correo con tu número de contacto.</p>
            </div>
        </div>
        <div class="footer">
            © ' . date('Y') . ' Angelow Ropa Infantil · Este es un mensaje automático.
        </div>
    </div>
</body>
</html>';

        $mail->send();
        error_log('[INVOICE_EMAIL] Refund email sent successfully to ' . $order['user_email'] . ' for order ' . ($order['order_number'] ?? $order['id']));
        return true;
    } catch (Exception $e) {
        error_log('[INVOICE_EMAIL] Error reembolso (PHPMailer): ' . $e->getMessage());

        // Fallback: intentar enviar un email simple con mail() para entornos donde SMTP falla
        try {
            $to = $order['user_email'];
            $subject = 'Reembolso confirmado · Pedido #' . ($order['order_number'] ?? $order['id']);
            $plaintext = "Hola " . ($order['user_name'] ?? "") . ",\n\n" .
                "Tu reembolso por el pedido #" . ($order['order_number'] ?? $order['id']) . " se ha procesado.\n" .
                "Monto: $" . number_format((float)$refundAmount, 0, ',', '.') . "\n" .
                "Método: " . ($paymentMethod) . "\n" .
                "Referencia: " . ($reference) . "\n\n" .
                "Por favor contacta soporte si tienes dudas.\n\n" .
                "--\nAngelow";

            $headers = 'From: soporte@angelow.com' . "\r\n" .
                       'Reply-To: soporte@angelow.com' . "\r\n" .
                       'X-Mailer: PHP/' . phpversion();

            $sent = @mail($to, $subject, $plaintext, $headers);
            if ($sent) {
                error_log('[INVOICE_EMAIL] Fallback mail() success for refund to ' . $to . ' (order ' . ($order['order_number'] ?? $order['id']) . ')');
                return true;
            }
            error_log('[INVOICE_EMAIL] Fallback mail() failed for refund to ' . $to . ' (order ' . ($order['order_number'] ?? $order['id']) . ')');
        } catch (Throwable $e2) {
            error_log('[INVOICE_EMAIL] Fallback mail() exception: ' . $e2->getMessage());
        }
        return false;
    }
}
