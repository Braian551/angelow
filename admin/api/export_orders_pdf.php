<?php
// admin/api/export_orders_pdf.php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Inicializar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar headers para JSON por defecto
header('Content-Type: application/json');

try {
    // Verificar autenticación y permisos
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Usuario no autenticado");
    }

    // Verificar rol de admin
    $userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['role'] !== 'admin') {
        throw new Exception("Acceso no autorizado");
    }

    // Obtener IDs de órdenes desde POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['order_ids']) || !is_array($data['order_ids']) || empty($data['order_ids'])) {
        throw new Exception("No se han seleccionado órdenes para exportar");
    }

    // Validar IDs de órdenes
    $orderIds = array_map('intval', $data['order_ids']);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    // Obtener información de las órdenes seleccionadas
    $ordersStmt = $conn->prepare("
        SELECT o.*, u.name as client_name, u.email as client_email, 
               u.phone, u.identification_type, u.identification_number,
               u.address, u.neighborhood, u.address_details,
               o.shipping_address, o.shipping_city,
               pt.reference_number, pt.bank_name, pt.account_number, pt.account_type,
               DATE_FORMAT(o.created_at, '%d/%m/%Y %H:%i') as formatted_date
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        WHERE o.id IN ($placeholders)
        ORDER BY o.created_at DESC
    ");

    if (!$ordersStmt->execute($orderIds)) {
        throw new Exception("Error al obtener las órdenes: " . implode(" ", $ordersStmt->errorInfo()));
    }

    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        throw new Exception("No se encontraron órdenes con los IDs proporcionados");
    }

    // Obtener items para todas las órdenes
    $itemsStmt = $conn->prepare("
        SELECT oi.*, o.order_number, p.slug as product_slug, pv.sku
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id, oi.id
    ");

    if (!$itemsStmt->execute($orderIds)) {
        throw new Exception("Error al obtener los items: " . implode(" ", $itemsStmt->errorInfo()));
    }

    $allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar items por orden
    $itemsByOrder = [];
    foreach ($allItems as $item) {
        $itemsByOrder[$item['order_id']][] = $item;
    }

    // Mapeo de estados en español
    $statusTranslations = [
        'pending' => 'Pendiente',
        'processing' => 'En proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado'
    ];
    
    $paymentStatusTranslations = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado'
    ];
    
    $paymentMethodTranslations = [
        'transferencia' => 'Transferencia Bancaria',
        'contra_entrega' => 'Contra Entrega',
        'pse' => 'Pago en Línea (PSE)',
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta de Crédito/Débito'
    ];

    // Limpiar buffers de salida antes de generar PDF
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Cambiar headers para descarga de PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_ordenes_' . date('Ymd_His') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Crear nuevo documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
    
    // Configuración del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Angelow Ropa Infantil');
    $pdf->SetTitle('Reporte de Órdenes');
    $pdf->SetSubject('Órdenes de Compra');
    $pdf->SetKeywords('Órdenes, Reporte, Angelow, Ropa Infantil');
    
    // Configuración de márgenes
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    $pdf->setPrintFooter(true);
    
    // Auto saltos de página
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Fuente principal
    $pdf->SetFont('helvetica', '', 10);
    
    // Colores corporativos
    $primaryColor = array(0, 102, 153); // Azul corporativo
    $secondaryColor = array(241, 241, 241); // Gris claro para fondos
    $accentColor = array(255, 153, 0); // Naranja para acentos
    $borderColor = array(200, 200, 200); // Gris para bordes
    
    // Verificar si existe el logo
    $logoPath = __DIR__ . '/../../images/logo2.png';
    $logoExists = file_exists($logoPath);
    
    // Generar una página por cada orden
    foreach ($orders as $order) {
        $pdf->AddPage();
        
        // Encabezado con logo y datos del reporte
        $html = '
        <style>
            .header-title {
                color: #006699;
                font-size: 16pt;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .header-subtitle {
                color: #666666;
                font-size: 10pt;
                margin-top: 0;
            }
            .section-title {
             
                color:rgb(0, 0, 0);
                padding: 6px 8px;
                font-size: 11pt;
                font-weight: bold;
                margin-top: 15px;
                border-radius: 3px;
            }
            .label {
                font-weight: bold;
                color: #333333;
                width: 120px;
                display: inline-block;
            }
            .value {
                color: #555555;
            }
            .total-row {
                font-weight: bold;
                background-color: #F8F8F8;
                border-top: 2px solid #DDDDDD;
                border-bottom: 2px solid #DDDDDD;
            }
            .footer {
                font-size: 8pt;
                color: #666666;
                text-align: center;
                border-top: 1px solid #CCCCCC;
                padding-top: 8px;
                margin-top: 20px;
            }
            .order-number {
                font-size: 14pt;
                color: #006699;
                font-weight: bold;
            }
            .product-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                table-layout: fixed;
            }
            .product-table th {
                background-color: #006699;
                color: #FFFFFF;
                border: 1px solid #DDDDDD;
                padding: 8px;
                font-weight: bold;
                overflow: hidden;
                font-size: 9pt;
            }
            .product-table td {
                border: 1px solid #DDDDDD;
                padding: 8px;
                vertical-align: top;
                overflow: hidden;
                font-size: 9pt;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .product-code {
                font-family: courier;
                font-size: 9pt;
                word-wrap: break-word;
            }
            .product-description {
                line-height: 1.4;
                word-wrap: break-word;
            }
            .variant-details {
                font-size: 8pt;
                color: #666666;
                display: block;
                margin-top: 3px;
                font-style: italic;
            }
            .col-code {
                width: 15%;
            }
            .col-desc {
                width: 45%;
            }
            .col-qty {
                width: 10%;
            }
            .col-price {
                width: 15%;
            }
            .col-subtotal {
                width: 15%;
            }
            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 9pt;
                font-weight: bold;
            }
            .status-pending {
                background-color: #FFF3CD;
                color: #856404;
            }
            .status-processing {
                background-color: #CCE5FF;
                color: #004085;
            }
            .status-shipped {
                background-color: #D4EDDA;
                color: #155724;
            }
            .status-delivered {
                background-color: #D4EDDA;
                color: #155724;
            }
            .status-cancelled {
                background-color: #F8D7DA;
                color: #721C24;
            }
            .status-refunded {
                background-color: #E2E3E5;
                color: #383D41;
            }
            .payment-paid {
                background-color: #D4EDDA;
                color: #155724;
            }
            .payment-pending {
                background-color: #FFF3CD;
                color: #856404;
            }
            .payment-failed {
                background-color: #F8D7DA;
                color: #721C24;
            }
            .totals-table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals-table td {
                padding: 6px 8px;
                border-bottom: 1px solid #EEEEEE;
            }
            .divider {
                height: 1px;
                background-color: #EEEEEE;
                margin: 10px 0;
            }
            .company-info {
                font-size: 9pt;
                color: #666666;
                line-height: 1.4;
            }
            .notes-box {
                border: 1px solid #EEEEEE;
                background-color: #F9F9F9;
                padding: 10px;
                border-radius: 4px;
                margin-top: 5px;
            }
        </style>
        
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="40%">';
        
        if ($logoExists) {
            $html .= '<img src="' . $logoPath . '" width="180">';
        } else {
            $html .= '<h1 class="header-title">Angelow Ropa Infantil</h1>
                      <p class="header-subtitle">Moda infantil de calidad</p>';
        }
        
        $html .= '
                </td>
                <td width="60%" style="text-align: right; vertical-align: top;">
                    <h1 class="header-title">REPORTE DE ORDEN</h1>
                    <p><span class="label">No. Orden:</span> <span class="order-number">' . htmlspecialchars($order['order_number']) . '</span></p>
                    <p><span class="label">Fecha:</span> ' . htmlspecialchars($order['formatted_date']) . '</p>
                    <p><span class="label">Estado:</span> <span class="status-badge status-' . htmlspecialchars($order['status']) . '">' . 
                      htmlspecialchars($statusTranslations[$order['status']] ?? ucfirst($order['status'])) . '</span></p>
                    <p><span class="label">Pago:</span> <span class="status-badge payment-' . htmlspecialchars($order['payment_status']) . '">' . 
                      htmlspecialchars($paymentStatusTranslations[$order['payment_status']] ?? ucfirst($order['payment_status'])) . '</span></p>
                </td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <!-- Datos del cliente -->
        <h3 class="section-title">INFORMACIÓN DEL CLIENTE</h3>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><span class="label">Nombre:</span></td>
                <td width="75%" class="value">' . htmlspecialchars($order['client_name'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td><span class="label">Documento:</span></td>
                <td class="value">' . htmlspecialchars($order['identification_number'] ?? 'N/A') . 
                  ' (' . htmlspecialchars(($order['identification_type'] ?? 'CC')) . ')</td>
            </tr>
            <tr>
                <td><span class="label">Dirección:</span></td>
                <td class="value">' . htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? 'N/A') . 
                  (isset($order['neighborhood']) ? ', ' . htmlspecialchars($order['neighborhood']) : '') . 
                  (isset($order['address_details']) ? ' - ' . htmlspecialchars($order['address_details']) : '') . '</td>
            </tr>
            <tr>
                <td><span class="label">Ciudad:</span></td>
                <td class="value">' . htmlspecialchars($order['shipping_city'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td><span class="label">Teléfono:</span></td>
                <td class="value">' . htmlspecialchars($order['phone'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td><span class="label">Email:</span></td>
                <td class="value">' . htmlspecialchars($order['client_email'] ?? 'N/A') . '</td>
            </tr>
        </table>
        
        <!-- Detalle de productos -->
        <h3 class="section-title">DETALLE DE PRODUCTOS</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th class="col-code" style="text-align: left;">Código</th>
                    <th class="col-desc" style="text-align: left;">Descripción</th>
                    <th class="col-qty" style="text-align: center;">Cantidad</th>
                    <th class="col-price" style="text-align: right;">P. Unitario</th>
                    <th class="col-subtotal" style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>';
        
        // Agregar items de esta orden
        $items = $itemsByOrder[$order['id']] ?? [];
        foreach ($items as $item) {
            $html .= '
                <tr>
                    <td class="col-code product-code">' . htmlspecialchars($item['sku'] ?? 'N/A') . '</td>
                    <td class="col-desc product-description">
                        ' . htmlspecialchars($item['product_name']) . 
                        ($item['variant_name'] ? '<span class="variant-details">Variante: ' . htmlspecialchars($item['variant_name']) . '</span>' : '') . '
                    </td>
                    <td class="col-qty text-center">' . intval($item['quantity']) . '</td>
                    <td class="col-price text-right">$' . number_format($item['price'], 2, ',', '.') . '</td>
                    <td class="col-subtotal text-right">$' . number_format($item['total'], 2, ',', '.') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>
        
        <!-- Totales -->
        <table class="totals-table" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="70%"></td>
                <td width="30%">
                    <table border="0" cellpadding="3" cellspacing="0" width="100%">
                        <tr>
                            <td><span class="label">Subtotal:</span></td>
                            <td class="text-right">$' . number_format($order['subtotal'], 2, ',', '.') . '</td>
                        </tr>
                        <tr>
                            <td><span class="label">Envío:</span></td>
                            <td class="text-right">$' . number_format($order['shipping_cost'], 2, ',', '.') . '</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL:</strong></td>
                            <td class="text-right" style="font-weight: bold; font-size: 11pt;">$' . number_format($order['total'], 2, ',', '.') . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- Forma de pago -->
        <h3 class="section-title">INFORMACIÓN DE PAGO</h3>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><span class="label">Método:</span></td>
                <td width="75%" class="value">' . htmlspecialchars($paymentMethodTranslations[$order['payment_method']] ?? ucfirst(str_replace('_', ' ', $order['payment_method']))) . '</td>
            </tr>';
        
        if ($order['payment_method'] === 'transferencia') {
            $html .= '
            <tr>
                <td><span class="label">Banco:</span></td>
                <td class="value">' . htmlspecialchars($order['bank_name'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td><span class="label">Tipo Cuenta:</span></td>
                <td class="value">' . htmlspecialchars($order['account_type'] === 'ahorros' ? 'Ahorros' : 'Corriente') . '</td>
            </tr>
            <tr>
                <td><span class="label">Número Cuenta:</span></td>
                <td class="value">' . htmlspecialchars($order['account_number'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td><span class="label">Referencia:</span></td>
                <td class="value">' . htmlspecialchars($order['reference_number'] ?? 'N/A') . '</td>
            </tr>';
        }
        
        $html .= '
        </table>
        
        <!-- Notas -->
        <h3 class="section-title">NOTAS ADICIONALES</h3>
        <div class="notes-box">' . nl2br(htmlspecialchars($order['notes'] ?? 'No hay observaciones registradas para esta orden.')) . '</div>
        
        <div class="footer">
            <strong>Angelow Ropa Infantil</strong><br>
            NIT: 901234567-8 | Tel: +57 604 1234567 | Email: contacto@angelow.com<br>
            Calle 10 # 40-20, Medellín, Antioquia - www.angelow.com
        </div>';
        
        // Escribir el HTML en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    // Generar nombre del archivo
    $filename = 'reporte_ordenes_' . date('Ymd_His') . '.pdf';
    
    // Salida del PDF
    $pdf->Output($filename, 'D');
    exit();

} catch (Exception $e) {
    // Limpiar buffers antes de enviar respuesta JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Registrar error detallado
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error al generar reporte PDF: " . $e->getMessage() . 
                " en " . $e->getFile() . " línea " . $e->getLine() . "\n";
    
    // Intentar escribir al log
    $logPath = __DIR__ . '/../../php_errors.log';
    if (is_writable(dirname($logPath))) {
        error_log($errorMsg, 3, $logPath);
    } else {
        error_log($errorMsg); // Log al sistema
    }
    
    // Responder con error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ocurrió un error al generar el reporte PDF.',
        'debug' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : null
    ]);
    exit();
}
?>