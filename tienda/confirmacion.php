<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Verificar disponibilidad de una librería para generar PDF (Dompdf o TCPDF)
$pdf_available = class_exists('\Dompdf\\Dompdf') || class_exists('Dompdf') || class_exists('TCPDF') || class_exists('\\TCPDF');

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

// Incluir el helper de correo (envío de confirmación)
require_once __DIR__ . '/api/pay/send_confirmation.php';
// Incluir helper puro para generar PDF (no ejecutar endpoint que haga stream/exit al incluir)
require_once __DIR__ . '/api/pay/pdf_helpers.php';

// Enviar correo de confirmación una sola vez por número de orden (adjuntar PDF si es posible)
try {
    $sessionFlag = 'confirmation_email_sent_' . $order_number;
    if (empty($_SESSION[$sessionFlag])) {
        $pdfContent = null;
        $pdfFilename = 'comprobante_pedido_' . $order_number . '.pdf';

        // Intentar generar PDF en memoria si hay un motor disponible (generate_pdf.php usa Dompdf)
        if ($pdf_available) {
            try {
                $pdfContent = generateOrderPdfContent($order, $orderItems);
            } catch (Exception $e) {
                error_log('Error al generar PDF en memoria para adjuntar: ' . $e->getMessage());
                $pdfContent = null;
            }
        }

        $sent = sendOrderConfirmationEmail($order, $orderItems, $pdfContent, $pdfFilename);
        if ($sent) {
            $_SESSION[$sessionFlag] = true;
        }
    }
} catch (Exception $e) {
    error_log('Error al intentar enviar correo de confirmación: ' . $e->getMessage());
}

// Procesar generación de PDF (descarga)
if (isset($_POST['download_pdf'])) {
    try {
        if (!$pdf_available) {
            error_log('Intento de generar PDF pero no hay un motor de PDF disponible (Dompdf/TCPDF).');
            throw new Exception('La generación de PDF no está disponible en este servidor.');
        }

        // Generar y enviar la descarga (generate_pdf.php implementa streamOrderPdfDownload usando Dompdf)
        streamOrderPdfDownload($order, $orderItems);
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
                        <span><?= htmlspecialchars(defined('SITE_EMAIL') ? constant('SITE_EMAIL') : 'no-reply@ejemplo.com') ?></span>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars(defined('SITE_CONTACT') ? constant('SITE_CONTACT') : 'Contacto') ?></span>
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