<?php
$reviewsClientPayload = $reviewsData ?? ['reviews' => [], 'stats' => []];
if (!isset($reviewsClientPayload['reviews']) || !is_array($reviewsClientPayload['reviews'])) {
    $reviewsClientPayload['reviews'] = [];
}
if (!isset($reviewsClientPayload['stats']) || !is_array($reviewsClientPayload['stats'])) {
    $reviewsClientPayload['stats'] = [];
}
if (!empty($reviewsClientPayload['reviews'])) {
    foreach ($reviewsClientPayload['reviews'] as &$clientReview) {
        $clientReview['user_name'] = $clientReview['user_name'] ?? 'Usuario';
        $clientReview['user_image'] = $clientReview['user_image'] ?? 'images/default-avatar.png';
        $clientReview['images'] = !empty($clientReview['images']) ? (json_decode($clientReview['images'], true) ?: []) : [];
        $clientReview['display_date'] = !empty($clientReview['created_at']) ? date('d/m/Y H:i', strtotime($clientReview['created_at'])) : '';
        $clientReview['helpful_count'] = isset($clientReview['helpful_count']) ? (int)$clientReview['helpful_count'] : 0;
        $clientReview['user_has_voted'] = isset($clientReview['user_has_voted']) ? (int)$clientReview['user_has_voted'] : 0;
    }
    unset($clientReview);
}
?>
<script>
    $(document).ready(function() {
        // Variables globales
        let selectedColorId = <?= $variantsData['defaultColorId'] ?? 'null' ?>;
        let selectedSizeId = <?= $variantsData['defaultSizeId'] ?? 'null' ?>;
        let selectedVariantId = <?= $variantsData['defaultVariant']['variant_id'] ?? 'null' ?>;
        let selectedQuantity = <?= ($variantsData['defaultVariant']['quantity'] ?? 0) > 0 ? 1 : 0 ?>;
        let variantsByColor = <?= json_encode($variantsData['variantsByColor']) ?>;
        let productId = <?= $product['id'] ?? 'null' ?>;
        let isInWishlist = false;
        const currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
        const currentUserRole = <?= json_encode($_SESSION['user_role'] ?? null) ?>;
        const productSlug = <?= json_encode($product['slug'] ?? '') ?>;
        // Initial set of questions from server. Use let so we can update after delete.
        let initialQuestions = <?= json_encode($questionsData ?: []) ?>;
        let reviewsState = <?= json_encode($reviewsClientPayload, JSON_UNESCAPED_UNICODE) ?>;
        const reviewsEndpoint = productId ? '<?= BASE_URL ?>/api/get_reviews.php?product_id=' + productId : null;
        const seeAllReviewsUrl = <?= json_encode(BASE_URL . '/producto/opiniones/' . ($product['slug'] ?? '')) ?>;

        updateVariantBindings(selectedVariantId);

        // Read product page debug flag from the <meta name="debug"> tag (set to 1 to enable)
        const PRODUCT_PAGE_DEBUG = document.querySelector('meta[name="debug"]')?.content === '1';

        // Helper: remove a question from front-end state after delete
        function removeQuestionFromInitial(qId) {
            try {
                initialQuestions = initialQuestions.filter(q => q.id != qId);
            } catch (err) { if (PRODUCT_PAGE_DEBUG) console.warn('Could not remove from initialQuestions', err); }
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function nl2brSafe(value) {
            return escapeHtml(value).replace(/\n/g, '<br>');
        }

        function buildReviewStars(rating) {
            const stars = [];
            const cleanRating = parseFloat(rating) || 0;
            for (let i = 1; i <= 5; i++) {
                if (i <= Math.floor(cleanRating)) {
                    stars.push('<i class="fas fa-star"></i>');
                } else if (i - cleanRating <= 0.5 && cleanRating % 1 !== 0) {
                    stars.push('<i class="fas fa-star-half-alt"></i>');
                } else {
                    stars.push('<i class="far fa-star"></i>');
                }
            }
            return stars.join('');
        }

        function formatRatingLabel(value) {
            if (!Number.isFinite(value)) return '';
            return Number.isInteger(value) ? String(value) : value.toFixed(1).replace(/\.0$/, '');
        }

        function buildReviewImages(images) {
            if (!Array.isArray(images) || !images.length) return '';
            return `<div class="review-images">${images.map(img => {
                const src = '<?= BASE_URL ?>/' + escapeHtml(img);
                return `<div class="review-image"><img src="${src}" alt="Imagen reseña"></div>`;
            }).join('')}</div>`;
        }

        function buildReviewCardHTML(review) {
            const safeId = escapeHtml(review.id || '');
            const safeName = escapeHtml(review.user_name || 'Usuario');
            const avatarSrc = '<?= BASE_URL ?>/' + escapeHtml(review.user_image || 'images/default-avatar.png');
            const badge = review.is_verified ? '<span class="badge verified">Compra verificada</span>' : '';
            const title = escapeHtml(review.title || '');
            const comment = nl2brSafe(review.comment || '');
            const imagesHtml = buildReviewImages(review.images);
            const helpfulCount = review.helpful_count || 0;
            const userHasVoted = review.user_has_voted ? parseInt(review.user_has_voted) === 1 : false;
            const displayDate = escapeHtml(review.display_date || '');
            const starsHtml = buildReviewStars(review.rating);

            return `
                <div class="review-card" data-review-id="${safeId}">
                    <div class="review-meta">
                        <div class="user-avatar">
                            <img src="${avatarSrc}" alt="${safeName}">
                        </div>
                        <div class="user-info">
                            <strong>${safeName}</strong>
                            ${badge}
                            <span class="time">${displayDate}</span>
                        </div>
                        <!-- stars moved to the review body to align with title -->
                    </div>
                    <div class="review-body">
                        <div class="review-head">
                            <h4 class="review-title">${title}</h4>
                            <div class="user-rating review-stars">${starsHtml}</div>
                        </div>
                        <p class="review-comment">${comment}</p>
                        ${imagesHtml}
                        <div class="review-actions">
                            <button class="btn small helpful-btn${userHasVoted ? ' active' : ''}" data-review-id="${safeId}" aria-pressed="${userHasVoted}">Útil (${helpfulCount})</button>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateReviewTabCount(total) {
            const reviewsTab = document.querySelector('.tab-btn[data-tab="reviews"]');
            if (!reviewsTab) return;
            const badge = reviewsTab.querySelector('[data-reviews-tab-count]');
            if (badge) {
                badge.textContent = `(${total})`;
            } else {
                const baseText = reviewsTab.textContent.replace(/\(.*\)/, '').trim();
                reviewsTab.textContent = `${baseText} (${total})`;
            }
        }

        function renderReviewsSection(payload, rebuildList = false) {
            if (!payload || typeof payload !== 'object') return;
            const stats = payload.stats || {};
            const total = parseInt(stats.total_reviews || 0, 10);
            const avg = parseFloat(stats.average_rating || 0);

            // If the server indicates the current user already left a review, remove/hide the 'write review' button
            const userHasReview = payload.user_has_review || false;
            const writeBtn = document.getElementById('write-review-btn');
            if (userHasReview && writeBtn) {
                writeBtn.remove();
                // Also hide review form if present
                const reviewForm = document.getElementById('review-form-container');
                if (reviewForm) reviewForm.style.display = 'none';
                // Info banner removed (duplicate). UI only hides the write button.
            }

            const avgNode = document.querySelector('[data-average-rating]');
            if (avgNode) avgNode.textContent = avg.toFixed(1);

            const totalLabel = document.querySelector('[data-total-reviews-label]');
            if (totalLabel) totalLabel.textContent = `${total} opiniones`;

            const percentKey = {
                5: 'five_star_percent',
                4: 'four_star_percent',
                3: 'three_star_percent',
                2: 'two_star_percent',
                1: 'one_star_percent'
            };

            document.querySelectorAll('[data-rating-row]').forEach(row => {
                const ratingVal = parseInt(row.dataset.ratingRow, 10);
                const percent = stats[percentKey[ratingVal]] ?? 0;
                const bar = row.querySelector('[data-rating-bar]');
                if (bar) bar.style.width = `${percent}%`;
                const percentLabel = row.querySelector('[data-rating-percent]');
                if (percentLabel) percentLabel.textContent = `${percent}%`;
            });

            updateReviewTabCount(total);

            const listEl = document.querySelector('.reviews-list');
            if (!listEl) return;
            listEl.dataset.qaReviewsCount = total;

            if (!rebuildList) return;

            if (!payload.reviews || !payload.reviews.length) {
                listEl.innerHTML = `
                    <div class="no-reviews">
                        <i class="fas fa-comment-alt"></i>
                        <p>Este producto aún no tiene opiniones. Sé el primero en opinar.</p>
                    </div>
                `;
                return;
            }

            const cardsHtml = payload.reviews.map(buildReviewCardHTML).join('');
            const seeAllLink = total > 10 && seeAllReviewsUrl ? `<a href="${seeAllReviewsUrl}" class="see-all-reviews">Ver todas las opiniones (${total})</a>` : '';
            listEl.innerHTML = cardsHtml + seeAllLink;
        }

        function fetchLatestReviews() {
            if (!reviewsEndpoint) return;
            fetch(reviewsEndpoint, { headers: { 'Accept': 'application/json' } })
                .then(resp => resp.json())
                .then(data => {
                    if (!data.success) {
                        if (PRODUCT_PAGE_DEBUG) console.warn('fetchLatestReviews failed', data);
                        return;
                    }
                    reviewsState = data.data || { reviews: [], stats: {} };
                    renderReviewsSection(reviewsState, true);
                })
                .catch(err => {
                    if (PRODUCT_PAGE_DEBUG) console.error('fetchLatestReviews error', err);
                });
        }

        // Manejar clic en "Útil"
        $(document).on('click', '.helpful-btn', function(e) {
            e.preventDefault();
            if (!productId) return;

            const btn = $(this);
            const reviewId = btn.data('review-id');

            if (!currentUserId) {
                showNotification('Debes iniciar sesión para marcar una opinión como útil', 'error');
                return;
            }

            // find the review in state if available
            const rv = (reviewsState.reviews || []).find(r => String(r.id) === String(reviewId));
            if (rv && String(rv.user_id) === String(currentUserId)) {
                showNotification('No puedes marcar tu propia reseña como útil', 'info');
                return;
            }

            btn.prop('disabled', true);

            fetch('<?= BASE_URL ?>/api/toggle_review_helpful.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ review_id: reviewId })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showNotification(data.message || 'Error al marcar útil', 'error');
                    return;
                }

                // update local state and UI
                try {
                    if (rv) {
                        rv.helpful_count = data.data.helpful_count;
                        rv.user_has_voted = data.data.user_has_voted;
                    }

                    // Update button state
                    if (data.data.user_has_voted) {
                        btn.addClass('active');
                        btn.attr('aria-pressed', 'true');
                    } else {
                        btn.removeClass('active');
                        btn.attr('aria-pressed', 'false');
                    }
                    btn.text('Útil (' + data.data.helpful_count + ')');
                } catch (err) {
                    if (PRODUCT_PAGE_DEBUG) console.error('Error updating helpful state', err);
                }
            })
            .catch(err => {
                console.error('toggle helpful error', err);
                showNotification('Error de conexión', 'error');
            })
            .finally(() => btn.prop('disabled', false));
        });

        renderReviewsSection(reviewsState);




        // Verificar si el producto está en la lista de deseos al cargar la página
        function checkWishlistStatus() {
            if (!productId) return;

            $.ajax({
                url: '<?= BASE_URL ?>/tienda/api/wishlist/get-wishlist.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        isInWishlist = response.items.some(item => item.product_id == productId);
                        updateWishlistButton();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al verificar lista de deseos:', error);
                }
            });
        }

        // Actualizar el botón de wishlist
        function updateWishlistButton() {
            const wishlistBtn = $('#add-to-wishlist');
            if (isInWishlist) {
                wishlistBtn.html('<i class="fas fa-heart" style="color: red;"></i>');
                wishlistBtn.attr('title', 'Eliminar de favoritos');
            } else {
                wishlistBtn.html('<i class="far fa-heart"></i>');
                wishlistBtn.attr('title', 'Añadir a favoritos');
            }
        }

        // Manejar clic en el botón de wishlist
        $('#add-to-wishlist').click(function(e) {
            e.preventDefault();

            if (!productId) return;

            const endpoint = isInWishlist ? 'remove.php' : 'add.php';

            $.ajax({
                url: `<?= BASE_URL ?>/tienda/api/wishlist/${endpoint}`,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    product_id: productId
                }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        isInWishlist = !isInWishlist;
                        updateWishlistButton();

                        // Mostrar notificación
                        const message = isInWishlist ?
                            'Producto añadido a tu lista de deseos' :
                            'Producto eliminado de tu lista de deseos';
                        const type = isInWishlist ? 'success' : 'info';
                        showNotification(message, type);
                    } else {
                        showNotification(response.message || 'Error al actualizar la lista de deseos', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 401) {
                        showNotification('Debes iniciar sesión para usar la lista de deseos', 'error');
                    } else {
                        showNotification('Error al actualizar la lista de deseos', 'error');
                        console.error('Error:', error);
                    }
                }
            });
        });

        // Mostrar notificación
        function showNotification(message, type) {
            const isCartSuccess = type === 'success' && message.toLowerCase().includes('carrito');
            // Build HTML without nested template literals to avoid syntax issues
            const notificationHtml =
                '<div class="floating-notification ' + type + ' ' + (isCartSuccess ? 'clickable' : '') + '"' + (isCartSuccess ? ' style="cursor: pointer;"' : '') + '>' +
                '<div class="notification-content">' +
                    '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i>' +
                    '<span>' + message + '</span>' +
                '</div>' +
                '<button class="close-notification">&times;</button>' +
                '</div>';

            const notification = $(notificationHtml);

            $('body').append(notification);

            // Manejar clic en la notificación
            if (type === 'success' && message.toLowerCase().includes('carrito')) {
                notification.click(function(e) {
                    // No redirigir si se hace clic en el botón de cerrar
                    if (!$(e.target).hasClass('close-notification') && !$(e.target).closest('.close-notification').length) {
                        window.location.href = '<?= BASE_URL ?>/tienda/pagos/cart.php';
                    }
                });
            }

            // Auto cerrar después de 3 segundos
            setTimeout(() => {
                notification.addClass('fade-out');
                setTimeout(() => notification.remove(), 500);
            }, 3000);

            // Cerrar manualmente
            notification.find('.close-notification').click(function(e) {
                e.stopPropagation(); // Evitar que el clic se propague a la notificación
                notification.addClass('fade-out');
                setTimeout(() => notification.remove(), 500);
            });
        }

        // Verificar estado de wishlist al cargar
        checkWishlistStatus();

        // Manejar clic en miniaturas
        $(document).on('click', '.thumb-item', function() {
            const index = $(this).data('index');
            $('.thumb-item').removeClass('active');
            $(this).addClass('active');

            $('.main-image').removeClass('active').hide();
            $('.main-image[data-index="' + index + '"]').addClass('active').fadeIn();

            // Scroll suave a la miniatura seleccionada
            const container = $('.thumbnails-track')[0];
            const thumb = $(this)[0];
            container.scrollTo({
                left: thumb.offsetLeft - (container.offsetWidth - thumb.offsetWidth) / 2,
                behavior: 'smooth'
            });

            updateThumbNavs();
        });

        // Manejar navegación de miniaturas
        $('.thumb-nav.prev').click(function() {
            const container = $('.thumbnails-track')[0];
            container.scrollBy({
                left: -200,
                behavior: 'smooth'
            });
            updateThumbNavs();
        });

        $('.thumb-nav.next').click(function() {
            const container = $('.thumbnails-track')[0];
            container.scrollBy({
                left: 200,
                behavior: 'smooth'
            });
            updateThumbNavs();
        });

        // Actualizar visibilidad de botones de navegación
        function updateThumbNavs() {
            const container = $('.thumbnails-track')[0];
            const prevBtn = $('.thumb-nav.prev');
            const nextBtn = $('.thumb-nav.next');

            prevBtn.toggleClass('hidden', container.scrollLeft <= 10);
            nextBtn.toggleClass('hidden', container.scrollLeft >= container.scrollWidth - container.offsetWidth - 10);
        }

        function formatCurrency(value) {
            const numericValue = Number(value ?? 0);
            return '$' + numericValue.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function updateVariantBindings(variantId) {
            const targetId = variantId || '';
            $('#add-to-cart, #buy-now').attr('data-variant-id', targetId);
        }

        function updatePriceInfo(price, comparePrice) {
            const pricingSection = $('.product-pricing');
            if (!pricingSection.length) return;

            const currentPriceEl = pricingSection.find('.current-price');
            const originalPriceEl = pricingSection.find('.original-price');
            const discountBadge = pricingSection.find('.discount-badge');

            const fallbackPrice = Number(currentPriceEl.data('current-price')) || 0;
            const numericPrice = Number(price);
            const currentValue = Number.isFinite(numericPrice) && numericPrice > 0 ? numericPrice : fallbackPrice;

            currentPriceEl
                .text(formatCurrency(currentValue))
                .attr('data-current-price', currentValue)
                .data('current-price', currentValue);

            const baseCompareRaw = pricingSection.data('product-compare');
            const baseCompare = Number(baseCompareRaw);
            const normalizedBaseCompare = Number.isFinite(baseCompare) && baseCompare > currentValue ? baseCompare : null;

            const variantCompare = Number(comparePrice);
            let effectiveCompare = Number.isFinite(variantCompare) && variantCompare > currentValue ? variantCompare : null;
            if (!effectiveCompare) {
                effectiveCompare = normalizedBaseCompare;
            }

            if (effectiveCompare) {
                originalPriceEl
                    .text(formatCurrency(effectiveCompare))
                    .show();
                const discount = Math.max(0, Math.round(((effectiveCompare - currentValue) / effectiveCompare) * 100));
                discountBadge
                    .text(`${discount}% OFF`)
                    .show();
            } else {
                originalPriceEl.hide();
                discountBadge.hide();
            }
        }

        // Inicializar navegación
        updateThumbNavs();

        // Manejar scroll para actualizar botones
        $('.thumbnails-track').on('scroll', function() {
            updateThumbNavs();
        });

        // Cambiar imágenes al seleccionar color
        $('.color-option').click(function() {
            const colorId = $(this).data('color-id');
            if (colorId === selectedColorId) return;

            selectedColorId = colorId;
            $('.color-option').removeClass('selected');
            $(this).addClass('selected');
            $('#selected-color-name').text($(this).data('color-name'));

            // Actualizar opciones de talla
            updateSizeOptions(colorId);

            // Cambiar imágenes
            const colorData = variantsByColor[colorId];
            if (!colorData) return;
            const images = colorData.images.length ? colorData.images : [{
                image_path: '<?= $product['primary_image'] ?>',
                alt_text: '<?= $product['name'] ?> - Imagen principal'
            }];

            // Actualizar thumbs
            $('.thumbnails-track').empty();
            images.forEach((image, index) => {
                $('.thumbnails-track').append(`
                <div class="thumb-item ${index === 0 ? 'active' : ''}" data-index="${index}">
                    <img src="<?= BASE_URL ?>/${image.image_path}" alt="${image.alt_text}">
                </div>
            `);
            });

            // Actualizar main images
            $('.gallery-main .main-image').remove();
            images.forEach((image, index) => {
                $('.gallery-main').append(`
                <div class="main-image ${index === 0 ? 'active' : ''}" data-index="${index}">
                    <img src="<?= BASE_URL ?>/${image.image_path}" alt="${image.alt_text}">
                    <button class="zoom-btn" aria-label="Ampliar imagen">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
            `);
            });

            // Mostrar solo la primera imagen
            $('.main-image').hide();
            $('.main-image.active').show();

            // Actualizar navegación
            updateThumbNavs();
        });

        // Zoom de imagen
        $(document).on('click', '.zoom-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const imgSrc = $(this).siblings('img').attr('src');
            const imgAlt = $(this).siblings('img').attr('alt');

            $('#zoomed-image').attr('src', imgSrc).attr('alt', imgAlt);
            $('#imageZoomModal').addClass('active').fadeIn();
        });

        // Cerrar modal de zoom
        $('.modal-close').click(function() {
            $('#imageZoomModal').removeClass('active').fadeOut();
        });

        // Cerrar modal al hacer clic fuera
        $(document).click(function(e) {
            if ($(e.target).hasClass('image-zoom-modal')) {
                $('#imageZoomModal').removeClass('active').fadeOut();
            }
        });

        // Actualizar opciones de talla cuando cambia el color
        function updateSizeOptions(colorId) {
            const colorData = variantsByColor[colorId];
            if (!colorData) {
                $('#size-options').html('<div class="no-sizes">No hay tallas disponibles para este color</div>');
                $('#selected-size-name').text('No disponible');
                $('#product-sku').text('N/A');
                selectedSizeId = null;
                selectedVariantId = null;
                updateVariantBindings(null);
                updatePriceInfo(null, null);
                updateStockInfo(0);
                return;
            }
            const firstAvailableSize = colorData.sizes ? Object.keys(colorData.sizes)[0] : null;

            $('#size-options').empty();

            if (colorData.sizes && Object.keys(colorData.sizes).length) {
                $.each(colorData.sizes, function(sizeId, sizeData) {
                    $('#size-options').append(`
                    <div class="size-option ${sizeId == firstAvailableSize ? 'selected' : ''}" 
                         data-size-id="${sizeId}"
                         data-size-name="${sizeData.size_name}"
                         data-variant-id="${sizeData.variant_id}">
                        ${sizeData.size_name}
                    </div>
                `);
                });

                selectedSizeId = firstAvailableSize;
                selectedVariantId = colorData.sizes[firstAvailableSize].variant_id;
                $('#selected-size-name').text(colorData.sizes[firstAvailableSize].size_name);
                $('#product-sku').text(colorData.sizes[firstAvailableSize].sku);
                updateVariantBindings(selectedVariantId);
                updatePriceInfo(colorData.sizes[firstAvailableSize].price, colorData.sizes[firstAvailableSize].compare_price);

                // Actualizar disponibilidad
                updateStockInfo(colorData.sizes[firstAvailableSize].quantity);
            } else {
                $('#size-options').html('<div class="no-sizes">No hay tallas disponibles para este color</div>');
                $('#selected-size-name').text('No disponible');
                $('#product-sku').text('N/A');
                selectedSizeId = null;
                selectedVariantId = null;
                updateVariantBindings(null);
                updatePriceInfo(null, null);
                updateStockInfo(0);
            }
        }

        // Seleccionar talla
        $(document).on('click', '.size-option', function() {
            const sizeId = $(this).data('size-id');
            if (sizeId === selectedSizeId) return;

            selectedSizeId = sizeId;
            selectedVariantId = $(this).data('variant-id');
            $('.size-option').removeClass('selected');
            $(this).addClass('selected');
            $('#selected-size-name').text($(this).data('size-name'));

            // Actualizar disponibilidad
            const colorData = variantsByColor[selectedColorId];
            updateStockInfo(colorData.sizes[sizeId].quantity);
            updateVariantBindings(selectedVariantId);
            updatePriceInfo(colorData.sizes[sizeId].price, colorData.sizes[sizeId].compare_price);
        });

        // Actualizar información de stock
        function updateStockInfo(quantity) {
            const stockInfo = $('.stock-info');
            const quantityInput = $('#product-quantity');
            const addToCartBtn = $('#add-to-cart');
            const buyNowBtn = $('#buy-now');
            const normalizedQuantity = Number(quantity) || 0;

            // Actualizar el texto de stock
            if (normalizedQuantity > 5) {
                stockInfo.html('<i class="fas fa-check-circle in-stock"></i> Disponible (' + normalizedQuantity + ' unidades)');
            } else if (normalizedQuantity > 0) {
                stockInfo.html('<i class="fas fa-exclamation-circle low-stock"></i> Últimas ' + normalizedQuantity + ' unidades');
            } else {
                stockInfo.html('<i class="fas fa-times-circle out-of-stock"></i> Agotado');
            }

            if (normalizedQuantity > 0) {
                const maxAllowed = Math.min(normalizedQuantity, 10);
                quantityInput
                    .attr('max', maxAllowed)
                    .attr('min', 1)
                    .prop('disabled', false);
                let currentValue = parseInt(quantityInput.val(), 10);
                if (!Number.isInteger(currentValue) || currentValue < 1) {
                    currentValue = 1;
                }
                if (currentValue > maxAllowed) {
                    currentValue = maxAllowed;
                }
                quantityInput.val(currentValue);
                selectedQuantity = currentValue;
            } else {
                quantityInput
                    .attr('max', 0)
                    .attr('min', 0)
                    .prop('disabled', true)
                    .val(0);
                selectedQuantity = 0;
            }

            const isAvailable = normalizedQuantity > 0;
            addToCartBtn.prop('disabled', !isAvailable);
            buyNowBtn.prop('disabled', !isAvailable);
        }

        // Control de cantidad
        $('.qty-btn.minus').click(function() {
            const input = $('#product-quantity');
            if (input.prop('disabled')) return;
            let value = parseInt(input.val());
            if (value > 1) {
                input.val(value - 1);
                selectedQuantity = value - 1;
            }
        });

        $('.qty-btn.plus').click(function() {
            const input = $('#product-quantity');
            if (input.prop('disabled')) return;
            const max = parseInt(input.attr('max'));
            let value = parseInt(input.val());
            if (value < max) {
                input.val(value + 1);
                selectedQuantity = value + 1;
            }
        });

        $('#product-quantity').change(function() {
            let value = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            const min = parseInt($(this).attr('min'));

            if (isNaN(value)) value = min;
            if (value < min) value = min;
            if (value > max) value = max;

            $(this).val(value);
            selectedQuantity = value;
        });

        // Pestañas
        $('.tab-btn').click(function() {
            const tabId = $(this).data('tab');

            $('.tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.tab-pane').removeClass('active').hide();
            $('#' + tabId).addClass('active').fadeIn();
        });

        // Formulario de reseña
        $(document).on('click', '#write-review-btn', function() {
            const container = $('#review-form-container');
            if (!container.length) return;

            if (container.is(':visible')) {
                container.slideUp();
            } else {
                container.slideDown(() => {
                    container[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        });

        $(document).on('click', '#cancel-review', function() {
            $('#review-form-container').slideUp();
        });

        $(document).on('click', '.locked-review-btn', function() {
            const reason = $(this).data('lock-reason');
            if (reason === 'auth') {
                showNotification('Inicia sesión para dejar una opinión sobre este producto.', 'error');
            } else {
                showNotification('Solo los clientes que han comprado este producto pueden calificarlo.', 'info');
            }
        });

        $(document).on('submit', '#review-form', function(e) {
            e.preventDefault();
            const formElement = this;
            const ratingVal = $('#rating-value').val();
            if (!ratingVal || parseInt(ratingVal, 10) < 1) {
                showNotification('Selecciona una calificación (1-5 estrellas) para enviar tu opinión.', 'error');
                return;
            }

            const formData = new FormData(formElement);
            formData.set('product_id', productId);
            const submitBtn = $(formElement).find('button[type="submit"]');
            submitBtn.prop('disabled', true);

            fetch('<?= BASE_URL ?>/api/submit_review.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Opinión enviada correctamente', 'success');
                    formElement.reset();
                    $('#rating-value').val('');
                    $('.rating-input .fa-star').removeClass('fas active hover').addClass('far');
                    $('#review-form-container').slideUp();
                    fetchLatestReviews();
                } else {
                    showNotification(data.message || 'Error al enviar la opinión', 'error');
                }
            })
            .catch(() => {
                showNotification('Error de conexión. Intenta de nuevo.', 'error');
            })
            .finally(() => {
                submitBtn.prop('disabled', false);
            });
        });

        // Rating con estrellas
        // Star rating interactivity (supports multiple .rating-input groups, e.g., review form and question form)
        $(document).on('mouseenter', '.rating-input .fa-star', function() {
            const rating = $(this).data('rating');
            const group = $(this).closest('.rating-input');
            group.find('.fa-star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).removeClass('far').addClass('fas');
                } else {
                    $(this).removeClass('fas').addClass('far');
                }
            });
        });

        $(document).on('mouseleave', '.rating-input .fa-star', function() {
            const group = $(this).closest('.rating-input');
            const currentRating = group.find('input[type="hidden"]').val();
            group.find('.fa-star').each(function() {
                if ($(this).data('rating') <= currentRating) {
                    $(this).removeClass('far').addClass('fas');
                } else {
                    $(this).removeClass('fas').addClass('far');
                }
            });
        });

        $(document).on('click', '.rating-input .fa-star', function() {
            const rating = $(this).data('rating');
            const group = $(this).closest('.rating-input');
            group.find('input[type="hidden"]').val(rating);
            // Add active classes visually
            group.find('.fa-star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).removeClass('far').addClass('fas active');
                } else {
                    $(this).removeClass('fas active').addClass('far');
                }
            });
        });

        // Formulario de pregunta (delegated so it works with injected buttons)
        $(document).on('click', '#ask-question-btn', function() {
            $('#question-form-container').slideDown();
        });

        $(document).on('click', '#cancel-question', function() {
                $('#question-form-container').slideUp();
        });

        // Enviar pregunta via AJAX (evita recarga completa)
        // Use delegated submit in case the form is injected dynamically
        $(document).on('submit', '#question-form', function(e) {
            e.preventDefault();

            const form = $(this);
            const question = $('#question-text').val().trim();
            const productId = form.find('input[name="product_id"]').val();

            if (question.length < 10) {
                showNotification('La pregunta debe tener al menos 10 caracteres', 'error');
                return;
            }

            // No se permite enviar calificación junto a la pregunta
            const payload = { product_id: productId, question };

            // Deshabilitar botones
            form.find('button[type="submit"]').prop('disabled', true);

            fetch('<?= BASE_URL ?>/api/submit_question.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(resp => resp.json())
                    .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Pregunta enviada correctamente', 'success');

                    // Limpiar formulario y cerrar
                    $('#question-text').val('');
                    $('#question-form-container').slideUp();

                    // Recargar la página completa para mostrar la nueva pregunta y mantener contexto #questions
                    window.location.href = window.location.pathname + window.location.search + '#questions';
                } else {
                    showNotification(data.message || data.error || 'Error al enviar la pregunta', 'error');
                }
            })
            .catch(err => {
                console.error('Error submit question:', err);
                showNotification('Error de conexión. Intenta de nuevo más tarde', 'error');
            })
            .finally(() => {
                form.find('button[type="submit"]').prop('disabled', false);
            });
        });

        // Botón responder pregunta
        $(document).on('click', '.answer-btn', function() {
            $(this).siblings('.answer-form-container').slideToggle();
        });

        // === Editar reseña (autor) ===
        $(document).on('click', '.edit-review', function() {
            const card = $(this).closest('.review-card');
            if (card.find('.edit-review-form').length) return; // ya existe

            const reviewId = $(this).data('review-id');
            const title = card.find('.review-title').text().trim();
            const comment = card.find('.review-comment').text().trim();
            const currentRating = parseInt(card.data('review-rating') || 0, 10);

            // Construir el formulario de edición
            const ratingStars = [1,2,3,4,5].map(r => `<i class="${r <= currentRating ? 'fas' : 'far'} fa-star" data-rating="${r}"></i>`).join('');

            const formHtml = `
                <form class="edit-review-form" data-review-id="${reviewId}">
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="title" value="${escapeHtml(title)}" required>
                    </div>
                    <div class="form-group">
                        <label>Calificación</label>
                        <div class="rating-input edit-review-rating">${ratingStars}<input type="hidden" name="rating" class="edit-review-rating-value" value="${currentRating}"></div>
                    </div>
                    <div class="form-group">
                        <label>Comentario</label>
                        <textarea name="comment" rows="4" required>${escapeHtml(comment)}</textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-edit-review btn small">Cancelar</button>
                        <button type="submit" class="btn small save-edit-review">Guardar</button>
                    </div>
                </form>
            `;

            card.find('.review-body').append(formHtml);
            // For accessibility and UX, scroll to form
            card.find('.edit-review-form')[0]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        // Cancelar edición
        $(document).on('click', '.cancel-edit-review', function() {
            $(this).closest('.edit-review-form').remove();
        });

        // Guardar edición de reseña
        $(document).on('submit', '.edit-review-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const reviewId = form.data('review-id');
            const title = form.find('input[name="title"]').val().trim();
            const rating = parseInt(form.find('.edit-review-rating-value').val() || 0, 10);
            const comment = form.find('textarea[name="comment"]').val().trim();

            if (title.length < 3 || comment.length < 10) {
                showNotification('El título debe tener al menos 3 caracteres y el comentario al menos 10.', 'error');
                return;
            }

            const payload = { review_id: reviewId, title, rating, comment };
            const submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true);

            fetch('<?= BASE_URL ?>/api/edit_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showNotification('Reseña actualizada', 'success');
                    // Actualizar DOM
                    const card = $(`.review-card[data-review-id="${reviewId}"]`);
                    card.find('.review-title').text(title);
                    card.find('.review-comment').html(escapeHtml(comment).replace(/\n/g, '<br>'));
                    card.attr('data-review-rating', rating);
                    // actualizar estrellas
                    card.find('.review-stars').html(function() {
                        let out = '';
                        for (let i=1;i<=5;i++) out += (i <= rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        return out;
                    });
                    form.remove();
                } else {
                    showNotification(data.message || 'Error al actualizar reseña', 'error');
                }
            }).catch(err => {
                console.error('Edit review error:', err);
                showNotification('Error de conexión. Intenta de nuevo.', 'error');
            }).finally(() => submitBtn.prop('disabled', false));
        });

        // Editar pregunta (sólo para autor) - muestra editor inline y usa alertas del usuario para feedback
        $(document).on('click', '.edit-question', function() {
            const qItem = $(this).closest('.question-item');
            const qId = qItem.data('question-id');
            const currentText = qItem.find('.question-text p').text().trim();

            // Si ya existe un editor, no añadir otro
            if (qItem.find('.edit-question-form').length) return;

            // Try to reuse the existing rating markup (if any) so the user sees the stars while editing
            const existingRatingHtml = qItem.find('.question-rating').length ? qItem.find('.question-rating').prop('outerHTML') : '';

            // Build interactive rating input from current displayed rating
            const currentRating = qItem.find('.question-rating i.fas').length || 0;
            const ratingInputs = [];
            for (let r = 1; r <= 5; r++) {
                const cls = r <= currentRating ? 'fas fa-star active' : 'far fa-star';
                ratingInputs.push(`<i class="${cls}" data-rating="${r}"></i>`);
            }

            // Remove rating input in edit question flow — questions can't include ratings anymore
            const ratingInputHtml = '';

            const editor = $(
                `<div class="edit-question-form">` +
                    ratingInputHtml +
                    `<textarea class="edit-question-textarea">${$('<div/>').text(currentText).html()}</textarea>` +
                    `<div class="edit-question-actions">` +
                        `<button class="btn small secondary cancel-edit">Cancelar</button>` +
                        `<button class="btn small primary save-edit">Guardar</button>` +
                    `</div>` +
                `</div>`
            );

            qItem.append(editor);

            // Cancel edit
            editor.on('click', '.cancel-edit', function(e) {
                e.preventDefault();
                editor.remove();
            });

            // Save edit
            editor.on('click', '.save-edit', function(e) {
                e.preventDefault();
                const newText = editor.find('.edit-question-textarea').val().trim();
                if (!newText || newText.length < 10) return showUserWarning('La pregunta debe tener al menos 10 caracteres');

                fetch('<?= BASE_URL ?>/api/edit_question.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ question_id: qId, question: newText })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        // Update internal questions array so future rerenders don't bring it back
                        try { removeQuestionFromInitial(qId); } catch (err) { if (PRODUCT_PAGE_DEBUG) console.warn('removeQuestionFromInitial failed', err); }
                        qItem.find('.question-text p').text(newText);
                        // Update rating display in the question item (append or replace)
                        // No rating update for questions
                        const existingRatingEl = qItem.find('.question-rating');
                        if (updatedRating > 0) {
                            const starsHtml = buildReviewStars(updatedRating);
                            if (existingRatingEl.length) {
                                existingRatingEl.html(starsHtml);
                            } else {
                                qItem.find('.question-meta').append(`<div class="user-rating question-rating" aria-label="Calificación: ${updatedRating} sobre 5">${starsHtml}</div>`);
                            }
                        } else {
                            // Remove rating display if rating set to 0 or empty
                            if (existingRatingEl.length) existingRatingEl.remove();
                        }
                        editor.remove();
                        // Usar las alertas de usuario para notificar éxito
                        showUserSuccess('Pregunta actualizada correctamente', { confirmText: 'OK' });
                    } else {
                        showUserError(data.message || 'Error al actualizar pregunta');
                    }
                }).catch(e => { console.error(e); showUserError('Error de conexión'); });
            });
        });

        // Eliminar pregunta (sólo para autor)
        $(document).on('click', '.delete-question', function() {
            const qItem = $(this).closest('.question-item');
            const qId = qItem.data('question-id');

            // Reemplazar confirm() por la alerta del usuario
            showUserConfirm('¿Estás seguro de eliminar esta pregunta?', async function() {
                try {
                    const resp = await fetch('<?= BASE_URL ?>/api/delete_question.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ question_id: qId })
                    });
                    const data = await resp.json();
                    if (data.success) {
                        qItem.slideUp(300, function() { $(this).remove(); });
                        // Mostrar éxito usando el sistema de alertas de usuario
                        if (typeof showUserSuccess === 'function') {
                            showUserSuccess('Pregunta eliminada correctamente');
                        } else {
                            window.wishlistManager?.notificationSystem.show('Pregunta eliminada', 'success');
                        }

                        // Update questions tab count
                        try {
                            const tabBtn = document.querySelector('.tab-btn[data-tab="questions"]');
                            if (tabBtn) {
                                const m = tabBtn.textContent.match(/\((\d+)\)/);
                                let n = m ? parseInt(m[1]) - 1 : null;
                                if (n !== null && n >= 0) tabBtn.textContent = tabBtn.textContent.replace(/\(.*\)/, '(' + n + ')');
                            }
                        } catch (e) { if (PRODUCT_PAGE_DEBUG) console.warn('Could not update questions count', e); }

                        // Re-render questions to show placeholder if none remain
                        try { renderQuestions(initialQuestions); } catch (err) { if (PRODUCT_PAGE_DEBUG) console.warn('Render after delete failed', err); }
                    } else {
                        showNotification(data.message || 'Error al eliminar pregunta', 'error');
                    }

                    // If there are no more questions after delete, ensure ask button and form are present
                    try {
                        const questionsList = document.querySelector('.questions-list');
                        const remaining = questionsList ? questionsList.querySelectorAll('.question-item').length : 0;
                        // Update the dataset counter
                        if (questionsList) questionsList.dataset.qaQuestionsCount = remaining;

                        // Recreate ask-button if missing
                        if (!document.getElementById('ask-question-btn')) {
                            const header = document.querySelector('#questions .questions-header');
                            if (header) {
                                const btn = document.createElement('button');
                                btn.id = 'ask-question-btn';
                                btn.className = 'tab-action-btn question-btn';
                                btn.innerHTML = '<i class="fas fa-question-circle"></i> Hacer una pregunta';
                                header.appendChild(btn);

                                // Rebind click to slide down form
                                btn.addEventListener('click', function() {
                                    const qForm = document.getElementById('question-form-container');
                                    if (qForm) qForm.style.display = 'block';
                                });
                            }
                        }
                        // If question form hidden, ensure it's available
                        const qForm = document.getElementById('question-form-container');
                        if (qForm && qForm.style.display === 'none') {
                            // Optionally leave hidden; user can click the Ask button to open.
                        }
                    } catch (err) { if (PRODUCT_PAGE_DEBUG) console.warn('Could not ensure ask button exists', err); }
                } catch (e) {
                    console.error(e);
                    showUserError('Error de conexión');
                }
            });
        });

        function renderQuestions(questions) {
            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions called, questions length:', questions.length);
            let container = $('.questions-list');
            
            // If the regular selector didn't find the container, try a scoped selector or a direct DOM query.
            if (!container.length) {
                const fallback = document.querySelector('#questions .questions-list') || document.querySelector('.questions-list');
                if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: fallback queryAll length:', document.querySelectorAll('.questions-list').length);
                if (fallback) {
                    container = $(fallback);
                } else {
                    if (PRODUCT_PAGE_DEBUG) console.warn('renderQuestions: could not find .questions-list in DOM');
                    return; // nothing to render into
                }
            }
            container.empty();

            if (!questions || questions.length === 0) {
                if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: no questions, showing placeholder');
                container.html('<div class="no-questions"><i class="fas fa-question-circle"></i><p>No hay preguntas sobre este producto. Sé el primero en preguntar.</p></div>');
                return;
            }

            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions debug:', typeof questions, Array.isArray(questions), questions);
            try {
            questions.forEach(q => {
                const userName = q.user_name || 'Usuario';
                // Prefer a known existing placeholder -> default-avatar.png
                const userImage = q.user_image ? '<?= BASE_URL ?>/' + q.user_image : '<?= BASE_URL ?>/images/default-avatar.png';

                const qItem = $('<div>').addClass('question-item').attr('data-question-id', q.id);
                const qMeta = $('<div>').addClass('question-meta');
                const avatar = $('<div>').addClass('user-avatar').append($('<img>').attr('src', userImage).attr('alt', userName));
                const userInfo = $('<div>').addClass('user-info').append($('<strong>').text(userName)).append($('<span>').addClass('time').text(q.created_at ? new Date(q.created_at).toLocaleString() : ''));
                qMeta.append(avatar).append(userInfo);

                const ratingValue = typeof q.rating !== 'undefined' ? parseFloat(q.rating) : NaN;
                if (!Number.isNaN(ratingValue) && ratingValue > 0) {
                    const ratingLabel = formatRatingLabel(ratingValue);
                    const ratingEl = $('<div>').addClass('user-rating question-rating')
                        .attr('aria-label', `Calificación: ${ratingLabel} de 5`)
                        .html(buildReviewStars(ratingValue));
                    qMeta.append(ratingEl);
                }

                const qText = $('<div>').addClass('question-text').append($('<p>').html($('<div/>').text(q.question).html()));

                qItem.append(qMeta).append(qText);

                if (q.answers && q.answers.length) {
                    const answersList = $('<div>').addClass('answers-list');
                    q.answers.forEach(a => {
                        const answerItem = $('<div>').addClass('answer-item');
                        const meta = $('<div>').addClass('answer-meta');
                        const ansUserName = a.user_name || 'Usuario';
                        const ansUserImage = a.user_image ? '<?= BASE_URL ?>/' + a.user_image : '<?= BASE_URL ?>/images/default-avatar.png';
                        const ansAvatar = $('<div>').addClass('user-avatar').append($('<img>').attr('src', ansUserImage).attr('alt', ansUserName));
                        meta.append(ansAvatar);
                        meta.append($('<strong>').text(a.user_name || 'Usuario'));
                        if (a.is_seller) meta.append(' ').append($('<span>').addClass('badge seller').text('Vendedor'));
                        meta.append(' ').append($('<span>').addClass('time').text(a.created_at ? new Date(a.created_at).toLocaleString() : ''));
                        answerItem.append(meta).append($('<p>').html($('<div/>').text(a.answer).html()));
                        answersList.append(answerItem);
                    });
                    qItem.append(answersList);
                }

                if (<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                    const actions = $('<div>').addClass('question-actions');
                    // Only show the responder button to admins
                    if (currentUserRole === 'admin') {
                        actions.append($('<button>').addClass('answer-btn btn small').text('Responder'));
                        // Append an answer form for inline admin replies
                        const ansForm = $('<div>').addClass('answer-form-container').hide().append(
                            $('<form>').addClass('answer-form').attr('method','POST').attr('action','<?= BASE_URL ?>/api/submit_answer.php').append(
                                $('<input>').attr('type','hidden').attr('name','question_id').val(q.id),
                                $('<div>').addClass('form-group').append($('<textarea>').attr('name','answer').attr('rows','3').addClass('form-control').attr('required', true).attr('placeholder','Escribe tu respuesta')),
                                $('<div>').addClass('form-actions').append($('<button>').attr('type','button').addClass('cancel-answer btn small').text('Cancelar'), $('<button>').attr('type','submit').addClass('btn primary small').text('Enviar'))
                            )
                        );
                        qItem.append(ansForm);
                    }
                    // Show edit/delete to the owner of the question
                    if (currentUserId && currentUserId == q.user_id) {
                        actions.append($('<button>').addClass('edit-question btn small').text('Editar'));
                        actions.append($('<button>').addClass('delete-question btn small danger').text('Eliminar'));
                    }
                    qItem.append(actions);
                }

                // Try jQuery append and native append as fallback
                try {
                    container.append(qItem);
                    // Force visibility in case CSS/other scripts hide children
                    container.show();
                    container.find('.question-item').css('display', 'block');
                    // If jQuery append didn't create DOM nodes (some libs may prevent jQuery insertion),
                    // use raw HTML fallback
                    if (container.find('.question-item').length === 0) {
                        if (PRODUCT_PAGE_DEBUG) console.warn('renderQuestions: appended items not visible; using HTML fallback');
                        const fallbackHtml = questions.map(q2 => {
                            const uName = q2.user_name || 'Usuario';
                            const uImg = q2.user_image ? '<?= BASE_URL ?>/' + q2.user_image : '<?= BASE_URL ?>/images/default-avatar.png';
                            const answersHtml = (q2.answers || []).map(a => {
                                const ansName = (a.user_name || 'Usuario');
                                const ansImg = a.user_image ? '<?= BASE_URL ?>/' + a.user_image : '<?= BASE_URL ?>/images/default-avatar.png';
                                return `<div class="answer-item"><div class="answer-meta"><div class="user-avatar"><img src="${ansImg}" alt="${ansName}"></div><strong>${ansName}</strong>${a.is_seller ? ' <span class="badge seller">Vendedor</span>' : ''} <span class="time">${a.created_at ? new Date(a.created_at).toLocaleString() : ''}</span></div><p>${(a.answer || '')}</p></div>`;
                            }).join('');
                            const ratingVal = typeof q2.rating !== 'undefined' ? parseFloat(q2.rating) : NaN;
                            const ratingLabel = !Number.isNaN(ratingVal) && ratingVal > 0 ? formatRatingLabel(ratingVal) : null;
                            const ratingHtml = ratingLabel ? `<div class="user-rating question-rating" aria-label="Calificación: ${ratingLabel} de 5">${buildReviewStars(ratingVal)}</div>` : '';
                            return `<div class="question-item"><div class="question-meta"><div class="user-avatar"><img src="${uImg}" alt="${uName}"></div><div class="user-info"><strong>${uName}</strong><span class="time">${q2.created_at ? new Date(q2.created_at).toLocaleString() : ''}</span></div>${ratingHtml}</div><div class="question-text"><p>${(q2.question || '')}</p></div>${answersHtml}</div>`;
                        }).join('');
                        container.html(fallbackHtml);
                    }
                    if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions debug append: container jQuery length:', container.length, 'native elem:', container.get(0), 'qItem native:', qItem.get(0));
                    if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions debug check find:', container.find('.question-item').length);
                } catch (e) {
                    if (PRODUCT_PAGE_DEBUG) console.warn('jQuery append failed, trying native append', e);
                    try {
                        document.querySelector('.questions-list').appendChild(qItem.get(0));
                    } catch (err) {
                        console.error('native append also failed', err);
                    }
                }
            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: appended qItem for id=', q.id, 'container children now=', container.children().length);
            });
            } catch(e) {
                console.error('renderQuestions loop error:', e);
            }
            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: finished, DOM count=', container.find('.question-item').length);
            // For debugging: log the container children
            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: container children:', container.children().toArray().map(e => e.outerHTML ? e.outerHTML.slice(0,150) : e.innerText));
            if (PRODUCT_PAGE_DEBUG) console.log('renderQuestions: container element:', document.querySelector('.questions-list'));

                // Update tab counter
                const tabBtn = document.querySelector('.tab-btn[data-tab="questions"]');
                if (tabBtn) {
                    const text = tabBtn.textContent.replace(/\(.*\)/, '').trim();
                    tabBtn.textContent = text + ' (' + questions.length + ')';
                }
        }

        function getQuestionsListElement() {
            return document.querySelector('#questions .questions-list') || document.querySelector('.questions-list');
        }

        function injectQuestionsFallback() {
            const existing = getQuestionsListElement();
            if (existing) return existing;

            const tabContent = document.querySelector('.tabs-content');
            if (!tabContent) {
                console.error('Questions fallback: .tabs-content not found');
                return null;
            }

            tabContent.insertAdjacentHTML('beforeend', `
                <div id="questions" class="tab-pane">
                    <div class="questions-header">
                        <h3>Preguntas y respuestas</h3>
                        <button id="ask-question-btn" class="tab-action-btn question-btn">
                            <i class="fas fa-question-circle"></i> Hacer una pregunta
                        </button>
                    </div>
                    <div class="questions-list" data-qa-questions-count="${initialQuestions.length}"></div>
                    <div id="question-form-container" class="question-form-container" style="display:none;">
                        <form id="question-form" method="POST" action="<?= BASE_URL ?>/api/submit_question.php">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div class="form-group">
                                <label for="question-text">Tu pregunta</label>
                                <textarea id="question-text" name="question" rows="3" required placeholder="Escribe tu pregunta sobre este producto (mín. 10 caracteres)"></textarea>
                                <small class="form-note">Las preguntas serán respondidas por el vendedor u otros compradores.</small>
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cancel-question" class="tab-action-btn review-btn">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button type="submit" class="tab-action-btn question-btn">
                                    <i class="fas fa-paper-plane"></i> Enviar pregunta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `);

            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab && activeTab.dataset && activeTab.dataset.tab === 'questions') {
                const injected = document.getElementById('questions');
                if (injected) {
                    injected.classList.add('active');
                    injected.style.display = 'block';
                }
            }

            return getQuestionsListElement();
        }

        function logQuestionsMarkupSnapshot() {
            try {
                const listing = document.querySelector('#questions .questions-list');
                    if (PRODUCT_PAGE_DEBUG) console.log('post-render .questions-list innerHTML length:', listing ? listing.innerHTML.length : 'no-listing');
            } catch (err) {
                console.error('post-render debugging failed', err);
            }
        }

        function bootstrapQuestionsSection(attempt = 0) {
            const container = getQuestionsListElement();
            if (container) {
                renderQuestions(initialQuestions);
                logQuestionsMarkupSnapshot();
                return;
            }

            if (attempt < 8) {
                setTimeout(() => bootstrapQuestionsSection(attempt + 1), 100);
                return;
            }

            const fallback = injectQuestionsFallback();
            if (fallback) {
                renderQuestions(initialQuestions);
                logQuestionsMarkupSnapshot();
            } else {
                console.error('Unable to prepare questions section; .questions-list is still missing.');
            }
        }

        // On load, ensure questions are rendered consistently with the server data
        try {
            // initialQuestions and removeQuestionFromInitial are declared above
            bootstrapQuestionsSection();
            
            // Ensure questions are re-rendered when the Questions tab is activated
            $(document).on('click', '.tab-btn[data-tab="questions"]', function() {
                if (PRODUCT_PAGE_DEBUG) console.log('Questions tab clicked, rendering questions if missing');
                try { renderQuestions(initialQuestions); } catch (e) { console.error('render on tab click failed', e); }
            });

            // If the container is dynamically injected or later emptied by other scripts, use a MutationObserver
            const questionsParent = document.querySelector('#questions');
            if (questionsParent && typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver((mutations, obs) => {
                    const qlist = questionsParent.querySelector('.questions-list');
                    if (qlist && qlist.children.length === 0 && initialQuestions.length > 0) {
                        if (PRODUCT_PAGE_DEBUG) console.log('MutationObserver detected empty .questions-list; re-rendering questions');
                        try { renderQuestions(initialQuestions); } catch (e) { console.error('Observer render failed', e); }
                        // stop observing after a successful re-render to avoid loops
                        obs.disconnect();
                    }
                });

                observer.observe(questionsParent, { childList: true, subtree: true });
            }

            // Fallback: observe the whole body if .questions-list is not yet in DOM — this catches cases where
            // another script rewrites the tabs after DOMContentLoaded
            if (!document.querySelector('.questions-list')) {
                if (PRODUCT_PAGE_DEBUG) console.log('No .questions-list detected initially; adding body-level observer');
                const bodyObserver = new MutationObserver((mutations, obs) => {
                    const node = document.querySelector('.questions-list');
                    if (node) {
                        if (PRODUCT_PAGE_DEBUG) console.log('Body observer found .questions-list; running renderQuestions');
                        try {
                            renderQuestions(initialQuestions);
                        } catch (e) { console.error('Body observer render failed', e); }
                        obs.disconnect();
                    }
                });

                bodyObserver.observe(document.body, { childList: true, subtree: true });
            }
        } catch (e) {
            console.error('Error rendering initial questions:', e);
        }

        $(document).on('click', '.cancel-answer', function() {
            $(this).closest('.answer-form-container').slideUp();
        });

        // Submit answer via AJAX for inline admin answer forms
        $(document).on('submit', '.answer-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const qId = form.find('input[name="question_id"]').val();
            const answerText = form.find('textarea[name="answer"]').val().trim();

            if (!answerText) return showNotification('El texto de la respuesta no puede estar vacío.', 'error');

            fetch(form.attr('action'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ question_id: qId, answer: answerText })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    // Append answer to answers list
                    const qItem = form.closest('.question-item');
                    const answersList = qItem.find('.answers-list');
                    if (!answersList.length) {
                        qItem.append('<div class="answers-list"></div>');
                    }
                    const userName = data.data?.user_name || 'Administrador';
                    const userImage = data.data?.user_image ? '<?= BASE_URL ?>/' + data.data.user_image : '<?= BASE_URL ?>/images/default-avatar.png';
                    const ansDom = $('<div>').addClass('answer-item').append(
                        $('<div>').addClass('answer-meta').append($('<div>').addClass('user-avatar').append($('<img>').attr('src', userImage).attr('alt', userName))).append($('<div>').addClass('user-info').append($('<strong>').text(userName)).append($('<span>').addClass('time').text(new Date().toLocaleString()))),
                        $('<p>').text(answerText)
                    );
                    qItem.find('.answers-list').append(ansDom);
                    form.closest('.answer-form-container').slideUp();
                    showNotification('Respuesta enviada', 'success');
                } else {
                    showNotification(data.message || 'Error al enviar respuesta', 'error');
                }
            }).catch(err => { console.error(err); showNotification('Error de conexión', 'error'); });

        });

        // Mejorar interactividad de pestañas
        function updateActiveTabIndicator() {
            const activeTab = $('.tab-btn.active');
            const indicator = $('.active-indicator');

            if (activeTab.length && !indicator.length) {
                $('.tabs-header').append('<div class="active-indicator"></div>');
            }

            if (activeTab.length) {
                $('.active-indicator').css({
                    width: activeTab.outerWidth(),
                    left: activeTab.position().left
                });
            }
        }

        // Inicializar indicador
        updateActiveTabIndicator();

        // Actualizar indicador al cambiar tamaño de ventana
        $(window).resize(function() {
            updateActiveTabIndicator();
        });

        // Mejorar animación al cambiar pestañas
        $('.tab-btn').click(function() {
            const tabId = $(this).data('tab');

            // Agregar clase de transición
            $('.tabs-content').addClass('transitioning');

            // Cambiar pestaña activa
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');

            // Actualizar indicador
            updateActiveTabIndicator();

            // Ocultar todas las pestañas primero
            $('.tab-pane').removeClass('active').hide();

            // Mostrar la pestaña seleccionada después de un breve retraso
            setTimeout(() => {
                $('#' + tabId).addClass('active').show();
                $('.tabs-content').removeClass('transitioning');
            }, 50);
        });


        // Añadir al carrito
        $('#add-to-cart').click(function(e) {
            e.preventDefault();

            if (!selectedVariantId) {
                showNotification('Por favor selecciona una variante válida', 'error');
                return;
            }

            // Obtener el color_variant_id correcto para la talla seleccionada
            const colorVariantId = variantsByColor[selectedColorId].color_variant_id;

            $.ajax({
                url: '<?= BASE_URL ?>/tienda/api/cart/buy-now.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    product_id: productId,
                    color_variant_id: colorVariantId,
                    size_variant_id: selectedVariantId,
                    quantity: selectedQuantity
                }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        updateCartCount();

                        if ($('.mini-cart').is(':visible')) {
                            updateMiniCart();
                        }
                    } else {
                        showNotification(response.error || 'Error al añadir al carrito', 'error');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Error al añadir al carrito';
                    if (xhr.status === 401) {
                        errorMsg = 'Debes iniciar sesión para añadir productos al carrito';
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    showNotification(errorMsg, 'error');
                    console.error('Error:', xhr.responseJSON || xhr.statusText);
                }
            });
        });

        // Comprar ahora -> agregar al carrito y redirigir al paso de envío
        $('#buy-now').click(function(e) {
            e.preventDefault();

            if (!selectedVariantId) {
                showNotification('Por favor selecciona una variante válida', 'error');
                return;
            }

            const colorVariantId = variantsByColor[selectedColorId].color_variant_id;

            // Mostrar un estado de carga en el botón
            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
            btn.prop('disabled', true);

            $.ajax({
                url: '<?= BASE_URL ?>/tienda/api/cart/add-cart.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    product_id: productId,
                    color_variant_id: colorVariantId,
                    size_variant_id: selectedVariantId,
                    quantity: selectedQuantity
                }),
                dataType: 'json',
                success: function(response) {
                    if (!response || !response.success) {
                        showNotification(response?.error || 'Error al procesar compra', 'error');
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                        return;
                    }

                    // Si no está logueado, redirigir a login y luego a la página de envío
                    if (!currentUserId) {
                        // Mantener el carrito en la sesión actual; después de login, el usuario será redirigido a /tienda/pagos/envio.php
                        const redirectUrl = encodeURIComponent('<?= BASE_URL ?>/tienda/pagos/envio.php');
                        window.location.href = '<?= BASE_URL ?>/auth/login.php?redirect=' + redirectUrl;
                        return;
                    }

                    // Redirigir al checkout (envío) — el paso de envío cargará el último carrito del usuario
                    // We've stored the buy-now cart id in the session on the server. Redirect to envio.
                    window.location.href = '<?= BASE_URL ?>/tienda/pagos/envio.php?buy_now=1&cart_id=' + encodeURIComponent(response.cart_id);
                },
                error: function(xhr) {
                    let errorMsg = 'Error al procesar compra';
                    if (xhr.status === 401) {
                        // No autenticado — redirigir a login
                        const redirectUrl = encodeURIComponent('<?= BASE_URL ?>/tienda/pagos/envio.php');
                        window.location.href = '<?= BASE_URL ?>/auth/login.php?redirect=' + redirectUrl;
                        return;
                    }

                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    showNotification(errorMsg, 'error');
                },
                complete: function() {
                    // Restaurar estado del botón si no hubo redirección
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                }
            });
        });

 





    });
</script>