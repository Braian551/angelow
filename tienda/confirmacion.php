<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

$pageTitle = "Confirmación - Paso 4: Pedido Confirmado";
$currentPage = 'confirmation';

$user_id = $_SESSION['user_id'];

// Verificar que exista la última orden en la sesión
if (!isset($_SESSION['last_order'])) {
    header("Location: " . BASE_URL . "/tienda/cart.php");
    exit();
}

$order_number = $_SESSION['last_order'];

// Obtener información completa de la orden
try {
    $orderQuery = "
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
               pt.reference_number, pt.payment_proof, pt.created_at as payment_date
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        WHERE o.order_number = ? AND o.user_id = ?
        ORDER BY o.created_at DESC 
        LIMIT 1
    ";
    $stmt = $conn->prepare($orderQuery);
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Orden no encontrada");
    }
    
    // Obtener items de la orden
    $itemsQuery = "
        SELECT oi.*, p.slug as product_slug,
               COALESCE(vi.image_path, pi.image_path) as primary_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON oi.color_variant_id = pcv.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE oi.order_id = ?
    ";
    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([$order['id']]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener dirección de envío desde los datos de la orden
    $shipping_address = $order['shipping_address'];
    
} catch (Exception $e) {
    error_log("Error al obtener información de la orden: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/cart.php");
    exit();
}

