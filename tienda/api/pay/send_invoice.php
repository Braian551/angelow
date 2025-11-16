<?php
// tienda/api/pay/send_invoice.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../pagos/helpers/shipping_helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function invoiceBuildPublicImageUrl(?string $path): string
{
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

function sendInvoiceEmail(array $order, array $orderItems, ?string $pdfContent = null, ?string $pdfFilename = null): bool
{
    if (empty($order['user_email'])) {
        error_log('[INVOICE_EMAIL] Orden sin email: ' . ($order['order_number'] ?? 'N/A'));
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
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
            $imageUrl = invoiceBuildPublicImageUrl($item['primary_image'] ?? null);
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

        $subtotal = $order['subtotal'] ?? array_sum(array_column($orderItems, 'total'));
        $discount = $order['discount_amount'] ?? 0;
        $shipping = $order['shipping_cost'] ?? 0;
        $total = $order['total'] ?? ($subtotal - $discount + $shipping);

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
                <table class="items-table">' . $itemsHtml . '</table>
                <div class="totals">
                    <div class="totals-row"><span>Subtotal</span><span>$' . number_format($subtotal, 0, ',', '.') . '</span></div>
                    ' . ($discount > 0 ? '<div class="totals-row"><span>Descuento</span><span>-$' . number_format($discount, 0, ',', '.') . '</span></div>' : '') . '
                    <div class="totals-row"><span>Envío</span><span>$' . number_format($shipping, 0, ',', '.') . '</span></div>
                    <div class="totals-row" style="font-size:16px; font-weight:700;"><span>Total pagado</span><span>$' . number_format($total, 0, ',', '.') . '</span></div>
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
