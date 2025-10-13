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

$pageTitle = "Pago - Paso 3: Confirmación y Pago";
$currentPage = 'payment';

$user_id = $_SESSION['user_id'];

// Verificar que existan datos de checkout en la sesión
if (!isset($_SESSION['checkout_data'])) {
    header("Location: " . BASE_URL . "/tienda/envio.php");
    exit();
}

$checkout_data = $_SESSION['checkout_data'];

// Obtener información de la cuenta bancaria configurada
try {
    $bankQuery = "SELECT bac.*, cb.bank_name 
                  FROM bank_account_config bac 
                  LEFT JOIN colombian_banks cb ON bac.bank_code = cb.bank_code 
                  WHERE bac.is_active = 1 
                  ORDER BY bac.created_at DESC 
                  LIMIT 1";
    $stmt = $conn->prepare($bankQuery);
    $stmt->execute();
    $bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bankAccount) {
        throw new Exception("No hay cuenta bancaria configurada para pagos");
    }
} catch (Exception $e) {
    error_log("Error al obtener cuenta bancaria: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/envio.php?error=bank_config");
    exit();
}

// Obtener información de la dirección seleccionada
try {
    $addressQuery = "SELECT * FROM user_addresses WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($addressQuery);
    $stmt->execute([$checkout_data['address_id'], $user_id]);
    $selectedAddress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$selectedAddress) {
        throw new Exception("Dirección no encontrada");
    }
} catch (Exception $e) {
    error_log("Error al obtener dirección: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/envio.php?error=address");
    exit();
}

// Obtener información del método de envío
try {
    $shippingQuery = "SELECT * FROM shipping_methods WHERE id = ?";
    $stmt = $conn->prepare($shippingQuery);
    $stmt->execute([$checkout_data['shipping_method_id']]);
    $selectedShipping = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$selectedShipping) {
        throw new Exception("Método de envío no encontrado");
    }
} catch (Exception $e) {
    error_log("Error al obtener método de envío: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/envio.php?error=shipping");
    exit();
}

