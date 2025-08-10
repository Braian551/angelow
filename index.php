
<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layouts/headerproducts.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angelow - Ropa Infantil Premium</title>
    <meta name="description" content="Tienda online de ropa infantil de alta calidad. Moda cómoda y segura para bebés, niñas y niños. Envíos a todo el país.">
    <meta name="keywords" content="ropa infantil, moda niños, ropa bebé, vestidos niñas, conjuntos niños, pijamas infantiles">
    <link rel="icon" href="images/logo.png" type="image/x-icon">
<link rel="stylesheet" href="css/style.css">
    <!-- Preconexión para mejorar performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Barra de anuncio superior -->
    <div class="announcement-bar">
        <p>¡Envío gratis en compras superiores a $50.000! | 3 cuotas sin interés</p>
    </div>



    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="hero-slider">
            <div class="hero-slide active">
                <picture>
                    <source media="(max-width: 768px)" srcset="images/hero-mobile-1.jpg">
                    <img src="images/productos/coleccion organica.jpg" alt="Colección Verano 2025">
                </picture>
                <div class="hero-content">
                    <h1>Colección Verano 2025</h1>
                    <p>Descubre los diseños más frescos para esta temporada</p>
                    <a href="verano-2025.html" class="btn">Ver colección</a>
                </div>
            </div>
            <!-- Más slides aquí -->
        </div>
        <div class="hero-dots">
            <span class="dot active"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
    </section>

    <!-- Categorías destacadas -->
    <section class="featured-categories">
        <h2 class="section-title">Explora nuestras categorías</h2>
        <div class="categories-grid">
            <a href="ninas-vestidos.html" class="category-card">
                <img src="images/productos/vestido.jpg" alt="Vestidos para niñas">
                <h3>Vestidos</h3>
            </a>
            <a href="ninos-conjuntos.html" class="category-card">
                <img src="images/productos/conjuto.jpeg" alt="Conjuntos para niños">
                <h3>Conjuntos</h3>
            </a>
            <a href="bebes-pijamas.html" class="category-card">
                <img src="images/productos/pijamas.jpg" alt="Pijamas para bebés">
                <h3>Pijamas</h3>
            </a>
            <a href="deportivo.html" class="category-card">
                <img src="images/productos/deportivo.jpg" alt="Deportivo infantiles">
                <h3>Deportivo</h3>
            </a>
        </div>
    </section>

    <!-- Productos destacados -->
    <section class="featured-products">
        <div class="section-header">
            <h2 class="section-title">Productos destacados</h2>
            <a href="productos.html" class="view-all">Ver todos</a>
        </div>
        
        <div class="products-grid">
            <!-- Producto 1 -->
            <div class="product-card">
                <div class="product-badge">OFERTA</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/conjunto_niña.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Conjunto</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">Conjunto Niña</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
            
            <!-- Producto 2 -->
              <div class="product-card">
                <div class="product-badge">50%</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/conjunto_niña2.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Vestidos</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">Overol</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
            
            
            <!-- Producto 3 -->
            <div class="product-card">
                <div class="product-badge">Nuevo</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/conjunto_niño.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Pijama</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">Pijama Niño</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
            
            
            <!-- Producto 4 -->
            <div class="product-card">
                <div class="product-badge">Nuevo</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/conjunto_niño2.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Conjunto</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">Conjunto Niño</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
            
            <!-- Producto 5 -->
            <div class="product-card">
                <div class="product-badge">Nuevo</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/deportivo2.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Deportivo</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">Deportivo Niño</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
              
            <!-- Producto 6 -->
            <div class="product-card">
                <div class="product-badge">Nuevo</div>
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="producto-vestido-verano.html" class="product-image">
                    <img src="images/productos/simba.jpg" alt="Vestido de verano para niña">
                </a>
                <div class="product-info">
                    <span class="product-category">Deportivo</span>
                    <h3 class="product-title">
                        <a href="producto-vestido-verano.html">SIMBA</a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars">★★★★☆</span>
                        <span class="count">(24)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$35.900</span>
                        <span class="original-price">$42.900</span>
                    </div>
                    <button class="add-to-cart">Añadir al carrito</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Banner promocional -->
    <section class="promo-banner">
        <div class="promo-content">
            <h2>¡Compra 2 prendas y llévate la 3ra con 50% de descuento!</h2>
            <p>Válido hasta el 30 de junio o hasta agotar existencias</p>
            <a href="promo-3x2.html" class="btn">Aprovechar oferta</a>
        </div>
    </section>

    <!-- Colecciones destacadas -->
    <section class="featured-collections">
        <h2 class="section-title">Nuestras colecciones</h2>
        <div class="collections-grid">
            <a href="coleccion-playa.html" class="collection-card">
                <img src="images/productos/coleccion playera.jpg" alt="Colección playa">
                <div class="collection-overlay">
                    <h3>Colección Playa</h3>
                </div>
            </a>
            <a href="coleccion-primavera.html" class="collection-card">
                <img src="images/productos/coleccion primavera.jpg" alt="Colección primavera">
                <div class="collection-overlay">
                    <h3>Colección Primavera</h3>
                </div>
            </a>
            <a href="coleccion-organica.html" class="collection-card">
                <img src="images/productos/coleccion organica.jpg" alt="Colección orgánica">
                <div class="collection-overlay">
                    <h3>Colección Orgánica</h3>
                </div>
            </a>
        </div>
    </section>

    <!-- Testimonios -->
    <section class="testimonials">
        <h2 class="section-title">Lo que dicen nuestros clientes</h2>
        <div class="testimonials-slider">
            <!-- Testimonio 1 -->
            <div class="testimonial">
                <div class="testimonial-content">
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"La calidad de la ropa superó mis expectativas. Mi hija está encantada con sus vestidos y lo mejor es que resisten muy bien los lavados."</p>
                    <p class="testimonial-author">- María G.</p>
                </div>
            </div>
            <!-- Más testimonios aquí -->
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter">
        <div class="newsletter-container">
            <div class="newsletter-content">
                <h2>Suscríbete a Nuestra Tienda</h2>
                <p>Recibe ofertas exclusivas y novedades antes que nadie</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Tu correo electrónico" required>
                    <button type="submit">Suscribirse</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->

<?php include 'layouts/footer.php'; ?>
    <!-- Botón flotante de WhatsApp -->
  

    <!-- Scripts -->

</body>
</html>