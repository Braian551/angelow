<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Carrito de Compras";
$currentPage = 'cart';

// Obtener el carrito del usuario
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

try {
    // Obtener el carrito activo
    $cartQuery = "SELECT c.id FROM carts c WHERE ";
    if ($user_id) {
        $cartQuery .= "c.user_id = :user_id";
        $params = [':user_id' => $user_id];
    } else {
        $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
        $params = [':session_id' => $session_id];
    }
    $cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";

    $stmt = $conn->prepare($cartQuery);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    $cartItems = [];
    $cartTotal = 0;
    $itemCount = 0;

    if ($cart) {
        // Obtener los items del carrito con información completa
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
    ORDER BY ci.created_at DESC
";

        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([':cart_id' => $cart['id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular totales
        foreach ($cartItems as $item) {
            $cartTotal += $item['item_total'];
            $itemCount += $item['quantity'];
        }
    }
} catch (PDOException $e) {
    error_log("Error al obtener el carrito: " . $e->getMessage());
    $cartItems = [];
    $cartTotal = 0;
    $itemCount = 0;
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificaciones/notification2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../../layouts/headerproducts.php'; ?>

    <main class="container cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Tu Carrito</h1>
            <div class="cart-steps">
                <div class="step active">
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
                <div class="step">
                    <span>4</span>
                    <p>Confirmación</p>
                </div>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Tu carrito está vacío</h2>
                <p>¡Explora nuestra tienda y descubre productos increíbles!</p>
                <a href="<?= BASE_URL ?>/tienda" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Continuar comprando
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <div class="cart-items-header">
                        <div class="header-product">Producto</div>
                        <div class="header-price">Precio</div>
                        <div class="header-quantity">Cantidad</div>
                        <div class="header-total">Total</div>
                        <div class="header-actions"></div>
                    </div>

                    <div class="cart-items-list">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-item-id="<?= $item['item_id'] ?>">
                                <div class="item-product">
                                    <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $item['product_slug'] ?>" class="product-image">
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['primary_image'] ?? 'assets/images/placeholder-product.jpg') ?>"
                                            alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    </a>
                                    <div class="product-details">
                                        <h3>
                                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $item['product_slug'] ?>">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </a>
                                        </h3>
                                        <?php if ($item['color_name'] || $item['size_name']): ?>
                                            <div class="product-variants">
                                                <?php if ($item['color_name']): ?>
                                                    <span class="variant-color">Color: <?= htmlspecialchars($item['color_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($item['size_name']): ?>
                                                    <span class="variant-size">Talla: <?= htmlspecialchars($item['size_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-price">
                                    $<?= number_format($item['variant_price'] ?? $item['product_price'], 0, ',', '.') ?>
                                </div>
                                <div class="item-quantity">
                                    <div class="quantity-control">
                                        <button class="quantity-btn minus" data-item-id="<?= $item['item_id'] ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number"
                                            class="quantity-input"
                                            value="<?= $item['quantity'] ?>"
                                            min="1"
                                            max="<?= $item['stock_available'] ?>"
                                            data-item-id="<?= $item['item_id'] ?>">
                                        <button class="quantity-btn plus" data-item-id="<?= $item['item_id'] ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="item-total">
                                    $<?= number_format($item['item_total'], 0, ',', '.') ?>
                                </div>
                                <div class="item-actions">
                                    <button class="remove-item" data-item-id="<?= $item['item_id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-continue-shopping">
                        <a href="<?= BASE_URL ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Continuar comprando
                        </a>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Resumen del Pedido</h3>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>$<?= number_format($cartTotal, 0, ',', '.') ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Envío</span>
                                <span class="shipping-cost">Se calcula en el siguiente paso</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>$<?= number_format($cartTotal, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <div class="summary-actions">
                            <a href="<?= BASE_URL ?>/tienda/pagos/envio.php" class="btn btn-primary btn-block">
                                Proceder al pago <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>


                    </div>

                    
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../../layouts/footer.php'; ?>

    <div class="floating-notification-container"></div>

    <?php include __DIR__ . '/../../js/cart/cartjs.php'; ?>
</body>

</html>