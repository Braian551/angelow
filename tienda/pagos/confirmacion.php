<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/helpers/shipping_helpers.php';

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
    header("Location: " . BASE_URL . "/tienda/pagos/cart.php");
    exit();
}

$order_number = $_SESSION['last_order'];
$shippingMethodName = '';
$shippingMethodDesc = '';
$isStorePickup = false;
$storeAddress = getStorePickupAddress();
$storeCoords = getStorePickupCoordinates();
$customerLat = null;
$customerLng = null;
$routeLink = null;

// Verificar si la columna shipping_method_id existe (migraciones anteriores)
$hasShippingMethodColumn = false;
try {
    $colStmt = $conn->prepare("SHOW COLUMNS FROM `orders` LIKE 'shipping_method_id'");
    $colStmt->execute();
    $hasShippingMethodColumn = (bool)$colStmt->fetch();
} catch (Exception $e) {
    $hasShippingMethodColumn = false;
}

// Obtener información completa de la orden
try {
    // Construir select y join condicional para shipping method
    $shippingSelect = $hasShippingMethodColumn ? ", sm.name AS shipping_method_name, sm.description AS shipping_method_description, sm.delivery_time AS shipping_delivery_time" : '';
    $shippingJoin = $hasShippingMethodColumn ? "LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id" : '';

    $orderQuery = "
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
               pt.reference_number, pt.payment_proof, pt.created_at as payment_date" . $shippingSelect . ",
               ua.gps_latitude, ua.gps_longitude, ua.recipient_name AS address_recipient,
               ua.recipient_phone AS address_phone, ua.alias AS address_alias
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_transactions pt ON o.id = pt.order_id
        " . $shippingJoin . "
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
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
    
    // Obtener items de la orden con rutas absolutas de imágenes
    $itemsQuery = "
        SELECT oi.*, p.slug as product_slug,
               CONCAT('" . BASE_URL . "/', COALESCE(vi.image_path, pi.image_path)) as primary_image
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
    $shippingMethodName = $order['shipping_method_name'] ?? 'Envío a domicilio';
    $shippingMethodDesc = $order['shipping_method_description'] ?? '';
    $isStorePickup = isStorePickupMethod([
        'name' => $shippingMethodName,
        'description' => $shippingMethodDesc
    ]);
    $customerLat = $order['gps_latitude'] ?? null;
    $customerLng = $order['gps_longitude'] ?? null;
    $routeLink = $shipping_address ? buildStoreRouteLink($shipping_address) : null;
    if ($isStorePickup) {
        $shipping_address = $shipping_address ?: $storeAddress;
        if (!$routeLink && $storeAddress) {
            $routeLink = buildStoreRouteLink($storeAddress);
        }
    }
    $trackingSteps = $isStorePickup
        ? [
            [
                'icon' => 'fas fa-shopping-cart',
                'title' => 'Pedido realizado',
                'subtitle' => date('d/m H:i', strtotime($order['created_at'])),
                'class' => 'completed'
            ],
            [
                'icon' => 'fas fa-money-bill-wave',
                'title' => 'Verificación de pago',
                'subtitle' => 'En proceso',
                'class' => 'active'
            ],
            [
                'icon' => 'fas fa-box',
                'title' => 'Preparando pedido',
                'subtitle' => 'Próximamente',
                'class' => ''
            ],
            [
                'icon' => 'fas fa-store',
                'title' => 'Listo para recoger',
                'subtitle' => 'Te avisaremos por correo',
                'class' => ''
            ],
            [
                'icon' => 'fas fa-box-open',
                'title' => 'Pedido entregado',
                'subtitle' => 'Pendiente de recogida',
                'class' => ''
            ],
        ]
        : [
            [
                'icon' => 'fas fa-shopping-cart',
                'title' => 'Pedido realizado',
                'subtitle' => date('d/m H:i', strtotime($order['created_at'])),
                'class' => 'completed'
            ],
            [
                'icon' => 'fas fa-money-bill-wave',
                'title' => 'Verificación de pago',
                'subtitle' => 'En proceso',
                'class' => 'active'
            ],
            [
                'icon' => 'fas fa-box',
                'title' => 'Preparando pedido',
                'subtitle' => 'Próximamente',
                'class' => ''
            ],
            [
                'icon' => 'fas fa-truck',
                'title' => 'En camino',
                'subtitle' => 'Pendiente',
                'class' => ''
            ],
            [
                'icon' => 'fas fa-home',
                'title' => 'Entregado',
                'subtitle' => 'Pendiente',
                'class' => ''
            ],
        ];
    
} catch (Exception $e) {
    error_log("Error al obtener información de la orden: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/pagos/cart.php");
    exit();
}

