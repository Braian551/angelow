<?php
// tienda/api/pay/pdf_helpers.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera el contenido PDF del comprobante de pedido y lo devuelve como string
 */

function generateOrderPdfContent($order, $orderItems) {
    // Configurar Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    $options->set('enable_php', true);
    $options->set('isPhpEnabled', true);
    
    $dompdf = new Dompdf($options);

    // HTML para el contenido del PDF (mismo que el archivo principal)
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Helvetica, Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #006699;
                padding-bottom: 20px;
            }
            .header-title {
                color: #006699;
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .header-subtitle {
                color: #666666;
                font-size: 14px;
                margin-top: 0;
            }
            .company-info {
                text-align: center;
                margin-bottom: 20px;
                font-size: 10px;
                color: #666;
            }
            .order-info {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .info-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 20px;
            }
            .info-section {
                flex: 1;
                min-width: 250px;
            }
            .section-title {
                color: #006699;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            .info-item {
                margin-bottom: 5px;
            }
            .info-label {
                font-weight: bold;
                color: #333;
                display: inline-block;
                width: 120px;
            }
            .info-value {
                color: #555;
            }
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .products-table th {
                background-color: #006699;
                color: white;
                padding: 8px;
                text-align: left;
                font-weight: bold;
                font-size: 11px;
            }
            .products-table td {
                border: 1px solid #ddd;
                padding: 8px;
                font-size: 10px;
                vertical-align: top;
            }
            .product-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 3px;
            }
            .totals-section {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .total-row {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px solid #eee;
            }
            .total-row:last-child {
                border-bottom: none;
            }
            .grand-total {
                font-weight: bold;
                font-size: 14px;
                color: #006699;
                border-top: 2px solid #ddd;
                margin-top: 5px;
                padding-top: 10px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 10px;
                color: #666;
            }
            .discount-text {
                color: #28a745;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: bold;
            }
            .status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .status-confirmed {
                background: #d1ecf1;
                color: #0c5460;
            }
            .status-completed {
                background: #d4edda;
                color: #155724;
            }
            .notes {
                background: #fff3cd;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
                font-size: 10px;
                border-left: 3px solid #ffc107;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Encabezado -->
            <div class="header">';
    
    // Agregar el logo usando BASE_URL
    $logoUrl = BASE_URL . '/images/logo2.png';
    $html .= '<img src="' . $logoUrl . '" style="width:150px; margin-bottom:10px;" alt="Logo Angelow">';
    
    $html .= '
                <h1 class="header-title">Angelow Ropa Infantil</h1>
                <p class="header-subtitle">Comprobante de Pedido</p>
            </div>

            <!-- Información de la empresa -->
            <div class="company-info">
                <strong>Angelow Ropa Infantil</strong><br>
                NIT: 901234567-8 | Tel: +57 604 1234567 | Email: contacto@angelow.com<br>
                Calle 10 # 40-20, Medellín, Antioquia - www.angelow.com
            </div>

            <!-- Información del pedido -->
            <div class="order-info">
                <div class="info-grid">
                    <div class="info-section">
                        <h3 class="section-title">Información del Pedido</h3>
                        <div class="info-item">
                            <span class="info-label">Número de Orden:</span>
                            <span class="info-value">' . htmlspecialchars($order['order_number']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha:</span>
                            <span class="info-value">' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span class="info-value">
                                <span class="status-badge status-' . htmlspecialchars($order['status']) . '">
                                    ' . ucfirst(htmlspecialchars($order['status'])) . '
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Método de Pago:</span>
                            <span class="info-value">Transferencia Bancaria</span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3 class="section-title">Información del Cliente</h3>
                        <div class="info-item">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value">' . htmlspecialchars($order['user_name']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">' . htmlspecialchars($order['user_email']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">' . htmlspecialchars($order['user_phone'] ?? 'No proporcionado') . '</span>
                        </div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-section">
                        <h3 class="section-title">Dirección de Envío</h3>
                        <div class="info-item">
                            <span class="info-value">' . nl2br(htmlspecialchars($order['shipping_address'])) . '</span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3 class="section-title">Información de Pago</h3>
                        <div class="info-item">
                            <span class="info-label">Referencia:</span>
                            <span class="info-value">' . htmlspecialchars($order['reference_number']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha Pago:</span>
                            <span class="info-value">' . ($order['payment_date'] ? date('d/m/Y H:i', strtotime($order['payment_date'])) : 'Pendiente') . '</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <h3 class="section-title">Productos del Pedido</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Imagen</th>
                        <th>Producto</th>
                        <th style="width: 80px;">Precio</th>
                        <th style="width: 60px;">Cantidad</th>
                        <th style="width: 80px;">Total</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($orderItems as $item) {
        // Obtener la URL de la imagen
        $imageUrl = !empty($item['primary_image']) ? 
                   BASE_URL . '/' . $item['primary_image'] : 
                   BASE_URL . '/images/default-product.jpg';
                   
        $html .= '
                    <tr>
                        <td class="text-center">
                            <img src="' . $imageUrl . '" class="product-image" style="width:50px; height:50px; object-fit:cover;">';
        
        $html .= '
                        </td>
                        <td>
                            <strong>' . htmlspecialchars($item['product_name']) . '</strong>';
        
        if (!empty($item['variant_name'])) {
            $html .= '<br><small style="color:#666;">' . htmlspecialchars($item['variant_name']) . '</small>';
        }
        
        $html .= '
                        </td>
                        <td class="text-right">$' . number_format($item['price'], 0, ',', '.') . '</td>
                        <td class="text-center">' . $item['quantity'] . '</td>
                        <td class="text-right">$' . number_format($item['total'], 0, ',', '.') . '</td>
                    </tr>';
    }

    $html .= '
                </tbody>
            </table>

            <!-- Totales -->
            <div class="totals-section">
                <h3 class="section-title">Resumen de Pagos</h3>
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$' . number_format($order['subtotal'], 0, ',', '.') . '</span>
                </div>';

    if ($order['discount_amount'] > 0) {
        $html .= '
                <div class="total-row">
                    <span>Descuento:</span>
                    <span class="discount-text">-$' . number_format($order['discount_amount'], 0, ',', '.') . '</span>
                </div>';
    }

    $html .= '
                <div class="total-row">
                    <span>Costo de Envío:</span>
                    <span>$' . number_format($order['shipping_cost'], 0, ',', '.') . '</span>
                </div>
                <div class="total-row grand-total">
                    <span>TOTAL:</span>
                    <span>$' . number_format($order['total'], 0, ',', '.') . '</span>
                </div>
            </div>

            <!-- Notas importantes -->
            <div class="notes">
                <strong><i class="fas fa-info-circle"></i> Información Importante:</strong><br>
                • Este comprobante es generado automáticamente y no requiere firma.<br>
                • Para consultas sobre tu pedido, contacta a: contacto@angelow.com<br>
                • Horario de atención: Lunes a Viernes 8:00 AM - 6:00 PM<br>
                • Tiempo de procesamiento: 24-48 horas hábiles después de confirmado el pago.
            </div>

            <!-- Pie de página -->
            <div class="footer">
                <strong>Angelow Ropa Infantil</strong> - Moda infantil de calidad<br>
                Comprobante generado el ' . date('d/m/Y \a \l\a\s H:i') . ' | www.angelow.com
            </div>
        </div>
    </body>
    </html>';

    // Cargar el HTML en Dompdf
    $dompdf->loadHtml($html);
    
    // Configurar el tamaño y orientación del papel
    $dompdf->setPaper('A4', 'portrait');
    
    // Renderizar el PDF
    $dompdf->render();
    
    // Devolver el contenido del PDF
    return $dompdf->output();
}

/**
 * Función para enviar el PDF directamente al navegador
 */
function streamOrderPdfDownload($order, $orderItems) {
    $pdfContent = generateOrderPdfContent($order, $orderItems);
    
    if (!$pdfContent) {
        throw new Exception("No se pudo generar el PDF");
    }
    
    // Limpiar buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Configurar headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="comprobante_pedido_' . $order['order_number'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($pdfContent));
    
    echo $pdfContent;
    exit();
}
?>