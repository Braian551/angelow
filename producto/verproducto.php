<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/includes/product-functions.php';

// Obtener slug del producto desde la URL
$productSlug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Obtener información básica del producto
$product = getProductBySlug($conn, $productSlug);

if (!$product) {
    header("HTTP/1.0 404 Not Found");
    includeFromRoot('error/404.php');
    exit();
}

// Obtener datos relacionados con el producto
$variantsData = getProductVariants($conn, $product['id']);
$additionalImages = getAdditionalImages($conn, $product['id']);
$reviewsData = getProductReviews($conn, $product['id']);
$questionsData = getProductQuestions($conn, $product['id']);
$relatedProducts = getRelatedProducts($conn, $product['id'], $product['category_id']);

// Verificar si el usuario puede dejar reseña
$canReview = canUserReviewProduct($conn, $_SESSION['user_id'] ?? null, $product['id']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Angelow Ropa Infantil</title>
    <meta name="description" content="<?= htmlspecialchars($product['description'] ? substr($product['description'], 0, 160) : 'Descubre este producto en Angelow Ropa Infantil') ?>">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificacion.css">
    <!-- carritocheck.css not found; removed to avoid 404 -->

    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-view.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-gallery.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-info.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-tabs.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/related-products.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-modal.css">
</head>

<body>
<div class="back-button-container">
    <a href="javascript:history.back()" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>


    <div class="product-detail-container">
        <!-- Sección principal del producto -->
        <section class="product-main-section">
            <?php include 'includes/product-gallery.php'; ?>
            <?php include 'includes/product-info.php'; ?>
        </section>

        <!-- Sección de pestañas -->
        <?php include 'includes/product-tabs.php'; ?>

        <!-- Productos relacionados -->
        <?php include 'includes/related-products.php'; ?>
    </div>

    <!-- Modal para zoom de imagen -->
    <div class="image-zoom-modal" id="imageZoomModal">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <div class="modal-image-container">
                <img src="" alt="" id="zoomed-image">
            </div>
        </div>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
     <?php require_once __DIR__ . '/../js/producto/verproductojs.php'; ?>


</body>

</html>