// Procesar generación de PDF
if (isset($_POST['download_pdf'])) {
    try {
        // Limpiar buffers de salida
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Configurar headers para descarga de PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="comprobante_pedido_' . $order_number . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Crear nuevo documento PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(SITE_NAME);
        $pdf->SetTitle('Comprobante de Pedido ' . $order_number);
        $pdf->SetSubject('Comprobante de Pedido');
        $pdf->SetKeywords('Pedido, Comprobante, ' . SITE_NAME);
        
        // Configuración de márgenes
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->setPrintFooter(true);
        
        // Auto saltos de página
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Fuente principal
        $pdf->SetFont('helvetica', '', 10);
        
        // Verificar si existe el logo
        $logoPath = __DIR__ . '/../../images/logo2.png';
        $logoExists = file_exists($logoPath);
        
        // Agregar página
        $pdf->AddPage();
        
        // HTML para el contenido del PDF
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
            .order-number {
                font-size: 20pt;
                color: #006699;
                font-weight: bold;
                text-align: center;
                margin: 20px 0;
                letter-spacing: 2px;
            }
            .order-total {
                font-size: 16pt;
                color: #FF6600;
                font-weight: bold;
                text-align: center;
                margin: 10px 0;
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
            .text-left {
                text-align: left;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-weight: bold;
                font-size: 9pt;
            }
            .status-pending {
                background-color: #FFF3CD;
                color: #856404;
            }
            .status-confirmed {
                background-color: #D1ECF1;
                color: #0C5460;
            }
            .company-info {
                font-size: 9pt;
                color: #666666;
                line-height: 1.4;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            .summary-table td {
                padding: 6px 8px;
                border-bottom: 1px solid #EEEEEE;
            }
            .summary-table .total-row {
                border-top: 2px solid #006699;
                font-weight: bold;
            }
        </style>
        
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="40%">';
        
        if ($logoExists) {
            $html .= '<img src="' . $logoPath . '" width="180">';
        } else {
            $html .= '<h1 class="header-title">' . SITE_NAME . '</h1>
                      <p class="header-subtitle">Moda infantil de calidad</p>';
        }
        
        $html .= '
                </td>
                <td width="60%" style="text-align: right; vertical-align: top;">
                    <h1 class="header-title">COMPROBANTE DE PEDIDO</h1>
                    <p><span class="label">Fecha:</span> ' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</p>
                    <p><span class="label">Cliente:</span> ' . htmlspecialchars($order['user_name']) . '</p>
                </td>
            </tr>
        </table>
        
        <!-- Número de orden destacado -->
        <div class="order-number">PEDIDO #' . htmlspecialchars($order_number) . '</div>
        <div class="order-total">TOTAL: $' . number_format($order['total'], 0, ',', '.') . '</div>
        
        <!-- Información del pedido -->
        <h3 class="section-title">INFORMACIÓN DEL PEDIDO</h3>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><span class="label">Número de orden:</span></td>
                <td width="75%" class="value">' . htmlspecialchars($order_number) . '</td>
            </tr>
            <tr>
                <td><span class="label">Fecha:</span></td>
                <td class="value">' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</td>
            </tr>
            <tr>
                <td><span class="label">Estado:</span></td>
                <td class="value"><span class="status-badge status-pending">' . ucfirst($order['status']) . '</span></td>
            </tr>
            <tr>
                <td><span class="label">Método de pago:</span></td>
                <td class="value">Transferencia bancaria</td>
            </tr>
            <tr>
                <td><span class="label">Referencia:</span></td>
                <td class="value">' . htmlspecialchars($order['reference_number']) . '</td>
            </tr>
        </table>
        
        <!-- Información de envío -->
        <h3 class="section-title">INFORMACIÓN DE ENVÍO</h3>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><span class="label">Cliente:</span></td>
                <td width="75%" class="value">' . htmlspecialchars($order['user_name']) . '</td>
            </tr>';
        
        if ($order['user_phone']) {
            $html .= '
            <tr>
                <td><span class="label">Teléfono:</span></td>
                <td class="value">' . htmlspecialchars($order['user_phone']) . '</td>
            </tr>';
        }
        
        $html .= '
            <tr>
                <td><span class="label">Email:</span></td>
                <td class="value">' . htmlspecialchars($order['user_email']) . '</td>
            </tr>
            <tr>
                <td><span class="label">Dirección:</span></td>
                <td class="value">' . htmlspecialchars($shipping_address) . '</td>
            </tr>
        </table>
        
        <!-- Productos del pedido -->
        <h3 class="section-title">PRODUCTOS DEL PEDIDO</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Producto</th>
                    <th style="text-align: center;">Variante</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: right;">Precio Unit.</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($orderItems as $item) {
            $html .= '
                <tr>
                    <td class="text-left">' . htmlspecialchars($item['product_name']) . '</td>
                    <td class="text-center">' . htmlspecialchars($item['variant_name'] ?? 'N/A') . '</td>
                    <td class="text-center">' . $item['quantity'] . '</td>
                    <td class="text-right">$' . number_format($item['price'], 0, ',', '.') . '</td>
                    <td class="text-right">$' . number_format($item['total'], 0, ',', '.') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>
        
        <!-- Resumen de pagos -->
        <h3 class="section-title">RESUMEN DE PAGOS</h3>
        <table class="summary-table">
            <tr>
                <td class="text-left">Subtotal:</td>
                <td class="text-right">$' . number_format($order['subtotal'], 0, ',', '.') . '</td>
            </tr>';
        
        if ($order['discount_amount'] > 0) {
            $html .= '
            <tr>
                <td class="text-left">Descuento:</td>
                <td class="text-right">-$' . number_format($order['discount_amount'], 0, ',', '.') . '</td>
            </tr>';
        }
        
        $html .= '
            <tr>
                <td class="text-left">Costo de envío:</td>
                <td class="text-right">$' . number_format($order['shipping_cost'], 0, ',', '.') . '</td>
            </tr>
            <tr class="total-row">
                <td class="text-left"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>$' . number_format($order['total'], 0, ',', '.') . '</strong></td>
            </tr>
        </table>
        
        <!-- Información de pago -->
        <h3 class="section-title">INFORMACIÓN DE PAGO</h3>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><span class="label">Método:</span></td>
                <td width="75%" class="value">Transferencia bancaria</td>
            </tr>
            <tr>
                <td><span class="label">Referencia:</span></td>
                <td class="value">' . htmlspecialchars($order['reference_number']) . '</td>
            </tr>
            <tr>
                <td><span class="label">Fecha de pago:</span></td>
                <td class="value">' . date('d/m/Y H:i', strtotime($order['payment_date'])) . '</td>
            </tr>
            <tr>
                <td><span class="label">Estado:</span></td>
                <td class="value"><span class="status-badge status-pending">Pendiente de verificación</span></td>
            </tr>
        </table>
        
        <!-- Instrucciones -->
        <h3 class="section-title">PRÓXIMOS PASOS</h3>
        <p>1. Tu pedido ha sido recibido y está siendo procesado.</p>
        <p>2. Verificaremos el pago en un plazo máximo de 24 horas.</p>
        <p>3. Recibirás una notificación cuando tu pedido sea enviado.</p>
        <p>4. El tiempo de entrega depende del método de envío seleccionado.</p>
        
        <div class="footer">
            <strong>' . SITE_NAME . '</strong><br>
            ' . SITE_CONTACT . ' | Email: ' . SITE_EMAIL . '<br>
            ' . SITE_ADDRESS . ' - ' . SITE_URL . '
        </div>';
        
        // Escribir el HTML en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Salida del PDF
        $pdf->Output('comprobante_pedido_' . $order_number . '.pdf', 'D');
        exit();

    } catch (Exception $e) {
        error_log("Error al generar PDF: " . $e->getMessage());
        // Si hay error en PDF, continuar mostrando la página normal
    }
}

// Limpiar carrito y datos de sesión después de mostrar la confirmación
try {
    // Obtener el carrito del usuario
    $cartQuery = "SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart) {
        // Limpiar items del carrito
        $clearCartQuery = "DELETE FROM cart_items WHERE cart_id = ?";
        $stmt = $conn->prepare($clearCartQuery);
        $stmt->execute([$cart['id']]);
    }
    
    // Limpiar descuentos aplicados que fueron usados
    if ($order['discount_amount'] > 0) {
        $clearDiscountsQuery = "
            UPDATE user_applied_discounts 
            SET is_used = 1, used_at = NOW() 
            WHERE user_id = ? AND is_used = 0
        ";
        $stmt = $conn->prepare($clearDiscountsQuery);
        $stmt->execute([$user_id]);
    }
    
    // Limpiar sesión (excepto last_order para que se pueda ver la confirmación)
    $last_order = $_SESSION['last_order'];
    unset($_SESSION['checkout_data']);
    $_SESSION['last_order'] = $last_order;
    
} catch (Exception $e) {
    error_log("Error al limpiar carrito: " . $e->getMessage());
    // Continuar aunque falle la limpieza
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/cart.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/envio.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/tienda/confirmacion.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificaciones/notification2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/headerproducts.php'; ?>

    <main class="container checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-check-circle"></i> ¡Pedido Confirmado!</h1>
            <div class="checkout-steps">
                <div class="step">
                    <span>1</span>
                    <p>Carrito</p>
                </div>
                <div class="step">
                    <span>2</span>
                    <p>Envío</p>
                </div>
                <div class="step">
                    <span>3</span>
                    <p>Pago</p>
                </div>
                <div class="step active">
                    <span>4</span>
                    <p>Confirmación</p>
                </div>
            </div>
        </div>

        <div class="confirmation-content">
            <!-- Mensaje de éxito -->
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-content">
                    <h2>¡Gracias por tu compra!</h2>
                    <p class="success-message">
                        Tu pedido <strong>#<?= htmlspecialchars($order_number) ?></strong> ha sido recibido y está siendo procesado. 
                        Te hemos enviado un correo de confirmación a <strong><?= htmlspecialchars($order['user_email']) ?></strong>.
                    </p>
                    <div class="order-summary">
                        <div class="summary-item">
                            <i class="fas fa-receipt"></i>
                            <div>
                                <span class="label">Número de orden</span>
                                <span class="value">#<?= htmlspecialchars($order_number) ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-calendar"></i>
                            <div>
                                <span class="label">Fecha</span>
                                <span class="value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <span class="label">Total pagado</span>
                                <span class="value">$<?= number_format($order['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <i class="fas fa-truck"></i>
                            <div>
                                <span class="label">Método de envío</span>
                                <span class="value">Transferencia bancaria</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="confirmation-grid">
                <!-- Información del pedido -->
                <section class="confirmation-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shopping-bag"></i> Resumen del Pedido</h2>
                    </div>

                    <div class="order-items-confirmation">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="order-item-confirm">
                                <div class="item-image">
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['primary_image'] ?? 'assets/images/placeholder-product.jpg') ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>">
                                </div>
                                <div class="item-details">
                                    <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                    <?php if ($item['variant_name']): ?>
                                        <p class="item-variant"><?= htmlspecialchars($item['variant_name']) ?></p>
                                    <?php endif; ?>
                                    <div class="item-meta">
                                        <span class="quantity"><?= $item['quantity'] ?> x $<?= number_format($item['price'], 0, ',', '.') ?></span>
                                        <span class="total">$<?= number_format($item['total'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-totals-confirm">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>$<?= number_format($order['subtotal'], 0, ',', '.') ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="total-row discount">
                                <span>Descuento</span>
                                <span>-$<?= number_format($order['discount_amount'], 0, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="total-row">
                            <span>Envío</span>
                            <span>$<?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span>$<?= number_format($order['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </section>

                <!-- Información de envío y pago -->
                <section class="confirmation-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shipping-fast"></i> Información de Envío</h2>
                    </div>

                    <div class="shipping-info">
                        <div class="info-group">
                            <label>Dirección de envío:</label>
                            <p><?= htmlspecialchars($shipping_address) ?></p>
                        </div>
                        <div class="info-group">
                            <label>Método de envío:</label>
                            <p>Transferencia bancaria - Pendiente de verificación</p>
                        </div>
                        <div class="info-group">
                            <label>Referencia de pago:</label>
                            <p class="reference-number"><?= htmlspecialchars($order['reference_number']) ?></p>
                        </div>
                    </div>

                    <!-- Información de seguimiento -->
                    <div class="tracking-info">
                        <h3><i class="fas fa-map-marker-alt"></i> Progreso del Pedido</h3>
                        <div class="tracking-steps">
                            <div class="tracking-step completed">
                                <div class="step-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="step-content">
                                    <span class="step-title">Pedido realizado</span>
                                    <span class="step-date"><?= date('d/m H:i', strtotime($order['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="tracking-step active">
                                <div class="step-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="step-content">
                                    <span class="step-title">Verificación de pago</span>
                                    <span class="step-date">En proceso</span>
                                </div>
                            </div>
                            <div class="tracking-step">
                                <div class="step-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="step-content">
                                    <span class="step-title">Preparando pedido</span>
                                    <span class="step-date">Próximamente</span>
                                </div>
                            </div>
                            <div class="tracking-step">
                                <div class="step-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="step-content">
                                    <span class="step-title">En camino</span>
                                    <span class="step-date">Próximamente</span>
                                </div>
                            </div>
                            <div class="tracking-step">
                                <div class="step-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="step-content">
                                    <span class="step-title">Entregado</span>
                                    <span class="step-date">Próximamente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Acciones -->
            <div class="confirmation-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="download_pdf" class="btn btn-primary">
                        <i class="fas fa-download"></i> Descargar Comprobante (PDF)
                    </button>
                </form>
                <a href="<?= BASE_URL ?>/tienda" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i> Seguir Comprando
                </a>
                <a href="<?= BASE_URL ?>/users/profile.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Ver Mis Pedidos
                </a>
            </div>

            <!-- Información de contacto -->
            <div class="contact-info">
                <h3><i class="fas fa-headset"></i> ¿Necesitas ayuda?</h3>
                <p>Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos:</p>
                <div class="contact-methods">
                    <div class="contact-method">
                        <i class="fas fa-envelope"></i>
                        <span><?= SITE_EMAIL ?></span>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-phone"></i>
                        <span><?= SITE_CONTACT ?></span>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-clock"></i>
                        <span>Horario de atención: Lunes a Viernes 8:00 AM - 6:00 PM</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animación para los pasos de seguimiento
            const trackingSteps = document.querySelectorAll('.tracking-step');
            
            trackingSteps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.opacity = '1';
                    step.style.transform = 'translateY(0)';
                }, index * 300);
            });

            // Mostrar notificación de éxito
            setTimeout(() => {
                const successCard = document.querySelector('.success-card');
                if (successCard) {
                    successCard.classList.add('animate-in');
                }
            }, 500);
        });
    </script>
</body>

</html>