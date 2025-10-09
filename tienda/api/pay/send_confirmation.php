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
        // Configuración SMTP
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
        $mail->Subject = 'Confirmación de Pedido #' . htmlspecialchars($order['order_number']);

        // Añadir logo al email como imagen embebida
        $logoPath = realpath(__DIR__ . '/../../../images/logo2.png');
        $logoEmbedId = 'logo';
        $logoUrl = BASE_URL . '/images/logo2.png'; // URL por defecto
        
        if ($logoPath && file_exists($logoPath)) {
            try {
                $mail->addEmbeddedImage($logoPath, $logoEmbedId, 'logo.png');
                $logoUrl = 'cid:' . $logoEmbedId;
            } catch (Exception $e) {
                error_log('Error al embeber logo en email: ' . $e->getMessage());
            }
        } else {
            error_log('Logo no encontrado en: ' . __DIR__ . '/../../../images/logo2.png');
        }

        $itemsHtml = '';
        $itemCount = 0;
        foreach ($orderItems as $it) {
            $itemCount++;
            $defaultImagePath = realpath(__DIR__ . '/../../../images/default-product.jpg');
            // Asegurarnos de que primary_image no contenga la URL completa
            $rawPath = !empty($it['primary_image']) ? $it['primary_image'] : 'images/default-product.jpg';
            
            // Limpiar la URL si existe
            $imagePath = str_replace(
                [BASE_URL . '/', 'http://localhost/angelow/', 'https://localhost/angelow/'],
                '',
                $rawPath
            );
            
            // Obtener la ruta absoluta
            $productImagePath = realpath(__DIR__ . '/../../../' . $imagePath);
            
            $embedId = 'product_' . md5(uniqid()); // ID único para cada imagen
            $imageUrl = BASE_URL . (!empty($it['primary_image']) ? '/' . $it['primary_image'] : '/images/default-product.jpg'); // URL por defecto
            
            if ($productImagePath && file_exists($productImagePath)) {
                if (is_readable($productImagePath)) {
                    try {
                        $mail->addEmbeddedImage($productImagePath, $embedId, basename($productImagePath));
                        $imageUrl = 'cid:' . $embedId;
                    } catch (Exception $e) {
                        error_log('Error al embeber imagen de producto en email: ' . $e->getMessage());
                    }
                }
            }

            $rowStyle = $itemCount % 2 === 0 ? 'background-color: #fafafa;' : 'background-color: #ffffff;';
            
            $itemsHtml .= '<tr style="' . $rowStyle . '">' .
                '<td style="padding: 16px; border-bottom: 1px solid #e8e8e8; vertical-align: middle;">' .
                '<div style="display: flex; align-items: center; gap: 12px;">' .
                '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($it['product_name']) . '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0;">' .
                '<div>' .
                '<div style="font-weight: 600; color: #333; margin-bottom: 4px;">' . htmlspecialchars($it['product_name']) . '</div>' .
                '<div style="font-size: 13px; color: #666;">' . htmlspecialchars($it['variant_name'] ?? '') . '</div>' .
                '</div>' .
                '</div>' .
                '</td>' .
                '<td style="padding: 16px; border-bottom: 1px solid #e8e8e8; text-align: center; vertical-align: middle; font-weight: 500;">' . intval($it['quantity']) . '</td>' .
                '<td style="padding: 16px; border-bottom: 1px solid #e8e8e8; text-align: right; vertical-align: middle; color: #333; font-weight: 500;">$' . number_format($it['price'], 0, ',', '.') . '</td>' .
                '<td style="padding: 16px; border-bottom: 1px solid #e8e8e8; text-align: right; vertical-align: middle; color: #2968c8; font-weight: 600;">$' . number_format($it['total'], 0, ',', '.') . '</td>' .
            '</tr>';
        }

        // Calcular subtotales
        $subtotal = $order['subtotal'] ?? array_sum(array_column($orderItems, 'total'));
        $shippingCost = $order['shipping_cost'] ?? 0;
        $discountAmount = $order['discount_amount'] ?? 0;
        $tax = $order['tax'] ?? 0;

        $body = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - ' . htmlspecialchars(SITE_NAME) . '</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", Arial, sans-serif; background-color: #f8f9fa; color: #333333; line-height: 1.6; }
        .container { max-width: 700px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #2968c8 0%, #1e4d9c 100%); color: #ffffff; padding: 30px; text-align: center; }
        .logo { max-height: 50px; margin-bottom: 15px; }
        .content { padding: 40px; }
        .greeting { font-size: 18px; margin-bottom: 20px; color: #333; }
        .order-number { background: #f0f7ff; border: 1px solid #d1e3ff; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center; }
        .order-number strong { color: #2968c8; font-size: 20px; }
        .section-title { font-size: 18px; font-weight: 600; color: #2968c8; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #f0f7ff; }
        .section-title i { margin-right: 10px; width: 20px; text-align: center; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th { background: #f8fafc; color: #2968c8; font-weight: 600; padding: 16px; text-align: left; border-bottom: 2px solid #e8e8e8; }
        .summary { background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-total { border-top: 2px solid #e8e8e8; margin-top: 10px; padding-top: 15px; font-size: 18px; font-weight: 700; color: #2968c8; }
        .payment-info { background: #fff8e6; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .footer { background: #f8f9fa; color: #666; padding: 30px; text-align: center; font-size: 14px; border-top: 1px solid #e8e8e8; }
        .social-links { margin: 20px 0; }
        .social-links a { margin: 0 10px; color: #2968c8; text-decoration: none; }
        .badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .step-icon { font-size: 24px; margin-bottom: 10px; color: #2968c8; }
        .step-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .step { text-align: center; padding: 20px; background: #f0f7ff; border-radius: 8px; }
        .contact-btn { background: #2968c8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; }
        @media (max-width: 600px) {
            .content { padding: 20px; }
            .table { font-size: 14px; }
            .table th, .table td { padding: 12px 8px; }
            .step-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="' . $logoUrl . '" alt="' . htmlspecialchars(SITE_NAME) . '" class="logo">
            <h1 style="margin: 10px 0; font-size: 28px; font-weight: 700;">
                <i class="fas fa-check-circle" style="margin-right: 10px;"></i>¡Pedido Confirmado!
            </h1>
            <p style="font-size: 16px; opacity: 0.9;">Gracias por tu compra en ' . htmlspecialchars(SITE_NAME) . '</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                <p>Hola <strong>' . htmlspecialchars($order['user_name'] ?? '') . '</strong>,</p>
                <p>Tu pedido ha sido procesado exitosamente. Estamos preparando todo para que lo recibas pronto.</p>
            </div>

            <div class="order-number">
                <span style="font-size: 16px;">Número de pedido:</span><br>
                <strong>#' . htmlspecialchars($order['order_number']) . '</strong>
                <div style="margin-top: 8px;">
                    <span class="badge"><i class="fas fa-check" style="margin-right: 5px;"></i>Confirmado</span>
                </div>
            </div>

            <!-- Resumen del Pedido -->
            <div class="section-title">
                <i class="fas fa-box"></i>Resumen del Pedido
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Producto</th>
                        <th style="width: 15%; text-align: center;">Cantidad</th>
                        <th style="width: 20%; text-align: right;">Precio Unitario</th>
                        <th style="width: 20%; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>

            <!-- Resumen de Pagos -->
            <div class="section-title">
                <i class="fas fa-receipt"></i>Resumen de Pagos
            </div>
            <div class="summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$' . number_format($subtotal, 0, ',', '.') . '</span>
                </div>';
                
                if ($discountAmount > 0) {
                    $body .= '<div class="summary-row" style="color: #10b981;">
                        <span><i class="fas fa-tag" style="margin-right: 8px;"></i>Descuento:</span>
                        <span>-$' . number_format($discountAmount, 0, ',', '.') . '</span>
                    </div>';
                }
                
                if ($shippingCost > 0) {
                    $body .= '<div class="summary-row">
                        <span><i class="fas fa-shipping-fast" style="margin-right: 8px;"></i>Costo de Envío:</span>
                        <span>$' . number_format($shippingCost, 0, ',', '.') . '</span>
                    </div>';
                } else {
                    $body .= '<div class="summary-row" style="color: #10b981;">
                        <span><i class="fas fa-shipping-fast" style="margin-right: 8px;"></i>Costo de Envío:</span>
                        <span>¡GRATIS!</span>
                    </div>';
                }
                
                if ($tax > 0) {
                    $body .= '<div class="summary-row">
                        <span><i class="fas fa-percentage" style="margin-right: 8px;"></i>Impuestos:</span>
                        <span>$' . number_format($tax, 0, ',', '.') . '</span>
                    </div>';
                }
                
                $body .= '<div class="summary-row summary-total">
                    <span><strong>Total Final:</strong></span>
                    <span><strong>$' . number_format($order['total'], 0, ',', '.') . '</strong></span>
                </div>
            </div>

            <!-- Información de Pago -->
            <div class="section-title">
                <i class="fas fa-credit-card"></i>Información de Pago
            </div>
            <div class="payment-info">
                <p><strong><i class="fas fa-wallet" style="margin-right: 8px;"></i>Método de pago:</strong> ' . htmlspecialchars($order['payment_method'] ?? 'Transferencia') . '</p>';
                
                if (!empty($order['reference_number'])) {
                    $body .= '<p><strong><i class="fas fa-hashtag" style="margin-right: 8px;"></i>Referencia de pago:</strong> <span style="background: #fff; padding: 4px 8px; border-radius: 4px; font-family: monospace;">' . htmlspecialchars($order['reference_number']) . '</span></p>';
                }
                
                $body .= '<p style="margin-top: 10px; font-size: 14px; color: #666;">
                    <strong><i class="fas fa-info-circle" style="margin-right: 8px;"></i>Nota importante:</strong> Conserva esta referencia para cualquier consulta sobre tu pedido.
                </p>
            </div>

            <!-- Información de Envío -->
            <div class="section-title">
                <i class="fas fa-truck"></i>Información de Envío
            </div>
            <div style="background: #f0f7ff; padding: 20px; border-radius: 8px; margin: 15px 0;">
                <p><strong><i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>Dirección de envío:</strong><br>' . nl2br(htmlspecialchars($order['shipping_address'] ?? '')) . '</p>';
                
                if (!empty($order['shipping_city'])) {
                    $body .= '<p><strong><i class="fas fa-city" style="margin-right: 8px;"></i>Ciudad:</strong> ' . htmlspecialchars($order['shipping_city']) . '</p>';
                }
                
                if (!empty($order['delivery_notes'])) {
                    $body .= '<p><strong><i class="fas fa-clipboard-list" style="margin-right: 8px;"></i>Instrucciones de entrega:</strong><br>' . nl2br(htmlspecialchars($order['delivery_notes'])) . '</p>';
                }
                
                $body .= '</div>

            <!-- Próximos Pasos -->
            <div class="section-title">
                <i class="fas fa-list-alt"></i>Próximos Pasos
            </div>
            <div class="step-container">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <strong>Preparación</strong>
                    <p style="font-size: 14px; margin-top: 8px;">Estamos preparando tu pedido con cuidado</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <strong>Envío</strong>
                    <p style="font-size: 14px; margin-top: 8px;">Tu pedido será enviado pronto</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <strong>Entrega</strong>
                    <p style="font-size: 14px; margin-top: 8px;">Recibirás tu pedido en la dirección indicada</p>
                </div>
            </div>

            <!-- Asistencia -->
            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 30px 0; text-align: center;">
                <h3 style="color: #2968c8; margin-bottom: 10px;">
                    <i class="fas fa-question-circle" style="margin-right: 10px;"></i>¿Necesitas ayuda?
                </h3>
                <p style="margin-bottom: 15px;">Estamos aquí para ayudarte con cualquier pregunta sobre tu pedido.</p>
                <a href="' . BASE_URL . '/contacto" class="contact-btn">
                    <i class="fas fa-headset" style="margin-right: 8px;"></i>Contactar Soporte
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="social-links">
                <a href="' . BASE_URL . '"><i class="fas fa-store" style="margin-right: 5px;"></i>Visitar Tienda</a> | 
                <a href="' . BASE_URL . '/mi-cuenta/pedidos"><i class="fas fa-clipboard-list" style="margin-right: 5px;"></i>Ver Mis Pedidos</a> | 
                <a href="' . BASE_URL . '/contacto"><i class="fas fa-phone" style="margin-right: 5px;"></i>Contacto</a>
            </div>
            <p><i class="far fa-copyright" style="margin-right: 5px;"></i>' . date('Y') . ' ' . htmlspecialchars(SITE_NAME) . '. Todos los derechos reservados.</p>
            <p style="font-size: 12px; margin-top: 10px; color: #888;">
                <i class="fas fa-info-circle" style="margin-right: 5px;"></i>Este es un correo automático, por favor no respondas a este mensaje.<br>
                Si tienes alguna pregunta, contáctanos a través de nuestro sitio web.
            </p>
        </div>
    </div>
</body>
</html>';

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