<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/functions.php';
require_once __DIR__ . '/../layouts/client/headerclientconfig.php';

?>

<!-- El resto de tu HTML permanece igual -->

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Angelow</title>
    <meta name="description" content="Panel de control de tu cuenta en Angelow Ropa Infantil. Gestiona tus pedidos, direcciones y preferencias.">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Preconexión para mejorar performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
</head>

<body>
    <div class="user-dashboard-container">
    
  <?php
require_once __DIR__ . '/../layouts/asideuser.php';
  ?>

        <!-- Contenido principal -->
        <main class="user-main-content">
            <div class="dashboard-header">
                <h1>Bienvenido a tu cuenta</h1>
                <p>Aquí puedes gestionar tus pedidos, direcciones y preferencias.</p>
            </div>

            <!-- Resumen rápido -->
            <section class="dashboard-summary">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Pedidos</h3>
                        <p>0 pedidos realizados</p>
                        <a href="<?= BASE_URL ?>/users/orders.php">Ver historial</a>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Direcciones</h3>
                        <p>0 direcciones guardadas</p>
                        <a href="<?= BASE_URL ?>/users/addresses.php">Gestionar direcciones</a>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="summary-content">
                        <h3>Favoritos</h3>
                        <p>0 productos guardados</p>
                        <a href="<?= BASE_URL ?>/users/wishlist.php">Ver favoritos</a>
                    </div>
                </div>
            </section>

            <!-- Pedidos recientes -->
            <section class="recent-orders">
                <div class="section-header">
                    <h2>Pedidos recientes</h2>
                    <a href="<?= BASE_URL ?>/users/orders.php" class="view-all">Ver todos</a>
                </div>

                <div class="no-orders">
                    <i class="fas fa-box-open"></i>
                    <p>Aún no has realizado ningún pedido</p>
                    <a href="<?= BASE_URL ?>" class="btn">Ir a la tienda</a>
                </div>
            </section>

            <!-- Recomendaciones personalizadas -->
            <section class="personalized-recommendations">
                <div class="section-header">
                    <h2>Recomendaciones para ti</h2>
                </div>

                <div class="recommendations-grid">
                    <div class="product-card">
                        <div class="product-wishlist">
                            <button aria-label="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <a href="<?= BASE_URL ?>/producto-vestido-verano.html" class="product-image">
                            <img src="<?= BASE_URL ?>/images/productos/conjunto_niña.jpg" alt="Vestido de verano para niña">
                        </a>
                        <div class="product-info">
                            <span class="product-category">Conjunto</span>
                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto-vestido-verano.html">Conjunto Niña</a>
                            </h3>
                            <div class="product-price">
                                <span class="current-price">$35.900</span>
                            </div>
                            <button class="add-to-cart">Añadir al carrito</button>
                        </div>
                    </div>

                    <div class="product-card">
                        <div class="product-wishlist">
                            <button aria-label="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <a href="<?= BASE_URL ?>/producto-vestido-verano.html" class="product-image">
                            <img src="<?= BASE_URL ?>/images/productos/pijamas.jpg" alt="Pijama infantil">
                        </a>
                        <div class="product-info">
                            <span class="product-category">Pijama</span>
                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto-vestido-verano.html">Pijama Niño</a>
                            </h3>
                            <div class="product-price">
                                <span class="current-price">$29.900</span>
                            </div>
                            <button class="add-to-cart">Añadir al carrito</button>
                        </div>
                    </div>

                    <div class="product-card">
                        <div class="product-wishlist">
                            <button aria-label="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <a href="<?= BASE_URL ?>/producto-vestido-verano.html" class="product-image">
                            <img src="<?= BASE_URL ?>/images/productos/deportivo.jpg" alt="Ropa deportiva">
                        </a>
                        <div class="product-info">
                            <span class="product-category">Deportivo</span>
                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto-vestido-verano.html">Conjunto Deportivo</a>
                            </h3>
                            <div class="product-price">
                                <span class="current-price">$39.900</span>
                            </div>
                            <button class="add-to-cart">Añadir al carrito</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>
</body>

</html>