// Obtener items del carrito para mostrar resumen
try {
    $cartQuery = "SELECT c.id FROM carts c WHERE c.user_id = :user_id ORDER BY c.created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cartQuery);
    $stmt->execute([':user_id' => $user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception("Carrito no encontrado");
    }

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
            (COALESCE(psv.price, p.price) * ci.quantity) as item_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_color_variants pcv ON ci.color_variant_id = pcv.id
        LEFT JOIN colors c ON pcv.color_id = c.id
        LEFT JOIN product_size_variants psv ON ci.size_variant_id = psv.id
        LEFT JOIN sizes s ON psv.size_id = s.id
        LEFT JOIN variant_images vi ON pcv.id = vi.color_variant_id AND vi.is_primary = 1
        WHERE ci.cart_id = :cart_id
        ORDER BY ci.created_at DESC
    ";

    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([':cart_id' => $cart['id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception("No hay items en el carrito");
    }

    // Calcular totales
    $cartSubtotal = 0;
    $itemCount = 0;
    foreach ($cartItems as $item) {
        $cartSubtotal += $item['item_total'];
        $itemCount += $item['quantity'];
    }
} catch (Exception $e) {
    error_log("Error al obtener items del carrito: " . $e->getMessage());
    header("Location: " . BASE_URL . "/tienda/cart.php");
    exit();
}

// Procesar el formulario de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validar archivo de comprobante
    $payment_proof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $file_type = $_FILES['payment_proof']['type'];
        $file_size = $_FILES['payment_proof']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "El archivo debe ser JPG, PNG o PDF";
        }
        
        if ($file_size > 5 * 1024 * 1024) { // 5MB
            $errors[] = "El archivo no puede ser mayor a 5MB";
        }
        
        if (empty($errors)) {
            $upload_dir = __DIR__ . '/../uploads/payment_proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $file_name = 'proof_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $file_path)) {
                $payment_proof = 'uploads/payment_proofs/' . $file_name;
            } else {
                // Logging detallado para diagnosticar fallos en move_uploaded_file
                error_log('Error al mover archivo de comprobante. tmp_name: ' . ($_FILES['payment_proof']['tmp_name'] ?? '') . ' dest: ' . $file_path . ' file_error: ' . ($_FILES['payment_proof']['error'] ?? '') . ' user_id: ' . $user_id);
                error_log('$_FILES payment_proof: ' . print_r($_FILES['payment_proof'], true));
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    $errors[] = "Error al subir el archivo: no se pudo mover el archivo al directorio de uploads. Revisa permisos y espacio en disco.";
                } else {
                    $errors[] = "Error al subir el archivo";
                }
            }
        }
    } else {
        $errors[] = "Debes subir el comprobante de pago";
    }
    
    // Validar número de referencia
    $reference_number = trim($_POST['reference_number'] ?? '');
    if (empty($reference_number)) {
        $errors[] = "Debes ingresar el número de referencia de la transferencia";
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Generar número de orden
            $order_number = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            
            // Crear la orden
            $orderQuery = "
                INSERT INTO orders (
                    order_number, user_id, status, subtotal, shipping_cost, 
                    total, payment_method, payment_status,
                    shipping_address_id, shipping_address, shipping_city
                ) VALUES (?, ?, 'pending', ?, ?, ?, 'transfer', 'pending', ?, ?, ?)
            ";
            
            $shipping_address = $selectedAddress['address'] . ', ' . $selectedAddress['neighborhood'];
            if ($selectedAddress['complement']) {
                $shipping_address .= ' - ' . $selectedAddress['complement'];
            }
            
            $stmt = $conn->prepare($orderQuery);
            $stmt->execute([
                $order_number,
                $user_id,
                $checkout_data['subtotal'],
                $checkout_data['shipping_cost'],
                $checkout_data['total'],
                $selectedAddress['id'], // Guardar el ID de la dirección seleccionada
                $shipping_address,
                'Medellín' // Asumiendo que todas las direcciones son en Medellín
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // Crear items de la orden
            $orderItemQuery = "
                INSERT INTO order_items (
                    order_id, product_id, color_variant_id, size_variant_id,
                    product_name, variant_name, price, quantity, total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            foreach ($cartItems as $item) {
                $variant_name = '';
                if ($item['color_name']) {
                    $variant_name .= 'Color: ' . $item['color_name'];
                }
                if ($item['size_name']) {
                    if ($variant_name) $variant_name .= ' - ';
                    $variant_name .= 'Talla: ' . $item['size_name'];
                }
                
                $stmt = $conn->prepare($orderItemQuery);
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['color_variant_id'],
                    $item['size_variant_id'],
                    $item['product_name'],
                    $variant_name,
                    $item['variant_price'] ?? $item['product_price'],
                    $item['quantity'],
                    $item['item_total']
                ]);
            }
            
            // Registrar la transacción de pago
            $transactionQuery = "
                INSERT INTO payment_transactions (
                    order_id, user_id, amount, reference_number, payment_proof, status
                ) VALUES (?, ?, ?, ?, ?, 'pending')
            ";
            
            $stmt = $conn->prepare($transactionQuery);
            $stmt->execute([
                $order_id,
                $user_id,
                $checkout_data['total'],
                $reference_number,
                $payment_proof
            ]);
            
            // Marcar descuento como usado si aplica
            if ($checkout_data['discount_id']) {
                $discountUpdate = "
                    UPDATE user_applied_discounts 
                    SET is_used = 1, used_at = NOW() 
                    WHERE user_id = ? AND discount_code_id = ? AND is_used = 0
                ";
                $stmt = $conn->prepare($discountUpdate);
                $stmt->execute([$user_id, $checkout_data['discount_id']]);
            }
            
            // Limpiar carrito
            $clearCartQuery = "DELETE FROM cart_items WHERE cart_id = ?";
            $stmt = $conn->prepare($clearCartQuery);
            $stmt->execute([$cart['id']]);
            
            $conn->commit();
            
            // Limpiar datos de sesión y redirigir a confirmación
            unset($_SESSION['checkout_data']);
            $_SESSION['last_order'] = $order_number;
            
            header("Location: " . BASE_URL . "/tienda/confirmacion.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            // Registrar traza completa y datos relevantes para diagnóstico
            error_log("Error al procesar pago: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString() . "\nPOST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true) . "\nCheckout data: " . print_r($checkout_data, true));

            // En modo DEBUG mostrar detalle en la interfaz para facilitar debugging local
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $errors[] = "Error al procesar el pago: " . $e->getMessage();
            } else {
                $errors[] = "Error al procesar el pago. Por favor intenta nuevamente.";
            }
        }
    }
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/tienda/pay.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificaciones/notification2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/headerproducts.php'; ?>

    <main class="container checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-credit-card"></i> Confirmar y Pagar</h1>
            <div class="checkout-steps">
                <div class="step">
                    <span>1</span>
                    <p>Carrito</p>
                </div>
                <div class="step">
                    <span>2</span>
                    <p>Envío</p>
                </div>
                <div class="step active">
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

        <form method="POST" class="checkout-form" id="payment-form" enctype="multipart/form-data">
            <div class="checkout-content">
                <div class="checkout-sections">
                    <!-- Sección de Información Bancaria -->
                    <section class="checkout-section">
                        <div class="section-header">
                            <h2><i class="fas fa-university"></i> Información para Transferencia</h2>
                        </div>

                        <div class="bank-info-card">
                            <div class="bank-header">
                                <div class="bank-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="bank-details">
                                    <h3><?= htmlspecialchars($bankAccount['bank_name']) ?></h3>
                                    <p>Cuenta de <?= $bankAccount['account_type'] === 'ahorros' ? 'Ahorros' : 'Corriente' ?></p>
                                </div>
                            </div>

                            <div class="account-details-grid">
                                <div class="account-detail">
                                    <span class="detail-label">Número de Cuenta:</span>
                                    <span class="detail-value"><?= htmlspecialchars($bankAccount['account_number']) ?></span>
                                </div>
                                <div class="account-detail">
                                    <span class="detail-label">Titular:</span>
                                    <span class="detail-value"><?= htmlspecialchars($bankAccount['account_holder']) ?></span>
                                </div>
                                <div class="account-detail">
                                    <span class="detail-label">Tipo de Documento:</span>
                                    <span class="detail-value">
                                        <?= $bankAccount['identification_type'] === 'cc' ? 'Cédula' : 
                                           ($bankAccount['identification_type'] === 'ce' ? 'Cédula Extranjería' : 'NIT') ?>
                                    </span>
                                </div>
                                <div class="account-detail">
                                    <span class="detail-label">Número de Documento:</span>
                                    <span class="detail-value"><?= htmlspecialchars($bankAccount['identification_number']) ?></span>
                                </div>
                                <?php if ($bankAccount['email']): ?>
                                <div class="account-detail">
                                    <span class="detail-label">Email de Contacto:</span>
                                    <span class="detail-value"><?= htmlspecialchars($bankAccount['email']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($bankAccount['phone']): ?>
                                <div class="account-detail">
                                    <span class="detail-label">Teléfono:</span>
                                    <span class="detail-value"><?= htmlspecialchars($bankAccount['phone']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="payment-instructions">
                                <h4>Instrucciones para el pago:</h4>
                                <ol>
                                    <li>Realiza la transferencia por el monto total de <strong>$<?= number_format($checkout_data['total'], 0, ',', '.') ?></strong></li>
                                    <li>Guarda el comprobante de la transferencia (captura de pantalla o PDF)</li>
                                    <li>Ingresa el número de referencia de la transferencia</li>
                                    <li>Sube el comprobante en el formulario a continuación</li>
                                    <li>Confirma tu pedido</li>
                                </ol>
                                <div class="important-note">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Tu pedido será procesado una vez verifiquemos el pago. Recibirás una confirmación por email.</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Sección de Comprobante de Pago -->
                    <section class="checkout-section">
                        <div class="section-header">
                            <h2><i class="fas fa-file-upload"></i> Comprobante de Pago</h2>
                        </div>

                        <div class="payment-proof-section">
                            <div class="form-group">
                                <label for="reference_number" class="form-label">
                                    Número de Referencia de la Transferencia *
                                </label>
                                <input type="text" 
                                       id="reference_number" 
                                       name="reference_number" 
                                       class="form-control"
                                       placeholder="Ingresa el número de referencia proporcionado por tu banco"
                                       value="<?= htmlspecialchars($_POST['reference_number'] ?? '') ?>"
                                       required>
                                <small class="form-text">Este número lo encuentras en el comprobante de transferencia</small>
                            </div>

                            <div class="form-group">
                                <label for="payment_proof" class="form-label">
                                    Comprobante de Pago (JPG, PNG o PDF) *
                                </label>
                                <div class="file-upload-area" id="file-upload-area">
                                    <div class="file-upload-content">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <h4>Arrastra tu comprobante aquí o haz clic para seleccionar</h4>
                                        <p>Formatos aceptados: JPG, PNG, PDF (Máx. 5MB)</p>
                                        <button type="button" class="btn btn-outline btn-sm" id="browse-files">
                                            <i class="fas fa-folder-open"></i> Seleccionar Archivo
                                        </button>
                                    </div>
                                    <input type="file" 
                                           id="payment_proof" 
                                           name="payment_proof" 
                                           class="file-input"
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           required>
                                    <div class="file-preview" id="file-preview"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Sección de Resumen de Pedido -->
                    <section class="checkout-section">
                        <div class="section-header">
                            <h2><i class="fas fa-shopping-bag"></i> Resumen de tu Pedido</h2>
                        </div>

                        <div class="order-summary-details">
                            <!-- Dirección de Envío -->
                            <div class="summary-block">
                                <h4><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h4>
                                <div class="address-summary">
                                    <p><strong><?= htmlspecialchars($selectedAddress['recipient_name']) ?></strong> (<?= htmlspecialchars($selectedAddress['recipient_phone']) ?>)</p>
                                    <p><?= htmlspecialchars($selectedAddress['address']) ?></p>
                                    <?php if ($selectedAddress['complement']): ?>
                                        <p><?= htmlspecialchars($selectedAddress['complement']) ?></p>
                                    <?php endif; ?>
                                    <p><?= htmlspecialchars($selectedAddress['neighborhood']) ?></p>
                                    <?php if ($selectedAddress['delivery_instructions']): ?>
                                        <p class="delivery-notes">
                                            <i class="fas fa-info-circle"></i>
                                            <?= htmlspecialchars($selectedAddress['delivery_instructions']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Método de Envío -->
                            <div class="summary-block">
                                <h4><i class="fas fa-truck"></i> Método de Envío</h4>
                                <div class="shipping-summary">
                                    <p><strong><?= htmlspecialchars($selectedShipping['name']) ?></strong></p>
                                    <p><?= htmlspecialchars($selectedShipping['description']) ?></p>
                                    <p class="shipping-time">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars($selectedShipping['delivery_time']) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Productos -->
                            <div class="summary-block">
                                <h4><i class="fas fa-box"></i> Productos (<?= $itemCount ?>)</h4>
                                <div class="products-summary">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="product-summary-item">
                                            <div class="product-image">
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['primary_image'] ?? 'assets/images/placeholder-product.jpg') ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            </div>
                                            <div class="product-details">
                                                <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                                <?php if ($item['color_name'] || $item['size_name']): ?>
                                                    <div class="product-variants">
                                                        <?php if ($item['color_name']): ?>
                                                            <span>Color: <?= htmlspecialchars($item['color_name']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($item['size_name']): ?>
                                                            <span>Talla: <?= htmlspecialchars($item['size_name']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="product-quantity-price">
                                                    <span><?= $item['quantity'] ?> x $<?= number_format($item['variant_price'] ?? $item['product_price'], 0, ',', '.') ?></span>
                                                    <strong>$<?= number_format($item['item_total'], 0, ',', '.') ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Resumen del Pago -->
                <div class="checkout-summary">
                    <div class="summary-card">
                        <h3>Resumen del Pago</h3>

                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>$<?= number_format($checkout_data['subtotal'], 0, ',', '.') ?></span>
                            </div>

                            <div class="summary-row">
                                <span>Envío</span>
                                <span>$<?= number_format($checkout_data['shipping_cost'], 0, ',', '.') ?></span>
                            </div>

                            <?php if ($checkout_data['discount_amount'] > 0): ?>
                                <div class="summary-row discount-row">
                                    <span>Descuento (<?= htmlspecialchars($checkout_data['discount_code']) ?>)</span>
                                    <span class="discount-text">-$<?= number_format($checkout_data['discount_amount'], 0, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="summary-row total">
                                <span>Total a Pagar</span>
                                <span class="total-amount">$<?= number_format($checkout_data['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <!-- Términos y Condiciones -->
                        <div class="terms-section">
                            <div class="form-check">
                                <input type="checkbox" id="accept_terms" name="accept_terms" class="form-check-input" required>
                                <label for="accept_terms" class="form-check-label">
                                    Acepto los <a href="<?= BASE_URL ?>/terminos.php" target="_blank">términos y condiciones</a> 
                                    y la <a href="<?= BASE_URL ?>/privacidad.php" target="_blank">política de privacidad</a>
                                </label>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="checkout-actions">
                            <a href="<?= BASE_URL ?>/tienda/envio.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Volver al Envío
                            </a>
                            <button type="submit" class="btn btn-primary btn-confirm" id="confirm-payment">
                                <i class="fas fa-check-circle"></i> Confirmar Pedido
                            </button>
                        </div>

                        <div class="security-notice">
                            <i class="fas fa-shield-alt"></i>
                            <span>Tu información de pago está protegida</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('payment_proof');
            const fileUploadArea = document.getElementById('file-upload-area');
            const filePreview = document.getElementById('file-preview');
            const browseFilesBtn = document.getElementById('browse-files');
            const paymentForm = document.getElementById('payment-form');
            const confirmBtn = document.getElementById('confirm-payment');

            // Manejar la subida de archivos
            browseFilesBtn.addEventListener('click', function() {
                fileInput.click();
            });

            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', function() {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelection(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    handleFileSelection(this.files[0]);
                }
            });

            function handleFileSelection(file) {
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido. Solo se aceptan JPG, PNG o PDF.');
                    return;
                }

                // Validar tamaño (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. Máximo 5MB.');
                    return;
                }

                // Mostrar preview
                filePreview.innerHTML = '';
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('file-preview-image');
                        filePreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-file-pdf file-preview-icon';
                    filePreview.appendChild(icon);
                }

                const fileName = document.createElement('div');
                fileName.className = 'file-preview-name';
                fileName.textContent = file.name;
                filePreview.appendChild(fileName);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'file-remove-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.onclick = function() {
                    fileInput.value = '';
                    filePreview.innerHTML = '';
                };
                filePreview.appendChild(removeBtn);

                filePreview.style.display = 'block';
            }

            // Validación del formulario
            paymentForm.addEventListener('submit', function(e) {
                const referenceNumber = document.getElementById('reference_number').value.trim();
                const paymentProof = document.getElementById('payment_proof').files.length;
                const acceptTerms = document.getElementById('accept_terms').checked;

                let errors = [];

                if (!referenceNumber) {
                    errors.push('Debes ingresar el número de referencia');
                }

                if (!paymentProof) {
                    errors.push('Debes subir el comprobante de pago');
                }

                if (!acceptTerms) {
                    errors.push('Debes aceptar los términos y condiciones');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Por favor completa los siguientes campos:\n\n' + errors.join('\n'));
                } else {
                    // Mostrar loading
                    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    confirmBtn.disabled = true;
                }
            });
        });
    </script>
</body>

</html>