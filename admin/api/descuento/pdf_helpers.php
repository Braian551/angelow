<?php
// admin/api/descuento/generate_pdf.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use TCPDF;

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Genera el PDF del código de descuento y devuelve el contenido binario (string).
 * Retorna null en caso de error.
 */
function generateDiscountPdfContent(int $id)
{
    // Usa la conexión disponible en el contexto global
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

        // Crear el PDF en memoria
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
        $logoPath = __DIR__ . '/../../../../images/logo2.png';
        $logoExists = file_exists($logoPath);
        $pdf->AddPage();

        // Construir HTML (igual que en el script principal)
        $html = '';
        // ... construimos el mismo HTML que usa el script (simplificado llamando a la parte existente)
        // Para mantener la misma apariencia usamos el HTML que ya existe más abajo creando una copia ligera aquí

        $html .= '<div style="font-family:helvetica">';
        if ($logoExists) {
            $html .= '<div style="text-align:left;"><img src="' . $logoPath . '" width="180"/></div>';
        } else {
            $html .= '<h1 style="color:#006699">Angelow Ropa Infantil</h1>';
        }
        $html .= '<h2 style="text-align:center;color:#006699">CÓDIGO DE DESCUENTO</h2>';
        $html .= '<div style="text-align:center;font-size:24pt;color:#006699;font-weight:bold;letter-spacing:3px;">' . htmlspecialchars($codigo['code']) . '</div>';
        $html .= '<div style="text-align:center;font-size:18pt;color:#FF6600;margin-bottom:10px;">' . htmlspecialchars($codigo['discount_type_name']) . ' DEL ' . $codigo['discount_value'] . '%</div>';
        if (!empty($codigo['end_date'])) {
            $html .= '<div style="text-align:center;font-style:italic;color:#666">Válido hasta: ' . date('d/m/Y', strtotime($codigo['end_date'])) . '</div>';
        } else {
            $html .= '<div style="text-align:center;font-style:italic;color:#666">Sin fecha de expiración</div>';
        }

        // Información básica
        $html .= '<h3 style="color:#006699">INFORMACIÓN DEL DESCUENTO</h3>';
        $html .= '<table cellpadding="3" cellspacing="0" width="100%">';
        $html .= '<tr><td style="width:25%;font-weight:bold">Tipo:</td><td>' . htmlspecialchars($codigo['discount_type_name']) . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold">Valor:</td><td>' . $codigo['discount_value'] . '% de descuento</td></tr>';
        $html .= '<tr><td style="font-weight:bold">Usos máximos:</td><td>' . ($codigo['max_uses'] ? $codigo['max_uses'] : 'Ilimitados') . '</td></tr>';
        $html .= '</table>';

        if (!empty($productos)) {
            $html .= '<h3 style="color:#006699">PRODUCTOS APLICABLES</h3>';
            $html .= '<table width="100%" border="1" cellpadding="4" cellspacing="0">';
            $html .= '<tr style="background:#006699;color:#fff"><th>Producto</th><th style="text-align:right">Precio</th></tr>';
            foreach ($productos as $p) {
                $html .= '<tr><td>' . htmlspecialchars($p['name']) . '</td><td style="text-align:right">$' . number_format($p['price'], 2, ',', '.') . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>Este descuento aplica a todos los productos de la tienda.</p>';
        }

        $html .= '<div style="font-size:8pt;color:#666;margin-top:10px">Angelow Ropa Infantil</div>';
        $html .= '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Devolver contenido en memoria
        return $pdf->Output('codigo_descuento_' . $codigo['code'] . '.pdf', 'S');
    } catch (Exception $e) {
        error_log('generateDiscountPdfContent error: ' . $e->getMessage());
        return null;
    }
}

// Si el archivo es accedido directamente por URL, responder con el PDF descargable.
// Cuando se incluye el archivo (require_once) no queremos ejecutar nada.
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // Archivo accedido directamente
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
        // Verificar autenticación y permisos de admin
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
