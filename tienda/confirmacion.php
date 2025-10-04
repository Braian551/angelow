<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay datos de checkout
if (!isset($_SESSION['checkout_data'])) {
    header("Location: " . BASE_URL . "/tienda/cart.php");
    exit();
}

$pageTitle = "Confirmación de Pedido";
$currentPage = 'checkout';

// Aquí procesarías la orden real en tu base de datos
// Por ahora solo mostramos una confirmación

$checkoutData = $_SESSION['checkout_data'];
unset($_SESSION['checkout_data']); // Limpiar datos de sesión

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/headerproducts.php'; ?>

    <main class="container checkout-container">
        <div class="confirmation-wrapper">
            <div class="confirmation-card">
                <div class="confirmation-header">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>¡Pedido Confirmado!</h1>
                    <p class="confirmation-subtitle">Tu pedido ha sido procesado exitosamente</p>
                </div>

                <div class="confirmation-details">
                    <div class="detail-group">
                        <h3><i class="fas fa-receipt"></i> Resumen del Pedido</h3>
                        <div class="detail-item">
                            <span>Número de Pedido:</span>
                            <strong>#<?= rand(10000, 99999) ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Fecha:</span>
                            <span><?= date('d/m/Y H:i') ?></span>
                        </div>
                        <div class="detail-item">
                            <span>Total:</span>
                            <strong>$<?= number_format($checkoutData['total'], 0, ',', '.') ?></strong>
                        </div>
                    </div>

                    <div class="detail-group">
                        <h3><i class="fas fa-truck"></i> Información de Envío</h3>
                        <div class="detail-item">
                            <span>Método de Envío:</span>
                            <span>Estándar</span>
                        </div>
                        <div class="detail-item">
                            <span>Tiempo Estimado:</span>
                            <span>3-5 días hábiles</span>
                        </div>
                    </div>

                    <div class="detail-group">
                        <h3><i class="fas fa-credit-card"></i> Información de Pago</h3>
                        <div class="detail-item">
                            <span>Método:</span>
                            <span><?= ucfirst(str_replace('_', ' ', $checkoutData['payment_method'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span>Estado:</span>
                            <span class="status-pending">Pendiente</span>
                        </div>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="<?= BASE_URL ?>/tienda/tienda.php" class="btn btn-outline">
                        <i class="fas fa-shopping-bag"></i> Seguir Comprando
                    </a>
                    <a href="<?= BASE_URL ?>/users/orders.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Ver Mis Pedidos
                    </a>
                </div>

                <div class="confirmation-footer">
                    <p><i class="fas fa-envelope"></i> Hemos enviado un correo de confirmación a tu email</p>
                    <p><i class="fas fa-headset"></i> ¿Necesitas ayuda? <a href="<?= BASE_URL ?>/contacto.php">Contáctanos</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>