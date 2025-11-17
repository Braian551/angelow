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
            } else if (data) {
                const params = new URLSearchParams();
                for (const key in data) {
                    params.append(key, data[key]);
                }
                endpoint += `?${params.toString()}`;
            }

            return fetch(endpoint, options)
                .then(response => {
                    return response.text().then(text => {
                        let jsonResponse;
                        try {
                            jsonResponse = JSON.parse(text);
                        } catch (e) {
                            // Si no es JSON válido, crear una respuesta de error
                            jsonResponse = { success: false, error: text || 'Respuesta inválida del servidor' };
                        }

                        if (!response.ok) {
                            // Usar el mensaje de error del JSON si existe, sino el texto de la respuesta
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

        // Manejar despliegue/contracción de secciones de filtro con animación
        document.querySelectorAll('.filter-title').forEach(title => {
            title.addEventListener('click', function() {
                const targetId = this.getAttribute('data-toggle');
                const target = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                target.classList.toggle('active');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });

        // Función para manejar la lista de deseos (wishlist)
        function handleWishlist(action, productId, callback) {
            const endpoint = `<?= BASE_URL ?>/tienda/api/wishlist/${action}.php`;
            callApi(endpoint, 'POST', { product_id: productId }, function(response) {
                if (response.success) {
                    if (callback) callback(response);
                } else {
                    // El error ya se muestra en callApi, solo llamar callback si existe
                    if (callback) callback(response);
                }
            });
        }

        // Evento para botones de wishlist
        document.querySelectorAll('.wishlist-btn').forEach(button => {
            button.addEventListener('click', function() {
                <?php if (!isset($_SESSION['user_id'])): ?>
                    showNotification('Debes iniciar sesión para usar la lista de deseos', 'error');
                    return;
                <?php endif; ?>

                const productId = this.getAttribute('data-product-id');
                const isActive = this.classList.contains('active');

                handleWishlist(isActive ? 'remove' : 'add', productId, function(response) {
                    if (response.success) {
                        this.classList.toggle('active');
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('far');
                            icon.classList.toggle('fas');
                        }
                        showNotification(
                            isActive ? 'Producto eliminado de tu lista de deseos' : 'Producto añadido a tu lista de deseos',
                            isActive ? 'info' : 'success'
                        );
                    } else {
                        // El error ya se mostró en callApi, no mostrar otro mensaje
                        console.log('Error en wishlist:', response.error);
                    }
                }.bind(this));
            });
        });

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

        // Cargar wishlist del usuario
        function loadWishlistProducts() {
            <?php if (isset($_SESSION['user_id'])): ?>
                callApi('<?= BASE_URL ?>/tienda/api/wishlist/get-wishlist.php', 'GET', null, function(response) {
                    if (response.success) {
                        response.items.forEach(item => {
                            const button = document.querySelector(`.wishlist-btn[data-product-id="${item.product_id}"]`);
                            if (button) {
                                button.classList.add('active');
                                const icon = button.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('far');
                                    icon.classList.add('fas');
                                }
                            }
                        });
                    }
                });
            <?php endif; ?>
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

        // Inicializar imágenes al cargar la página
        function initImages() {
            document.querySelectorAll('.product-image').forEach(container => {
                const img = container.querySelector('img');
                if (!img) return;
                
                // Agregar clase loading al contenedor
                container.classList.add('loading');
                
                // Si la imagen ya está cargada
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
        }

        // Función para cargar productos via AJAX
        function loadProducts(params = {}) {
            const productsGrid = document.querySelector('.products-grid');

            // Mostrar placeholders shimmer
            showShimmerPlaceholders(productsGrid);

            callApi('<?= BASE_URL ?>/tienda/ajax/filter_products.php', 'POST', params)
                .then(response => {
                    if (response.success) {
                        updateProductsGrid(response);
                        updatePagination(response);
                        updateProductCount(response);
                    }
                })
                .catch(error => {
                    // En caso de error, mostrar mensaje de error
                    productsGrid.innerHTML = `
                        <div class="no-products">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error al cargar los productos. Inténtalo de nuevo.</p>
                            <button class="btn retry-load">Reintentar</button>
                        </div>
                    `;

                    // Agregar manejador de evento al botón Reintentar en lugar de inline onclick
                    const retryBtn = productsGrid.querySelector('.no-products .retry-load');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', function() {
                            // Reintentar con los mismos parámetros
                            loadProducts(params);
                        });
                    }
                });
        }

        // Función para mostrar placeholders shimmer
        function showShimmerPlaceholders(container) {
            // Crear 12 placeholders (una página completa)
            let shimmerHtml = '';
            for (let i = 0; i < 12; i++) {
                shimmerHtml += `
                    <div class="product-card shimmer">
                        <div class="shimmer-wishlist"></div>
                        <div class="shimmer-image"></div>
                        <div class="shimmer-info">
                            <div class="shimmer-category"></div>
                            <div class="shimmer-title"></div>
                            <div class="shimmer-title"></div>
                            <div class="shimmer-rating"></div>
                            <div class="shimmer-price"></div>
                            <div class="shimmer-button"></div>
                        </div>
                    </div>
                `;
            }
            container.innerHTML = shimmerHtml;
        }

        // Actualizar la cuadrícula de productos
        function updateProductsGrid(data) {
            const productsGrid = document.querySelector('.products-grid');
            
            if (data.products.length === 0) {
                productsGrid.innerHTML = `
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>No se encontraron productos con los filtros seleccionados.</p>
                        <a href="<?= BASE_URL ?>/tienda/productos.php" class="btn">Ver todos los productos</a>
                    </div>
                `;
                return;
            }

            let html = '';
            data.products.forEach(product => {
                    // Obtener información de valoración (comprobación segura)
                    const ratingInfo = (data.productRatings && data.productRatings[product.id]) ? data.productRatings[product.id] : null;
                    const avgRating = ratingInfo ? round(ratingInfo.avg_rating, 1) : 0;
                const reviewCount = ratingInfo ? ratingInfo.review_count : 0;

                // Determinar precio y descuento a mostrar
                const displayPrice = Number(product.display_price ?? product.min_price ?? product.price ?? 0);
                const hasDiscount = Boolean(product.has_discount);
                const validDiscount = hasDiscount && Number(product.discount_percentage) > 0;
                const discountBadge = (validDiscount)
                    ? `<div class="product-badge sale">${product.discount_percentage}% OFF</div>`
                    : '';
                const featuredBadge = product.is_featured ? '<div class="product-badge">Destacado</div>' : '';

                // Obtener nombre de categoría
                let categoryName = 'Sin categoría';
                data.categories.forEach(cat => {
                    if (cat.id == product.category_id) {
                        categoryName = cat.name;
                    }
                });

                html += `
                    <div class="product-card" data-product-id="${product.id}">
                        ${featuredBadge}${discountBadge}
                        <button class="wishlist-btn ${product.is_favorite ? 'active' : ''}"
                            aria-label="Añadir a favoritos"
                            data-product-id="${product.id}">
                            <i class="${product.is_favorite ? 'fas' : 'far'} fa-heart"></i>
                        </button>
                        <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=${product.slug}" class="product-image loading">
                            ${product.primary_image ? 
                                `<img src="<?= BASE_URL ?>/${escapeHtml(product.primary_image)}" alt="${escapeHtml(product.name)}">` : 
                                `<img src="<?= BASE_URL ?>/images/default-product.jpg" alt="Producto sin imagen">`
                            }
                        </a>
                        <div class="product-info">
                            <span class="product-category">${escapeHtml(categoryName)}</span>
                            <h3 class="product-title">
                                <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=${product.slug}">${escapeHtml(product.name)}</a>
                            </h3>
                            <div class="product-rating">
                                <div class="stars">
                                    ${renderStars(avgRating)}
                                </div>
                                <span class="rating-count">(${reviewCount})</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$${formatPrice(displayPrice)}</span>
                                ${validDiscount && product.compare_price ? 
                                    `<span class="original-price">$${formatPrice(product.compare_price)}</span>` : ''
                                }
                            </div>
                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=${product.slug}" class="view-product-btn">
                                <i class="fas fa-eye"></i> Ver producto
                            </a>
                        </div>
                    </div>
                `;
            });

            productsGrid.innerHTML = html;
            
            // Inicializar las imágenes después de agregarlas al DOM
            initImages();
            
            // Reasignar eventos a los nuevos botones de wishlist
            document.querySelectorAll('.wishlist-btn').forEach(button => {
                button.addEventListener('click', function() {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        showNotification('Debes iniciar sesión para usar la lista de deseos', 'error');
                        return;
                    <?php endif; ?>

                    const productId = this.getAttribute('data-product-id');
                    const isActive = this.classList.contains('active');

                    handleWishlist(isActive ? 'remove' : 'add', productId, function(response) {
                        if (response.success) {
                            this.classList.toggle('active');
                            const icon = this.querySelector('i');
                            if (icon) {
                                icon.classList.toggle('far');
                                icon.classList.toggle('fas');
                            }
                            showNotification(
                                isActive ? 'Producto eliminado de tu lista de deseos' : 'Producto añadido a tu lista de deseos',
                                isActive ? 'info' : 'success'
                            );
                        } else {
                            // El error ya se mostró en callApi
                            console.log('Error en wishlist:', response.error);
                        }
                    }.bind(this));
                });
            });
        }

        // Actualizar la paginación
        function updatePagination(data) {
            const pagination = document.querySelector('.pagination');
            if (!pagination) return;
            
            if (data.totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';
            const currentPage = data.currentPage;
            const queryParams = new URLSearchParams(window.location.search);
            
            if (currentPage > 1) {
                queryParams.set('page', currentPage - 1);
                html += `<a href="?${queryParams.toString()}" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>`;
            }

            for (let i = 1; i <= data.totalPages; i++) {
                queryParams.set('page', i);
                html += `<a href="?${queryParams.toString()}" class="page-link ${i == currentPage ? 'active' : ''}">
                            ${i}
                        </a>`;
            }

            if (currentPage < data.totalPages) {
                queryParams.set('page', currentPage + 1);
                html += `<a href="?${queryParams.toString()}" class="page-link next">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>`;
            }

            pagination.innerHTML = html;
        }

        // Actualizar el contador de productos
        function updateProductCount(data) {
            const totalProductsElement = document.querySelector('.total-products p');
            if (totalProductsElement) {
                totalProductsElement.textContent = `${data.totalProducts} producto${data.totalProducts != 1 ? 's' : ''} encontrado${data.totalProducts != 1 ? 's' : ''}`;
            }
        }

        // Funciones auxiliares
        // Helper para redondear (empleado en la visualización de reviews)
        function round(value, decimals = 0) {
            const num = Number(value);
            if (isNaN(num)) return 0;
            return Number(num.toFixed(decimals));
        }
        function renderStars(rating) {
            let stars = '';
            const fullStars = Math.floor(rating);
            const hasHalfStar = (rating - fullStars) >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

            // Estrellas llenas
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star"></i>';
            }

            // Media estrella
            if (hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            }

            // Estrellas vacías
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star"></i>';
            }

            return stars;
        }

        function formatPrice(price) {
            const numericPrice = Number(price ?? 0);
            return numericPrice.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) {
                return '';
            }

            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // --- Filtros y ordenamiento ---
        const sortSelect = document.getElementById('sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                updateUrlParam('sort', this.value, true);
            });
        }

        // Filtros por categoría y género
        document.querySelectorAll('input[name="category"], input[name="gender"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const paramName = this.getAttribute('name');
                updateUrlParam(paramName, this.value, true);
            });
        });

        // Filtro por precio
        const minPriceInput = document.querySelector('.min-price');
        const maxPriceInput = document.querySelector('.max-price');
        const minPriceValue = document.querySelector('.min-price-value');
        const maxPriceValue = document.querySelector('.max-price-value');

        if (minPriceInput && maxPriceInput) {
            minPriceInput.addEventListener('input', function() {
                minPriceValue.textContent = `$${formatPrice(this.value)}`;
            });

            maxPriceInput.addEventListener('input', function() {
                maxPriceValue.textContent = `$${formatPrice(this.value)}`;
            });

            minPriceInput.addEventListener('change', () => applyPriceFilter(true));
            maxPriceInput.addEventListener('change', () => applyPriceFilter(true));
        }

        // Botón aplicar filtros
        document.querySelector('.apply-filters')?.addEventListener('click', () => applyPriceFilter(true));
        document.querySelector('.clear-filters')?.addEventListener('click', () => {
            window.location.href = `<?= BASE_URL ?>/tienda/productos.php`;
        });

        // Funciones auxiliares
        function applyPriceFilter(useAjax = false) {
            updateUrlParam('min_price', minPriceInput.value, useAjax);
            updateUrlParam('max_price', maxPriceInput.value, useAjax);
        }

        function updateUrlParam(key, value, useAjax = false) {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (value === '' || value === null || value === undefined) {
                params.delete(key);
            } else {
                params.set(key, value);
            }

            // Mantener todos los parámetros existentes excepto 'page' cuando se cambia un filtro
            if (key !== 'page') {
                params.delete('page');
            }

            // Actualizar la URL sin recargar
            const newUrl = `${url.pathname}?${params.toString()}`;
            window.history.pushState({ path: newUrl }, '', newUrl);

            if (useAjax) {
                // Convertir los parámetros a un objeto para AJAX
                const paramsObj = {};
                params.forEach((val, key) => {
                    paramsObj[key] = val;
                });
                
                loadProducts(paramsObj);
            }
        }

        // Manejar el evento popstate (navegación hacia atrás/adelante)
        window.addEventListener('popstate', function() {
            const params = new URLSearchParams(window.location.search);
            const paramsObj = {};
            params.forEach((val, key) => {
                paramsObj[key] = val;
            });
            
            // Actualizar controles del formulario
            if (paramsObj.sort) {
                sortSelect.value = paramsObj.sort;
            }
            
            if (paramsObj.category) {
                document.querySelector(`input[name="category"][value="${paramsObj.category}"]`).checked = true;
            }
            
            if (paramsObj.gender) {
                document.querySelector(`input[name="gender"][value="${paramsObj.gender}"]`).checked = true;
            }
            
            if (paramsObj.min_price) {
                minPriceInput.value = paramsObj.min_price;
                minPriceValue.textContent = `$${formatPrice(paramsObj.min_price)}`;
            }
            
            if (paramsObj.max_price) {
                maxPriceInput.value = paramsObj.max_price;
                maxPriceValue.textContent = `$${formatPrice(paramsObj.max_price)}`;
            }
            
            // Cargar productos
            loadProducts(paramsObj);
        });

        // Cargar productos iniciales
        const initialParams = {};
        new URLSearchParams(window.location.search).forEach((val, key) => {
            initialParams[key] = val;
        });
        loadProducts(initialParams);

        // Inicializar wishlist
        loadWishlistProducts();

        // Inicializar imágenes al cargar la página
        initImages();
    });
</script>