<?php
// Envío de correo de confirmación de pedido usando PHPMailer
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOrderConfirmationEmail(array $order, array $orderItems, $pdfContent = null, $pdfFilename = null) {
    // Evitar enviar si no hay email
    if (empty($order['user_email'])) {
        error_log('No hay email de usuario para enviar confirmación de pedido: ' . ($order['order_number'] ?? 'N/A'));
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // Configuración SMTP (igual que en otros ejemplos del proyecto)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('angelow2025sen@gmail.com', 'Angelow');
        $mail->addAddress($order['user_email'], $order['user_name'] ?? 'Cliente');

        $mail->isHTML(true);
        $mail->Subject = 'Confirmación de tu pedido #' . htmlspecialchars($order['order_number']);

        // Construir cuerpo HTML con estilo más profesional
        $logoUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/images/logo2.png' : '';

        $itemsHtml = '';
        foreach ($orderItems as $it) {
            $itemsHtml .= '<tr>' .
                '<td style="padding:8px;border-bottom:1px solid #e6e6e6;">' . htmlspecialchars($it['product_name']) . '</td>' .
                '<td style="padding:8px;border-bottom:1px solid #e6e6e6;text-align:center;">' . intval($it['quantity']) . '</td>' .
                '<td style="padding:8px;border-bottom:1px solid #e6e6e6;text-align:right;">$' . number_format($it['price'], 0, ',', '.') . '</td>' .
                '<td style="padding:8px;border-bottom:1px solid #e6e6e6;text-align:right;">$' . number_format($it['total'], 0, ',', '.') . '</td>' .
            '</tr>';
        }

        $body = '<!doctype html><html><head><meta charset="utf-8"><title>Confirmación de pedido</title></head><body>' .
            '<div style="font-family:Arial,Helvetica,sans-serif;color:#333;max-width:700px;margin:0 auto;padding:20px;">' .
            '<div style="text-align:center;padding:20px 0;">' .
            ($logoUrl ? '<img src="' . $logoUrl . '" alt="' . htmlspecialchars(SITE_NAME) . '" style="max-height:60px;">' : '<h1 style="margin:0;color:#2968c8;">' . htmlspecialchars(SITE_NAME) . '</h1>') .
            '</div>' .
            '<div style="background:#ffffff;border:1px solid #f0f0f0;border-radius:8px;overflow:hidden;">' .
            '<div style="background:#2968c8;color:#fff;padding:18px 20px;">' .
            '<h2 style="margin:0;font-size:18px;">Tu pedido ha sido recibido</h2>' .
            '</div>' .
            '<div style="padding:18px 20px;">' .
            '<p>Hola ' . htmlspecialchars($order['user_name'] ?? '') . ',</p>' .
            '<p>Gracias por comprar en <strong>' . htmlspecialchars(SITE_NAME) . '</strong>. Aquí tienes los detalles de tu pedido <strong>#' . htmlspecialchars($order['order_number']) . '</strong>:</p>' .
            '<table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:14px;">' .
            '<thead><tr style="background:#fafafa;"><th style="text-align:left;padding:8px;border-bottom:1px solid #e6e6e6;">Producto</th><th style="padding:8px;border-bottom:1px solid #e6e6e6;">Cant.</th><th style="padding:8px;border-bottom:1px solid #e6e6e6;text-align:right;">Precio U.</th><th style="padding:8px;border-bottom:1px solid #e6e6e6;text-align:right;">Total</th></tr></thead>' .
            '<tbody>' . $itemsHtml . '</tbody>' .
            '</table>' .
            '<div style="margin-top:18px;text-align:right;font-size:16px;"><strong>Total: $' . number_format($order['total'], 0, ',', '.') . '</strong></div>' .
            '<p style="margin-top:12px;">Referencia de pago: <strong>' . htmlspecialchars($order['reference_number'] ?? '') . '</strong></p>' .
            '<p style="margin-top:6px;color:#666;font-size:13px;">Si necesitas ayuda, responde a este correo o visita <a href="' . BASE_URL . '" style="color:#2968c8;">nuestra tienda</a>.</p>' .
            '</div>' .
            '<div style="background:#fafafa;padding:12px 20px;font-size:12px;color:#777;">' .
            '<p style="margin:0;">© ' . date('Y') . ' ' . htmlspecialchars(SITE_NAME) . ' — Todos los derechos reservados.</p>' .
            '</div>' .
            '</div></body></html>';

        $mail->Body = $body;

        // Adjuntar PDF si se proporcionó
        if (!empty($pdfContent) && !empty($pdfFilename)) {
            try {
                $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');
            } catch (Exception $e) {
                error_log('Error adjuntando PDF al correo: ' . $e->getMessage());
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Error al enviar correo de confirmación: ' . $e->getMessage());
        return false;
    }
}

?>
