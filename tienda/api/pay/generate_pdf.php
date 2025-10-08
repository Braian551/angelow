<?php
// Generador de PDF para comprobantes de pedido (usa TCPDF)
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * Genera el PDF del pedido y devuelve el contenido binario (string) o null si falla.
 * @param array $order
 * @param array $orderItems
 * @return string|null
 */
function generateOrderPdfContent(array $order, array $orderItems) {
    // Verificar disponibilidad de la clase TCPDF
    if (!class_exists('TCPDF') && !class_exists('\TCPDF')) {
        error_log('TCPDF no disponible para generar PDF');
        return null;
    }

    try {
        $orientation = defined('PDF_PAGE_ORIENTATION') ? PDF_PAGE_ORIENTATION : 'P';
        $unit = defined('PDF_UNIT') ? PDF_UNIT : 'mm';
        $pageFormat = 'LETTER';
        $filename = 'comprobante.pdf';

        $pdf = new \TCPDF($orientation, $unit, $pageFormat, true, 'UTF-8', false);
        $pdf->SetCreator(defined('PDF_CREATOR') ? PDF_CREATOR : SITE_NAME);
        $pdf->SetAuthor(SITE_NAME);
        $pdf->SetTitle('Comprobante de Pedido ' . ($order['order_number'] ?? ''));
        $pdf->SetSubject('Comprobante de Pedido');
        $pdf->SetKeywords('Pedido, Comprobante, ' . SITE_NAME);
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->setPrintFooter(true);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Logo
        $logoPath = __DIR__ . '/../../../images/logo2.png';
        $logoExists = file_exists($logoPath);

        // Construir el HTML (basado en el diseño usado en confirmacion.php)
        $html = '<style>
            .header-title { color: #006699; font-size: 16pt; font-weight: bold; margin-bottom: 5px; }
            .header-subtitle { color: #666666; font-size: 10pt; margin-top: 0; }
            .section-title { color: #006699; padding: 6px 8px; font-size: 11pt; font-weight: bold; margin-top: 15px; border-radius: 3px; }
            .label { font-weight: bold; color: #333333; width: 120px; display: inline-block; }
            .value { color: #555555; }
            .order-number { font-size: 20pt; color: #006699; font-weight: bold; text-align: center; margin: 20px 0; letter-spacing: 2px; }
            .order-total { font-size: 16pt; color: #FF6600; font-weight: bold; text-align: center; margin: 10px 0; }
            .product-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .product-table th { background-color: #006699; color: #FFFFFF; border: 1px solid #DDDDDD; padding: 8px; font-weight: bold; font-size: 9pt; }
            .product-table td { border: 1px solid #DDDDDD; padding: 8px; font-size: 9pt; }
            .summary-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            </style>';

        $html .= '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td width="40%">';
        if ($logoExists) {
            $html .= '<img src="' . $logoPath . '" width="180">';
        } else {
            $html .= '<h1 class="header-title">' . SITE_NAME . '</h1><p class="header-subtitle">Moda infantil de calidad</p>';
        }
        $html .= '</td><td width="60%" style="text-align:right;vertical-align:top;">';
        $html .= '<h1 class="header-title">COMPROBANTE DE PEDIDO</h1>';
        $html .= '<p><span class="label">Fecha:</span> ' . (!empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A') . '</p>';
        $html .= '<p><span class="label">Cliente:</span> ' . htmlspecialchars($order['user_name'] ?? '') . '</p>';
        $html .= '</td></tr></table>';

        $html .= '<div class="order-number">PEDIDO #' . htmlspecialchars($order['order_number'] ?? '') . '</div>';
        $html .= '<div class="order-total">TOTAL: $' . number_format($order['total'] ?? 0, 0, ',', '.') . '</div>';

        $html .= '<h3 class="section-title">INFORMACIÓN DEL PEDIDO</h3>';
        $html .= '<table border="0" cellpadding="3" cellspacing="0" width="100%">';
        $html .= '<tr><td width="25%"><span class="label">Número de orden:</span></td><td width="75%" class="value">' . htmlspecialchars($order['order_number'] ?? '') . '</td></tr>';
        $html .= '<tr><td><span class="label">Fecha:</span></td><td class="value">' . (!empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A') . '</td></tr>';
        $html .= '<tr><td><span class="label">Estado:</span></td><td class="value">' . htmlspecialchars($order['status'] ?? '') . '</td></tr>';
        $html .= '<tr><td><span class="label">Método de pago:</span></td><td class="value">Transferencia bancaria</td></tr>';
        $html .= '<tr><td><span class="label">Referencia:</span></td><td class="value">' . htmlspecialchars($order['reference_number'] ?? '') . '</td></tr>';
        $html .= '</table>';

        $html .= '<h3 class="section-title">INFORMACIÓN DE ENVÍO</h3>';
        $html .= '<table border="0" cellpadding="3" cellspacing="0" width="100%">';
        $html .= '<tr><td width="25%"><span class="label">Cliente:</span></td><td width="75%" class="value">' . htmlspecialchars($order['user_name'] ?? '') . '</td></tr>';
        if (!empty($order['user_phone'])) {
            $html .= '<tr><td><span class="label">Teléfono:</span></td><td class="value">' . htmlspecialchars($order['user_phone']) . '</td></tr>';
        }
        $html .= '<tr><td><span class="label">Email:</span></td><td class="value">' . htmlspecialchars($order['user_email'] ?? '') . '</td></tr>';
        $html .= '<tr><td><span class="label">Dirección:</span></td><td class="value">' . htmlspecialchars($order['shipping_address'] ?? '') . '</td></tr>';
        $html .= '</table>';

        // Productos
        $html .= '<h3 class="section-title">PRODUCTOS DEL PEDIDO</h3>';
        $html .= '<table class="product-table"><thead><tr><th style="text-align:left;">Producto</th><th style="text-align:center;">Variante</th><th style="text-align:center;">Cantidad</th><th style="text-align:right;">Precio Unit.</th><th style="text-align:right;">Total</th></tr></thead><tbody>';
        foreach ($orderItems as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['product_name'] ?? '') . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($item['variant_name'] ?? 'N/A') . '</td>';
            $html .= '<td style="text-align:center;">' . intval($item['quantity'] ?? 0) . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($item['price'] ?? 0, 0, ',', '.') . '</td>';
            $html .= '<td style="text-align:right;">$' . number_format($item['total'] ?? 0, 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // Resumen
        $html .= '<h3 class="section-title">RESUMEN DE PAGOS</h3>';
        $html .= '<table class="summary-table">';
        $html .= '<tr><td class="text-left">Subtotal:</td><td class="text-right">$' . number_format($order['subtotal'] ?? 0, 0, ',', '.') . '</td></tr>';
        if (!empty($order['discount_amount']) && $order['discount_amount'] > 0) {
            $html .= '<tr><td class="text-left">Descuento:</td><td class="text-right">-$' . number_format($order['discount_amount'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr><td class="text-left">Costo de envío:</td><td class="text-right">$' . number_format($order['shipping_cost'] ?? 0, 0, ',', '.') . '</td></tr>';
        $html .= '<tr class="total-row"><td class="text-left"><strong>TOTAL:</strong></td><td class="text-right"><strong>$' . number_format($order['total'] ?? 0, 0, ',', '.') . '</strong></td></tr>';
        $html .= '</table>';

        $html .= '<div class="footer"><strong>' . htmlspecialchars(SITE_NAME) . '</strong><br>' . (defined('SITE_CONTACT') ? constant('SITE_CONTACT') : 'Contacto') . ' | Email: ' . (defined('SITE_EMAIL') ? constant('SITE_EMAIL') : 'no-reply@ejemplo.com') . '<br>' . (defined('SITE_ADDRESS') ? constant('SITE_ADDRESS') : 'Dirección no disponible') . ' - ' . (defined('SITE_URL') ? constant('SITE_URL') : BASE_URL) . '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Devolver contenido
        return $pdf->Output($filename, 'S');
    } catch (Exception $e) {
        error_log('Error generando PDF: ' . $e->getMessage());
        return null;
    }
}

/**
 * Envía el PDF al navegador para descarga.
 * @param array $order
 * @param array $orderItems
 */
function streamOrderPdfDownload(array $order, array $orderItems) {
    $pdfContent = generateOrderPdfContent($order, $orderItems);
    if (empty($pdfContent)) {
        throw new Exception('No se pudo generar el PDF para descargar');
    }

    $filename = 'comprobante_pedido_' . ($order['order_number'] ?? time()) . '.pdf';
    // Enviar headers y contenido
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdfContent;
}

?>
