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
                        <button class="btn btn-icon" id="export-products">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                        <button class="btn btn-icon" id="bulk-actions">
                            <i class="fas fa-tasks"></i> Acciones masivas
                        </button>
                    </div>
                </div>

                <!-- Listado de productos -->
                <div class="orders-table-container" id="products-container">
                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</div>
                </div>

                <!-- Paginación -->
                <div class="pagination" id="pagination-container"></div>
            </div>
        </main>
    </div>

    <!-- Modal para vista rápida -->
    <div class="modal-overlay" id="quick-view-modal">
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

    <!-- Modal para confirmar eliminación -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Eliminación</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro que deseas eliminar este producto? Esta acción no se puede deshacer.</p>
                <p class="text-danger"><strong>Advertencia:</strong> También se eliminarán todas las variantes e imágenes asociadas.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" id="confirm-delete">Eliminar</button>
                <button class="btn btn-secondary modal-close">Cancelar</button>
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
        
        fetch(apiUrl, {
            method: 'GET',
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) throw new Error(data.error);
                renderProducts(data.products);
                updateResultsCount(data.meta.total, data.products.length);
                renderPagination(data.meta.total, page, data.meta.per_page);
            })
            .catch(error => {
                console.error('Error en loadProducts:', error);
                productsContainer.innerHTML = `<div class="empty-state"><h3>Error al cargar productos</h3><p>${error.message}</p></div>`;
            })
            .finally(() => {
                isLoading = false;
            });
    }
    
    // Función para renderizar productos en una tabla
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
        
        let tableHTML = `
            <table class="orders-table" id="products-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        products.forEach(product => {
            const imageUrl = product.primary_image || '<?= BASE_URL ?>/images/default-product.jpg';
            const statusText = product.is_active ? 'Activo' : 'Inactivo';
            const statusClass = product.is_active ? 'status-paid' : 'status-cancelled'; // Re-usando clases de orders

            tableHTML += `
                <tr>
                    <td><input type="checkbox" class="select-row" data-id="${product.id}"></td>
                    <td>
                        <div class="product-cell">
                            <img src="${imageUrl}" alt="${product.name}" class="product-cell-img" onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'">
                            <div class="product-cell-info">
                                <a href="<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}" class="product-name">${product.name}</a>
                                <span class="product-sku">SKU: ${product.sku || 'N/A'}</span>
                            </div>
                        </div>
                    </td>
                    <td>${product.category_name || 'N/A'}</td>
                    <td>${product.total_stock}</td>
                    <td>$${Number(product.min_price).toFixed(2)} - $${Number(product.max_price).toFixed(2)}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="actions-cell">
                            <a href="<?= BASE_URL ?>/admin/editproducto.php?id=${product.id}" class="btn-action" title="Editar"><i class="fas fa-edit"></i></a>
                            <button class="btn-action btn-quick-view" data-id="${product.id}" title="Vista Rápida"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-delete" data-id="${product.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableHTML += `
                </tbody>
            </table>
        `;
        productsContainer.innerHTML = tableHTML;

        assignButtonEvents();
    }
    
    // Actualizar contador de resultados
    function updateResultsCount(total, showing) {
        resultsCount.textContent = `Mostrando ${showing} de ${total} productos`;
    }
    
    // Renderizar paginación
    function renderPagination(totalProducts, currentPage, perPage) {
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
        // Lógica de vista rápida y eliminación
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

    // Lógica para modales (vista rápida, eliminación)
});
</script>
</body>

</html>