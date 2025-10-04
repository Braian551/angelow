    // HTML para el contenido del PDF (usando helper para mantener consistencia)
    $html = getDiscountPdfHtml($codigo, $productos);
+
+
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
                <h1 class="header-title">CÓDIGO DE DESCUENTO</h1>
                <p><span class="label">Generado por:</span> ' . htmlspecialchars($codigo['created_by_name'] ?? 'Administrador') . '</p>
                <p><span class="label">Fecha creación:</span> ' . date('d/m/Y H:i', strtotime($codigo['created_at'])) . '</p>
            </td>
        </tr>
    </table>
    
    <!-- Código de descuento destacado -->
    <div class="barcode">
        <div class="discount-code">' . htmlspecialchars($codigo['code']) . '</div>
        <div class="discount-value">' . htmlspecialchars($codigo['discount_type_name']) . ' DEL ' . $codigo['discount_value'] . '%</div>';
    
    if ($codigo['end_date']) {
        $html .= '<div class="validity">Válido hasta: ' . date('d/m/Y', strtotime($codigo['end_date'])) . '</div>';
    } else {
        $html .= '<div class="validity">Sin fecha de expiración</div>';
    }
    
    $html .= '
    </div>
    
    <!-- Detalles del descuento -->
    <h3 class="section-title">INFORMACIÓN DEL DESCUENTO</h3>
    <table border="0" cellpadding="3" cellspacing="0" width="100%">
        <tr>
            <td width="25%"><span class="label">Tipo:</span></td>
            <td width="75%" class="value">' . htmlspecialchars($codigo['discount_type_name']) . '</td>
        </tr>
        <tr>
            <td><span class="label">Valor:</span></td>
            <td class="value">' . $codigo['discount_value'] . '% de descuento</td>
        </tr>
        <tr>
            <td><span class="label">Usos máximos:</span></td>
            <td class="value">' . ($codigo['max_uses'] ? $codigo['max_uses'] : 'Ilimitados') . '</td>
        </tr>
        <tr>
            <td><span class="label">Uso único:</span></td>
            <td class="value">' . ($codigo['is_single_use'] ? 'Sí' : 'No') . '</td>
        </tr>
        <tr>
            <td><span class="label">Válido desde:</span></td>
            <td class="value">' . ($codigo['start_date'] ? date('d/m/Y H:i', strtotime($codigo['start_date'])) : 'Inmediatamente') . '</td>
        </tr>
        <tr>
            <td><span class="label">Válido hasta:</span></td>
            <td class="value">' . ($codigo['end_date'] ? date('d/m/Y H:i', strtotime($codigo['end_date'])) : 'Sin fecha de expiración') . '</td>
        </tr>
    </table>';
    
    // Mostrar productos aplicables si no es para todos
    if ($codigo['product_count'] > 0) {
        $html .= '
        <h3 class="section-title">PRODUCTOS APLICABLES</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Producto</th>
                    <th style="text-align: right;">Precio</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($productos as $producto) {
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($producto['name']) . '</td>
                    <td class="text-right">$' . number_format($producto['price'], 2, ',', '.') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
    } else {
        $html .= '
        <h3 class="section-title">APLICACIÓN</h3>
        <p>Este descuento aplica a todos los productos de la tienda.</p>';
    }
    
    // Términos y condiciones
    $html .= '
    <h3 class="section-title">TÉRMINOS Y CONDICIONES</h3>
    <div class="terms">
        <p>1. Este código es válido para una sola transacción y no puede ser combinado con otras promociones.</p>
        <p>2. El descuento aplica sobre el valor total de los productos antes de impuestos y envío.</p>
        <p>3. Angelow se reserva el derecho de modificar o cancelar esta promoción en cualquier momento.</p>
        <p>4. Para reclamar el descuento, ingrese el código en el campo correspondiente durante el proceso de pago.</p>
        <p>5. No aplica para compras anteriores a la fecha de generación del código.</p>
    </div>
    
    <div class="footer">
        <strong>Angelow Ropa Infantil</strong><br>
        NIT: 901234567-8 | Tel: +57 604 1234567 | Email: contacto@angelow.com<br>
        Calle 10 # 40-20, Medellín, Antioquia - www.angelow.com
    </div>';
    
    // Escribir el HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Salida del PDF
    $pdf->Output('codigo_descuento_' . $codigo['code'] . '.pdf', 'D');
    exit();

} catch (Exception $e) {
    // Limpiar buffers antes de enviar respuesta de error
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Registrar error detallado
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error al generar PDF de descuento: " . $e->getMessage() . 
                " en " . $e->getFile() . " línea " . $e->getLine() . "\n";
    
    // Intentar escribir al log
    $logPath = __DIR__ . '/../../../../php_errors.log';
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
        'error' => 'Ocurrió un error al generar el PDF del código de descuento.',
        'debug' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : null
    ]);
    exit();
}