// Incluir el helper de correo (envío de confirmación)
require_once __DIR__ . '/../api/pay/send_confirmation.php';
// Incluir helper puro para generar PDF (no ejecutar endpoint que haga stream/exit al incluir)
require_once __DIR__ . '/../api/pay/pdf_helpers.php';

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

        // Ya no necesitamos modificar las rutas aquí ya que se obtienen completas desde la consulta SQL
        
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
    <?php if ($isStorePickup): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css">
    <?php endif; ?>
</head>

<body>
    <?php include __DIR__ . '/../../layouts/headerproducts.php'; ?>

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
                                <span class="label">Método de entrega</span>
                                <span class="value">
                                    <?= htmlspecialchars($shippingMethodName ?: 'Envío a domicilio') ?>
                                </span>
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
                                    <img src="<?= htmlspecialchars($item['primary_image'] ?? BASE_URL . '/images/default-product.jpg') ?>" 
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

                <!-- Información de envío -->
                <section class="confirmation-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shipping-fast"></i> Información de Envío</h2>
                    </div>

                    <div class="shipping-info">
                        <?php if ($isStorePickup): ?>
                            <div class="info-group">
                                <label>Tipo de entrega:</label>
                                <p>Recogida en tienda</p>
                            </div>
                            <div class="info-group">
                                <label>Tienda principal:</label>
                                <p><?= htmlspecialchars($storeAddress) ?></p>
                            </div>
                            <div class="info-group">
                                <label>Contacto registrado:</label>
                                <p>
                                    <?= htmlspecialchars($order['address_recipient'] ?? $order['user_name']) ?>
                                    <?php if (!empty($order['address_phone'])): ?>
                                        <br><span class="muted">Tel: <?= htmlspecialchars($order['address_phone']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="info-group">
                                <label>Indicaciones:</label>
                                <p>
                                    Te avisaremos por correo o notificaciones de la plataforma cuando el pedido esté listo.
                                    Presenta tu documento y el número de orden al recogerlo.
                                </p>
                            </div>
                            <?php if (!empty($shippingMethodDesc)): ?>
                                <div class="info-group">
                                    <label>Detalles del método:</label>
                                    <p><?= htmlspecialchars($shippingMethodDesc) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-group">
                                <label>Dirección de envío:</label>
                                <p><?= htmlspecialchars($shipping_address) ?></p>
                            </div>
                            <div class="info-group">
                                <label>Método de envío:</label>
                                <p>
                                    <?= htmlspecialchars($shippingMethodName ?: 'Envío a domicilio') ?>
                                    <?php if (!empty($shippingMethodDesc)): ?>
                                        <br><span class="muted"><?= htmlspecialchars($shippingMethodDesc) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if (!empty($order['reference_number'])): ?>
                                <div class="info-group">
                                    <label>Referencia de pago:</label>
                                    <p class="reference-number"><?= htmlspecialchars($order['reference_number']) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($order['reference_number']) && $isStorePickup): ?>
                            <div class="info-group">
                                <label>Referencia de pago:</label>
                                <p class="reference-number"><?= htmlspecialchars($order['reference_number']) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="info-group">
                            <label>Estado de pago:</label>
                            <p>Transferencia bancaria - Pendiente de verificación</p>
                        </div>
                    </div>
                </section>

                <?php if ($isStorePickup): ?>
                    <!-- Mapa de recogida: separado en su propia sección para no alargar el bloque de envío -->
                    <section class="confirmation-section pickup-section">
                        <div class="section-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Cómo llegar a la tienda</h2>
                        </div>
                        <div class="pickup-map-wrapper">
                            <div id="pickup-map" class="pickup-map"></div>
                            <div class="map-hint">
                                <?php if ($customerLat && $customerLng): ?>
                                    <p>Mostramos la ruta desde tu dirección guardada hasta la tienda.</p>
                                <?php else: ?>
                                    <p>Para trazar la ruta exacta agrega una dirección con GPS en tu perfil.</p>
                                <?php endif; ?>
                                <?php if ($routeLink): ?>
                                    <a href="<?= htmlspecialchars($routeLink) ?>" target="_blank" class="route-link">
                                        <i class="fas fa-location-arrow"></i> Abrir en Google Maps
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Información de seguimiento -->
                <section class="confirmation-section">
                    <div class="section-header">
                        <h2><i class="fas fa-map-marker-alt"></i> Progreso del Pedido</h2>
                    </div>
                    <div class="tracking-info">
                        <div class="tracking-steps">
                            <?php foreach ($trackingSteps as $step): ?>
                                <div class="tracking-step <?= htmlspecialchars($step['class'] ?? '') ?>">
                                    <div class="step-icon">
                                        <i class="<?= htmlspecialchars($step['icon']) ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <span class="step-title"><?= htmlspecialchars($step['title']) ?></span>
                                        <span class="step-date"><?= htmlspecialchars($step['subtitle']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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

    <?php include __DIR__ . '/../../layouts/footer.php'; ?>

    <?php if ($isStorePickup): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <?php endif; ?>

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

            <?php if ($isStorePickup): ?>
            const mapElement = document.getElementById('pickup-map');
            const storeLat = <?= isset($storeCoords['lat']) ? json_encode((float)$storeCoords['lat']) : 'null' ?>;
            const storeLng = <?= isset($storeCoords['lng']) ? json_encode((float)$storeCoords['lng']) : 'null' ?>;
            const customerLat = <?= $customerLat ? json_encode((float)$customerLat) : 'null' ?>;
            const customerLng = <?= $customerLng ? json_encode((float)$customerLng) : 'null' ?>;

                if (mapElement && storeLat && storeLng && typeof L !== 'undefined') {
                const map = L.map('pickup-map').setView([storeLat, storeLng], customerLat && customerLng ? 13 : 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                L.marker([storeLat, storeLng]).addTo(map)
                    .bindPopup('Tienda principal')
                    .openPopup();

                if (customerLat && customerLng && typeof L.Routing !== 'undefined') {
                    L.marker([customerLat, customerLng]).addTo(map)
                        .bindPopup('Tu dirección guardada');

                    L.Routing.control({
                        waypoints: [
                            L.latLng(customerLat, customerLng),
                            L.latLng(storeLat, storeLng)
                        ],
                        draggableWaypoints: false,
                        addWaypoints: false,
                        show: false,
                        lineOptions: {
                            styles: [{ color: '#ff6f3d', weight: 5 }]
                        }
                    }).addTo(map);
                } else {
                    // Si L no está disponible (CDN bloqueado o no cargó), mostraremos una pista y un enlace a Google Maps
                    const hint = document.querySelector('.map-hint');
                    if (hint) {
                        const fallback = document.createElement('div');
                        fallback.className = 'map-fallback';
                        fallback.style.marginTop = '8px';
                        fallback.innerHTML = '<p style="margin:0; color: #666;">No fue posible cargar el mapa interactivo. Puedes abrir la ruta en Google Maps:</p>' +
                            (<?= json_encode($routeLink ? htmlspecialchars($routeLink) : null) ?> ? '<a href="' + <?= json_encode($routeLink ? htmlspecialchars($routeLink) : null) ?> + '" target="_blank" class="route-link"><i class="fas fa-location-arrow"></i> Abrir en Google Maps</a>' : '');
                        hint.appendChild(fallback);
                    }
                }
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>