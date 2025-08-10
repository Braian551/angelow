<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let currentPage = 1;
    let isLoading = false;
    let productToDelete = null;
    
    // Elementos del DOM
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-filter');
    const genderFilter = document.getElementById('gender-filter');
    const orderFilter = document.getElementById('order-filter');
    const productsContainer = document.getElementById('products-container');
    const resultsCount = document.getElementById('results-count');
    const paginationContainer = document.getElementById('pagination-container');
    const searchForm = document.getElementById('search-form');
    
    // Cargar productos al inicio
    loadProducts();
    
    // Función para cargar productos con AJAX
    function loadProducts(page = 1) {
        if (isLoading) return;
        
        isLoading = true;
        currentPage = page;
        productsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</div>';
        
        const params = new URLSearchParams();
        if (searchInput.value) params.append('search', searchInput.value);
        if (categoryFilter.value) params.append('category', categoryFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (genderFilter.value) params.append('gender', genderFilter.value);
        params.append('order', orderFilter.value);
        params.append('page', page);
        
        const apiUrl = `<?= BASE_URL ?>/ajax/admin/productos/productsearchadmin.php?${params.toString()}`;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                if (!data.products || !Array.isArray(data.products)) {
                    throw new Error('Formato de datos inválido');
                }
                
                renderProducts(data.products);
                updateResultsCount(data.meta.total, data.products.length);
                renderPagination(data.meta.total, page);
            })
            .catch(error => {
                console.error('Error en loadProducts:', error);
                showAlert('error', 'Error al cargar productos: ' + error.message);
                productsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error al cargar productos</h3>
                        <p>${error.message}</p>
                        <button onclick="window.location.reload()" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Recargar
                        </button>
                    </div>
                `;
            })
            .finally(() => {
                isLoading = false;
            });
    }
    
    // Función para renderizar productos
    function renderProducts(products) {
        if (!products || products.length === 0) {
            productsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No se encontraron productos</h3>
                    <p>No hay productos que coincidan con tus criterios de búsqueda.</p>
                    <a href="<?= BASE_URL ?>/admin/subproducto.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar primer producto
                    </a>
                </div>
            `;
            return;
        }
        
        let html = '';
        products.forEach(product => {
            const statusClass = product.is_active ? '' : 'inactive';
            const genderIcon = product.gender === 'niño' ? 'male' : 
                             (product.gender === 'niña' ? 'female' : 'child');
            
            // Usar la imagen principal del producto
            const imageUrl = product.primary_image || '<?= BASE_URL ?>/images/default-product.jpg';
            
            html += `
                <div class="product-card ${statusClass}">
                    <div class="product-image">
                        <img src="${imageUrl}" alt="${product.name}" 
                             onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'">
                        
                        <div class="product-badges">
                            ${product.total_stock <= 0 ? 
                                `<span class="badge badge-danger">Agotado</span>` : 
                                (product.total_stock < 5 ? `<span class="badge badge-warning">Bajo stock</span>` : '')}
                            
                            ${!product.is_active ? `<span class="badge badge-secondary">Inactivo</span>` : ''}
                        </div>
                        
                        <div class="product-actions">
                            <a href="<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}" 
                               class="btn-action btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-action btn-quick-view" 
                                    data-id="${product.id}" title="Vista rápida">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-delete" 
                                    data-id="${product.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h3>${product.name}</h3>
                        <p class="product-meta">
                            <span class="category">
                                <i class="fas fa-tag"></i> ${product.category_name || 'Sin categoría'}
                            </span>
                            <span class="gender">
                                <i class="fas fa-${genderIcon}"></i> 
                                ${product.gender.charAt(0).toUpperCase() + product.gender.slice(1)}
                            </span>
                        </p>
                        
                        <div class="product-stats">
                            <div class="stat-item">
                                <i class="fas fa-layer-group"></i>
                                <span>${product.variant_count} variantes</span>
                            </div>
                            
                            <div class="stat-item">
                                <i class="fas fa-boxes"></i>
                                <span>${product.total_stock} en stock</span>
                            </div>
                            
                            <div class="stat-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span>
                                    $${Number(product.min_price).toFixed(2)} - $${Number(product.max_price).toFixed(2)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        productsContainer.innerHTML = html;
        assignButtonEvents();
    }
    
    // Actualizar contador de resultados
    function updateResultsCount(total, showing) {
        resultsCount.textContent = `Mostrando ${showing} de ${total} productos`;
    }
    
    // Renderizar paginación
    function renderPagination(totalProducts, currentPage) {
        const perPage = 12;
        const totalPages = Math.ceil(totalProducts / perPage);
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let html = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (currentPage > 1) {
            html += `<a href="#" data-page="1" class="page-link"><i class="fas fa-angle-double-left"></i></a>`;
            html += `<a href="#" data-page="${currentPage - 1}" class="page-link"><i class="fas fa-angle-left"></i></a>`;
        }
        
        if (startPage > 1) {
            html += `<span class="page-dots">...</span>`;
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<a href="#" data-page="${i}" class="page-link ${i === currentPage ? 'active' : ''}">${i}</a>`;
        }
        
        if (endPage < totalPages) {
            html += `<span class="page-dots">...</span>`;
        }
        
        if (currentPage < totalPages) {
            html += `<a href="#" data-page="${currentPage + 1}" class="page-link"><i class="fas fa-angle-right"></i></a>`;
            html += `<a href="#" data-page="${totalPages}" class="page-link"><i class="fas fa-angle-double-right"></i></a>`;
        }
        
        paginationContainer.innerHTML = html;
        
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page)) {
                    loadProducts(page);
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }
            });
        });
    }
    
    // Asignar eventos a los botones
    function assignButtonEvents() {
        // Vista rápida
        document.querySelectorAll('.btn-quick-view').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const quickViewUrl = `<?= BASE_URL ?>/admin/api/productos/get_product_details.php?id=${productId}`;
                
                fetch(quickViewUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error en los datos de vista rápida');
                        }

                        document.getElementById('quick-view-content').innerHTML = `
                            <div class="quick-view-grid">
                                <div class="quick-view-images">
                                    ${data.images && data.images.length > 0 ? 
                                        data.images.map(img => `
                                            <img src="<?= BASE_URL ?>/${img.image_path}" alt="${img.alt_text || 'Imagen del producto'}" 
                                                 onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'">
                                        `).join('') : 
                                        `<div class="no-image"><i class="fas fa-image"></i> No hay imágenes</div>`}
                                </div>
                                <div class="quick-view-details">
                                    <h4>${data.product.name}</h4>
                                    <p><strong>Marca:</strong> ${data.product.brand || 'N/A'}</p>
                                    <p><strong>Categoría:</strong> ${data.product.category_name || 'N/A'}</p>
                                    <p><strong>Género:</strong> ${data.product.gender || 'N/A'}</p>
                                    <p><strong>Estado:</strong> ${data.product.is_active ? 'Activo' : 'Inactivo'}</p>
                                    <p><strong>Variantes:</strong> ${data.variants ? data.variants.length : 0}</p>
                                    <p><strong>Stock total:</strong> ${data.total_stock || 0}</p>
                                    <p><strong>Precios:</strong> $${Number(data.min_price || 0).toFixed(2)} - $${Number(data.max_price || 0).toFixed(2)}</p>
                                    <p><strong>Creado:</strong> ${new Date(data.product.created_at).toLocaleDateString()}</p>
                                    <p><strong>Descripción:</strong> ${data.product.description || 'N/A'}</p>
                                </div>
                            </div>
                            ${data.variants && data.variants.length > 0 ? `
                            <div class="quick-view-variants">
                                <h5>Variantes Disponibles</h5>
                                <table>
                                    <thead>
                                        <tr>
                                      
                                            <th>Talla</th>
                                            <th>Color</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.variants.map(variant => `
                                            <tr>
                                             
                                                <td>${variant.size_name || 'N/A'}</td>
                                                <td>${variant.color_name || 'N/A'}</td>
                                                <td>$${Number(variant.price || 0).toFixed(2)}</td>
                                                <td>${variant.quantity || 0}</td>
                                                <td>${variant.is_active ? 'Activo' : 'Inactivo'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            ` : '<p>No hay variantes disponibles</p>'}
                        `;
                        
                        document.getElementById('edit-product-btn').href = `<?= BASE_URL ?>/admin/editproducto.php?id=${productId}`;
                        document.getElementById('quick-view-modal').classList.add('active');
                    })
                    .catch(error => {
                        console.error('Error en vista rápida:', error);
                        showAlert('error', 'Error al cargar los detalles: ' + error.message);
                    });
            });
        });
        
        // Eliminar producto
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                productToDelete = this.getAttribute('data-id');
                document.getElementById('delete-modal').classList.add('active');
            });
        });
    }
    
    // Event listeners para búsqueda y filtros
    searchInput.addEventListener('input', debounce(() => loadProducts(), 500));
    categoryFilter.addEventListener('change', () => loadProducts());
    statusFilter.addEventListener('change', () => loadProducts());
    genderFilter.addEventListener('change', () => loadProducts());
    orderFilter.addEventListener('change', () => loadProducts());
    
    // Evitar envío del formulario tradicional
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadProducts();
    });
    
    // Eliminar producto
    document.getElementById('confirm-delete').addEventListener('click', function() {
        if (productToDelete) {
            const deleteUrl = `<?= BASE_URL ?>/admin/api/delete_product.php?id=${productToDelete}`;
            
            fetch(deleteUrl, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al eliminar el producto');
                }
                
                showAlert('success', 'Producto eliminado correctamente');
                document.getElementById('delete-modal').classList.remove('active');
                loadProducts(currentPage);
            })
            .catch(error => {
                console.error('Error al eliminar:', error);
                showAlert('error', 'Error al eliminar: ' + error.message);
            });
        }
    });
    
    // Cerrar modales
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });
    
    // Función debounce para optimizar búsqueda
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }

    // Exportar productos
    document.getElementById('export-products').addEventListener('click', function() {
        const params = new URLSearchParams();
        if (searchInput.value) params.append('search', searchInput.value);
        if (categoryFilter.value) params.append('category', categoryFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (genderFilter.value) params.append('gender', genderFilter.value);
        params.append('order', orderFilter.value);
        params.append('export', 'true');
        
        window.location.href = `<?= BASE_URL ?>/admin/api/export_products.php?${params.toString()}`;
    });
});
</script>