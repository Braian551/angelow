/**
 * Gestor de Productos - Admin
 * Maneja toda la lógica de UI para la gestión de productos
 */

class ProductsManager {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.currentPage = 1;
        this.isLoading = false;
        this.currentImages = [];
        
        // Elementos del DOM
        this.elements = {
            productsContainer: document.getElementById('products-container'),
            resultsCount: document.getElementById('results-count'),
            paginationContainer: document.getElementById('pagination-container'),
            searchInput: document.getElementById('search-input'),
            clearSearchBtn: document.getElementById('clear-search'),
            filterForm: document.getElementById('search-form'),
            quickViewModal: document.getElementById('quick-view-modal'),
            imageZoomModal: document.getElementById('image-zoom-modal')
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadProducts();
    }
    
    setupEventListeners() {
        // Búsqueda con debounce
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', this.debounce(() => {
                this.loadProducts();
            }, 500));
        }
        
        // Limpiar búsqueda
        if (this.elements.clearSearchBtn) {
            this.elements.clearSearchBtn.addEventListener('click', () => {
                this.elements.searchInput.value = '';
                this.elements.clearSearchBtn.style.display = 'none';
                this.elements.searchInput.focus();
                this.loadProducts();
            });
        }
        
        // Submit de formulario
        if (this.elements.filterForm) {
            this.elements.filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.loadProducts();
            });
        }
        
        // Cerrar modales
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal-overlay').classList.remove('active');
            });
        });
        
        // Cerrar modal al hacer clic en overlay
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
    }
    
    renderLoadingState() {
        if (!this.elements.productsContainer) return;

        const skeletonCard = `
            <div class="product-skeleton-card">
                <div class="skeleton skeleton-thumb"></div>
                <div class="skeleton-body">
                    <div class="skeleton skeleton-line w-80"></div>
                    <div class="skeleton skeleton-line w-60"></div>
                    <div class="skeleton-tags">
                        <span class="skeleton skeleton-pill"></span>
                        <span class="skeleton skeleton-pill"></span>
                    </div>
                    <div class="skeleton skeleton-line w-70"></div>
                    <div class="skeleton skeleton-line w-40"></div>
                </div>
                <div class="skeleton-actions">
                    <span class="skeleton skeleton-btn"></span>
                    <span class="skeleton skeleton-btn"></span>
                </div>
            </div>
        `;

        const skeletonMarkup = new Array(6).fill(skeletonCard).join('');
        this.elements.productsContainer.innerHTML = `
            <div class="products-skeleton" aria-hidden="true">
                ${skeletonMarkup}
            </div>
        `;
    }

    async loadProducts(page = 1) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.currentPage = page;
        this.renderLoadingState();
        
        try {
            const params = new URLSearchParams(new FormData(this.elements.filterForm));
            params.append('page', page);
            
            const response = await fetch(`${this.baseUrl}/ajax/admin/productos/productsearchadmin.php?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido al cargar productos');
            }
            
            this.renderProducts(data.products);
            this.updateResultsCount(data.meta.total, data.products.length);
            this.renderPagination(data.meta.total, page, data.meta.perPage);
            
        } catch (error) {
            console.error('Error en loadProducts:', error);
            this.elements.productsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error al cargar productos</h3>
                    <p>${error.message}</p>
                    <button onclick="productsManager.loadProducts()" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Reintentar
                    </button>
                </div>
            `;
        } finally {
            this.isLoading = false;
        }
    }
    
    renderProducts(products) {
        if (!products || products.length === 0) {
            this.elements.productsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Intenta ajustar tus filtros o agrega un nuevo producto.</p>
                    <a href="${this.baseUrl}/admin/subproducto.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </a>
                </div>
            `;
            return;
        }
        
        let gridHTML = '<div class="products-admin-grid">';
        
        products.forEach(product => {
            const imageUrl = product.primary_image || `${this.baseUrl}/images/default-product.jpg`;
            const statusText = product.is_active ? 'Activo' : 'Inactivo';
            const statusClass = product.is_active ? 'status-active' : 'status-inactive';
            const priceRange = product.min_price === product.max_price 
                ? `$${Number(product.min_price).toLocaleString('es-CO')}` 
                : `$${Number(product.min_price).toLocaleString('es-CO')} - $${Number(product.max_price).toLocaleString('es-CO')}`;
            
            gridHTML += `
                <div class="product-admin-card" data-id="${product.id}">
                    <div class="product-admin-select">
                        <input type="checkbox" class="select-row" data-id="${product.id}">
                    </div>
                    
                    <div class="product-admin-status">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="product-admin-image">
                        <img src="${imageUrl}" alt="${product.name}" onerror="this.src='${this.baseUrl}/images/default-product.jpg'">
                        <div class="product-admin-overlay">
                            <button class="btn-overlay btn-quick-view" data-id="${product.id}" title="Vista Rápida">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-admin-body">
                        <div class="product-admin-header">
                            <h3 class="product-admin-title">
                                <a href="${this.baseUrl}/admin/editproducto.php?id=${product.id}">${product.name}</a>
                            </h3>
                            <span class="product-admin-id">ID: ${product.id}</span>
                        </div>
                        
                        <div class="product-admin-meta">
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span>${product.category_name || 'Sin categoría'}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-palette"></i>
                                <span>${product.variant_count} variante${product.variant_count !== 1 ? 's' : ''}</span>
                            </div>
                        </div>
                        
                        <div class="product-admin-info">
                            <div class="info-item">
                                <label>Stock Total:</label>
                                <span class="stock-value ${product.total_stock < 10 ? 'low-stock' : ''}">${product.total_stock} unidades</span>
                            </div>
                            <div class="info-item">
                                <label>Precio:</label>
                                <span class="price-value">${priceRange}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="product-admin-actions">
                        <a href="${this.baseUrl}/admin/editproducto.php?id=${product.id}" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                            <span>Editar</span>
                        </a>
                        <button class="btn-action btn-toggle-status ${product.is_active ? 'btn-secondary' : 'btn-success'}" data-id="${product.id}" data-status="${product.is_active}" title="${product.is_active ? 'Desactivar producto' : 'Activar producto'}">
                            <i class="fas fa-${product.is_active ? 'eye-slash' : 'eye'}"></i>
                            <span>${product.is_active ? 'Desactivar' : 'Activar'}</span>
                        </button>
                    </div>
                </div>
            `;
        });
        
        gridHTML += '</div>';
        this.elements.productsContainer.innerHTML = gridHTML;
        
        this.assignButtonEvents();
    }
    
    assignButtonEvents() {
        // Vista rápida
        document.querySelectorAll('.btn-quick-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-id');
                this.openQuickView(productId);
            });
        });
        
        // Toggle status (activar/desactivar)
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-id');
                const currentStatus = btn.getAttribute('data-status') === '1';
                this.confirmToggleStatus(productId, currentStatus);
            });
        });
    }
    
    async openQuickView(productId) {
        try {
            const response = await fetch(`${this.baseUrl}/admin/api/productos/get_product_details.php?id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderQuickView(data);
                this.elements.quickViewModal.classList.add('active');
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al cargar los detalles del producto');
        }
    }
    
    renderQuickView(data) {
        const product = data.product;
        const images = data.images;
        const variants = data.variants;
        
        this.currentImages = images;
        
        let imagesHtml = '';
        if (images.length > 0) {
            const imagesByColor = this.groupImagesByColor(images);
            const primaryImage = images.find(img => img.is_primary == 1) || images[0];
            
            imagesHtml = this.buildGalleryHTML(imagesByColor, primaryImage, product.name);
        }
        
        let variantsHtml = '';
        if (variants.length > 0) {
            variantsHtml = this.buildVariantsHTML(variants);
        }
        
        const html = `
            <div class="quick-view-content">
                ${imagesHtml}
                <div class="quick-view-info">
                    <div class="product-header">
                        <h2>${product.name}</h2>
                        <span class="product-id">ID: ${product.id}</span>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span>Categoría: ${product.category_name || 'Sin categoría'}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-palette"></i>
                            <span>${variants.length} variante${variants.length !== 1 ? 's' : ''}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-boxes"></i>
                            <span>Stock total: ${data.total_stock} unidades</span>
                        </div>
                    </div>
                    
                    <div class="product-description">
                        <h4>Descripción</h4>
                        <p>${product.description || 'Sin descripción'}</p>
                    </div>
                    
                    <div class="product-pricing">
                        <h4>Precios</h4>
                        <p>Rango: $${Number(data.min_price).toLocaleString('es-CO')} - $${Number(data.max_price).toLocaleString('es-CO')}</p>
                    </div>
                    
                    ${variantsHtml}
                </div>
            </div>
        `;
        
        document.getElementById('quick-view-content').innerHTML = html;
        document.getElementById('edit-product-btn').href = `${this.baseUrl}/admin/editproducto.php?id=${product.id}`;
        
        this.setupImageGallery();
    }
    
    groupImagesByColor(images) {
        const imagesByColor = {};
        images.forEach(img => {
            const color = img.color_name || 'General';
            if (!imagesByColor[color]) {
                imagesByColor[color] = [];
            }
            imagesByColor[color].push(img);
        });
        return imagesByColor;
    }
    
    buildGalleryHTML(imagesByColor, primaryImage, productName) {
        const colorButtons = [`<button class="color-filter-btn active" data-color="General"><span class="color-text">Principal</span></button>`];
        
        Object.keys(imagesByColor).forEach(color => {
            if (color !== 'General') {
                const firstImage = imagesByColor[color][0];
                const hexCode = firstImage.hex_code || '#CCCCCC';
                colorButtons.push(`
                    <button class="color-filter-btn" data-color="${color}" title="${color}">
                        <span class="color-circle" style="background-color: ${hexCode};"></span>
                        <span class="color-text">${color}</span>
                    </button>
                `);
            }
        });
        
        const thumbnailsHTML = this.currentImages.map((img, index) => `
            <img src="${img.url}" 
                 alt="${img.alt_text || 'Imagen ' + (index + 1)}" 
                 class="thumbnail ${img.id === primaryImage.id ? 'active' : ''}" 
                 data-index="${img.id}" 
                 data-color="${img.color_name || 'General'}" 
                 style="${(img.color_name && img.color_name !== 'General') ? 'display: none;' : ''}">
        `).join('');
        
        return `
            <div class="quick-view-gallery">
                <div class="gallery-filters">${colorButtons.join('')}</div>
                <div class="main-image">
                    <img src="${primaryImage.url}" alt="${primaryImage.alt_text || productName}" id="main-product-image">
                    <button class="image-zoom-btn" data-image="${primaryImage.url}" data-alt="${primaryImage.alt_text || productName}">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="thumbnail-gallery-container">
                    <button class="gallery-arrow left" id="gallery-left"><i class="fas fa-chevron-left"></i></button>
                    <div class="thumbnail-gallery" id="thumbnail-gallery">${thumbnailsHTML}</div>
                    <button class="gallery-arrow right" id="gallery-right"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        `;
    }
    
    buildVariantsHTML(variants) {
        const colors = [...new Set(variants.map(v => v.color_name))];
        const sizes = [...new Set(variants.map(v => v.size_name))];
        
        return `
            <div class="variants-section">
                <h4>Variantes</h4>
                ${colors.length > 0 ? `
                    <div class="variant-group">
                        <label>Colores:</label>
                        <div class="color-options">${colors.map(color => `<span class="color-tag">${color}</span>`).join('')}</div>
                    </div>
                ` : ''}
                ${sizes.length > 0 ? `
                    <div class="variant-group">
                        <label>Tallas:</label>
                        <div class="size-options">${sizes.map(size => `<span class="size-tag">${size}</span>`).join('')}</div>
                    </div>
                ` : ''}
                <div class="variant-table">
                    <table>
                        <thead>
                            <tr><th>Color</th><th>Talla</th><th>Precio</th><th>Stock</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            ${variants.map(v => `
                                <tr>
                                    <td data-label="Color">${v.color_name}</td>
                                    <td data-label="Talla">${v.size_name}</td>
                                    <td data-label="Precio">$${Number(v.price).toLocaleString('es-CO')}</td>
                                    <td data-label="Stock">${v.quantity}</td>
                                    <td data-label="Estado"><span class="status ${v.is_active ? 'active' : 'inactive'}">${v.is_active ? 'Activo' : 'Inactivo'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    setupImageGallery() {
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.getElementById('main-product-image');
        const zoomBtn = document.querySelector('.image-zoom-btn');
        
        // Event listeners para miniaturas
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', () => {
                const imgId = thumb.getAttribute('data-index');
                const image = this.currentImages.find(img => img.id == imgId);
                
                if (image) {
                    mainImage.src = image.url;
                    mainImage.alt = image.alt_text || '';
                    zoomBtn.setAttribute('data-image', image.url);
                    zoomBtn.setAttribute('data-alt', image.alt_text || '');
                    thumbnails.forEach(t => t.classList.remove('active'));
                    thumb.classList.add('active');
                }
            });
        });
        
        // Zoom
        if (zoomBtn) {
            zoomBtn.addEventListener('click', () => {
                const imageUrl = zoomBtn.getAttribute('data-image');
                const altText = zoomBtn.getAttribute('data-alt');
                this.openImageZoom(imageUrl, altText);
            });
        }
        
        // Filtros de color
        document.querySelectorAll('.color-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const selectedColor = btn.getAttribute('data-color');
                document.querySelectorAll('.color-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                thumbnails.forEach(thumb => {
                    const thumbColor = thumb.getAttribute('data-color');
                    thumb.style.display = (selectedColor === 'General' && (!thumbColor || thumbColor === 'General')) || thumbColor === selectedColor ? 'block' : 'none';
                });
                
                const firstVisible = document.querySelector(`.thumbnail[data-color="${selectedColor === 'General' ? 'General' : selectedColor}"]`);
                if (firstVisible) firstVisible.click();
            });
        });
    }
    
    openImageZoom(imageUrl, altText) {
        document.getElementById('zoom-image').src = imageUrl;
        document.getElementById('zoom-image').alt = altText;
        document.getElementById('zoom-title').textContent = altText;
        this.elements.imageZoomModal.classList.add('active');
    }
    
    confirmToggleStatus(productId, currentStatus) {
        const confirmMessage = currentStatus 
            ? '¿Estás seguro de que deseas desactivar este producto? El producto será desactivado y no aparecerá en la tienda.'
            : '¿Estás seguro de que deseas activar este producto? El producto será activado y volverá a aparecer en la tienda.';

        if (typeof window.openConfirmationModal === 'function') {
            window.openConfirmationModal({
                message: confirmMessage,
                title: currentStatus ? 'Desactivar producto' : 'Activar producto',
                confirmText: currentStatus ? 'Desactivar' : 'Activar',
                cancelText: 'Cancelar',
                type: currentStatus ? 'warning' : 'success',
                onConfirm: () => this.toggleProductStatus(productId, !currentStatus)
            });
            return;
        }

        if (confirm(confirmMessage)) {
            this.toggleProductStatus(productId, !currentStatus);
        }
    }
    
    async toggleProductStatus(productId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('action', newStatus ? 'activate' : 'deactivate');
            
            const response = await fetch(`${this.baseUrl}/ajax/admin/productos/toggle_product_status.php`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Recargar la página para mostrar la alerta de admin
                window.location.reload();
            } else {
                // En caso de error, también recargar para mostrar la alerta
                window.location.reload();
            }
            
        } catch (error) {
            console.error('Error al cambiar estado del producto:', error);
            // Recargar la página para mostrar la alerta de error
            window.location.reload();
        }
    }
    
    updateResultsCount(total, showing) {
        this.elements.resultsCount.textContent = `Mostrando ${showing} de ${total} productos`;
    }
    
    renderPagination(totalProducts, currentPage, perPage) {
        const totalPages = Math.ceil(totalProducts / perPage);
        
        if (totalPages <= 1) {
            this.elements.paginationContainer.style.display = 'none';
            return;
        }
        
        this.elements.paginationContainer.style.display = 'flex';
        
        let html = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (currentPage > 1) {
            html += `<a href="#" data-page="1" class="pagination-item" title="Primera página"><i class="fas fa-angle-double-left"></i></a>`;
            html += `<a href="#" data-page="${currentPage - 1}" class="pagination-item" title="Página anterior"><i class="fas fa-angle-left"></i></a>`;
        }
        
        if (startPage > 1) {
            html += `<span class="pagination-item">...</span>`;
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<a href="#" data-page="${i}" class="pagination-item ${i === currentPage ? 'active' : ''}">${i}</a>`;
        }
        
        if (endPage < totalPages) {
            html += `<span class="pagination-item">...</span>`;
        }
        
        if (currentPage < totalPages) {
            html += `<a href="#" data-page="${currentPage + 1}" class="pagination-item" title="Página siguiente"><i class="fas fa-angle-right"></i></a>`;
            html += `<a href="#" data-page="${totalPages}" class="pagination-item" title="Última página"><i class="fas fa-angle-double-right"></i></a>`;
        }
        
        this.elements.paginationContainer.innerHTML = html;
        
        document.querySelectorAll('.pagination-item').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (!isNaN(page)) {
                    this.loadProducts(page);
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }
            });
        });
    }
    
    debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}

// Exportar para uso global
window.ProductsManager = ProductsManager;
