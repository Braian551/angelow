<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Limpiar buffers de salida
    while (ob_get_level()) ob_end_clean();
    ob_start();

    // Verificar si se está solicitando una factura específica
    if (!isset($_GET['order'])) {
        throw new Exception("No se ha especificado el número de pedido");
    }

    $orderNumber = $_GET['order'];

    // Validar formato del número de pedido
    if (!preg_match('/^ORD-\d{8}-[A-Z0-9]{6}$/', $orderNumber)) {
        throw new Exception("Formato de número de pedido inválido");
    }

    // Obtener información del pedido con todos los campos necesarios
    $orderStmt = $conn->prepare("
        SELECT o.*, u.name as client_name, u.email as client_email, 
               u.phone, u.identification_type, u.identification_number,
               u.address, u.neighborhood, u.address_details,
               o.shipping_address, o.shipping_city,
               pt.reference_number, pt.bank_name, pt.account_number, pt.account_type,
               DATE_FORMAT(o.created_at, '%d/%m/%Y %H:%i') as formatted_date
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        WHERE o.order_number = ?
        LIMIT 1
    ");

    if (!$orderStmt) {
        throw new Exception("Error al preparar consulta: " . implode(" ", $conn->errorInfo()));
    }

    if (!$orderStmt->execute([$orderNumber])) {
        throw new Exception("Error al ejecutar consulta: " . implode(" ", $orderStmt->errorInfo()));
    }

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Pedido no encontrado en la base de datos");
    }

    // Obtener items del pedido
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.slug as product_slug, pv.sku
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id = ?
    ");

    if (!$itemsStmt) {
        throw new Exception("Error al preparar consulta de items: " . implode(" ", $conn->errorInfo()));
    }

    if (!$itemsStmt->execute([$order['id']])) {
        throw new Exception("Error al ejecutar consulta de items: " . implode(" ", $itemsStmt->errorInfo()));
    }

    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception("No se encontraron productos para este pedido");
    }

    // Configurar cabeceras para la descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="factura_' . $order['order_number'] . '.pdf"');

    // Crear nuevo documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
    
    // Configuración del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Angelow Ropa Infantil');
    $pdf->SetTitle('Factura ' . $order['order_number']);
    $pdf->SetSubject('Factura de Venta');
    $pdf->SetKeywords('Factura, DIAN, Angelow');
    
    // Margenes
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->setPrintFooter(true);
    
    // Auto saltos de página
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Fuente
    $pdf->SetFont('helvetica', '', 10);
    
    // Añadir página
    $pdf->AddPage();
    
    // Logo de la empresa
    $logoUrl = BASE_URL . '/images/logo2.png';
    $logo = '<img src="' . $logoUrl . '" width="120">';
    
    // Encabezado con logo y datos de la empresa
    $html = '
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="40%">
                '.$logo.'
            </td>
            <td width="60%" style="text-align: right;">
                <h1>FACTURA DE VENTA</h1>
                <p><strong>No. Factura:</strong> ' . $order['order_number'] . '</p>
                <p><strong>Fecha:</strong> ' . $order['formatted_date'] . '</p>
                <p><strong>Resolución DIAN:</strong> ' . ($order['invoice_resolution'] ?? '18764000000000') . '</p>
                <p><strong>Rango autorizado:</strong> FV1-1 al FV1-100</p>
            </td>
        </tr>
    </table>
    
    <hr>
    
    <!-- Datos del vendedor y comprador -->
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="50%" valign="top">
                <h3>EMISOR</h3>
                <p><strong>Angelow Ropa Infantil</strong></p>
                <p>NIT: 901234567-8</p>
                <p>Dirección: Calle 10 # 40-20, Medellín, Antioquia</p>
                <p>Teléfono: +57 604 1234567</p>
                <p>Email: contacto@angelow.com</p>
                <p>Régimen: Simplificado</p>
                <p>Responsable de IVA: No</p>
            </td>
            <td width="50%" valign="top">
                <h3>CLIENTE</h3>
                <p><strong>' . htmlspecialchars($order['client_name']) . '</strong></p>
                <p>Documento: ' . ($order['identification_number'] ?? 'Consumidor final') . ' (' . 
                  ($order['identification_type'] ?? 'CC') . ')</p>
                <p>Dirección: ' . htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? 'No especificada') . 
                  (isset($order['neighborhood']) ? ', ' . htmlspecialchars($order['neighborhood']) : '') . 
                  (isset($order['address_details']) ? ' - ' . htmlspecialchars($order['address_details']) : '') . '</p>
                <p>Ciudad: ' . htmlspecialchars($order['shipping_city'] ?? 'Medellín') . '</p>
                <p>Teléfono: ' . ($order['phone'] ?? 'No especificado') . '</p>
                <p>Email: ' . htmlspecialchars($order['client_email'] ?? 'No especificado') . '</p>
            </td>
        </tr>
    </table>
    
    <br>
    
    <!-- Detalle de productos -->
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="10%"><strong>Código</strong></th>
                <th width="40%"><strong>Descripción</strong></th>
                <th width="10%"><strong>Cantidad</strong></th>
                <th width="15%"><strong>Valor Unitario</strong></th>
                <th width="15%"><strong>Valor Total</strong></th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($items as $item) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($item['sku'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($item['product_name']) . 
                    ($item['variant_name'] ? ' (' . htmlspecialchars($item['variant_name']) . ')' : '') . '</td>
                <td style="text-align: center;">' . $item['quantity'] . '</td>
                <td style="text-align: right;">$' . number_format($item['price'], 2, ',', '.') . '</td>
                <td style="text-align: right;">$' . number_format($item['total'], 2, ',', '.') . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>
    
    <br>
    
    <!-- Totales -->
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="70%"></td>
            <td width="30%">
                <table border="0" cellpadding="5" cellspacing="0" width="100%">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td style="text-align: right;">$' . number_format($order['subtotal'], 2, ',', '.') . '</td>
                    </tr>
                    <tr>
                        <td><strong>Envío:</strong></td>
                        <td style="text-align: right;">$' . number_format($order['shipping_cost'], 2, ',', '.') . '</td>
                    </tr>
                    <tr style="font-weight: bold; font-size: 1.1em;">
                        <td><strong>TOTAL:</strong></td>
                        <td style="text-align: right;">$' . number_format($order['total'], 2, ',', '.') . '</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <br>
    
    <!-- Forma de pago -->
    <h3>FORMA DE PAGO</h3>
    <p>';
    
    if ($order['payment_method'] === 'transferencia') {
        $html .= 'Transferencia bancaria';
        if ($order['bank_name']) {
            $html .= ' - Banco: ' . htmlspecialchars($order['bank_name']);
        }
        if ($order['account_number']) {
            $html .= ' - Cuenta: ' . htmlspecialchars($order['account_number']) . 
                     ' (' . ($order['account_type'] === 'ahorros' ? 'Ahorros' : 'Corriente') . ')';
        }
        if ($order['reference_number']) {
            $html .= ' - Referencia: ' . htmlspecialchars($order['reference_number']);
        }
    } elseif ($order['payment_method'] === 'contra_entrega') {
        $html .= 'Pago contra entrega en efectivo';
    } else {
        $html .= ucfirst(str_replace('_', ' ', $order['payment_method']));
    }
    
    $html .= '</p>
    
    <br>
    
    <!-- Notas y QR DIAN -->
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="60%" valign="top">
                <h3>NOTAS</h3>
                <p>' . nl2br(htmlspecialchars($order['notes'] ?? 'Sin observaciones')) . '</p>
                <p>Gracias por su compra. Para devoluciones o reclamos, contáctenos dentro de los 3 días siguientes.</p>
                <p><strong>Condiciones de venta:</strong> Productos nuevos con garantía de fábrica.</p>
            </td>
            <td width="40%" valign="top" style="text-align: center;">
                <h3>VALIDACIÓN DIAN</h3>
                <p>Verifique esta factura en el portal de la DIAN</p>
            </td>
        </tr>
    </table>';
    
    // Escribir el HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Generar código QR para DIAN
    $qrData = "https://catalogo-vpfe.dian.gov.co/User/ConsultaDocumento?"
             . "tipoDocumento=FV&numeroDocumento=" . $order['order_number']
             . "&fechaEmision=" . date('Y-m-d', strtotime($order['created_at']))
             . "&valorTotal=" . $order['total']
             . "&nitEmisor=9012345678";
    
    // Estilo para el QR
    $style = array(
        'border' => 0,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => array(0,0,0),
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1
    );
    
    // Posicionar el QR en el documento (coordenadas X, Y, ancho, alto)
    $pdf->write2DBarcode($qrData, 'QRCODE,M', 140, $pdf->GetY() - 30, 40, 40, $style, 'N');
    
    // Pie de página
    $pdf->SetY($pdf->GetY() + 20);
    $pdf->writeHTML('
    <hr>
    <p style="text-align: center; font-size: 0.8em;">
        Angelow Ropa Infantil - NIT: 901234567-8 - Tel: +57 604 1234567<br>
        Calle 10 # 40-20, Medellín, Antioquia - contacto@angelow.com<br>
        Esta factura cumple con los requisitos legales exigidos por la DIAN para el Régimen Simplificado
    </p>', true, false, true, false, '');
    
    // Generar y descargar PDF
    ob_end_clean();
    $pdf->Output('factura_' . $order['order_number'] . '.pdf', 'D');

} catch (Exception $e) {
    // Registrar error detallado
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error al generar factura: " . $e->getMessage() . 
                " en " . $e->getFile() . " línea " . $e->getLine() . "\n";
    error_log($errorMsg, 3, BASE_PATH . '/php_errors.log');
    
    // Limpiar buffers antes de redireccionar
    while (ob_get_level()) ob_end_clean();
    
    $_SESSION['error'] = "Ocurrió un error al generar la factura. Por favor intente nuevamente.";
    if (DEBUG_MODE) {
        $_SESSION['error'] .= " Detalles: " . $e->getMessage();
    }
    header("Location: " . BASE_URL . "/pagos/confirmacion.php?order=" . ($orderNumber ?? ''));
    exit();
}
?>