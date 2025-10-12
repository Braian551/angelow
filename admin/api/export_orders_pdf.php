<?php
// admin/api/export_orders_pdf.php

// Inicializar sesión primero
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para enviar error en formato JSON
function sendJsonError($message, $code = 500) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

// Verificar archivos requeridos
$configPath = __DIR__ . '/../../config.php';
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';

if (!file_exists($configPath)) {
    sendJsonError('Archivo config.php no encontrado');
}

if (!file_exists($autoloadPath)) {
    sendJsonError('Dependencias de Composer no instaladas. Por favor ejecuta: composer install', 500);
}

require_once $configPath;
require_once $autoloadPath;

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar que Dompdf esté disponible
if (!class_exists('Dompdf\Dompdf')) {
    sendJsonError('La librería Dompdf no está disponible. Ejecuta: composer install', 500);
}

// Verificar extensiones PHP necesarias
$requiredExtensions = ['mbstring', 'dom', 'gd', 'fileinfo'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    error_log("[PDF_ERROR] Extensiones PHP faltantes: " . implode(', ', $missingExtensions));
    // Continuar de todas formas, pero con advertencia
    error_log("[PDF_WARNING] Continuando sin algunas extensiones. Las imágenes pueden no funcionar correctamente.");
}

// Función para obtener la ruta absoluta del servidor desde una URL
function getServerPathFromUrl($url) {
    $relativePath = str_replace(BASE_URL . '/', '', $url);
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/angelow/' . $relativePath;
}

