<?php
// Helper para construir el HTML del PDF de código de descuento
function getDiscountPdfHtml(array $codigo, array $productos = []) {
    // Usamos la misma estructura y estilos que en generate_pdf.php
    $logoPath = __DIR__ . '/../../../../images/logo2.png';
    $logoExists = file_exists($logoPath);

    $html = "\n    <style>\n        .header-title {\n            color: #006699;\n            font-size: 16pt;\n            font-weight: bold;\n            margin-bottom: 5px;\n        }\n        .header-subtitle {\n            color: #666666;\n            font-size: 10pt;\n            margin-top: 0;\n        }\n        .section-title {\n            color: #006699;\n            padding: 6px 8px;\n            font-size: 11pt;\n            font-weight: bold;\n            margin-top: 15px;\n            border-radius: 3px;\n        }\n        .label {\n            font-weight: bold;\n            color: #333333;\n            width: 120px;\n            display: inline-block;\n        }\n        .value {\n            color: #555555;\n        }\n        .footer {\n            font-size: 8pt;\n            color: #666666;\n            text-align: center;\n            border-top: 1px solid #CCCCCC;\n            padding-top: 8px;\n            margin-top: 20px;\n        }\n        .discount-code {\n            font-size: 24pt;\n            color: #006699;\n            font-weight: bold;\n            text-align: center;\n            margin: 20px 0;\n            letter-spacing: 3px;\n        }\n        .discount-value {\n            font-size: 18pt;\n            color: #FF6600;\n            font-weight: bold;\n            text-align: center;\n            margin: 10px 0;\n        }\n        .validity {\n            font-size: 10pt;\n            color: #666666;\n            text-align: center;\n            font-style: italic;\n        }\n        .product-table {\n            width: 100%;\n            border-collapse: collapse;\n            margin-top: 10px;\n        }\n        .product-table th {\n            background-color: #006699;\n            color: #FFFFFF;\n            border: 1px solid #DDDDDD;\n            padding: 8px;\n            font-weight: bold;\n            font-size: 9pt;\n        }\n        .product-table td {\n            border: 1px solid #DDDDDD;\n            padding: 8px;\n            font-size: 9pt;\n        }\n        .text-center {\n            text-align: center;\n        }\n        .text-right {\n            text-align: right;\n        }\n        .terms {\n            font-size: 8pt;\n            color: #666666;\n            margin-top: 15px;\n            border-top: 1px solid #EEEEEE;\n            padding-top: 10px;\n        }\n        .company-info {\n            font-size: 9pt;\n            color: #666666;\n            line-height: 1.4;\n        }\n        .barcode {\n            text-align: center;\n            margin: 15px 0;\n        }\n    </style>\n    \n    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n        <tr>\n            <td width=\"40%\">";

    if ($logoExists) {
        $html .= '<img src="' . $logoPath . '" width="180">';
    } else {
        $html .= '<h1 class="header-title">Angelow Ropa Infantil</h1>' .
                 '<p class="header-subtitle">Moda infantil de calidad</p>';
    }

    $html .= "</td>\n            <td width=\"60%\" style=\"text-align: right; vertical-align: top;\">\n                <h1 class=\"header-title\">C\xD3DIGO DE DESCUENTO</h1>\n                <p><span class=\"label\">Generado por:</span> " . htmlspecialchars($codigo['created_by_name'] ?? 'Administrador') . "</p>\n                <p><span class=\"label\">Fecha creaci\xF3n:</span> " . date('d/m/Y H:i', strtotime($codigo['created_at'])) . "</p>\n            </td>\n        </tr>\n    </table>\n    \n    <div class=\"barcode\">\n        <div class=\"discount-code\">" . htmlspecialchars($codigo['code']) . "</div>\n        <div class=\"discount-value\">" . htmlspecialchars($codigo['discount_type_name']) . " DEL " . $codigo['discount_value'] . "%</div>";

    if (!empty($codigo['end_date'])) {
        $html .= '<div class="validity">V&aacute;lido hasta: ' . date('d/m/Y', strtotime($codigo['end_date'])) . '</div>';
    } else {
        $html .= '<div class="validity">Sin fecha de expiraci&oacute;n</div>';
    }

    $html .= "</div>\n    \n    <h3 class=\"section-title\">INFORMACI\xD3N DEL DESCUENTO</h3>\n    <table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\">\n        <tr>\n            <td width=\"25%\"><span class=\"label\">Tipo:</span></td>\n            <td width=\"75%\" class=\"value\">" . htmlspecialchars($codigo['discount_type_name']) . "</td>\n        </tr>\n        <tr>\n            <td><span class=\"label\">Valor:</span></td>\n            <td class=\"value\">" . $codigo['discount_value'] . "% de descuento</td>\n        </tr>\n        <tr>\n            <td><span class=\"label\">Usos m\xE1ximos:</span></td>\n            <td class=\"value\">" . ($codigo['max_uses'] ? $codigo['max_uses'] : 'Ilimitados') . "</td>\n        </tr>\n        <tr>\n            <td><span class=\"label\">Uso \xFAnico:</span></td>\n            <td class=\"value\">" . ($codigo['is_single_use'] ? 'S&iacute;' : 'No') . "</td>\n        </tr>\n        <tr>\n            <td><span class=\"label\">V\xE1lido desde:</span></td>\n            <td class=\"value\">" . ($codigo['start_date'] ? date('d/m/Y H:i', strtotime($codigo['start_date'])) : 'Inmediatamente') . "</td>\n        </tr>\n        <tr>\n            <td><span class=\"label\">V\xE1lido hasta:</span></td>\n            <td class=\"value\">" . ($codigo['end_date'] ? date('d/m/Y H:i', strtotime($codigo['end_date'])) : 'Sin fecha de expiraci&oacute;n') . "</td>\n        </tr>\n    </table>";

    if (!empty($productos) && count($productos) > 0) {
        $html .= "\n        <h3 class=\"section-title\">PRODUCTOS APLICABLES</h3>\n        <table class=\"product-table\">\n            <thead>\n                <tr>\n                    <th style=\"text-align: left;\">Producto</th>\n                    <th style=\"text-align: right;\">Precio</th>\n                </tr>\n            </thead>\n            <tbody>";

        foreach ($productos as $producto) {
            $html .= "\n                <tr>\n                    <td>" . htmlspecialchars($producto['name']) . "</td>\n                    <td class=\"text-right\">$" . number_format($producto['price'], 2, ',', '.') . "</td>\n                </tr>";
        }

        $html .= "\n            </tbody>\n        </table>";
    } else {
        $html .= "\n        <h3 class=\"section-title\">APLICACI\xD3N</h3>\n        <p>Este descuento aplica a todos los productos de la tienda.</p>";
    }

    $html .= "\n    <h3 class=\"section-title\">T\xC9RMINOS Y CONDICIONES</h3>\n    <div class=\"terms\">\n        <p>1. Este c\xF3digo es v\xE1lido para una sola transacci\xF3n y no puede ser combinado con otras promociones.</p>\n        <p>2. El descuento aplica sobre el valor total de los productos antes de impuestos y env\xEDo.</p>\n        <p>3. Angelow se reserva el derecho de modificar o cancelar esta promoci\xF3n en cualquier momento.</p>\n        <p>4. Para reclamar el descuento, ingrese el c\xF3digo en el campo correspondiente durante el proceso de pago.</p>\n        <p>5. No aplica para compras anteriores a la fecha de generaci\xF3n del c\xF3digo.</p>\n    </div>\n    \n    <div class=\"footer\">\n        <strong>Angelow Ropa Infantil</strong><br>\n        NIT: 901234567-8 | Tel: +57 604 1234567 | Email: contacto@angelow.com<br>\n        Calle 10 # 40-20, Medell\xEDn, Antioquia - www.angelow.com\n    </div>";

    return $html;
}
