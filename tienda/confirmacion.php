<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay datos de checkout
if (!isset($_SESSION['checkout_data'])) {
    header("Location: " . BASE_URL . "/tienda/envio.php");
    exit();
}

$checkoutData = $_SESSION['checkout_data'];
$user_id = $_SESSION['user_id'];

// Aquí procesarías el pago y crearías la orden en la base de datos
// Por ahora solo mostramos una confirmación simulada

$pageTitle = "Confirmación de Compra";
$currentPage = 'confirmacion';

// Limpiar datos de checkout después de mostrar la confirmación
unset($_SESSION['checkout_data']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/tienda/pago/confirmacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/headerproducts.php'; ?>

    <main class="container confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>¡Pedido Confirmado!</h1>
                <p class="confirmation-subtitle">Tu pedido ha sido procesado exitosamente</p>
            </div>

            <div class="confirmation-details">
                <div class="detail-section">
                    <h2><i class="fas fa-receipt"></i> Resumen del Pedido</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span>Número de Pedido:</span>
                            <strong>#<?= rand(1000, 9999) ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Fecha:</span>
                            <span><?= date('d/m/Y H:i') ?></span>
                        </div>
                        <div class="detail-item">
                            <span>Total:</span>
                            <strong>$<?= number_format($checkoutData['total'], 0, ',', '.') ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Método de Pago:</span>
                            <span><?= ucfirst(str_replace('_', ' ', $checkoutData['payment_method'])) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($checkoutData['discount_code'])): ?>
                <div class="detail-section">
                    <h2><i class="fas fa-tag"></i> Descuento Aplicado</h2>
                    <div class="discount-applied">
                        <span>Código: <strong><?= $checkoutData['discount_code'] ?></strong></span>
                        <span class="discount-amount">-$<?= number_format($checkoutData['discount_amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="next-steps">
                <h2><i class="fas fa-list-alt"></i> Próximos Pasos</h2>
                <div class="steps-timeline">
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="step-content">
                            <h3>Confirmación por Email</h3>
                            <p>Recibirás un email con los detalles de tu pedido</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="step-content">
                            <h3>Procesamiento</h3>
                            <p>Tu pedido será preparado para el envío</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="step-content">
                            <h3>Envío</h3>
                            <p>Recibirás actualizaciones sobre el estado de tu envío</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="confirmation-actions">
                <a href="<?= BASE_URL ?>/tienda/index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Seguir Comprando
                </a>
                <a href="<?= BASE_URL ?>/users/orders.php" class="btn btn-outline">
                    <i class="fas fa-history"></i> Ver Mis Pedidos
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>