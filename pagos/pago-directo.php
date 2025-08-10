<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Verificar si es una compra directa
$directPurchase = isset($_POST['direct_purchase']) && $_POST['direct_purchase'] == '1';

// Obtener datos del producto si es compra directa
if ($directPurchase) {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variantId = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    try {
        // Obtener información del producto
        $productStmt = $conn->prepare("
            SELECT p.*, pi.image_path as primary_image, pv.price, pv.sku, 
                   c.name as category_name, co.name as color_name, s.name as size_name
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.id = ?
            LEFT JOIN colors co ON pv.color_id = co.id
            LEFT JOIN sizes s ON pv.size_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND p.is_active = 1 AND pv.is_active = 1
            LIMIT 1
        ");
        $productStmt->execute([$variantId, $productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Producto no encontrado o no disponible");
        }
        
        // Calcular total
        $subtotal = $product['price'] * $quantity;
        $total = $subtotal;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/producto/verproducto.php?slug=" . $product['slug']);
        exit();
    }
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para realizar un pago contra entrega";
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Procesar el formulario de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    try {
        $conn->beginTransaction();
        
        $userId = $_SESSION['user_id'];
        $paymentMethod = $_POST['payment_method'];
        
        // Validaciones específicas para pago contra entrega
        if ($paymentMethod === 'contra_entrega') {
            if (empty($_POST['shipping_address']) || empty($_POST['shipping_city'])) {
                throw new Exception("Debes proporcionar una dirección y ciudad de entrega");
            }
            
            // Verificar que sea Medellín
            $cityStmt = $conn->prepare("SELECT id FROM delivery_cities WHERE city_name = ? AND is_active = 1");
            $cityStmt->execute(['Medellín']);
            $validCity = $cityStmt->fetch();
            
            if (!$validCity || $_POST['shipping_city'] !== 'Medellín') {
                throw new Exception("El pago contra entrega solo está disponible para Medellín");
            }
        }
        
        // Crear el pedido
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        if ($directPurchase) {
            $orderStmt = $conn->prepare("
                INSERT INTO orders (
                    order_number, user_id, status, subtotal, total, 
                    payment_method, payment_status, shipping_address, shipping_city, delivery_notes, created_at, updated_at
                ) VALUES (?, ?, 'pending', ?, ?, ?, 'pending', ?, ?, ?, NOW(), NOW())
            ");
            $orderStmt->execute([
                $orderNumber,
                $userId,
                $subtotal,
                $total,
                $paymentMethod,
                $_POST['shipping_address'] ?? null,
                $_POST['shipping_city'] ?? null,
                $_POST['delivery_notes'] ?? null
            ]);
            $orderId = $conn->lastInsertId();
            
            // Añadir item al pedido
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, product_name, variant_name, 
                    price, quantity, total, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $variantName = '';
            if ($product['color_name']) $variantName .= $product['color_name'];
            if ($product['size_name']) $variantName .= ($variantName ? ', ' : '') . $product['size_name'];
            
            $itemStmt->execute([
                $orderId,
                $productId,
                $variantId,
                $product['name'],
                $variantName,
                $product['price'],
                $quantity,
                $subtotal
            ]);
        }
        
        // Procesar el archivo del comprobante si es transferencia
        $paymentProofPath = null;
        if ($paymentMethod === 'transferencia' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $fileType = mime_content_type($_FILES['payment_proof']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Formato de archivo no permitido. Solo se aceptan JPG, PNG o PDF");
            }
            
            // Validar tamaño (máx 2MB)
            if ($_FILES['payment_proof']['size'] > 2097152) {
                throw new Exception("El archivo es demasiado grande. Máximo 2MB permitidos");
            }
            
            // Crear directorio si no existe
            $uploadDir = __DIR__ . '/../uploads/payment_proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Verificar permisos de escritura
            if (!is_writable($uploadDir)) {
                throw new Exception("No se pueden guardar comprobantes en este momento. Intente más tarde.");
            }
            
            // Generar nombre único para el archivo
            $filename = uniqid() . '_' . basename($_FILES['payment_proof']['name']);
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
                $paymentProofPath = 'uploads/payment_proofs/' . $filename;
            } else {
                throw new Exception("Error al subir el comprobante. Intente nuevamente.");
            }
        }
        
        // Crear transacción de pago
        $transactionStmt = $conn->prepare("
            INSERT INTO payment_transactions (
                order_id, user_id, amount, payment_method, status, 
                reference_number, bank_name, account_number, account_type,
                account_holder, payment_proof, notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $transactionNotes = ($paymentMethod === 'contra_entrega') ? 
            "Pago contra entrega en Medellín. " . ($_POST['delivery_notes'] ?? '') : 
            ($_POST['payment_notes'] ?? '');
        
        $transactionStmt->execute([
            $orderId,
            $userId,
            $total,
            $paymentMethod,
            'pending', // status
            $_POST['reference_number'] ?? null,
            $_POST['bank_name'] ?? null,
            $_POST['account_number'] ?? null,
            $_POST['account_type'] ?? null,
            $_POST['account_holder'] ?? null,
            $paymentProofPath,
            $transactionNotes
        ]);
        
        $conn->commit();
        
        // Redirigir a confirmación
        $_SESSION['order_success'] = $orderNumber;
        header("Location: " . BASE_URL . "/pagos/confirmacion.php?order=" . $orderNumber);
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al procesar el pago: " . $e->getMessage();
        error_log("Error en pago: " . $e->getMessage());
        
        // Recargar los datos del producto si es compra directa para mostrar el formulario nuevamente
        if ($directPurchase) {
            try {
                $productStmt = $conn->prepare("
                    SELECT p.*, pi.image_path as primary_image, pv.price, pv.sku, 
                           c.name as category_name, co.name as color_name, s.name as size_name
                    FROM products p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.id = ?
                    LEFT JOIN colors co ON pv.color_id = co.id
                    LEFT JOIN sizes s ON pv.size_id = s.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = ? AND p.is_active = 1 AND pv.is_active = 1
                    LIMIT 1
                ");
                $productStmt->execute([$variantId, $productId]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $ex) {
                error_log("Error al recuperar producto: " . $ex->getMessage());
            }
        }
        
        header("Location: " . BASE_URL . "/pagos/pago-directo.php");
        exit();
    }
}

// Obtener lista de bancos para el formulario
try {
    $banks = $conn->query("SELECT bank_code, bank_name FROM colombian_banks WHERE is_active = 1 ORDER BY bank_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banks = [];
    error_log("Error al obtener bancos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Pago - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/payment.css">
</head>
<body>
    <?php require_once __DIR__ . '/../layouts/header2.php'; ?>

    <div class="payment-container">
        <div class="payment-steps">
            <div class="step <?= $directPurchase ? 'completed' : '' ?>">
                <span>1</span>
                <p>Producto</p>
            </div>
            <div class="step active">
                <span>2</span>
                <p>Pago</p>
            </div>
            <div class="step">
                <span>3</span>
                <p>Confirmación</p>
            </div>
        </div>

        <div class="payment-content">
            <div class="order-summary">
                <h3>Resumen del Pedido</h3>
                
                <?php if ($directPurchase): ?>
                    <div class="product-item">
                        <div class="product-image">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['primary_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-details">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            <p><?= htmlspecialchars($product['category_name']) ?></p>
                            <?php if ($product['color_name'] || $product['size_name']): ?>
                                <p class="variant"><?= htmlspecialchars($product['color_name']) ?><?= ($product['color_name'] && $product['size_name']) ? ', ' : '' ?><?= htmlspecialchars($product['size_name']) ?></p>
                            <?php endif; ?>
                            <p>Cantidad: <?= $quantity ?></p>
                        </div>
                        <div class="product-price">
                            $<?= number_format($product['price'], 0, ',', '.') ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
                    </div>
                    <div class="total-row">
                        <span>Envío:</span>
                        <span>$0</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>$<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="payment-methods">
                <h3>Métodos de Pago</h3>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert error">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data" id="payment-form">
                    <input type="hidden" name="direct_purchase" value="<?= $directPurchase ? '1' : '0' ?>">
                    <?php if ($directPurchase): ?>
                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                        <input type="hidden" name="variant_id" value="<?= $variantId ?>">
                        <input type="hidden" name="quantity" value="<?= $quantity ?>">
                    <?php endif; ?>

                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" name="payment_method" id="transferencia" value="transferencia" checked>
                            <label for="transferencia">
                                <i class="fas fa-university"></i>
                                <span>Transferencia Bancaria</span>
                            </label>
                        </div>

                        <div class="payment-option">
                            <input type="radio" name="payment_method" id="contra_entrega" value="contra_entrega">
                            <label for="contra_entrega">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Pago Contra Entrega</span>
                                <span class="badge">Solo Medellín</span>
                            </label>
                        </div>
                    </div>

                    <div class="payment-details" id="transferencia-details">
                        <h4>Datos para Transferencia Bancaria</h4>
                        <p>Por favor realiza la transferencia a nuestra cuenta y adjunta el comprobante.</p>
                        
                        <div class="bank-info">
                            <p><strong>Banco:</strong> Bancolombia</p>
                            <p><strong>Tipo de Cuenta:</strong> Cuenta de Ahorros</p>
                            <p><strong>Número:</strong> 123-456789-00</p>
                            <p><strong>Titular:</strong> Angelow Ropa Infantil</p>
                            <p><strong>Valor a transferir:</strong> $<?= number_format($total, 0, ',', '.') ?></p>
                        </div>

                        <div class="form-group">
                            <label for="bank_name">Banco desde el que transfiere:</label>
                            <select name="bank_name" id="bank_name" class="form-control" required>
                                <option value="">Seleccione su banco</option>
                                <?php foreach ($banks as $bank): ?>
                                    <option value="<?= htmlspecialchars($bank['bank_name']) ?>"><?= htmlspecialchars($bank['bank_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reference_number">Número de Referencia/Transacción:</label>
                            <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Ej: 123456789" required>
                        </div>

                        <div class="form-group">
                            <label for="account_type">Tipo de Cuenta:</label>
                            <select name="account_type" id="account_type" class="form-control" required>
                                <option value="">Seleccione tipo de cuenta</option>
                                <option value="ahorros">Ahorros</option>
                                <option value="corriente">Corriente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="account_number">Número de Cuenta:</label>
                            <input type="text" name="account_number" id="account_number" class="form-control" placeholder="Número de la cuenta que realizó la transferencia" required>
                        </div>

                        <div class="form-group">
                            <label for="account_holder">Titular de la Cuenta:</label>
                            <input type="text" name="account_holder" id="account_holder" class="form-control" placeholder="Nombre del titular de la cuenta" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_proof">Comprobante de Transferencia:</label>
                            <input type="file" name="payment_proof" id="payment_proof" class="form-control" accept="image/*,.pdf" required>
                            <small>Formatos aceptados: JPG, PNG, PDF (Máx. 2MB)</small>
                        </div>

                        <div class="form-group">
                            <label for="payment_notes">Notas adicionales:</label>
                            <textarea name="payment_notes" id="payment_notes" class="form-control" rows="3" placeholder="Alguna información adicional sobre el pago"></textarea>
                        </div>
                    </div>

                    <div class="payment-details" id="contra_entrega-details" style="display: none;">
                        <h4>Pago Contra Entrega - Solo Medellín</h4>
                        <p>Paga en efectivo cuando recibas tu pedido. Asegúrate de tener el dinero exacto.</p>
                        
                        <div class="form-group">
                            <label for="shipping_city">Ciudad:</label>
                            <input type="text" name="shipping_city" id="shipping_city" class="form-control" value="Medellín" readonly required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_address">Dirección Completa:</label>
                            <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3" required placeholder="Barrio, calle, carrera, número, apartamento, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_notes">Instrucciones adicionales:</label>
                            <textarea name="delivery_notes" id="delivery_notes" class="form-control" rows="2" placeholder="Ej: Piso, apartamento, referencias, horario de entrega"></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= $directPurchase ? BASE_URL . '/producto/verproducto.php?slug=' . $product['slug'] : BASE_URL . '/carrito' ?>" class="payment-back-btn">
                            <i class="fas fa-arrow-left"></i> Volver atrás
                        </a>
                        <button type="submit" class="payment-submit-btn">
                            <i class="fas fa-check-circle"></i> Confirmar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const paymentDetails = {
                'transferencia': document.getElementById('transferencia-details'),
                'contra_entrega': document.getElementById('contra_entrega-details')
            };

            function updatePaymentDetails() {
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
                
                // Ocultar todos los detalles primero
                Object.values(paymentDetails).forEach(detail => {
                    detail.style.display = 'none';
                    // Remover required de todos los campos
                    detail.querySelectorAll('[required]').forEach(field => {
                        field.required = false;
                    });
                });
                
                // Mostrar solo el seleccionado y hacer required sus campos
                if (paymentDetails[selectedMethod]) {
                    paymentDetails[selectedMethod].style.display = 'block';
                    paymentDetails[selectedMethod].querySelectorAll('[required]').forEach(field => {
                        field.required = true;
                    });
                }
            }

            paymentMethods.forEach(method => {
                method.addEventListener('change', updatePaymentDetails);
            });

            updatePaymentDetails();
            
            // Validar ciudad antes de enviar
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                const method = document.querySelector('input[name="payment_method"]:checked').value;
                if (method === 'contra_entrega') {
                    const city = document.getElementById('shipping_city').value;
                    if (city.toLowerCase() !== 'medellín') {
                        e.preventDefault();
                        alert('El pago contra entrega solo está disponible para Medellín');
                        return false;
                    }
                }
                
                // Validar archivo para transferencia
                if (method === 'transferencia') {
                    const fileInput = document.getElementById('payment_proof');
                    if (fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Debes adjuntar el comprobante de transferencia');
                        return false;
                    }
                }
                return true;
            });
        });
    </script>
</body>
</html>