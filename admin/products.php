<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../alertas/alerta1.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}

// Obtener categorías para el filtro
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener categorías: " . $e->getMessage());
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Gestión de Productos - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/products-grid.css">
</head>

<body>

    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-box-open"></i> Gestión de Productos
                    
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Productos</span>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="card filters-card">
                    <div class="filters-header">
                        <div class="filters-title">
                            <i class="fas fa-sliders-h"></i>
                            <h3>Filtros de búsqueda</h3>
                        </div>
                        <button type="button" class="filters-toggle collapsed" id="toggle-filters" aria-label="Mostrar/Ocultar filtros">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>

                    <form id="search-form" class="filters-form">
                        <!-- Barra de búsqueda principal -->
                        <div class="search-bar">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="search-input" 
                                    name="search" 
                                    placeholder="Buscar por nombre, SKU, etc..." 
                                    class="search-input"
                                    autocomplete="off">
                                <button type="button" class="search-clear" id="clear-search" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="submit" class="search-submit-btn">
                                <i class="fas fa-search"></i>
                                <span>Buscar</span>
                            </button>
                        </div>

                        <!-- Filtros avanzados -->
                        <div class="filters-advanced" id="advanced-filters" style="display: none;">
                            <div class="filters-row">
                                <div class="filter-group">
                                    <label for="category-filter" class="filter-label">
                                        <i class="fas fa-tag"></i>
                                        Categoría
                                    </label>
                                    <select name="category" id="category-filter" class="filter-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="status-filter" class="filter-label">
                                        <i class="fas fa-toggle-on"></i>
                                        Estado
                                    </label>
                                    <select name="status" id="status-filter" class="filter-select">
                                        <option value="">Todos los estados</option>
                                        <option value="active">Activos</option>
                                        <option value="inactive">Inactivos</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="gender-filter" class="filter-label">
                                        <i class="fas fa-venus-mars"></i>
                                        Género
                                    </label>
                                    <select name="gender" id="gender-filter" class="filter-select">
                                        <option value="">Todos los géneros</option>
                                        <option value="niño">Niño</option>
                                        <option value="niña">Niña</option>
                                        <option value="bebe">Bebé</option>
                                        <option value="unisex">Unisex</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="order-filter" class="filter-label">
                                        <i class="fas fa-sort-amount-down"></i>
                                        Ordenar por
                                    </label>
                                    <select name="order" id="order-filter" class="filter-select">
                                        <option value="newest">Más recientes</option>
                                        <option value="name_asc">Nombre (A-Z)</option>
                                        <option value="name_desc">Nombre (Z-A)</option>
                                        <option value="price_asc">Precio (menor a mayor)</option>
                                        <option value="price_desc">Precio (mayor a menor)</option>
                                        <option value="stock_asc">Stock (menor a mayor)</option>
                                        <option value="stock_desc">Stock (mayor a menor)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Acciones de filtrado -->
                            <div class="filters-actions-bar">
                                <div class="active-filters" id="active-filters-count">
                                    <i class="fas fa-filter"></i>
                                    <span>0 filtros activos</span>
                                </div>

                                <div class="filters-buttons">
                                    <button type="button" class="btn-clear-filters" id="clear-all-filters">
                                        <i class="fas fa-times-circle"></i>
                                        Limpiar todo
                                    </button>
                                    <button type="submit" class="btn-apply-filters">
                                        <i class="fas fa-check-circle"></i>
                                        Aplicar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Resumen de resultados -->
                <div class="results-summary">
                    <p id="results-count">Cargando productos...</p>

                    <div class="quick-actions">
                        <a href="<?= BASE_URL ?>/admin/subproducto.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                        <button class="btn btn-icon" id="export-products">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                    </div>
                </div>

                <!-- Listado de productos -->
                <div class="products-container" id="products-container">
                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</div>
                </div>

                <!-- Paginación -->
                <div class="pagination" id="pagination-container"></div>
            </div>
        </main>
    </div>

    <!-- Modal para vista rápida -->
    <div class="modal-overlay quick-view-modal" id="quick-view-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalles del Producto</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="quick-view-content"></div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="edit-product-btn">
                    <i class="fas fa-edit"></i> Editar Producto
                </a>
                <button class="btn btn-secondary modal-close">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal para zoom de imagen -->
    <div class="modal-overlay image-zoom-modal" id="image-zoom-modal">
        <div class="modal-content zoom-content">
            <div class="modal-header">
                <h3 id="zoom-title">Imagen del Producto</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body zoom-body">
                <img id="zoom-image" src="" alt="Zoomed image">
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== START OF FILTER UI LOGIC (from orderadmin.js) =====

    // ===== TOGGLE DE FILTROS =====
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const advancedFilters = document.getElementById('advanced-filters');
    
    if (toggleFiltersBtn && advancedFilters) {
        const icon = toggleFiltersBtn.querySelector('i');
        
        toggleFiltersBtn.addEventListener('click', function() {
            const isCollapsed = toggleFiltersBtn.classList.contains('collapsed');
            
            if (isCollapsed) {
                advancedFilters.style.display = 'flex';
                toggleFiltersBtn.classList.remove('collapsed');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                setTimeout(() => {
                    advancedFilters.style.opacity = '1';
                    advancedFilters.style.maxHeight = '1000px';
                }, 10);
            } else {
                advancedFilters.style.opacity = '0';
                advancedFilters.style.maxHeight = '0';
                toggleFiltersBtn.classList.add('collapsed');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                setTimeout(() => {
                    advancedFilters.style.display = 'none';
                }, 400);
            }
        });
    }

    // ===== BOTÓN DE LIMPIAR BÚSQUEDA =====
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput && clearSearchBtn) {
        searchInput.addEventListener('input', function() {
            clearSearchBtn.style.display = this.value.length > 0 ? 'flex' : 'none';
        });
        
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            searchInput.focus();
            loadProducts(); // Recargar productos
        });
    }

    // ===== CONTADOR DE FILTROS ACTIVOS =====
    const filterForm = document.getElementById('search-form');
    const activeFiltersCount = document.getElementById('active-filters-count');
    
    function updateActiveFiltersCount() {
        if (!filterForm || !activeFiltersCount) return;
        
        let count = 0;
        const formData = new FormData(filterForm);
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '' && key !== 'order' && key !== 'search') { // No contar el orden ni la busqueda como filtro
                count++;
            }
        }
        
        const countSpan = activeFiltersCount.querySelector('span');
        if (countSpan) {
            countSpan.textContent = `${count} ${count === 1 ? 'filtro activo' : 'filtros activos'}`;
        }
        
        activeFiltersCount.classList.toggle('has-filters', count > 0);
    }
    
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('change', updateActiveFiltersCount);
            input.addEventListener('input', updateActiveFiltersCount);
        });
        updateActiveFiltersCount();
    }

    // ===== LIMPIAR TODOS LOS FILTROS =====
    const clearAllFiltersBtn = document.getElementById('clear-all-filters');
    
    if (clearAllFiltersBtn && filterForm) {
        clearAllFiltersBtn.addEventListener('click', function() {
            filterForm.reset();
            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }
            updateActiveFiltersCount();
            loadProducts(); // Recargar productos
        });
    }

    // ===== BOTÓN APLICAR FILTROS =====
    const applyFiltersBtn = document.querySelector('.btn-apply-filters');
    if (applyFiltersBtn && filterForm) {
        applyFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loadProducts();
        });
    }

    // ===== END OF FILTER UI LOGIC =====


    // ===== START OF PRODUCT MANAGEMENT LOGIC (from productsadmin.php) =====

    // Variables globales
    let currentPage = 1;
    let isLoading = false;
    let productToDelete = null;
    
    // Elementos del DOM
    const productsContainer = document.getElementById('products-container');
    const resultsCount = document.getElementById('results-count');
    const paginationContainer = document.getElementById('pagination-container');
    
    // Cargar productos al inicio
    loadProducts();
    
    // Función para cargar productos con AJAX
    function loadProducts(page = 1) {
        if (isLoading) return;
        
        isLoading = true;
        currentPage = page;
        productsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</div>';
        
        const params = new URLSearchParams(new FormData(filterForm));
        params.append('page', page);
        
        const apiUrl = `<?= BASE_URL ?>/ajax/admin/productos/productsearchadmin.php?${params.toString()}`;
        console.log('Loading products from:', apiUrl);
        
        fetch(apiUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    renderProducts(data.products);
                    updateResultsCount(data.meta.total, data.products.length);
                    renderPagination(data.meta.total, page, data.meta.perPage);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Error al procesar la respuesta: ' + e.message);
                }
            })
            .catch(error => {
                console.error('Error en loadProducts:', error);
                productsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error al cargar productos</h3>
                        <p>${error.message}</p>
                        <button onclick="loadProducts()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Reintentar
                        </button>
                    </div>
                `;
            })
            .finally(() => {
                isLoading = false;
            });
    }
    
    // Función para renderizar productos en tarjetas
    function renderProducts(products) {
        if (!products || products.length === 0) {
            productsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Intenta ajustar tus filtros o agrega un nuevo producto.</p>
                    <a href="<?= BASE_URL ?>/admin/subproducto.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </a>
                </div>
            `;
            return;
        }
        
        let gridHTML = '<div class="products-admin-grid">';

        products.forEach(product => {
            const imageUrl = product.primary_image || '<?= BASE_URL ?>/images/default-product.jpg';
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
                        <img src="${imageUrl}" alt="${product.name}" onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'">
                        <div class="product-admin-overlay">
                            <button class="btn-overlay btn-quick-view" data-id="${product.id}" title="Vista Rápida">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="product-admin-body">
                        <div class="product-admin-header">
                            <h3 class="product-admin-title">
                                <a href="<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}">${product.name}</a>
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
                        <a href="<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                            <span>Editar</span>
                        </a>
                        <button class="btn-action btn-delete" data-id="${product.id}" title="Eliminar">
                            <i class="fas fa-trash"></i>
                            <span>Eliminar</span>
                        </button>
                    </div>
                </div>
            `;
        });

        gridHTML += '</div>';
        productsContainer.innerHTML = gridHTML;

        assignButtonEvents();
    }
    
    // Función para abrir vista rápida
    function openQuickView(productId) {
        fetch(`<?= BASE_URL ?>/admin/api/productos/get_product_details.php?id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderQuickView(data);
                    document.getElementById('quick-view-modal').classList.add('active');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los detalles del producto');
            });
    }
    
    // Función para renderizar vista rápida
    function renderQuickView(data) {
        const product = data.product;
        const images = data.images;
        const variants = data.variants;
        
        let imagesHtml = '';
        if (images.length > 0) {
            // Agrupar imágenes por color
            const imagesByColor = {};
            const colorVariants = {};
            images.forEach(img => {
                const color = img.color_name || 'General';
                if (!imagesByColor[color]) {
                    imagesByColor[color] = [];
                }
                imagesByColor[color].push(img);
                colorVariants[img.color_variant_id] = color;
            });

            // Imagen principal (primera imagen primaria o primera general)
            const primaryImage = images.find(img => img.is_primary == 1) || images[0];

            // Botones de filtro por color
            const colorButtons = [
                `<button class="color-filter-btn active" data-color="General">
                    <span class="color-text">Principal</span>
                </button>`
            ];
            
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

            imagesHtml = `
                <div class="quick-view-gallery">
                    <div class="gallery-filters">
                        ${colorButtons.join('')}
                    </div>
                    
                    <div class="main-image">
                        <img src="${primaryImage.url}" alt="${primaryImage.alt_text || product.name}" id="main-product-image">
                        <button class="image-zoom-btn" data-image="${primaryImage.url}" data-alt="${primaryImage.alt_text || product.name}">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    
                    <div class="thumbnail-gallery-container">
                        <button class="gallery-arrow left" id="gallery-left" aria-label="Ver imágenes anteriores">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.5 2L3.5 6L7.5 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="thumbnail-gallery" id="thumbnail-gallery">
                            ${images.map((img, index) => `
                                <img src="${img.url}" alt="${img.alt_text || 'Imagen ' + (index + 1)}" class="thumbnail ${img.id === primaryImage.id ? 'active' : ''}" data-index="${img.id}" data-color="${img.color_name || 'General'}" style="${(img.color_name && img.color_name !== 'General') ? 'display: none;' : ''}">
                            `).join('')}
                        </div>
                        <button class="gallery-arrow right" id="gallery-right" aria-label="Ver siguientes imágenes">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.5 2L8.5 6L4.5 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }
        
        let variantsHtml = '';
        if (variants.length > 0) {
            const colors = [...new Set(variants.map(v => v.color_name))];
            const sizes = [...new Set(variants.map(v => v.size_name))];
            
            variantsHtml = `
                <div class="variants-section">
                    <h4>Variantes</h4>
                    ${colors.length > 0 ? `
                    <div class="variant-group">
                        <label>Colores:</label>
                        <div class="color-options">
                            ${colors.map(color => `<span class="color-tag">${color}</span>`).join('')}
                        </div>
                    </div>
                    ` : ''}
                    ${sizes.length > 0 ? `
                    <div class="variant-group">
                        <label>Tallas:</label>
                        <div class="size-options">
                            ${sizes.map(size => `<span class="size-tag">${size}</span>`).join('')}
                        </div>
                    </div>
                    ` : ''}
                    <div class="variant-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th>Imagen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${variants.map(variant => `
                                    <tr>
                                        <td>${variant.color_name}</td>
                                        <td>${variant.size_name}</td>
                                        <td>$${Number(variant.price).toLocaleString('es-CO')}</td>
                                        <td>${variant.quantity}</td>
                                        <td><span class="status ${variant.is_active ? 'active' : 'inactive'}">${variant.is_active ? 'Activo' : 'Inactivo'}</span></td>
                                        <td><button class="btn-zoom-variant" data-color-id="${variant.color_id}" data-color="${variant.color_name}" data-size="${variant.size_name}"><i class="fas fa-eye"></i></button></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
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
        
        // Almacenar imágenes para uso en funciones
        window.currentImages = images;
        
        // Actualizar el enlace de editar
        document.getElementById('edit-product-btn').href = `<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}`;
        
        // Eventos para galería de imágenes
        setupImageGallery();
    }
    
    // Función para configurar galería de imágenes
    function setupImageGallery() {
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.getElementById('main-product-image');
        const zoomBtn = document.querySelector('.image-zoom-btn');
        const thumbnailGallery = document.getElementById('thumbnail-gallery');
        const galleryLeft = document.getElementById('gallery-left');
        const galleryRight = document.getElementById('gallery-right');
        
        // Función para mostrar/ocultar flechas según cantidad y desbordamiento
        function toggleGalleryArrows() {
            if (thumbnailGallery && galleryLeft && galleryRight) {
                const visibleThumbs = Array.from(document.querySelectorAll('.thumbnail')).filter(
                    thumb => thumb.style.display !== 'none'
                );
                const hasOverflow = thumbnailGallery.scrollWidth > thumbnailGallery.clientWidth;
                
                if (hasOverflow && visibleThumbs.length > 2) {
                    galleryLeft.classList.add('show');
                    galleryRight.classList.add('show');
                    updateArrowStates();
                } else {
                    galleryLeft.classList.remove('show');
                    galleryRight.classList.remove('show');
                }
            }
        }
        
        // Función para actualizar el estado de las flechas (habilitado/deshabilitado)
        function updateArrowStates() {
            if (thumbnailGallery && galleryLeft && galleryRight) {
                const scrollLeft = thumbnailGallery.scrollLeft;
                const maxScroll = thumbnailGallery.scrollWidth - thumbnailGallery.clientWidth;
                
                galleryLeft.disabled = scrollLeft <= 0;
                galleryRight.disabled = scrollLeft >= maxScroll - 1;
            }
        }
        
        // Event listener para scroll de galería
        if (thumbnailGallery) {
            thumbnailGallery.addEventListener('scroll', updateArrowStates);
        }
        
        // Event listeners para flechas de galería
        if (galleryLeft) {
            galleryLeft.addEventListener('click', () => {
                if (thumbnailGallery) {
                    const scrollAmount = thumbnailGallery.clientWidth * 0.75;
                    thumbnailGallery.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                }
            });
        }
        
        if (galleryRight) {
            galleryRight.addEventListener('click', () => {
                if (thumbnailGallery) {
                    const scrollAmount = thumbnailGallery.clientWidth * 0.75;
                    thumbnailGallery.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
            });
        }
        
        // Event listener para botón de zoom
        if (zoomBtn) {
            zoomBtn.addEventListener('click', function() {
                const imageUrl = this.getAttribute('data-image');
                const altText = this.getAttribute('data-alt');
                openImageZoom(imageUrl, altText);
            });
        }
        
        // Event listeners para botones de filtro de color
        document.querySelectorAll('.color-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const selectedColor = this.getAttribute('data-color');
                
                // Actualizar botones activos
                document.querySelectorAll('.color-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const thumbnails = document.querySelectorAll('.thumbnail');
                
                if (selectedColor === 'General') {
                    // Para "Principal", mostrar solo miniaturas sin color específico (General)
                    thumbnails.forEach(thumb => {
                        const thumbColor = thumb.getAttribute('data-color');
                        if (thumbColor === 'General' || !thumbColor) {
                            thumb.style.display = 'block';
                        } else {
                            thumb.style.display = 'none';
                        }
                    });
                    
                    // Cambiar a la imagen primaria
                    const primaryImage = window.currentImages.find(img => img.is_primary == 1) || window.currentImages[0];
                    if (primaryImage) {
                        document.getElementById('main-product-image').src = primaryImage.url;
                        document.getElementById('main-product-image').alt = primaryImage.alt_text || 'Imagen del producto';
                        document.querySelector('.image-zoom-btn').setAttribute('data-image', primaryImage.url);
                        document.querySelector('.image-zoom-btn').setAttribute('data-alt', primaryImage.alt_text || 'Imagen del producto');
                        
                        // Marcar como activa la miniatura principal
                        thumbnails.forEach(t => t.classList.remove('active'));
                        const primaryThumb = document.querySelector(`.thumbnail[data-index="${primaryImage.id}"]`);
                        if (primaryThumb) {
                            primaryThumb.classList.add('active');
                        }
                    }
                } else {
                    // Para colores específicos, mostrar solo miniaturas de ese color
                    thumbnails.forEach(thumb => {
                        const thumbColor = thumb.getAttribute('data-color');
                        if (thumbColor === selectedColor) {
                            thumb.style.display = 'block';
                        } else {
                            thumb.style.display = 'none';
                        }
                    });
                    
                    // Cambiar imagen principal a la primera visible
                    const firstVisible = document.querySelector(`.thumbnail[data-color="${selectedColor}"]`);
                    if (firstVisible) {
                        firstVisible.click();
                        firstVisible.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                    }
                }
                
                // Actualizar flechas de navegación
                setTimeout(() => {
                    toggleGalleryArrows();
                    updateArrowStates();
                }, 100);
            });
        });
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const imgId = this.getAttribute('data-index');
                const image = window.currentImages.find(img => img.id == imgId);
                
                if (image) {
                    // Cambiar imagen principal
                    mainImage.src = image.url;
                    mainImage.alt = image.alt_text || '${product.name}';
                    
                    // Actualizar botón de zoom
                    if (zoomBtn) {
                        zoomBtn.setAttribute('data-image', image.url);
                        zoomBtn.setAttribute('data-alt', image.alt_text || '${product.name}');
                    }
                    
                    // Actualizar thumbnail activo
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
        
        // Inicializar estado de las flechas
        setTimeout(() => {
            toggleGalleryArrows();
            updateArrowStates();
        }, 100);
        
        // Actualizar flechas cuando cambie el tamaño de la ventana
        window.addEventListener('resize', () => {
            toggleGalleryArrows();
            updateArrowStates();
        });
    }
    
    // Función para confirmar eliminación
    function confirmDelete(productId) {
        document.getElementById('confirm-delete').setAttribute('data-id', productId);
        document.getElementById('delete-modal').classList.add('active');
    }
    
    // Actualizar contador de resultados
    function updateResultsCount(total, showing) {
        resultsCount.textContent = `Mostrando ${showing} de ${total} productos`;
    }
    
    // Renderizar paginación
    function renderPagination(totalProducts, currentPage, perPage) {
        const totalPages = Math.ceil(totalProducts / perPage);
        
        // Ocultar paginación si hay menos de 13 productos (1 página)
        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        paginationContainer.style.display = 'flex';
        
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

        paginationContainer.innerHTML = html;
        
        document.querySelectorAll('.pagination-item').forEach(link => {
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
                openQuickView(productId);
            });
        });

        // Eliminación
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                confirmDelete(productId);
            });
        });
    }
    
    // Event listeners para filtros
    searchInput.addEventListener('input', debounce(() => loadProducts(), 500));
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadProducts();
    });
    
    // Función debounce
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Función para eliminar producto
    function deleteProduct(productId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
            return;
        }
        
        // Aquí iría la lógica para eliminar el producto via AJAX
        // Por ahora, solo cerramos el modal
        document.getElementById('delete-modal').classList.remove('active');
        alert('Funcionalidad de eliminación no implementada aún.');
    }
    
    // Función para abrir imagen en zoom
    function openImageZoom(imageUrl, altText) {
        document.getElementById('zoom-image').src = imageUrl;
        document.getElementById('zoom-image').alt = altText;
        document.getElementById('zoom-title').textContent = altText;
        document.getElementById('image-zoom-modal').classList.add('active');
    }

    // Lógica para modales (vista rápida, eliminación)
    // Cerrar modales
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });
    
    // Cerrar modal al hacer clic en overlay
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    
    // Confirmar eliminación
    document.getElementById('confirm-delete').addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        deleteProduct(productId);
    });
});
</script>
</body>

</html>