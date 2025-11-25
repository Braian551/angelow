<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings/site_settings.php';
require_once __DIR__ . '/layouts/headerproducts.php';

// Fetch site settings
$siteSettings = fetch_site_settings($conn);
$storeName = $siteSettings['store_name'] ?? 'Angelow';
$storeTagline = $siteSettings['store_tagline'] ?? 'Ropa Infantil Premium';

// Obtener TODAS las colecciones activas
$collections_query = "SELECT * FROM collections WHERE is_active = 1 ORDER BY launch_date DESC";
$collections_stmt = $conn->prepare($collections_query);
$collections_stmt->execute();
$collections = $collections_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colecciones - <?= htmlspecialchars($storeName) ?></title>
    <meta name="description" content="Descubre nuestras colecciones exclusivas de ropa infantil.">
    <link rel="icon" href="<?= !empty($siteSettings['brand_logo']) ? BASE_URL . '/' . $siteSettings['brand_logo'] : 'images/logo.png' ?>" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/productos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ajustes específicos para la página de colecciones */
        .collections-page {
            padding: 40px 0;
        }
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5rem;
            color: #333;
        }
    </style>
</head>

<body>

    <!-- Colecciones Grid -->
    <section class="featured-collections collections-page">
        <div class="container">
            <h1 class="section-title">Nuestras Colecciones</h1>
            <div class="collections-grid">
                <?php if (!empty($collections)): ?>
                    <?php foreach ($collections as $collection): ?>
                        <a href="<?php echo BASE_URL; ?>/tienda/productos.php?collection=<?php echo $collection['id']; ?>" class="collection-card">
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
                    <div class="no-results" style="text-align: center; width: 100%; grid-column: 1 / -1;">
                        <p>No hay colecciones disponibles en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'layouts/footer.php'; ?>

</body>
</html>
