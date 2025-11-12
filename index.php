
<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layouts/headerproducts.php';

// Obtener anuncios activos para barra superior (solo el de mayor prioridad)
$top_bar_query = "SELECT * FROM announcements 
                  WHERE type = 'top_bar' 
                  AND is_active = 1 
                  AND (start_date IS NULL OR start_date <= NOW())
                  AND (end_date IS NULL OR end_date >= NOW())
                  ORDER BY priority DESC, created_at DESC 
                  LIMIT 1";
$top_bar_stmt = $conn->prepare($top_bar_query);
$top_bar_stmt->execute();
$top_bar_announcement = $top_bar_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener anuncios activos para banner promocional (solo el de mayor prioridad)
$promo_banner_query = "SELECT * FROM announcements 
                       WHERE type = 'promo_banner' 
                       AND is_active = 1 
                       AND (start_date IS NULL OR start_date <= NOW())
                       AND (end_date IS NULL OR end_date >= NOW())
                       ORDER BY priority DESC, created_at DESC 
                       LIMIT 1";
$promo_banner_stmt = $conn->prepare($promo_banner_query);
$promo_banner_stmt->execute();
$promo_banner = $promo_banner_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener sliders activos
$sliders_query = "SELECT * FROM sliders WHERE is_active = 1 ORDER BY order_position ASC";
$sliders_stmt = $conn->prepare($sliders_query);
$sliders_stmt->execute();
$sliders = $sliders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías activas
$categories_query = "SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL LIMIT 4";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos destacados con sus variantes e imágenes
$products_query = "
    SELECT
        p.id,
        p.name,
        p.slug,
        p.price,
        p.compare_price,
        p.gender,
        p.is_featured,
        c.name as category_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, `order` ASC LIMIT 1) as main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.is_featured DESC, p.created_at DESC
    LIMIT 6
