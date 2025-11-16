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
        const currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
        const currentUserRole = <?= json_encode($_SESSION['user_role'] ?? null) ?>;




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

        // Enviar pregunta via AJAX (evita recarga completa)
        $('#question-form').submit(function(e) {
            e.preventDefault();

            const form = $(this);
            const question = $('#question-text').val().trim();
            const productId = form.find('input[name="product_id"]').val();

            if (question.length < 10) {
                showNotification('La pregunta debe tener al menos 10 caracteres', 'error');
                return;
            }

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

                    // Optionally: añadir la pregunta recibida a la lista (si vino en la respuesta)
                    // Refresh questions list from server to ensure up-to-date display
                    fetch('<?= BASE_URL ?>/api/get_questions.php?product_id=' + productId)
                        .then(r => r.json())
                        .then(newRes => {
                            if (newRes.success) {
                                renderQuestions(newRes.data || []);
                            } else {
                                console.error('Could not fetch updated questions', newRes.error);
                            }
                        }).catch(err => console.error(err));
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

        // Editar pregunta (sólo para autor) - muestra editor inline y usa alertas del usuario para feedback
        $(document).on('click', '.edit-question', function() {
            const qItem = $(this).closest('.question-item');
            const qId = qItem.data('question-id');
            const currentText = qItem.find('.question-text p').text().trim();

            // Si ya existe un editor, no añadir otro
            if (qItem.find('.edit-question-form').length) return;

            const editor = $(
                `<div class="edit-question-form">
                    <textarea class="edit-question-textarea">${$('<div/>').text(currentText).html()}</textarea>
                    <div class="edit-question-actions">
                        <button class="btn small secondary cancel-edit">Cancelar</button>
                        <button class="btn small primary save-edit">Guardar</button>
                    </div>
                </div>`
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
                        qItem.find('.question-text p').text(newText);
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
                        } catch (e) { console.warn('Could not update questions count', e); }
                    } else {
                        showNotification(data.message || 'Error al eliminar pregunta', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    showUserError('Error de conexión');
                }
            });
        });

        function renderQuestions(questions) {
            console.log('renderQuestions called, questions length:', questions.length);
            let container = $('.questions-list');
            
            // If the regular selector didn't find the container, try a scoped selector or a direct DOM query.
            if (!container.length) {
                const fallback = document.querySelector('#questions .questions-list') || document.querySelector('.questions-list');
                console.log('renderQuestions: fallback queryAll length:', document.querySelectorAll('.questions-list').length);
                if (fallback) {
                    container = $(fallback);
                } else {
                    console.warn('renderQuestions: could not find .questions-list in DOM');
                    return; // nothing to render into
                }
            }
            container.empty();

            if (!questions || questions.length === 0) {
                console.log('renderQuestions: no questions, showing placeholder');
                container.html('<div class="no-questions"><i class="fas fa-question-circle"></i><p>No hay preguntas sobre este producto. Sé el primero en preguntar.</p></div>');
                return;
            }

            console.log('renderQuestions debug:', typeof questions, Array.isArray(questions), questions);
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

                const qText = $('<div>').addClass('question-text').append($('<p>').html($('<div/>').text(q.question).html()));

                qItem.append(qMeta).append(qText);

                if (q.answers && q.answers.length) {
                    const answersList = $('<div>').addClass('answers-list');
                    q.answers.forEach(a => {
                        const answerItem = $('<div>').addClass('answer-item');
                        const meta = $('<div>').addClass('answer-meta');
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
                        console.warn('renderQuestions: appended items not visible; using HTML fallback');
                        const fallbackHtml = questions.map(q2 => {
                            const uName = q2.user_name || 'Usuario';
                            const uImg = q2.user_image ? '<?= BASE_URL ?>/' + q2.user_image : '<?= BASE_URL ?>/images/default-avatar.png';
                            const answersHtml = (q2.answers || []).map(a => `<div class="answer-item"><div class="answer-meta"><strong>${(a.user_name||'Usuario')}</strong>${a.is_seller ? ' <span class="badge seller">Vendedor</span>' : ''} <span class="time">${a.created_at ? new Date(a.created_at).toLocaleString() : ''}</span></div><p>${(a.answer || '')}</p></div>`).join('');
                            return `<div class="question-item"><div class="question-meta"><div class="user-avatar"><img src="${uImg}" alt="${uName}"></div><div class="user-info"><strong>${uName}</strong><span class="time">${q2.created_at ? new Date(q2.created_at).toLocaleString() : ''}</span></div></div><div class="question-text"><p>${(q2.question || '')}</p></div>${answersHtml}</div>`;
                        }).join('');
                        container.html(fallbackHtml);
                    }
                    console.log('renderQuestions debug append: container jQuery length:', container.length, 'native elem:', container.get(0), 'qItem native:', qItem.get(0));
                    console.log('renderQuestions debug check find:', container.find('.question-item').length);
                } catch (e) {
                    console.warn('jQuery append failed, trying native append', e);
                    try {
                        document.querySelector('.questions-list').appendChild(qItem.get(0));
                    } catch (err) {
                        console.error('native append also failed', err);
                    }
                }
            console.log('renderQuestions: appended qItem for id=', q.id, 'container children now=', container.children().length);
            });
            } catch(e) {
                console.error('renderQuestions loop error:', e);
            }
            console.log('renderQuestions: finished, DOM count=', container.find('.question-item').length);
            // For debugging: log the container children
            console.log('renderQuestions: container children:', container.children().toArray().map(e => e.outerHTML ? e.outerHTML.slice(0,150) : e.innerText));
            console.log('renderQuestions: container element:', document.querySelector('.questions-list'));

                // Update tab counter
                const tabBtn = document.querySelector('.tab-btn[data-tab="questions"]');
                if (tabBtn) {
                    const text = tabBtn.textContent.replace(/\(.*\)/, '').trim();
                    tabBtn.textContent = text + ' (' + questions.length + ')';
                }
        }

        // On load, ensure questions are rendered consistently with the server data
        try {
            const initialQuestions = <?= json_encode($questionsData ?: []) ?>;
            // Delay slightly to avoid race with layout scripts and ensure the .questions-list is in DOM
            setTimeout(() => {
                console.log('renderQuestions delayed call, initial questions', initialQuestions.length);
                // If .questions-list is missing but server returned questions, create a fallback container
                if (!document.querySelector('.questions-list') && initialQuestions.length) {
                    console.warn('No .questions-list found - injecting fallback #questions/.questions-list');
                    const tabContent = document.querySelector('.tabs-content');
                    if (tabContent) {
                        tabContent.insertAdjacentHTML('beforeend', `
                            <div id="questions" class="tab-pane">
                                <div class="questions-header"><h3>Preguntas y respuestas</h3></div>
                                <div class="questions-list" data-qa-questions-count="${initialQuestions.length}"></div>
                            </div>
                        `);
                        // If the Questions tab is already active, ensure the injected pane is visible
                        try {
                            const activeTab = document.querySelector('.tab-btn.active');
                            if (activeTab && activeTab.dataset && activeTab.dataset.tab === 'questions') {
                                const injected = document.getElementById('questions');
                                if (injected) {
                                    injected.classList.add('active');
                                    injected.style.display = 'block';
                                }
                            }
                        } catch (err) { console.warn('Error checking active tab after injection', err); }
                    } else {
                        console.warn('No .tabs-content found to insert fallback questions');
                    }
                }
                renderQuestions(initialQuestions);
                // After rendering, log the computed HTML for debugging
                try {
                    const listing = document.querySelector('#questions .questions-list');
                    console.log('post-render .questions-list innerHTML length:', listing ? listing.innerHTML.length : 'no-listing');
                } catch (err) { console.error('post-render debugging failed', err); }
            }, 50);
            
            // Ensure questions are re-rendered when the Questions tab is activated
            $(document).on('click', '.tab-btn[data-tab="questions"]', function() {
                console.log('Questions tab clicked, rendering questions if missing');
                try { renderQuestions(initialQuestions); } catch (e) { console.error('render on tab click failed', e); }
            });

            // If the container is dynamically injected or later emptied by other scripts, use a MutationObserver
            const questionsParent = document.querySelector('#questions');
            if (questionsParent && typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver((mutations, obs) => {
                    const qlist = questionsParent.querySelector('.questions-list');
                    if (qlist && qlist.children.length === 0 && initialQuestions.length > 0) {
                        console.log('MutationObserver detected empty .questions-list; re-rendering questions');
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
                console.log('No .questions-list detected initially; adding body-level observer');
                const bodyObserver = new MutationObserver((mutations, obs) => {
                    const node = document.querySelector('.questions-list');
                    if (node) {
                        console.log('Body observer found .questions-list; running renderQuestions');
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