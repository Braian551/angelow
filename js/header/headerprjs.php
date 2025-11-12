<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('header-search');
        const searchResults = document.getElementById('search-results');
        let searchTimeout;

        // Variable para almacenar términos buscados previamente
        let searchedTerms = [];

        // Función para obtener el historial de búsqueda
        const fetchSearchHistory = async () => {
            try {
                const response = await fetch(`<?= BASE_URL ?>/ajax/busqueda/get_search_history.php`);

                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('La respuesta no es JSON');
                }

                const data = await response.json();

                if (data && Array.isArray(data.terms)) {
                    searchedTerms = data.terms
                        .filter(term => term && typeof term === 'string')
                        .map(term => term.toLowerCase());
                }
            } catch (error) {
                console.error('Error al obtener historial:', error);
                searchedTerms = [];
            }
        };

        // Obtener historial solo si el usuario está logueado
        <?php if (isset($_SESSION['user_id'])): ?>
            fetchSearchHistory();
        <?php endif; ?>

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const term = this.value.trim();

            if (term.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`<?= BASE_URL ?>/ajax/busqueda/search.php?term=${encodeURIComponent(term)}`);

                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta de búsqueda no es JSON');
                    }

                    const data = await response.json();

                    let html = '';

                    // Manejar sugerencias de productos
                    if (data?.suggestions?.length > 0) {
                        const item = data.suggestions[0];
                        if (item?.slug && item?.image_path) {
                            html += `
                            <a href="<?= BASE_URL ?>/producto/verproducto.php?slug=${item.slug}" class="product-item">
                                <img src="<?= BASE_URL ?>/${item.image_path.replace(/^\/+/, '')}" 
                                     alt="${item.name || 'Producto'}">
                                <div class="product-info2">
                                    <div>${item.name || 'Producto'}</div>
                                </div>
                            </a>
                        `;
                        }
                    }

                    // Manejar términos de búsqueda
                    if (data?.terms?.length > 0) {
                        data.terms.slice(0, 5).forEach(term => {
                            if (!term || typeof term !== 'string') return;

                            const termLower = term.toLowerCase();
                            const wasSearched = searchedTerms.includes(termLower);

                            html += `
        <div class="suggestion-item" onclick="window.location.href='<?= BASE_URL ?>/tienda/productos.php?search=${encodeURIComponent(term)}'">
            <i class="fas ${wasSearched ? 'fa-clock' : 'fa-search'}"></i>
            <span>${term}</span>
        </div>
        `;
                        });
                    }

                    if (html) {
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error en la búsqueda:', error);
                    searchResults.style.display = 'none';
                }
            }, 300);
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Guardar búsqueda al enviar el formulario
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const term = searchInput.value.trim();
            if (term.length > 0 && <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
                fetch(`<?= BASE_URL ?>/ajax/busqueda/save_search.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `term=${encodeURIComponent(term)}`
                }).catch(error => console.error('Error al guardar búsqueda:', error));
            }
        });

        // Función para guardar una búsqueda
        const saveSearch = async (term) => {
            try {
                await fetch(`<?= BASE_URL ?>/ajax/busqueda/save_search.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `term=${encodeURIComponent(term)}`
                });
            } catch (error) {
                console.error('Error al guardar búsqueda:', error);
            }
        };

        // Manejar clic en sugerencias
        document.addEventListener('click', function(e) {
            if (e.target.closest('.suggestion-item') || e.target.closest('.product-item')) {
                const suggestionItem = e.target.closest('.suggestion-item, .product-item');
                if (suggestionItem.classList.contains('suggestion-item')) {
                    const term = suggestionItem.querySelector('span').textContent;
                    saveSearch(term);
                }
                // El formulario se enviará automáticamente por el href del enlace
            }
        });

        // Manejar tecla Enter en las sugerencias
        searchInput.addEventListener('keydown', async function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const term = this.value.trim();
                
                // Guardar la búsqueda antes de continuar
                if (term.length > 0 && <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
                    await saveSearch(term);
                }
                
                try {
                    // Primero verificar si hay una coincidencia exacta
                    const response = await fetch(`<?= BASE_URL ?>/ajax/busqueda/search_exact.php?term=${encodeURIComponent(term)}`);
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        // Si hay coincidencia exacta, ir al producto específico
                        if (data.exactMatch && data.product) {
                            window.location.href = `<?= BASE_URL ?>/producto/verproducto.php?slug=${data.product.slug}`;
                            return;
                        }
                    }
                } catch (error) {
                    console.error('Error en búsqueda exacta:', error);
                }
                
                // Si no hay coincidencia exacta, ir a la página de productos con el término de búsqueda
                window.location.href = `<?= BASE_URL ?>/tienda/productos.php?search=${encodeURIComponent(term)}`;
            }
        });

        function updateCartCount() {
            fetch(`<?= BASE_URL ?>/ajax/cart/get_cart_count.php`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartCountElements = document.querySelectorAll('.cart-count');

                        if (data.count > 0) {
                            cartCountElements.forEach(el => {
                                // Animación para el cambio
                                el.style.transform = 'scale(1.2)';
                                setTimeout(() => {
                                    el.textContent = data.count;
                                    el.style.transform = 'scale(1)';
                                }, 200);
                            });
                        } else {
                            // Si no hay items, eliminar el contador
                            cartCountElements.forEach(el => el.remove());
                        }

                        // Actualizar en sessionStorage para persistencia
                        sessionStorage.setItem('lastCartCount', data.count);
                        sessionStorage.setItem('lastCartUpdate', new Date().getTime());
                    }
                })
                .catch(error => console.error('Error al obtener carrito:', error));
        }

        // Verificar si hay un carrito antiguo que necesite actualización
        function checkStaleCart() {
            const lastUpdate = sessionStorage.getItem('lastCartUpdate');
            const lastCount = sessionStorage.getItem('lastCartCount');

            if (lastUpdate && lastCount) {
                const now = new Date().getTime();
                const hoursSinceUpdate = (now - parseInt(lastUpdate)) / (1000 * 60 * 60);

                // Si han pasado más de 12 horas, forzar una actualización
                if (hoursSinceUpdate > 12) {
                    updateCartCount();
                } else {
                    // Usar el valor almacenado mientras se carga el nuevo
                    document.querySelectorAll('.cart-count').forEach(el => {
                        el.textContent = lastCount;
                    });
                }
            }

            // Siempre actualizar al cargar la página
            updateCartCount();
        }

        // Llamar a la función al cargar la página
        checkStaleCart();

        // Actualizar el contador cada 5 minutos
        setInterval(updateCartCount, 5 * 60 * 1000);

        // Función para mostrar mensaje de inicio de sesión requerido
        window.showLoginRequired = function(event, feature) {
            event.preventDefault();
            
            // Crear notificación
            const notification = document.createElement('div');
            notification.className = 'notification error';
            notification.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>Debes iniciar sesión para acceder a ${feature}</span>
            `;
            
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    notification.remove();
                    // Redirigir al login después de mostrar el mensaje
                    window.location.href = '<?= BASE_URL ?>/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }, 500);
            }, 2000);
        };
    });
</script>