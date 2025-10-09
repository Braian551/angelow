<?php
// admin/api/pay/generate_pdf.php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Función para obtener la ruta absoluta del servidor desde una URL
function getServerPathFromUrl($url) {
    // Remover el BASE_URL de la ruta
    $relativePath = str_replace(BASE_URL . '/', '', $url);
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/angelow/' . $relativePath;
}

// Función para obtener la URL completa
function getFullUrl($path) {
    // Asegurarse de que el path no empiece con /
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

// Función para convertir imagen a base64
function imageToBase64($path) {
    error_log("[PDF_IMG_DEBUG] Intentando convertir a base64: " . $path);
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        if ($data === false) {
            error_log("[PDF_IMG_DEBUG] Error leyendo archivo: " . $path);
            return false;
        }
        $base64 = base64_encode($data);
        error_log("[PDF_IMG_DEBUG] Imagen convertida exitosamente: " . $path);
        return 'data:image/' . $type . ';base64,' . $base64;
    }
    error_log("[PDF_IMG_DEBUG] Archivo no existe: " . $path);
    return false;
}

// Función para obtener la ruta real de la imagen
function getRealImagePath($relativePath) {
    // Si la ruta está vacía o es null, usar imagen por defecto
    if (empty($relativePath)) {
        return $_SERVER['DOCUMENT_ROOT'] . '/angelow/images/default-product.jpg';
    }

    // Limpiar la ruta y asegurarse que use uploads/productos
    $relativePath = ltrim($relativePath, '/');
    if (!str_starts_with($relativePath, 'uploads/productos/')) {
        $relativePath = 'uploads/productos/' . basename($relativePath);
    }
    
    // Intentar con la ruta completa
    $forcedLocal = $_SERVER['DOCUMENT_ROOT'] . '/angelow/' . $relativePath;
    error_log("[PDF_IMG_DEBUG] Intentando candidato forzado local: " . $forcedLocal);
    
    if (file_exists($forcedLocal)) {
        error_log("[PDF_IMG_DEBUG] Encontrada imagen en ruta local: " . $forcedLocal);
        return $forcedLocal;
    }

    error_log("[PDF_IMG_DEBUG] No se encontró la imagen, usando default");
    return $_SERVER['DOCUMENT_ROOT'] . '/angelow/images/default-product.jpg';
}

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error en PDF: $message en $file:$line");
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Función para obtener la ruta base del proyecto
function getProjectBasePath() {
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/angelow';
}

// Función para verificar y obtener la ruta de imagen
function getImagePath($relativePath) {
    if (empty($relativePath)) {
        return getProjectBasePath() . '/images/default-product.jpg';
    }
    
    // Limpiar la ruta relativa
    $relativePath = ltrim($relativePath, '/');
    $absolutePath = getProjectBasePath() . '/' . $relativePath;
    
    error_log("Verificando imagen en: " . $absolutePath);
    
    if (file_exists($absolutePath)) {
        error_log("Imagen encontrada: " . $absolutePath);
        return $absolutePath;
    }
    
    error_log("Imagen no encontrada, usando default: " . $absolutePath);
    return getProjectBasePath() . '/images/default-product.jpg';
}

