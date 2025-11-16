<?php
// tienda/api/pay/invoice_pdf_helpers.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../pagos/helpers/shipping_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function invoiceTranslatePaymentStatus(?string $status): string
{
    $map = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'cancelled' => 'Cancelado',
        'canceled' => 'Cancelado',
    ];

    $key = strtolower(trim((string) $status));
    if ($key === '') {
        return 'Pendiente';
    }

    if (isset($map[$key])) {
        return $map[$key];
    }

    return ucfirst($key);
}

/**
 * Helpers internos para resolver rutas de imagen siguiendo la lógica del comprobante original.
 */
function invoiceProjectBasePath(): string
{
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/angelow';
}

function invoiceNormalizeRelativePath(?string $relativePath): string
{
    if (empty($relativePath)) {
        return 'images/default-product.jpg';
    }

    $relativePath = ltrim($relativePath, '/');

    // Forzar uploads/productos cuando venga con otra carpeta
    if (strpos($relativePath, 'uploads/') === false && strpos($relativePath, 'images/') !== 0) {
        $relativePath = 'uploads/productos/' . basename($relativePath);
    }

    return $relativePath;
}

function invoiceAbsoluteImagePath(?string $relativePath): string
{
    $relative = invoiceNormalizeRelativePath($relativePath);
    $absolute = invoiceProjectBasePath() . '/' . $relative;

    if (!file_exists($absolute)) {
        return invoiceProjectBasePath() . '/images/default-product.jpg';
    }

    return $absolute;
}

function invoiceImageToBase64(?string $relativePath): string
{
    $absolute = invoiceAbsoluteImagePath($relativePath);

    if (!file_exists($absolute)) {
        $absolute = invoiceProjectBasePath() . '/images/default-product.jpg';
    }

    $data = @file_get_contents($absolute);
    if ($data === false) {
        $absolute = invoiceProjectBasePath() . '/images/default-product.jpg';
        $data = @file_get_contents($absolute) ?: '';
    }

    $ext = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'jpg';
    return 'data:image/' . $ext . ';base64,' . base64_encode($data);
}

/**
 * Genera el PDF de la factura (post-entrega) con el mismo diseño base del comprobante.
 */
