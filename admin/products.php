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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/style-admin.css">
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
    <script src="<?= BASE_URL ?>/js/admin/components/filterManager.js"></script>
    <script src="<?= BASE_URL ?>/js/admin/products/productsManager.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gestor de productos
    const productsManager = new ProductsManager('<?= BASE_URL ?>');
    window.productsManager = productsManager; // Exponer globalmente
    
    // Inicializar gestor de filtros
    const filterManager = new FilterManager('search-form', {
        onFilterChange: () => productsManager.loadProducts(),
        onClearFilters: () => productsManager.loadProducts()
    });
});

</script>
</body>

</html>