";
$products_stmt = $conn->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener colecciones activas
$collections_query = "SELECT * FROM collections WHERE is_active = 1 ORDER BY launch_date DESC LIMIT 3";
$collections_stmt = $conn->prepare($collections_query);
$collections_stmt->execute();
$collections = $collections_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="css/announcements.css">
    <!-- Preconexión para mejorar performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Barra de anuncio superior -->
    <?php if ($top_bar_announcement): ?>
        <div class="announcement-bar">
            <p>
                <?php if (!empty($top_bar_announcement['icon'])): ?>
                    <i class="fas <?= htmlspecialchars($top_bar_announcement['icon']) ?>"></i>
                <?php endif; ?>
                <?= htmlspecialchars($top_bar_announcement['message']) ?>
            </p>
        </div>
    <?php endif; ?>



    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="hero-slider">
            <?php if (!empty($sliders)): ?>
                <?php foreach ($sliders as $index => $slider): ?>
                    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $slider['image']); ?>" 
                             alt="<?php echo htmlspecialchars($slider['title']); ?>">
                        <div class="hero-content">
                            <h1><?php echo htmlspecialchars($slider['title']); ?></h1>
                            <p><?php echo htmlspecialchars($slider['subtitle']); ?></p>
                            <?php if (!empty($slider['link'])): ?>
                                <a href="<?php echo htmlspecialchars($slider['link']); ?>" class="btn">Ver más</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Slider por defecto si no hay datos -->
                <div class="hero-slide active">
                        <img src="uploads/productos/687c39ea4c00e_coleccion organica.jpg" alt="Colección Verano 2025">
                    <div class="hero-content">
                        <h1>Colección Verano 2025</h1>
                        <p>Descubre los diseños más frescos para esta temporada</p>
                        <a href="#" class="btn">Ver colección</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($sliders) > 1): ?>
            <div class="hero-dots">
                <?php foreach ($sliders as $index => $slider): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span>
                <?php endforeach; ?>
            </div>
            <button class="hero-prev" aria-label="Anterior">❮</button>
            <button class="hero-next" aria-label="Siguiente">❯</button>
        <?php endif; ?>
    </section>

    <!-- Categorías destacadas -->
    <section class="featured-categories">
        <h2 class="section-title">Explora nuestras categorías</h2>
        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <a href="<?php echo BASE_URL; ?>/tienda/tienda.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $category['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php else: ?>
                            <img src="images/default-category.jpg" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Categorías por defecto -->
                <a href="#" class="category-card">
                        <img src="images/default-product.jpg" alt="Vestidos para niñas">
                    <h3>Vestidos</h3>
                </a>
                <a href="#" class="category-card">
                        <img src="images/default-product.jpg" alt="Conjuntos para niños">
                    <h3>Conjuntos</h3>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Productos destacados -->
    <section class="featured-products">
        <div class="section-header">
            <h2 class="section-title">Productos destacados</h2>
            <a href="<?php echo BASE_URL; ?>/tienda/tienda.php" class="view-all">Ver todos</a>
        </div>
        
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['is_featured']): ?>
                            <div class="product-badge">DESTACADO</div>
                        <?php elseif (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                            <?php 
                            $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                            ?>
                            <div class="product-badge"><?php echo $discount; ?>% OFF</div>
                        <?php endif; ?>
                        <div class="product-wishlist">
                            <button aria-label="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/producto/verproducto.php?id=<?php echo $product['id']; ?>" class="product-image">
                            <?php if (!empty($product['main_image'])): ?>
                                <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $product['main_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <img src="<?php echo BASE_URL; ?>/images/default-product.jpg" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php endif; ?>
                        </a>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?></span>
                            <h3 class="product-title">
                                <a href="<?php echo BASE_URL; ?>/producto/verproducto.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <div class="product-rating">
                                <span class="stars">★★★★☆</span>
                                <span class="count">(0)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$<?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                    <span class="original-price">$<?php echo number_format($product['compare_price'], 0, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart" onclick="location.href='<?php echo BASE_URL; ?>/producto/verproducto.php?id=<?php echo $product['id']; ?>'">
                                Ver detalles
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 2rem;">No hay productos disponibles en este momento.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Banner promocional -->
    <?php if ($promo_banner): ?>
        <section class="promo-banner<?php echo (!empty($promo_banner['image'])) ? ' has-image' : ''; ?>"<?php if (!empty($promo_banner['image'])): ?> style="--promo-bg-image: url('<?= BASE_URL . '/' . htmlspecialchars($promo_banner['image']) ?>');"<?php endif; ?>>
            <?php if (!empty($promo_banner['image'])): ?>
                <div class="promo-image"></div>
            <?php endif; ?>
            <div class="promo-content">
                <?php if (!empty($promo_banner['icon'])): ?>
                    <i class="fas <?= htmlspecialchars($promo_banner['icon']) ?> fa-3x"></i>
                <?php endif; ?>
                <h2><?= htmlspecialchars($promo_banner['title']) ?></h2>
                <?php if (!empty($promo_banner['subtitle'])): ?>
                    <p><?= htmlspecialchars($promo_banner['subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($promo_banner['button_text']) && !empty($promo_banner['button_link'])): ?>
                    <a href="<?= htmlspecialchars($promo_banner['button_link']) ?>" class="btn"><?= htmlspecialchars($promo_banner['button_text']) ?></a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Colecciones destacadas -->
    <section class="featured-collections">
        <h2 class="section-title">Nuestras colecciones</h2>
        <div class="collections-grid">
            <?php if (!empty($collections)): ?>
                <?php foreach ($collections as $collection): ?>
                    <a href="<?php echo BASE_URL; ?>/tienda/tienda.php?collection=<?php echo $collection['id']; ?>" class="collection-card">
                        <?php if (!empty($collection['image'])): ?>
                            <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $collection['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($collection['name']); ?>">
                        <?php else: ?>
                            <img src="images/default-collection.jpg" 
                                 alt="<?php echo htmlspecialchars($collection['name']); ?>">
                        <?php endif; ?>
                        <div class="collection-overlay">
                            <h3><?php echo htmlspecialchars($collection['name']); ?></h3>
                            <?php if (!empty($collection['description'])): ?>
                                <p><?php echo htmlspecialchars($collection['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Colecciones por defecto -->
                <a href="#" class="collection-card">
                        <img src="uploads/productos/687c248b90bab_coleccion playera.jpg" alt="Colección playa">
                    <div class="collection-overlay">
                        <h3>Colección Playa</h3>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </section>




    <!-- Footer -->

<?php include 'layouts/footer.php'; ?>
    <!-- Botón flotante de WhatsApp -->
  

    <!-- Scripts -->
    <script>
        // Slider automático del hero banner
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.hero-dots .dot');
            const prevBtn = document.querySelector('.hero-prev');
            const nextBtn = document.querySelector('.hero-next');
            let currentSlide = 0;
            let slideInterval;

            if (slides.length <= 1) return; // No hacer nada si hay un solo slide

            function showSlide(n) {
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                currentSlide = (n + slides.length) % slides.length;
                
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            function nextSlide() {
                showSlide(currentSlide + 1);
            }

            function prevSlide() {
                showSlide(currentSlide - 1);
            }

            function startAutoSlide() {
                slideInterval = setInterval(nextSlide, 5000); // Cambiar cada 5 segundos
            }

            function stopAutoSlide() {
                clearInterval(slideInterval);
            }

            // Event listeners para los controles
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    stopAutoSlide();
                    nextSlide();
                    startAutoSlide();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    stopAutoSlide();
                    prevSlide();
                    startAutoSlide();
                });
            }

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    stopAutoSlide();
                    showSlide(index);
                    startAutoSlide();
                });
            });

            // Pausar el slider cuando el mouse está sobre él
            const heroSlider = document.querySelector('.hero-slider');
            if (heroSlider) {
                heroSlider.addEventListener('mouseenter', stopAutoSlide);
                heroSlider.addEventListener('mouseleave', startAutoSlide);
            }

            // Iniciar el slider automático
            startAutoSlide();
        });
    </script>

</body>
</html>