function generateInvoicePdfContent(array $order, array $orderItems)
{
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    $options->set('enable_php', true);
    $options->set('isPhpEnabled', true);

    $dompdf = new Dompdf($options);

    $isStorePickup = isStorePickupMethod($order);
    $storeAddress = $isStorePickup ? getStorePickupAddress() : null;
    $routeLink = $isStorePickup ? buildStoreRouteLink($order['shipping_address'] ?? '') : null;

    $logoUrl = BASE_URL . '/images/logo2.png';
    $invoiceNumber = 'FAC-' . ($order['order_number'] ?? $order['id']);
    $issueDate = date('d/m/Y H:i');

    $paymentStatusLabel = invoiceTranslatePaymentStatus($order['payment_status'] ?? 'pending');

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; margin: 0; padding: 0; }
            .container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #006699; padding-bottom: 20px; position: relative; }
            .header-title { color: #006699; font-size: 24px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
            .header-subtitle { color: #0f172a; font-size: 15px; margin: 0; letter-spacing: 0.5px; }
            .invoice-meta { font-size: 11px; color: #0f172a; margin-top: 10px; }
            .company-info { text-align: center; margin-bottom: 20px; font-size: 10px; color: #666; }
            .card { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb; }
            .info-grid { display: flex; flex-wrap: wrap; gap: 20px; }
            .info-section { flex: 1; min-width: 250px; }
            .section-title { color: #006699; font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #d1d5db; padding-bottom: 5px; }
            .info-item { margin-bottom: 6px; }
            .info-label { font-weight: bold; color: #0f172a; display: inline-block; width: 130px; }
            .info-value { color: #475569; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #006699; color: #fff; padding: 8px; text-align: left; font-size: 11px; }
            td { border: 1px solid #e2e8f0; padding: 8px; font-size: 10px; vertical-align: top; }
            .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; }
            .totals-card { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
            .totals-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e5e7eb; }
            .totals-row:last-child { border-bottom: none; }
            .grand-total { font-size: 16px; font-weight: bold; color: #006699; border-top: 2px solid #d1d5db; margin-top: 6px; padding-top: 10px; }
            .notes { font-size: 10px; color: #475569; margin-top: 20px; line-height: 1.5; background: #eef2ff; border-left: 3px solid #6366f1; padding: 12px; border-radius: 4px; }
            .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; background: #dbeafe; color: #1d4ed8; }
            .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #64748b; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div style="position:absolute; top:20px; left:20px;">
                    <img src="' . $logoUrl . '" style="width:80px; height:auto;" alt="Logo Angelow">
                </div>
                <h1 class="header-title">Angelow Ropa Infantil</h1>
                <p class="header-subtitle">Factura electrónica</p>
                <div class="invoice-meta">
                    <strong>Número de factura:</strong> ' . htmlspecialchars($invoiceNumber) . ' ·
                    <strong>Emisión:</strong> ' . $issueDate . '
                </div>
            </div>

            <div class="company-info">
                NIT: 901234567-8 · Tel: +57 604 1234567 · contacto@angelow.com · Calle 10 # 40-20, Medellín
            </div>

            <div class="card">
                <div class="info-grid">
                    <div class="info-section">
                        <h3 class="section-title">Datos del cliente</h3>
                        <div class="info-item"><span class="info-label">Nombre:</span><span class="info-value">' . htmlspecialchars($order['user_name']) . '</span></div>
                        <div class="info-item"><span class="info-label">Documento:</span><span class="info-value">' . htmlspecialchars($order['identification_number'] ?? 'No reportado') . '</span></div>
                        <div class="info-item"><span class="info-label">Correo:</span><span class="info-value">' . htmlspecialchars($order['user_email']) . '</span></div>
                        <div class="info-item"><span class="info-label">Teléfono:</span><span class="info-value">' . htmlspecialchars($order['user_phone'] ?? 'No reportado') . '</span></div>
                    </div>
                    <div class="info-section">
                        <h3 class="section-title">Datos de la orden</h3>
                        <div class="info-item"><span class="info-label">Orden:</span><span class="info-value">' . htmlspecialchars($order['order_number']) . '</span></div>
                        <div class="info-item"><span class="info-label">Fecha orden:</span><span class="info-value">' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</span></div>
                        <div class="info-item"><span class="info-label">Estado pago:</span><span class="info-value"><span class="badge">' . htmlspecialchars($paymentStatusLabel) . '</span></span></div>
                        <div class="info-item"><span class="info-label">Método pago:</span><span class="info-value">Transferencia bancaria</span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="info-grid">
                    <div class="info-section">
                        <h3 class="section-title">Destino</h3>
                        <div class="info-item"><span class="info-value">' . nl2br(htmlspecialchars($isStorePickup ? $storeAddress : ($order['shipping_address'] ?? ''))) . '</span></div>
                        ' . ($isStorePickup && $routeLink ? '<div class="info-item"><span class="info-label">Ruta:</span><span class="info-value"><a href="' . htmlspecialchars($routeLink) . '">' . htmlspecialchars($routeLink) . '</a></span></div>' : '') . '
                    </div>
                    <div class="info-section">
                        <h3 class="section-title">Referencia de pago</h3>
                        <div class="info-item"><span class="info-label">Referencia:</span><span class="info-value">' . htmlspecialchars($order['reference_number'] ?? 'No aplica') . '</span></div>
                        <div class="info-item"><span class="info-label">Fecha pago:</span><span class="info-value">' . ($order['payment_date'] ? date('d/m/Y H:i', strtotime($order['payment_date'])) : 'Pendiente') . '</span></div>
                    </div>
                </div>
            </div>

            <h3 class="section-title">Detalle de productos</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width:60px;">Imagen</th>
                        <th>Producto</th>
                        <th style="width:80px;">Precio</th>
                        <th style="width:60px;">Cant.</th>
                        <th style="width:90px;">Total</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($orderItems as $item) {
        $imageSrc = invoiceImageToBase64($item['primary_image'] ?? '');
        $variantInfo = !empty($item['variant_name']) ? '<br><small style="color:#64748b;">' . htmlspecialchars($item['variant_name']) . '</small>' : '';
        $html .= '
                    <tr>
                        <td style="text-align:center;"><img src="' . $imageSrc . '" class="product-image"></td>
                        <td><strong>' . htmlspecialchars($item['product_name']) . '</strong>' . $variantInfo . '</td>
                        <td style="text-align:right;">$' . number_format($item['price'], 0, ',', '.') . '</td>
                        <td style="text-align:center;">' . (int) $item['quantity'] . '</td>
                        <td style="text-align:right;">$' . number_format($item['total'], 0, ',', '.') . '</td>
                    </tr>';
    }

    $html .= '
                </tbody>
            </table>

            <div class="totals-card">
                <div class="totals-row"><span>Subtotal</span><span>$' . number_format($order['subtotal'] ?? array_sum(array_column($orderItems, 'total')), 0, ',', '.') . '</span></div>';

    if (!empty($order['discount_amount']) && $order['discount_amount'] > 0) {
        $html .= '<div class="totals-row"><span>Descuento aplicado</span><span>-$' . number_format($order['discount_amount'], 0, ',', '.') . '</span></div>';
    }

    $html .= '<div class="totals-row"><span>Costo de envío</span><span>$' . number_format($order['shipping_cost'] ?? 0, 0, ',', '.') . '</span></div>
                <div class="totals-row grand-total"><span>Total factura</span><span>$' . number_format($order['total'], 0, ',', '.') . '</span></div>
            </div>

            <div class="notes">
                Factura emitida automáticamente tras la confirmación de entrega. Esta factura no requiere firma y tiene validez legal según la normatividad vigente. Para aclaraciones o soporte escribe a facturacion@angelow.com o comunícate al +57 604 1234567.
            </div>

            <div class="footer">
                Angelow Ropa Infantil · Documento generado el ' . $issueDate . '
            </div>
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}

function streamInvoicePdfDownload(array $order, array $orderItems)
{
    $pdfContent = generateInvoicePdfContent($order, $orderItems);
    if (!$pdfContent) {
        throw new Exception('No se pudo generar la factura');
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="factura_' . ($order['order_number'] ?? $order['id']) . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($pdfContent));

    echo $pdfContent;
    exit();
}