// Inicializar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar ID de la orden
$order_id = $_GET['id'] ?? 0;
$order_id = filter_var($order_id, FILTER_VALIDATE_INT);
if (!$order_id) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Usuario no autenticado");
    }

    $user_id = $_SESSION['user_id'];

    // Obtener información completa de la orden
    $orderQuery = "
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
               pt.reference_number, pt.payment_proof, pt.created_at as payment_date,
               sm.name as shipping_method_name, sm.description as shipping_description
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
        WHERE o.id = ? AND o.user_id = ?
    ";
    $stmt = $conn->prepare($orderQuery);
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("HTTP/1.0 404 Not Found");
        exit();
    }

    // Obtener items de la orden con imágenes
    $itemsQuery = "
        SELECT 
            oi.*,
            p.name as product_name,
            p.slug as product_slug,
            COALESCE(vi.image_path, pi.image_path) as primary_image,
            pcv.name as variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE oi.order_id = ?
    ";
    
    error_log("Ejecutando consulta SQL: " . str_replace('?', $order_id, $itemsQuery));
    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Limpiar buffers de salida antes de generar PDF
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Configurar headers para descarga de PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="comprobante_pedido_' . $order['order_number'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Configurar Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    $options->set('enable_php', true);
    
    $dompdf = new Dompdf($options);

    // HTML para el contenido del PDF
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
    
    // Manejo del logo usando BASE_URL
    $logoUrl = BASE_URL . '/images/logo2.png';
    $logoPath = getServerPathFromUrl($logoUrl);
    error_log("[PDF_DEBUG] Intentando cargar logo desde URL: " . $logoUrl);
    error_log("[PDF_DEBUG] Ruta del servidor para logo: " . $logoPath);
    
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $html .= '<img src="data:image/png;base64,' . $logoData . '" style="width:150px; margin-bottom:10px;" alt="Logo Angelow">';
        error_log("[PDF_DEBUG] Logo cargado exitosamente");
    } else {
        error_log("[PDF_DEBUG] Logo no encontrado en: " . $logoPath);
        // Intentar con logo.png si logo2.png no existe
        $logoPath = str_replace('logo2.png', 'logo.png', $logoPath);
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $html .= '<img src="data:image/png;base64,' . $logoData . '" style="width:150px; margin-bottom:10px;" alt="Logo Angelow">';
            error_log("[PDF_DEBUG] Logo alternativo cargado exitosamente");
        }
    }

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
        error_log("[PDF_DEBUG] Procesando producto: " . $item['product_name']);
        error_log("[PDF_DEBUG] Imagen en DB: " . $item['primary_image']);
        
        // Construir la URL completa y la ruta del servidor
        $imageUrl = getFullUrl($item['primary_image']);
        $imagePath = getServerPathFromUrl($imageUrl);
        
        error_log("[PDF_DEBUG] URL de imagen: " . $imageUrl);
        error_log("[PDF_DEBUG] Ruta en servidor: " . $imagePath);
        
        $base64Image = null;
        if (file_exists($imagePath)) {
            try {
                $imageData = file_get_contents($imagePath);
                if ($imageData !== false) {
                    $base64Image = base64_encode($imageData);
                    error_log("[PDF_DEBUG] Imagen de producto cargada exitosamente desde: " . $imagePath);
                } else {
                    error_log("[PDF_DEBUG] No se pudo leer el contenido de la imagen: " . $imagePath);
                }
            } catch (Exception $e) {
                error_log("[PDF_DEBUG] Error al procesar imagen: " . $e->getMessage());
            }
        } else {
            error_log("[PDF_DEBUG] Imagen de producto no encontrada en: " . $imagePath);
        }
        
        $html .= '
                    <tr>
                        <td class="text-center">';
        
        if ($base64Image) {
            $html .= '<img src="data:image/jpeg;base64,' . $base64Image . '" class="product-image" style="width:50px; height:50px; object-fit:cover;">';
            error_log("[PDF_DEBUG] Imagen insertada en HTML");
        } else {
            // Intentar con la imagen por defecto
            $defaultImage = $_SERVER['DOCUMENT_ROOT'] . '/angelow/images/default-product.jpg';
            error_log("[PDF_DEBUG] Intentando cargar imagen por defecto: " . $defaultImage);
            
            if (file_exists($defaultImage)) {
                try {
                    $defaultData = file_get_contents($defaultImage);
                    if ($defaultData !== false) {
                        $defaultBase64 = base64_encode($defaultData);
                        $html .= '<img src="data:image/jpeg;base64,' . $defaultBase64 . '" class="product-image" style="width:50px; height:50px; object-fit:cover;">';
                        error_log("[PDF_DEBUG] Imagen por defecto insertada");
                    } else {
                        error_log("[PDF_DEBUG] No se pudo leer la imagen por defecto");
                    }
                } catch (Exception $e) {
                    error_log("[PDF_DEBUG] Error al procesar imagen por defecto: " . $e->getMessage());
                }
            } else {
                error_log("[PDF_DEBUG] Imagen por defecto no encontrada");
                $html .= '
                    <div style="width:50px;height:50px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:3px;">
                        <span style="font-size:8px;color:#999;">Sin imagen</span>
                    </div>';
            }
        }
        
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
    
    // Salida del PDF
    $dompdf->stream('comprobante_pedido_' . $order['order_number'] . '.pdf', [
        'Attachment' => true
    ]);
    exit();

} catch (Exception $e) {
    // Limpiar buffers antes de enviar respuesta de error
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Registrar error detallado
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error al generar PDF de comprobante: " . $e->getMessage() . 
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
        'error' => 'Ocurrió un error al generar el comprobante PDF.',
        'debug' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : null
    ]);
    exit();
}
?>