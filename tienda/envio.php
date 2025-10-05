<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

$pageTitle = "Envío - Paso 2: Envío y Pago";
$currentPage = 'envio';

$user_id = $_SESSION['user_id'];

// Obtener el carrito activo
try {
    $cartQuery = "SELECT c.id FROM carts c WHERE c.user_id = :user_id ORDER BY c.created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute([':user_id' => $user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        header("Location: " . BASE_URL . "/tienda/cart.php");
        exit();
    }

    // Obtener los items del carrito
    $itemsQuery = "
        SELECT 
            ci.id as item_id,
            ci.quantity,
            p.id as product_id,
            p.name as product_name,
            p.slug as product_slug,
            p.price as product_price,
            COALESCE(vi.image_path, pi.image_path) as primary_image,
            c.name as color_name,
            s.name as size_name,
            pcv.id as color_variant_id,
            psv.id as size_variant_id,
            psv.price as variant_price,
            (COALESCE(psv.price, p.price) * ci.quantity) as item_total,
            psv.quantity as stock_available
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON ci.color_variant_id = pcv.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
        LEFT JOIN sizes s ON psv.size_id = s.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE ci.cart_id = :cart_id
        GROUP BY ci.id
    ";

    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([':cart_id' => $cart['id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        header("Location: " . BASE_URL . "/tienda/cart.php");
        exit();
    }

    // Calcular totales
    $cartSubtotal = 0;
    $itemCount = 0;
    foreach ($cartItems as $item) {
        $cartSubtotal += $item['item_total'];
        $itemCount += $item['quantity'];
    }
} catch (PDOException $e) {
    error_log("Error al obtener el carrito: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/cart.php");
    exit();
}

// Obtener direcciones del usuario
try {
    $addressQuery = "SELECT * FROM user_addresses WHERE user_id = :user_id AND is_active = 1 ORDER BY is_default DESC, created_at DESC";
    $stmt = $conn->prepare($addressQuery);
    $stmt->execute([':user_id' => $user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener direcciones: " . $e->getMessage());
    $addresses = [];
}

// Obtener métodos de envío
try {
    $shippingQuery = "SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY base_cost ASC";
    $stmt = $conn->prepare($shippingQuery);
    $stmt->execute();
    $shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener métodos de envío: " . $e->getMessage());
    $shippingMethods = [];
}

// Verificar si hay un descuento aplicado previamente
$appliedDiscount = null;
try {
    $discountQuery = "
        SELECT uad.*, dc.code, pdc.percentage, pdc.max_discount_amount 
        FROM user_applied_discounts uad
        JOIN discount_codes dc ON uad.discount_code_id = dc.id
        LEFT JOIN percentage_discounts pdc ON dc.id = pdc.discount_code_id
        WHERE uad.user_id = :user_id 
        AND uad.is_used = 0 
        AND uad.expires_at > NOW()
        ORDER BY uad.applied_at DESC 
        LIMIT 1
    ";
    $stmt = $conn->prepare($discountQuery);
    $stmt->execute([':user_id' => $user_id]);
    $appliedDiscount = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener descuento aplicado: " . $e->getMessage());
}

// Procesar el formulario de checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;

    // Validar datos del formulario
    $selected_address_id = $_POST['selected_address'] ?? '';
    $shipping_method_id = $_POST['shipping_method'] ?? '';
    $discount_code = trim($_POST['discount_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    // Validaciones
    if (empty($selected_address_id)) {
        $errors[] = "Debes seleccionar una dirección de envío";
    }

    if (empty($shipping_method_id)) {
        $errors[] = "Debes seleccionar un método de envío";
    }

    if (empty($payment_method)) {
        $errors[] = "Debes seleccionar un método de pago";
    }

    // Verificar dirección seleccionada
    if (!empty($selected_address_id)) {
        $addressCheck = $conn->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $addressCheck->execute([$selected_address_id, $user_id]);
        $selectedAddress = $addressCheck->fetch(PDO::FETCH_ASSOC);

        if (!$selectedAddress) {
            $errors[] = "La dirección seleccionada no es válida";
        }
    }

    // Verificar método de envío
    if (!empty($shipping_method_id)) {
        $shippingCheck = $conn->prepare("SELECT * FROM shipping_methods WHERE id = ? AND is_active = 1");
        $shippingCheck->execute([$shipping_method_id]);
        $selectedShipping = $shippingCheck->fetch(PDO::FETCH_ASSOC);

        if (!$selectedShipping) {
            $errors[] = "El método de envío seleccionado no es válido";
        }
    }

    // Procesar código de descuento si se proporcionó
    $discount_amount = 0;
    $discount_id = null;
    $discount_code_used = '';

    if (!empty($discount_code)) {
        try {
            $discountQuery = "
                SELECT dc.*, pd.percentage, pd.max_discount_amount 
                FROM discount_codes dc 
                LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id 
                WHERE dc.code = ? AND dc.is_active = 1 
                AND (dc.start_date IS NULL OR dc.start_date <= NOW()) 
                AND (dc.end_date IS NULL OR dc.end_date >= NOW())
                AND (dc.max_uses IS NULL OR dc.used_count < dc.max_uses)
            ";
            $stmt = $conn->prepare($discountQuery);
            $stmt->execute([$discount_code]);
            $discount = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($discount) {
                // Verificar si es de un solo uso y ya fue usado por este usuario
                if ($discount['is_single_use']) {
                    $usageCheck = $conn->prepare("SELECT id FROM discount_code_usage WHERE discount_code_id = ? AND user_id = ?");
                    $usageCheck->execute([$discount['id'], $user_id]);
                    if ($usageCheck->fetch()) {
                        $errors[] = "Este código de descuento ya ha sido utilizado";
                    }
                }

                // Calcular descuento
                if ($discount['discount_type_id'] == 1 && $discount['percentage']) { // Porcentaje
                    $discount_amount = ($cartSubtotal * $discount['percentage']) / 100;
                    if ($discount['max_discount_amount'] > 0 && $discount_amount > $discount['max_discount_amount']) {
                        $discount_amount = $discount['max_discount_amount'];
                    }
                    $discount_id = $discount['id'];
                    $discount_code_used = $discount_code;

                    // Guardar en tabla de descuentos aplicados
                    $expiresAt = $discount['end_date'] ?: date('Y-m-d H:i:s', strtotime('+30 days'));
                    $saveDiscount = $conn->prepare("
                        INSERT INTO user_applied_discounts (user_id, discount_code_id, discount_code, discount_amount, expires_at) 
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE discount_amount = VALUES(discount_amount), expires_at = VALUES(expires_at)
                    ");
                    $saveDiscount->execute([$user_id, $discount_id, $discount_code, $discount_amount, $expiresAt]);
                }
            } else {
                $errors[] = "El código de descuento no es válido o ha expirado";
            }
        } catch (PDOException $e) {
            error_log("Error al procesar código de descuento: " . $e->getMessage());
            $errors[] = "Error al procesar el código de descuento";
        }
    } elseif ($appliedDiscount) {
        // Usar el descuento previamente aplicado
        $discount_amount = $appliedDiscount['discount_amount'];
        $discount_id = $appliedDiscount['discount_code_id'];
        $discount_code_used = $appliedDiscount['discount_code'];
    }

    // Calcular costos finales
    $shipping_cost = $selectedShipping['base_cost'] ?? 0;
    $tax = 0; // Podrías calcular impuestos aquí
    $total = $cartSubtotal + $shipping_cost + $tax - $discount_amount;

    if (empty($errors)) {
        // Aquí procesarías el pago y crearías la orden
        // Por ahora solo redirigimos a una página de confirmación
        $_SESSION['checkout_data'] = [
            'address_id' => $selected_address_id,
            'shipping_method_id' => $shipping_method_id,
            'discount_code' => $discount_code_used,
            'discount_amount' => $discount_amount,
            'discount_id' => $discount_id,
            'payment_method' => $payment_method,
            'subtotal' => $cartSubtotal,
            'shipping_cost' => $shipping_cost,
            'tax' => $tax,
            'total' => $total,
            'cart_id' => $cart['id']
        ];

        // Marcar el descuento como usado
        if ($discount_id && $appliedDiscount) {
            $markUsed = $conn->prepare("UPDATE user_applied_discounts SET is_used = 1, used_at = NOW() WHERE id = ?");
            $markUsed->execute([$appliedDiscount['id']]);
        }

        header("Location: " . BASE_URL . "/tienda/confirmacion.php");
        exit();
    }
}

// Calcular costos iniciales (con descuento aplicado si existe)
$shipping_cost = 0;
$tax = 0;
$currentDiscount = $appliedDiscount ? $appliedDiscount['discount_amount'] : 0;
$total = $cartSubtotal - $currentDiscount;

// Si hay un descuento aplicado, establecer el código en el input
$currentDiscountCode = $appliedDiscount ? $appliedDiscount['discount_code'] : '';
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificaciones/notification2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/headerproducts.php'; ?>

    <main class="container checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-shipping-fast"></i> Finalizar Compra</h1>
            <div class="checkout-steps">
                <div class="step">
                    <span>1</span>
                    <p>Carrito</p>
                </div>
                <div class="step active">
                    <span>2</span>
                    <p>Envío</p>
                </div>
                <div class="step">
                    <span>3</span>
                    <p>Pago</p>
                </div>
                <div class="step">
                    <span>4</span>
                    <p>Confirmación</p>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="checkout-form" id="checkout-form">
            <div class="checkout-content">
                <div class="checkout-sections">
                    <!-- Sección de Dirección de Envío -->
                    <section class="checkout-section">
                        <div class="section-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
                            <a href="<?= BASE_URL ?>/users/addresses.php" class="btn btn-outline btn-sm">
                                <i class="fas fa-plus"></i> Gestionar Direcciones
                            </a>
                        </div>

                        <div class="address-selection">
                            <?php if (empty($addresses)): ?>
                                <div class="no-addresses">
                                    <div class="empty-state">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <h3>No tienes direcciones guardadas</h3>
                                        <p>Agrega una dirección para continuar con tu compra</p>
                                        <a href="<?= BASE_URL ?>/users/addresses.php?action=add" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Agregar Dirección
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="addresses-grid" id="addresses-container">
                                    <?php foreach ($addresses as $index => $address): ?>
                                        <div class="address-card <?= $address['is_default'] ? 'default-address' : '' ?> <?= $index === 0 ? 'selected-address' : '' ?>" data-address-id="<?= $address['id'] ?>">
                                            <input type="radio"
                                                name="selected_address"
                                                value="<?= $address['id'] ?>"
                                                id="address_<?= $address['id'] ?>"
                                                <?= $index === 0 ? 'checked' : '' ?>
                                                class="address-radio">
                                            <label for="address_<?= $address['id'] ?>" class="address-label">
                                                <div class="address-header">
                                                    <div class="address-icon">
                                                        <?php if ($address['address_type'] === 'casa'): ?>
                                                            <i class="fas fa-home"></i>
                                                        <?php elseif ($address['address_type'] === 'apartamento'): ?>
                                                            <i class="fas fa-building"></i>
                                                        <?php elseif ($address['address_type'] === 'oficina'): ?>
                                                            <i class="fas fa-briefcase"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-map-marker-alt"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="address-title">
                                                        <h3><?= htmlspecialchars($address['alias']) ?></h3>
                                                        <span class="address-type"><?= ucfirst($address['address_type']) ?></span>
                                                    </div>
                                                    <?php if ($address['is_default']): ?>
                                                        <div class="default-badge">
                                                            <i class="fas fa-star"></i> Principal
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="address-details">
                                                    <div class="detail-item">
                                                        <i class="fas fa-user"></i>
                                                        <p><?= htmlspecialchars($address['recipient_name']) ?> (<?= htmlspecialchars($address['recipient_phone']) ?>)</p>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <p><?= htmlspecialchars($address['address']) ?></p>
                                                    </div>
                                                    <?php if (!empty($address['complement'])): ?>
                                                        <div class="detail-item">
                                                            <i class="fas fa-plus-circle"></i>
                                                            <p><?= htmlspecialchars($address['complement']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="detail-item">
                                                        <i class="fas fa-city"></i>
                                                        <p><?= htmlspecialchars($address['neighborhood']) ?></p>
                                                    </div>
                                                    <?php if (!empty($address['delivery_instructions'])): ?>
                                                        <div class="detail-item">
                                                            <i class="fas fa-info-circle"></i>
                                                            <p><?= htmlspecialchars($address['delivery_instructions']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Sección de Método de Envío -->
                    <section class="checkout-section">
                        <div class="section-header">
                            <h2><i class="fas fa-truck"></i> Método de Envío</h2>
                        </div>

                        <div class="shipping-methods" id="shipping-container">
                            <?php if (empty($shippingMethods)): ?>
                                <div class="no-shipping">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <p>No hay métodos de envío disponibles en este momento</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($shippingMethods as $index => $method): ?>
                                    <div class="shipping-method-card <?= $index === 1 ? 'selected-shipping' : '' ?>" data-shipping-id="<?= $method['id'] ?>">
                                        <input type="radio"
                                            name="shipping_method"
                                            value="<?= $method['id'] ?>"
                                            id="shipping_<?= $method['id'] ?>"
                                            data-cost="<?= $method['base_cost'] ?>"
                                            class="shipping-radio"
                                            <?= $index === 1 ? 'checked' : '' ?>>
                                        <label for="shipping_<?= $method['id'] ?>" class="shipping-label">
                                            <div class="shipping-header">
                                                <div class="shipping-icon">
                                                    <i class="<?= $method['icon'] ?? 'fas fa-truck' ?>"></i>
                                                </div>
                                                <div class="shipping-info">
                                                    <h3><?= htmlspecialchars($method['name']) ?></h3>
                                                    <p class="shipping-description"><?= htmlspecialchars($method['description']) ?></p>
                                                    <p class="shipping-time">
                                                        <i class="fas fa-clock"></i>
                                                        <?= htmlspecialchars($method['delivery_time']) ?>
                                                    </p>
                                                </div>
                                                <div class="shipping-cost">
                                                    <?php if ($method['base_cost'] > 0): ?>
                                                        $<?= number_format($method['base_cost'], 0, ',', '.') ?>
                                                    <?php else: ?>
                                                        <span class="free-shipping">Gratis</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>


                </div>

                <!-- Resumen del Pedido -->
                <div class="checkout-summary">
                    <div class="summary-card">
                        <h3>Resumen del Pedido</h3>

                        <div class="order-items-preview">
                            <h4>Productos (<?= $itemCount ?>)</h4>
                            <div class="items-list">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['primary_image'] ?? 'assets/images/placeholder-product.jpg') ?>"
                                                alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        </div>
                                        <div class="item-details">
                                            <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                            <?php if ($item['color_name'] || $item['size_name']): ?>
                                                <div class="item-variants">
                                                    <?php if ($item['color_name']): ?>
                                                        <span>Color: <?= htmlspecialchars($item['color_name']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($item['size_name']): ?>
                                                        <span>Talla: <?= htmlspecialchars($item['size_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="item-quantity-price">
                                                <span class="quantity"><?= $item['quantity'] ?> x</span>
                                                <span class="price">$<?= number_format($item['variant_price'] ?? $item['product_price'], 0, ',', '.') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="subtotal-amount">$<?= number_format($cartSubtotal, 0, ',', '.') ?></span>
                            </div>

                            <div class="summary-row shipping-cost-row">
                                <span>Envío</span>
                                <span id="shipping-cost">$0</span>
                            </div>

                            <?php if ($currentDiscount > 0): ?>
                                <div class="summary-row discount-row" id="discount-row">
                                    <span>Descuento (<?= $currentDiscountCode ?>)</span>
                                    <span id="discount-amount" class="discount-text">-$<?= number_format($currentDiscount, 0, ',', '.') ?></span>
                                </div>
                            <?php else: ?>
                                <div class="summary-row discount-row" id="discount-row" style="display: none;">
                                    <span>Descuento</span>
                                    <span id="discount-amount" class="discount-text">-$0</span>
                                </div>
                            <?php endif; ?>

                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="total-amount">$<?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <!-- Código de Descuento -->
                        <div class="discount-section">
                            <h4>¿Tienes un código de descuento?</h4>
                            <div class="discount-form">
                                <input type="text"
                                    name="discount_code"
                                    id="discount_code"
                                    placeholder="Ingresa tu código"
                                    class="discount-input"
                                    value="<?= htmlspecialchars($currentDiscountCode) ?>">
                                <div class="discount-buttons-container">
                                    <button type="button" id="apply-discount" class="discount-btn">
                                        <?= $currentDiscount > 0 ? 'Cambiar' : 'Aplicar' ?>
                                    </button>
                                    <?php if ($currentDiscount > 0): ?>
                                        <button type="button" id="remove-discount" class="btn-danger discount-remove" title="Eliminar descuento">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="discount-message" class="discount-message <?= $currentDiscount > 0 ? 'success' : '' ?>">
                                <?php if ($currentDiscount > 0): ?>
                                    ¡Descuento aplicado! Ahorras $<?= number_format($currentDiscount, 0, ',', '.') ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Botón de Confirmación -->
                        <div class="checkout-actions">
                            <a href="<?= BASE_URL ?>/tienda/cart.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Volver al Carrito
                            </a>
                            <button type="submit" class="btn btn-primary btn-confirm">
                                <i class="fas fa-lock"></i> Proceder al Pago
                            </button>
                        </div>

                        <div class="security-notice">
                            <i class="fas fa-shield-alt"></i>
                            <span>Tu información está protegida con encriptación SSL</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shippingRadios = document.querySelectorAll('.shipping-radio');
            const discountBtn = document.getElementById('apply-discount');
            const removeDiscountBtn = document.getElementById('remove-discount');
            const discountInput = document.getElementById('discount_code');
            const discountMessage = document.getElementById('discount-message');
            const subtotalAmount = document.getElementById('subtotal-amount');
            const shippingCost = document.getElementById('shipping-cost');
            const discountRow = document.getElementById('discount-row');
            const discountAmount = document.getElementById('discount-amount');
            const totalAmount = document.getElementById('total-amount');
            const checkoutForm = document.getElementById('checkout-form');

            const subtotal = <?= $cartSubtotal ?>;
            let currentDiscount = <?= $currentDiscount ?>;
            let currentShipping = 0;

            // Selección visual de direcciones
            const addressCards = document.querySelectorAll('.address-card');
            addressCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remover selección de todas las tarjetas
                    addressCards.forEach(c => c.classList.remove('selected-address'));
                    // Agregar selección a la tarjeta clickeada
                    this.classList.add('selected-address');
                    // Marcar el radio button
                    const radio = this.querySelector('.address-radio');
                    if (radio) radio.checked = true;
                });
            });

            // Selección visual de métodos de envío
            const shippingCards = document.querySelectorAll('.shipping-method-card');
            shippingCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remover selección de todas las tarjetas
                    shippingCards.forEach(c => c.classList.remove('selected-shipping'));
                    // Agregar selección a la tarjeta clickeada
                    this.classList.add('selected-shipping');
                    // Marcar el radio button
                    const radio = this.querySelector('.shipping-radio');
                    if (radio) {
                        radio.checked = true;
                        currentShipping = parseFloat(radio.dataset.cost);
                        updateTotals();
                    }
                });
            });

            // Selección visual de métodos de pago
            const paymentCards = document.querySelectorAll('.payment-method-card');
            paymentCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remover selección de todas las tarjetas
                    paymentCards.forEach(c => c.classList.remove('selected-payment'));
                    // Agregar selección a la tarjeta clickeada
                    this.classList.add('selected-payment');
                    // Marcar el radio button
                    const radio = this.querySelector('.payment-radio');
                    if (radio) radio.checked = true;
                });
            });

            // Actualizar costos de envío
            shippingRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        currentShipping = parseFloat(this.dataset.cost);
                        updateTotals();
                    }
                });
            });

            // Aplicar descuento
            if (discountBtn) {
                discountBtn.addEventListener('click', function() {
                    const code = discountInput.value.trim();

                    if (!code) {
                        showDiscountMessage('Ingresa un código de descuento', 'error');
                        return;
                    }

                    applyDiscount(code);
                });
            }

            // Remover descuento
            if (removeDiscountBtn) {
                removeDiscountBtn.addEventListener('click', function() {
                    removeDiscount();
                });
            }

            function applyDiscount(code) {
                // Mostrar loading en el botón
                const applyBtn = document.getElementById('apply-discount');
                const originalText = applyBtn.textContent;
                applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                applyBtn.disabled = true;

                fetch('<?= BASE_URL ?>/tienda/apply_discount.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'discount_code=' + encodeURIComponent(code) + '&subtotal=' + subtotal + '&action=apply'
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Restaurar botón
                        applyBtn.innerHTML = originalText;
                        applyBtn.disabled = false;

                        if (data.success) {
                            currentDiscount = data.discount_amount;
                            showDiscountMessage(data.message, 'success');
                            updateTotals();
                            // Recargar la página para mostrar el estado persistente
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            currentDiscount = 0;
                            showDiscountMessage(data.message, 'error');
                            updateTotals();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Restaurar botón
                        applyBtn.innerHTML = originalText;
                        applyBtn.disabled = false;
                        showDiscountMessage('Error al aplicar el descuento', 'error');
                    });
            }

            function removeDiscount() {
                // Mostrar loading en el botón de eliminar
                const removeBtn = document.getElementById('remove-discount');
                const originalHTML = removeBtn.innerHTML;
                removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                removeBtn.disabled = true;

                fetch('<?= BASE_URL ?>/tienda/apply_discount.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=remove'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentDiscount = 0;
                            discountInput.value = '';
                            showDiscountMessage(data.message, 'success');
                            updateTotals();
                            // Recargar la página
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Restaurar botón si hay error
                            removeBtn.innerHTML = originalHTML;
                            removeBtn.disabled = false;
                            showDiscountMessage('Error al remover el descuento', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Restaurar botón
                        removeBtn.innerHTML = originalHTML;
                        removeBtn.disabled = false;
                        showDiscountMessage('Error al remover el descuento', 'error');
                    });
            }

            function showDiscountMessage(message, type) {
                discountMessage.textContent = message;
                discountMessage.className = 'discount-message ' + type;
                discountMessage.style.display = 'block';
            }

            function updateTotals() {
                // Actualizar display de costos
                shippingCost.textContent = currentShipping > 0 ? '$' + currentShipping.toLocaleString('es-CO') : 'Gratis';

                // Actualizar descuento
                if (currentDiscount > 0) {
                    discountRow.style.display = 'flex';
                    discountAmount.textContent = '-$' + currentDiscount.toLocaleString('es-CO');
                } else {
                    discountRow.style.display = 'none';
                }

                // Calcular y actualizar total
                const total = subtotal + currentShipping - currentDiscount;
                totalAmount.textContent = '$' + total.toLocaleString('es-CO');
            }

            // Inicializar totales
            updateTotals();

            // Validación del formulario antes de enviar
            checkoutForm.addEventListener('submit', function(e) {
                const selectedAddress = document.querySelector('input[name="selected_address"]:checked');
                const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');

                let errors = [];

                if (!selectedAddress) {
                    errors.push('Debes seleccionar una dirección de envío');
                }

                if (!selectedShipping) {
                    errors.push('Debes seleccionar un método de envío');
                }

                if (!selectedPayment) {
                    errors.push('Debes seleccionar un método de pago');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Por favor completa los siguientes campos:\n\n' + errors.join('\n'));
                }
            });
        });
    </script>
</body>

</html>