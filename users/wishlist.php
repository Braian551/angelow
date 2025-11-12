<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener productos de la lista de deseos con sus detalles
try {
    $query = "
        SELECT 
            p.id,
            p.name,
            p.slug,
            p.price,
            p.compare_price,
            p.is_featured,
            p.category_id,
            c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, `order` ASC LIMIT 1) as main_image,
            w.created_at as added_date
        FROM wishlist w
        INNER JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = :user_id AND p.is_active = 1
        ORDER BY w.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $wishlistProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
    $wishlistProducts = [];
}

// Obtener categorías para referencia
$categories = [];
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Lista de Deseos - Angelow</title>
    <meta name="description" content="Tu lista de productos favoritos en Angelow.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/productos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/notificacion.css">
    <style>
        .main-header {
            position: relative;
        }

        .wishlist-page-container {
            max-width: 1600px;
            margin: 3rem auto;
            padding: 0 2rem;
            min-height: 70vh;
        }

        .wishlist-header {
            margin-bottom: 3rem;
            text-align: center;
        }

        .wishlist-header h1 {
            font-size: 3rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .wishlist-header p {
            font-size: 1.6rem;
            color: var(--text-light);
        }

        .wishlist-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: var(--product-card-shadow);
            text-align: center;
        }

        .stat-card .stat-value {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-label {
            font-size: 1.4rem;
            color: var(--text-light);
        }

        .empty-wishlist {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border-radius: 18px;
            box-shadow: var(--product-card-shadow);
        }

        .empty-wishlist i {
            font-size: 8rem;
            color: var(--primary-light);
            margin-bottom: 2rem;
            opacity: 0.7;
        }

        .empty-wishlist h2 {
            font-size: 2.4rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .empty-wishlist p {
            font-size: 1.6rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .empty-wishlist .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            background-color: var(--primary-color);
            color: white;
            padding: 1.4rem 3rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.5rem;
            transition: var(--transition-medium);
            text-decoration: none;
        }

        .empty-wishlist .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 119, 182, 0.3);
        }

        .wishlist-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--product-card-shadow);
        }

        .clear-all-btn {
            background: linear-gradient(135deg, #e63946, #d62828);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.4rem;
            cursor: pointer;
            transition: var(--transition-medium);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .clear-all-btn:hover {
            background: linear-gradient(135deg, #d62828, #c41e29);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(230, 57, 70, 0.3);
        }

        @media (max-width: 768px) {
            .wishlist-header h1 {
                font-size: 2.4rem;
            }

            .wishlist-stats {
                gap: 1.5rem;
            }

            .stat-card {
                padding: 1.5rem 2rem;
            }

            .wishlist-actions {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="wishlist-page-container">
        <!-- Header de la página -->
        <div class="wishlist-header">
            <h1><i class="fas fa-heart"></i> Mi Lista de Deseos</h1>
            <p>Guarda tus productos favoritos aquí</p>
        </div>

        <?php if (!empty($wishlistProducts)): ?>
            <!-- Estadísticas -->
            <div class="wishlist-stats">
                <div class="stat-card">
                    <span class="stat-value"><?= count($wishlistProducts) ?></span>
                    <span class="stat-label">Producto<?= count($wishlistProducts) != 1 ? 's' : '' ?> guardado<?= count($wishlistProducts) != 1 ? 's' : '' ?></span>
                </div>
            </div>

            <!-- Acciones -->
            <div class="wishlist-actions">
                <div class="total-products">
                    <p><?= count($wishlistProducts) ?> producto<?= count($wishlistProducts) != 1 ? 's' : '' ?> en tu lista</p>
                </div>
                <button class="clear-all-btn" id="clearAllWishlist">
                    <i class="fas fa-trash"></i>
                    Limpiar lista
                </button>
            </div>

            <!-- Grid de productos -->
            <div class="products-grid">
                <?php foreach ($wishlistProducts as $product): ?>
                    <div class="product-card" data-product-id="<?= $product['id'] ?>">
                        <?php if ($product['is_featured']): ?>
                            <div class="product-badge">Destacado</div>
                        <?php elseif (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                            <?php 
                            $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                            ?>
                            <div class="product-badge sale"><?= $discount ?>% OFF</div>
                        <?php endif; ?>

                        <!-- Botón de favoritos (siempre activo en esta página) -->
                        <button class="wishlist-btn active" aria-label="Quitar de favoritos" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-heart"></i>
                        </button>

                        <!-- Imagen del producto -->
                        <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="product-image loading">
                            <?php if ($product['main_image']): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['main_image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <img src="<?= BASE_URL ?>/images/default-product.jpg" 
                                     alt="Producto sin imagen">
                            <?php endif; ?>
                        </a>

                        <!-- Información del producto -->
                        <div class="product-info">
                            <span class="product-category">
                                <?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?>
                            </span>

                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>

                            <!-- Valoración -->
                            <div class="product-rating">
                                <div class="stars">
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <span class="rating-count">(0)</span>
                            </div>

                            <!-- Precio -->
                            <div class="product-price">
                                <span class="current-price">$<?= number_format($product['price'], 0, ',', '.') ?></span>
                                <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                    <span class="original-price">$<?= number_format($product['compare_price'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Botón de ver producto -->
                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=<?= $product['slug'] ?>" class="view-product-btn">
                                <i class="fas fa-eye"></i> Ver producto
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Lista vacía -->
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <h2>Tu lista de deseos está vacía</h2>
                <p>Agrega productos a tu lista de deseos para guardarlos aquí</p>
                <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">
                    <i class="fas fa-shopping-bag"></i>
                    Explorar productos
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función genérica para llamadas a la API
            function callApi(endpoint, method, data, callback) {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };

                if (method !== 'GET' && method !== 'HEAD') {
                    options.body = JSON.stringify(data);
                }

                return fetch(endpoint, options)
                    .then(response => {
                        return response.text().then(text => {
                            let jsonResponse;
                            try {
                                jsonResponse = JSON.parse(text);
                            } catch (e) {
                                jsonResponse = { success: false, error: text || 'Respuesta inválida del servidor' };
                            }

                            if (!response.ok) {
                                const errorMessage = jsonResponse.error || `Error del servidor: ${response.status}`;
                                throw new Error(errorMessage);
                            }

                            return jsonResponse;
                        });
                    })
                    .then(result => {
                        if (callback) callback(result);
                        return result;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification(error.message, 'error');
                        return { success: false, error: error.message };
                    });
            }

            // Función para manejar la lista de deseos
            function handleWishlist(action, productId, callback) {
                const endpoint = `<?= BASE_URL ?>/tienda/api/wishlist/${action}.php`;
                callApi(endpoint, 'POST', { product_id: productId }, function(response) {
                    if (callback) callback(response);
                });
            }

            // Notificación mejorada
            function showNotification(message, type) {
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-times-circle',
                    info: 'fa-info-circle'
                };
                
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('fade-out');
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
            }

            // Evento para botones de wishlist individuales
            document.querySelectorAll('.wishlist-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const productCard = this.closest('.product-card');

                    handleWishlist('remove', productId, function(response) {
                        if (response.success) {
                            // Animar y eliminar la tarjeta
                            productCard.style.transform = 'scale(0.8)';
                            productCard.style.opacity = '0';
                            
                            setTimeout(() => {
                                productCard.remove();
                                
                                // Verificar si quedan productos
                                const remainingProducts = document.querySelectorAll('.product-card').length;
                                if (remainingProducts === 0) {
                                    location.reload();
                                } else {
                                    // Actualizar contador
                                    const totalProducts = document.querySelector('.total-products p');
                                    if (totalProducts) {
                                        totalProducts.textContent = `${remainingProducts} producto${remainingProducts != 1 ? 's' : ''} en tu lista`;
                                    }
                                    const statValue = document.querySelector('.stat-value');
                                    if (statValue) {
                                        statValue.textContent = remainingProducts;
                                    }
                                    const statLabel = document.querySelector('.stat-label');
                                    if (statLabel) {
                                        statLabel.textContent = `Producto${remainingProducts != 1 ? 's' : ''} guardado${remainingProducts != 1 ? 's' : ''}`;
                                    }
                                }
                            }, 300);
                            
                            showNotification('Producto eliminado de tu lista de deseos', 'info');
                        }
                    });
                });
            });

            // Botón para limpiar toda la lista
            const clearAllBtn = document.getElementById('clearAllWishlist');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    if (!confirm('¿Estás seguro de que deseas eliminar todos los productos de tu lista de deseos?')) {
                        return;
                    }

                    const productIds = Array.from(document.querySelectorAll('.product-card')).map(card => 
                        card.getAttribute('data-product-id')
                    );

                    let completed = 0;
                    productIds.forEach(productId => {
                        handleWishlist('remove', productId, function(response) {
                            completed++;
                            if (completed === productIds.length) {
                                showNotification('Lista de deseos limpiada exitosamente', 'success');
                                setTimeout(() => location.reload(), 1000);
                            }
                        });
                    });
                });
            }

            // Función para manejar la carga de imágenes
            function handleImageLoad(img) {
                img.classList.add('loaded');
                const parent = img.parentElement;
                if (parent) {
                    parent.classList.remove('loading');
                }
            }

            // Función para manejar errores de carga de imágenes
            function handleImageError(img) {
                img.src = '<?= BASE_URL ?>/images/default-product.jpg';
                img.classList.add('loaded');
                const parent = img.parentElement;
                if (parent) {
                    parent.classList.remove('loading');
                }
            }

            // Inicializar imágenes
            document.querySelectorAll('.product-image').forEach(container => {
                const img = container.querySelector('img');
                if (!img) return;
                
                container.classList.add('loading');
                
                if (img.complete) {
                    handleImageLoad(img);
                } else {
                    img.addEventListener('load', function() {
                        handleImageLoad(img);
                    });
                    img.addEventListener('error', function() {
                        handleImageError(img);
                    });
                }
            });
        });
    </script>
</body>

</html>
