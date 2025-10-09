<?php
// admin/api/descuento/pdf_helpers.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use TCPDF;

// Configurar manejo de errores para obtener excepciones en lugar de warnings/notices
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Genera el PDF del código de descuento y devuelve el contenido binario (string).
 * Retorna null en caso de error.
 */
function generateDiscountPdfContent(int $id)
{
    global $conn;

    try {
        // Obtener información del código de descuento
        $stmt = $conn->prepare(
            "SELECT dc.*, dt.name as discount_type_name,"
            . " (SELECT COUNT(*) FROM discount_code_products WHERE discount_code_id = dc.id) as product_count,"
            . " u.name as created_by_name"
            . " FROM discount_codes dc"
            . " JOIN discount_types dt ON dc.discount_type_id = dt.id"
            . " LEFT JOIN users u ON dc.created_by = u.id"
            . " WHERE dc.id = ?"
        );
        $stmt->execute([$id]);
        $codigo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$codigo) {
            return null;
        }

        // Obtener productos asociados si aplica
        $productos = [];
        if ($codigo['product_count'] > 0) {
            $stmt = $conn->prepare(
                "SELECT p.id, p.name, p.price"
                . " FROM discount_code_products dcp"
                . " JOIN products p ON dcp.product_id = p.id"
                . " WHERE dcp.discount_code_id = ?"
                . " ORDER BY p.name"
            );
            $stmt->execute([$id]);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Crear el PDF en memoria con la misma configuración y HTML que generate_pdf.php
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Angelow Ropa Infantil');
        $pdf->SetTitle('Código de Descuento ' . $codigo['code']);
        $pdf->SetSubject('Código de Descuento');
        $pdf->SetKeywords('Descuento, Cupón, Promoción, Angelow');
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->setPrintFooter(true);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        
        // URL del logo
        $logoUrl = BASE_URL . '/images/logo2.png';
        
        $pdf->AddPage();

        // Reproducir exactamente el HTML y estilos de generate_pdf.php
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
            color: #006699;
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
        .footer {
            font-size: 8pt;
            color: #666666;
            text-align: center;
            border-top: 1px solid #CCCCCC;
            padding-top: 8px;
            margin-top: 20px;
        }
        .discount-code {
            font-size: 24pt;
            color: #006699;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            letter-spacing: 3px;
        }
        .discount-value {
            font-size: 18pt;
            color: #FF6600;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        .validity {
            font-size: 10pt;
            color: #666666;
            text-align: center;
            font-style: italic;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .product-table th {
            background-color: #006699;
            color: #FFFFFF;
            border: 1px solid #DDDDDD;
            padding: 8px;
            font-weight: bold;
            font-size: 9pt;
        }
        .product-table td {
            border: 1px solid #DDDDDD;
            padding: 8px;
            font-size: 9pt;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .terms {
            font-size: 8pt;
            color: #666666;
            margin-top: 15px;
            border-top: 1px solid #EEEEEE;
            padding-top: 10px;
        }
        .company-info {
            font-size: 9pt;
            color: #666666;
            line-height: 1.4;
        }
        .barcode {
            text-align: center;
            margin: 15px 0;
        }
    </style>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td width="40%">';

            $html .= '<img src="' . $logoUrl . '" width="180">';        $html .= '
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

        // Devolver contenido en memoria
        return $pdf->Output('codigo_descuento_' . $codigo['code'] . '.pdf', 'S');
    } catch (Exception $e) {
        error_log('generateDiscountPdfContent error: ' . $e->getMessage());
        return null;
    }
}

// Mantener comportamiento de descarga si el archivo es accedido directamente via URL
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // Acceso directo: iniciar sesión y validar
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id = $_GET['id'] ?? 0;
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id) {
        header("HTTP/1.0 404 Not Found");
        exit();
    }

    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Usuario no autenticado");
        }

        $userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['role'] !== 'admin') {
            throw new Exception("Acceso no autorizado");
        }

        $pdfContent = generateDiscountPdfContent($id);
        if (!$pdfContent) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }

        // Limpiar buffers y enviar el PDF al navegador
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="codigo_descuento_' . $id . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit();

    } catch (Exception $e) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(403);
        echo 'Acceso no autorizado';
        exit();
    }
}

?>