// Función para obtener la URL completa
function getFullUrl($path) {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

// Función para convertir imagen a base64
function imageToBase64($path) {
    if (!file_exists($path)) {
        error_log("[PDF_IMG] Archivo no existe: " . $path);
        return false;
    }
    
    try {
        $imageData = @file_get_contents($path);
        if ($imageData === false) {
            error_log("[PDF_IMG] No se pudo leer el archivo: " . $path);
            return false;
        }
        
        // Obtener el tipo MIME de la imagen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);
        
        // Si no se puede determinar el tipo MIME, usar la extensión
        if (!$mimeType) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeType = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        }
        
        $base64 = base64_encode($imageData);
        error_log("[PDF_IMG] Imagen convertida exitosamente: " . $path . " (" . strlen($base64) . " bytes)");
        
        return 'data:' . $mimeType . ';base64,' . $base64;
    } catch (Exception $e) {
        error_log("[PDF_IMG] Error al convertir imagen: " . $e->getMessage());
        return false;
    }
}

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Verificar autenticación y permisos
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Usuario no autenticado");
    }

    // Verificar conexión a base de datos
    if (!isset($conn)) {
        throw new Exception("No hay conexión a la base de datos");
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
        SELECT o.*, 
               u.name as client_name, 
               u.email as client_email, 
               u.phone as client_phone,
               u.identification_type, 
               u.identification_number,
               pt.reference_number,
               pt.payment_proof,
               pt.status as transaction_status,
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
        SELECT oi.*, o.order_number,
               COALESCE(vi.image_path, pi.image_path) as primary_image
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
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

    // Configurar Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('defaultMediaType', 'screen');
    $options->set('isFontSubsettingEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('chroot', realpath($_SERVER['DOCUMENT_ROOT']));
    $options->set('debugPng', false);
    $options->set('debugKeepTemp', false);
    $options->set('debugCss', false);
    $options->set('debugLayout', false);
    $options->set('debugLayoutLines', false);
    $options->set('debugLayoutBlocks', false);
    $options->set('debugLayoutInline', false);
    $options->set('debugLayoutPaddingBox', false);
    
    $dompdf = new Dompdf($options);
    
    // Verificar si existe el logo
    $logoUrl = BASE_URL . '/images/logo2.png';
    $logoPath = getServerPathFromUrl($logoUrl);
    $logoBase64 = null;
    
    error_log("[PDF_DEBUG] Intentando cargar logo desde: " . $logoPath);
    
    if (file_exists($logoPath)) {
        $logoBase64 = imageToBase64($logoPath);
        if ($logoBase64) {
            error_log("[PDF_DEBUG] Logo cargado exitosamente");
        } else {
            error_log("[PDF_DEBUG] Error al convertir logo a base64");
        }
    } else {
        error_log("[PDF_DEBUG] Logo no encontrado, intentando alternativa");
        // Intentar con logo.png
        $logoPath = str_replace('logo2.png', 'logo.png', $logoPath);
        if (file_exists($logoPath)) {
            $logoBase64 = imageToBase64($logoPath);
            if ($logoBase64) {
                error_log("[PDF_DEBUG] Logo alternativo cargado exitosamente");
            }
        } else {
            error_log("[PDF_DEBUG] Ningún logo encontrado, continuando sin logo");
        }
    }
    
    // Generar HTML para todas las órdenes
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @page {
                margin: 15mm;
            }
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 11px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-after: always;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                margin-bottom: 20px;
                border-bottom: 3px solid #006699;
                padding-bottom: 15px;
                background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
            }
            .header-content {
                display: table;
                width: 100%;
            }
            .logo-container {
                display: table-cell;
                width: 100px;
                vertical-align: middle;
                padding-right: 20px;
            }
            .logo {
                width: 80px;
                height: auto;
            }
            .header-text {
                display: table-cell;
                vertical-align: middle;
                text-align: center;
            }
            .header-title {
                color: #006699;
                font-size: 24px;
                font-weight: bold;
                margin: 0 0 5px 0;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .header-subtitle {
                color: #666666;
                font-size: 14px;
                margin: 0;
                font-weight: 500;
            }
            .company-info {
                text-align: center;
                margin-bottom: 20px;
                padding: 12px;
                font-size: 10px;
                color: #555;
                background: #f8f9fa;
                border-radius: 5px;
                border: 1px solid #e0e0e0;
                line-height: 1.6;
            }
            .order-header {
                background-color: #006699;
                color: #ffffff;
                padding: 18px 20px;
                border-radius: 8px;
                margin-bottom: 25px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .order-header * {
                color: #ffffff !important;
            }
            .order-header-grid {
                display: table;
                width: 100%;
            }
            .order-header-col {
                display: table-cell;
                vertical-align: middle;
                width: 50%;
            }
            .order-number {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                color: #ffffff;
            }
            .order-date {
                font-size: 12px;
                opacity: 0.95;
                font-weight: 400;
                color: #ffffff;
            }
            .status-badges {
                text-align: right;
            }
            .status-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 15px;
                font-size: 10px;
                font-weight: bold;
                margin-left: 8px;
                background: rgba(255,255,255,0.25);
                border: 1px solid rgba(255,255,255,0.4);
                box-shadow: 0 2px 4px rgba(0,0,0,0.15);
                color: #ffffff;
            }
            .info-grid {
                display: table;
                width: 100%;
                margin-bottom: 25px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                background: #ffffff;
            }
            .info-section {
                display: table-cell;
                width: 50%;
                padding: 18px;
                vertical-align: top;
                background: #ffffff;
            }
            .info-section:first-child {
                border-right: 2px solid #e0e0e0;
            }
            .section-title {
                color: #006699;
                font-size: 13px;
                font-weight: bold;
                margin: 0 0 12px 0;
                border-bottom: 3px solid #006699;
                padding-bottom: 6px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .info-item {
                margin-bottom: 8px;
                font-size: 11px;
                line-height: 1.6;
            }
            .info-label {
                font-weight: bold;
                color: #333;
                display: inline-block;
                width: 110px;
                vertical-align: top;
            }
            .info-value {
                color: #555;
                word-wrap: break-word;
                display: inline-block;
                width: calc(100% - 115px);
                vertical-align: top;
            }
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
                border: 2px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
            }
            .products-table th {
                background-color: #006699;
                color: #ffffff !important;
                padding: 12px 10px;
                text-align: left;
                font-weight: bold;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .products-table th * {
                color: #ffffff !important;
            }
            .products-table td {
                border: 1px solid #e0e0e0;
                padding: 12px 10px;
                font-size: 11px;
                vertical-align: middle;
                background: #ffffff;
            }
            .products-table tbody tr:nth-child(even) td {
                background: #f8f9fa;
            }
            .product-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 5px;
                border: 2px solid #e0e0e0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                display: block;
                margin: 0 auto;
            }
            .product-name {
                font-weight: bold;
                color: #006699;
                font-size: 11px;
                line-height: 1.4;
            }
            .product-variant {
                font-size: 9px;
                color: #777;
                font-style: italic;
                display: inline-block;
                margin-top: 4px;
                padding: 3px 8px;
                background: #f0f8ff;
                border-radius: 3px;
            }
            .totals-section {
                background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 25px;
                border: 2px solid #e0e0e0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .total-row {
                display: table;
                width: 100%;
                padding: 6px 0;
                border-bottom: 1px solid #e0e0e0;
                font-size: 11px;
            }
            .total-row:last-child {
                border-bottom: none;
            }
            .total-label {
                display: table-cell;
                text-align: left;
                width: 70%;
                color: #333;
            }
            .total-value {
                display: table-cell;
                text-align: right;
                width: 30%;
                font-weight: bold;
                color: #006699;
            }
            .grand-total {
                font-weight: bold;
                font-size: 15px;
                color: #006699;
                border-top: 3px solid #006699 !important;
                margin-top: 10px;
                padding-top: 12px;
                background: #f0f8ff;
                padding: 12px;
                border-radius: 5px;
            }
            .grand-total .total-value {
                font-size: 16px;
            }
            .payment-section {
                background: linear-gradient(to right, #e3f2fd 0%, #f0f8ff 100%);
                padding: 18px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 5px solid #006699;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                border: 1px solid #b3d9f2;
            }
            .notes-section {
                background: linear-gradient(to right, #fff8e1 0%, #fffbf0 100%);
                padding: 18px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 5px solid #ffc107;
                font-size: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                border: 1px solid #ffe082;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding: 20px;
                border-top: 3px solid #006699;
                font-size: 10px;
                color: #666;
                background: #f8f9fa;
                border-radius: 8px;
                line-height: 1.8;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .discount-text {
                color: #28a745;
                font-weight: bold;
            }
            .no-image-placeholder {
                width: 50px;
                height: 50px;
                background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
                border-radius: 5px;
                font-size: 7px;
                color: #999;
                border: 2px solid #e0e0e0;
                font-weight: bold;
                text-align: center;
                line-height: 46px;
                display: block;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>';
    
    // Generar una página por cada orden
    $orderCount = count($orders);
    $currentOrder = 0;
    
    foreach ($orders as $order) {
        $currentOrder++;
        $items = $itemsByOrder[$order['id']] ?? [];
        
        $html .= '
        <div class="container">
            <!-- Encabezado -->
            <div class="header">
                <div class="header-content">';
        
        if ($logoBase64) {
            $html .= '
                    <div class="logo-container">
                        <img src="' . $logoBase64 . '" class="logo" alt="Logo Angelow">
                    </div>';
        }
        
        $html .= '
                    <div class="header-text">
                        <h1 class="header-title">Angelow Ropa Infantil</h1>
                        <p class="header-subtitle">Reporte de Orden de Compra</p>
                    </div>
                </div>
            </div>

            <!-- Información de la empresa -->
            <div class="company-info">
                <strong style="font-size: 10px; color: #006699;">ANGELOW ROPA INFANTIL</strong><br>
                <strong>NIT:</strong> 901234567-8 | <strong>Tel:</strong> +57 604 1234567 | <strong>Email:</strong> contacto@angelow.com<br>
                Calle 10 # 40-20, Medellin, Antioquia - <strong>www.angelow.com</strong>
            </div>

            <!-- Cabecera de la orden -->
            <div class="order-header" style="background-color: #006699; color: #ffffff;">
                <div class="order-header-grid">
                    <div class="order-header-col">
                        <div class="order-number" style="color: #ffffff; font-size: 20px; font-weight: bold; margin-bottom: 8px;">ORDEN #' . htmlspecialchars($order['order_number']) . '</div>
                        <div class="order-date" style="color: #ffffff; font-size: 12px;">Fecha: ' . htmlspecialchars($order['formatted_date']) . '</div>
                    </div>
                    <div class="order-header-col status-badges">
                        <div style="margin-bottom: 5px;">
                            <strong style="color: #ffffff; font-size: 9px;">ESTADO DE LA ORDEN:</strong><br>
                            <span class="status-badge" style="color: #ffffff; background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; margin-top: 3px; display: inline-block;">' . htmlspecialchars($statusTranslations[$order['status']] ?? ucfirst($order['status'])) . '</span>
                        </div>
                        <div>
                            <strong style="color: #ffffff; font-size: 9px;">ESTADO DEL PAGO:</strong><br>
                            <span class="status-badge" style="color: #ffffff; background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; margin-top: 3px; display: inline-block;">' . htmlspecialchars($paymentStatusTranslations[$order['payment_status']] ?? ucfirst($order['payment_status'])) . '</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del cliente y envío -->
            <div class="info-grid">
                <div class="info-section">
                    <h3 class="section-title">INFORMACIÓN DEL CLIENTE</h3>
                    <div class="info-item">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value">' . htmlspecialchars($order['client_name'] ?? 'N/A') . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Documento:</span>
                        <span class="info-value">' . htmlspecialchars($order['identification_number'] ?? 'N/A') . 
                          ' (' . htmlspecialchars($order['identification_type'] ?? 'CC') . ')</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">' . htmlspecialchars($order['client_phone'] ?? 'N/A') . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">' . htmlspecialchars($order['client_email'] ?? 'N/A') . '</span>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title">DIRECCIÓN DE ENVÍO</h3>
                    <div class="info-item">
                        <span class="info-value">' . 
                            htmlspecialchars($order['shipping_address'] ?? 'N/A') .
                            (!empty($order['shipping_neighborhood']) ? '<br>' . htmlspecialchars($order['shipping_neighborhood']) : '') .
                            (!empty($order['shipping_complement']) ? '<br>' . htmlspecialchars($order['shipping_complement']) : '') .
                        '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ciudad:</span>
                        <span class="info-value">' . htmlspecialchars($order['shipping_city'] ?? 'N/A') . '</span>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <h3 class="section-title" style="margin-bottom: 15px; font-size: 14px;">DETALLE DE PRODUCTOS</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center; color: #ffffff !important; background-color: #006699;">IMAGEN</th>
                        <th style="width: 85px; color: #ffffff !important; background-color: #006699;">CODIGO</th>
                        <th style="color: #ffffff !important; background-color: #006699;">DESCRIPCION DEL PRODUCTO</th>
                        <th style="width: 60px; text-align: center; color: #ffffff !important; background-color: #006699;">CANT.</th>
                        <th style="width: 85px; text-align: right; color: #ffffff !important; background-color: #006699;">P. UNIT.</th>
                        <th style="width: 90px; text-align: right; color: #ffffff !important; background-color: #006699;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Agregar items de esta orden
        foreach ($items as $item) {
            // Generar código del item
            $itemCode = 'PROD-' . str_pad($item['product_id'], 5, '0', STR_PAD_LEFT);
            
            // Intentar cargar imagen del producto si existe
            $productImageBase64 = null;
            $imageLoaded = false;
            
            if (!empty($item['primary_image'])) {
                try {
                    $imageUrl = getFullUrl($item['primary_image']);
                    $imagePath = getServerPathFromUrl($imageUrl);
                    error_log("[PDF_PRODUCT_IMG] Intentando cargar: " . $imagePath);
                    
                    if (file_exists($imagePath)) {
                        $productImageBase64 = imageToBase64($imagePath);
                        if ($productImageBase64) {
                            $imageLoaded = true;
                            error_log("[PDF_PRODUCT_IMG] Imagen cargada exitosamente");
                        }
                    }
                } catch (Exception $e) {
                    error_log("[PDF_PRODUCT_IMG] Error al cargar imagen: " . $e->getMessage());
                }
            }
            
            // Si no se cargó la imagen del producto, intentar con la imagen por defecto
            if (!$imageLoaded) {
                try {
                    $defaultImagePath = $_SERVER['DOCUMENT_ROOT'] . '/angelow/images/default-product.jpg';
                    error_log("[PDF_PRODUCT_IMG] Intentando imagen por defecto: " . $defaultImagePath);
                    
                    if (file_exists($defaultImagePath)) {
                        $productImageBase64 = imageToBase64($defaultImagePath);
                        if ($productImageBase64) {
                            $imageLoaded = true;
                            error_log("[PDF_PRODUCT_IMG] Imagen por defecto cargada");
                        }
                    }
                } catch (Exception $e) {
                    error_log("[PDF_PRODUCT_IMG] Error al cargar imagen por defecto: " . $e->getMessage());
                }
            }
            
            $html .= '
                    <tr>
                        <td style="text-align: center; padding: 10px;">';
            
            if ($imageLoaded && $productImageBase64) {
                $html .= '<img src="' . $productImageBase64 . '" class="product-image" alt="Producto">';
            } else {
                // Usar placeholder HTML sin imagen
                $html .= '<div class="no-image-placeholder">SIN IMAGEN</div>';
            }
            
            $html .= '
                        </td>
                        <td style="font-family: monospace; font-size: 10px; color: #666; font-weight: bold;">' . htmlspecialchars($itemCode) . '</td>
                        <td>
                            <span class="product-name">' . htmlspecialchars($item['product_name']) . '</span>';
            
            if (!empty($item['variant_name'])) {
                $html .= '<br><span class="product-variant">Color: ' . htmlspecialchars($item['variant_name']) . '</span>';
            }
            
            $html .= '
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 13px; color: #006699;">' . intval($item['quantity']) . '</td>
                        <td style="text-align: right; font-weight: bold; color: #333;">$' . number_format($item['price'], 0, ',', '.') . '</td>
                        <td style="text-align: right; font-weight: bold; color: #006699; font-size: 12px;">$' . number_format($item['total'], 0, ',', '.') . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>

            <!-- Totales -->
            <div class="totals-section">
                <h3 class="section-title" style="margin-top: 0; font-size: 13px;">RESUMEN DE PAGO</h3>
                <div class="total-row">
                    <div class="total-label">Subtotal de Productos:</div>
                    <div class="total-value">$' . number_format($order['subtotal'], 0, ',', '.') . '</div>
                </div>';
        
            if (!empty($order['discount_amount']) && $order['discount_amount'] > 0) {
            $html .= '
                <div class="total-row">
                    <div class="total-label">Descuento Aplicado:</div>
                    <div class="total-value discount-text">-$' . number_format($order['discount_amount'], 0, ',', '.') . '</div>
                </div>';
        }
        
        $html .= '
                <div class="total-row">
                    <div class="total-label">Costo de Envío:</div>
                    <div class="total-value">$' . number_format($order['shipping_cost'], 0, ',', '.') . '</div>
                </div>
                <div class="total-row grand-total">
                    <div class="total-label"><strong>TOTAL A PAGAR:</strong></div>
                    <div class="total-value">$' . number_format($order['total'], 0, ',', '.') . '</div>
                </div>
            </div>

            <!-- Información de pago -->
            <div class="payment-section">
                <h3 class="section-title" style="margin-top: 0; color: #006699; font-size: 13px;">INFORMACION DE PAGO</h3>
                <div class="info-item">
                    <span class="info-label">Metodo de Pago:</span>
                    <span class="info-value" style="font-weight: bold; color: #006699;">' . htmlspecialchars($paymentMethodTranslations[$order['payment_method']] ?? ucfirst(str_replace('_', ' ', $order['payment_method']))) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado del Pago:</span>
                    <span class="info-value" style="font-weight: bold; color: ' . ($order['payment_status'] === 'paid' ? '#28a745' : ($order['payment_status'] === 'pending' ? '#ffc107' : '#dc3545')) . ';">';
        
        // Mostrar el estado del pago de forma más descriptiva
        if ($order['payment_status'] === 'paid') {
            $html .= 'PAGADO - Confirmado';
        } elseif ($order['payment_status'] === 'pending') {
            $html .= 'PENDIENTE - En verificacion';
        } elseif ($order['payment_status'] === 'failed') {
            $html .= 'FALLIDO - No procesado';
        } else {
            $html .= htmlspecialchars($paymentStatusTranslations[$order['payment_status']] ?? ucfirst($order['payment_status']));
        }
        
        $html .= '</span>
                </div>';
        
        if (!empty($order['reference_number'])) {
            $html .= '
                <div class="info-item">
                    <span class="info-label">Referencia:</span>
                    <span class="info-value">' . htmlspecialchars($order['reference_number']) . '</span>
                </div>';
        }
        
        if (!empty($order['payment_proof'])) {
            $html .= '
                <div class="info-item">
                    <span class="info-label">Comprobante:</span>
                    <span class="info-value" style="color: #28a745; font-weight: bold;">Adjunto</span>
                </div>';
        }
        
        $html .= '
            </div>

            <!-- Notas adicionales -->
            <div class="notes-section">
                <h3 class="section-title" style="margin-top: 0; color: #856404; font-size: 13px;">NOTAS Y OBSERVACIONES</h3>
                <p style="margin: 0 0 10px 0; line-height: 1.6; font-size: 11px;">' . nl2br(htmlspecialchars($order['notes'] ?? 'No hay observaciones registradas para esta orden.')) . '</p>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0e68c;">
                    <strong style="font-size: 10px; color: #856404;">Informacion importante:</strong><br>
                    <div style="font-size: 10px; margin-top: 5px; line-height: 1.6;">
                        - Tiempo de procesamiento: 24-48 horas habiles despues de confirmado el pago.<br>
                        - Para consultas sobre envios, contactar al WhatsApp: +57 300 1234567<br>
                        - Horario de atencion: Lunes a Viernes 8:00 AM - 6:00 PM, Sabados 9:00 AM - 2:00 PM
                    </div>
                </div>
            </div>

            <!-- Pie de página -->
            <div class="footer">
                <strong style="font-size: 11px; color: #006699;">ANGELOW ROPA INFANTIL</strong><br>
                <span style="font-style: italic; color: #888;">Moda infantil de calidad para tus pequeños</span><br><br>
                Documento generado el <strong>' . date('d/m/Y') . '</strong> a las <strong>' . date('H:i') . '</strong><br>
                Para consultas: <strong>contacto@angelow.com</strong> | Tel: <strong>+57 604 1234567</strong><br>
                Visitanos en: <strong style="color: #006699;">www.angelow.com</strong>
            </div>
        </div>';
        
        // Agregar salto de página si no es la última orden
        if ($currentOrder < $orderCount) {
            $html .= '<div class="page-break"></div>';
        }
    }
    
    $html .= '
    </body>
    </html>';

    // Cargar el HTML en Dompdf
    $dompdf->loadHtml($html);
    
    // Configurar el tamaño y orientación del papel
    $dompdf->setPaper('A4', 'portrait');
    
    // Renderizar el PDF
    $dompdf->render();
    
    // Generar nombre del archivo
    $filename = 'reporte_ordenes_' . date('Ymd_His') . '.pdf';
    
    // Configurar headers para descarga de PDF
    header('Content-Type: application/pdf; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Salida del PDF
    $dompdf->stream($filename, ['Attachment' => true]);
    exit();

} catch (Exception $e) {
    // Limpiar buffers antes de enviar respuesta JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Registrar error detallado
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error al generar reporte PDF: " . $e->getMessage() . 
                " en " . $e->getFile() . " línea " . $e->getLine() . "\n" .
                "Stack trace: " . $e->getTraceAsString() . "\n";
    
    // Intentar escribir al log
    $logPath = __DIR__ . '/../../php_errors.log';
    @error_log($errorMsg, 3, $logPath);
    
    // También al log del sistema
    error_log($errorMsg);
    
    // Responder con error detallado
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'debug_mode' => defined('DEBUG_MODE') ? DEBUG_MODE : false
    ]);
    exit();
}
?>