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
    <title>Gestión de Productos - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/productsadmin.css">
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
                    <form id="search-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <input type="text" id="search-input" name="search" placeholder="Buscar productos..."
                                    class="form-control">
                                <button type="submit" class="btn btn-search">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <div class="filter-group">
                                <select name="category" id="category-filter" class="form-control">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select name="status" id="status-filter" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="active">Activos</option>
                                    <option value="inactive">Inactivos</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select name="gender" id="gender-filter" class="form-control">
                                    <option value="">Todos los géneros</option>
                                    <option value="niño">Niño</option>
                                    <option value="niña">Niña</option>
                                    <option value="bebe">Bebé</option>
                                    <option value="unisex">Unisex</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select name="order" id="order-filter" class="form-control">
                                    <option value="newest">Más recientes</option>
                                    <option value="name_asc">Nombre (A-Z)</option>
                                    <option value="name_desc">Nombre (Z-A)</option>
                                    <option value="price_asc">Precio (menor a mayor)</option>
                                    <option value="price_desc">Precio (mayor a menor)</option>
                                    <option value="stock_asc">Stock (menor a mayor)</option>
                                    <option value="stock_desc">Stock (mayor a menor)</option>
                                </select>
                            </div>
                            <div class="filcen">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>

                                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt"></i> Limpiar
                                </a>
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
                <div class="products-grid" id="products-container">
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
 
       <?php require_once __DIR__ . '/../js/admin/productos/productsadmin.php'; ?>
   

</body>

</html>