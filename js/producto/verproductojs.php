<script>
    $(document).ready(function() {
        // Variables globales
        let selectedColorId = <?= $variantsData['defaultColorId'] ?? 'null' ?>;
        let selectedSizeId = <?= $variantsData['defaultSizeId'] ?? 'null' ?>;
        let selectedVariantId = <?= $variantsData['defaultVariant']['variant_id'] ?? 'null' ?>;
        let selectedQuantity = 1;
        let variantsByColor = <?= json_encode($variantsData['variantsByColor']) ?>;
        let productId = <?= $product['id'] ?? 'null' ?>;
        let isInWishlist = false;




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
            const notification = $(`
        <div class="floating-notification ${type}">
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="close-notification">&times;</button>
        </div>
    `);

            $('body').append(notification);

            // Auto cerrar después de 3 segundos
            setTimeout(() => {
                notification.addClass('fade-out');
                setTimeout(() => notification.remove(), 500);
            }, 3000);

            // Cerrar manualmente
            notification.find('.close-notification').click(function() {
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

                // Actualizar disponibilidad
                updateStockInfo(colorData.sizes[firstAvailableSize].quantity);
            } else {
                $('#size-options').html('<div class="no-sizes">No hay tallas disponibles para este color</div>');
                $('#selected-size-name').text('No disponible');
                $('#product-sku').text('N/A');
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
        });

        // Actualizar información de stock
        function updateStockInfo(quantity) {
            const stockInfo = $('.stock-info');
            const quantityInput = $('#product-quantity');

            // Actualizar el texto de stock
            if (quantity > 5) {
                stockInfo.html('<i class="fas fa-check-circle in-stock"></i> Disponible (' + quantity + ' unidades)');
                quantityInput.attr('max', Math.min(quantity, 10));
            } else if (quantity > 0) {
                stockInfo.html('<i class="fas fa-exclamation-circle low-stock"></i> Últimas ' + quantity + ' unidades');
                quantityInput.attr('max', quantity);

                // Ajustar cantidad si es mayor al nuevo máximo
                if (parseInt(quantityInput.val()) > quantity) {
                    quantityInput.val(quantity);
                    selectedQuantity = quantity;
                }
            } else {
                stockInfo.html('<i class="fas fa-times-circle out-of-stock"></i> Agotado');
                quantityInput.attr('max', 0);
            }
        }

        // Control de cantidad
        $('.qty-btn.minus').click(function() {
            const input = $('#product-quantity');
            let value = parseInt(input.val());
            if (value > 1) {
                input.val(value - 1);
                selectedQuantity = value - 1;
            }
        });

        $('.qty-btn.plus').click(function() {
            const input = $('#product-quantity');
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
        $('#write-review-btn').click(function() {
            $('#review-form-container').slideDown();
        });

        $('#cancel-review').click(function() {
            $('#review-form-container').slideUp();
        });

        // Rating con estrellas
        $('.rating-input .fa-star').hover(function() {
            const rating = $(this).data('rating');
            $('.rating-input .fa-star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).removeClass('far').addClass('fas');
                } else {
                    $(this).removeClass('fas').addClass('far');
                }
            });
        }, function() {
            const currentRating = $('#rating-value').val();
            $('.rating-input .fa-star').each(function() {
                if ($(this).data('rating') <= currentRating) {
                    $(this).removeClass('far').addClass('fas');
                } else {
                    $(this).removeClass('fas').addClass('far');
                }
            });
        });

        $('.rating-input .fa-star').click(function() {
            const rating = $(this).data('rating');
            $('#rating-value').val(rating);
        });

        // Formulario de pregunta
        $('#ask-question-btn').click(function() {
            $('#question-form-container').slideDown();
        });

        $('#cancel-question').click(function() {
            $('#question-form-container').slideUp();
        });

        // Botón responder pregunta
        $(document).on('click', '.answer-btn', function() {
            $(this).siblings('.answer-form-container').slideToggle();
        });

        $(document).on('click', '.cancel-answer', function() {
            $(this).closest('.answer-form-container').slideUp();
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

 





    });
</script>