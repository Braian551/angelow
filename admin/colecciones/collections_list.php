<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

requireRole('admin');

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => $_GET['status'] ?? '',
    'usage'  => $_GET['usage'] ?? '',
];

$activeFilters = count(array_filter($filters, static fn ($value) => $value !== ''));

try {
    $statsStmt = $conn->query(<<<SQL
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive
        FROM collections
    SQL);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active' => 0, 'inactive' => 0];

    $conditions = [];
    $params = [];

    if ($filters['search'] !== '') {
        $conditions[] = '(c.name LIKE :search OR c.slug LIKE :search OR c.description LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if ($filters['status'] === 'active') {
        $conditions[] = 'c.is_active = 1';
    } elseif ($filters['status'] === 'inactive') {
        $conditions[] = 'c.is_active = 0';
    }

    if ($filters['usage'] === 'with-products') {
        $conditions[] = '(COALESCE(p_counts.total_products, 0) + COALESCE(pc_counts.total_products, 0)) > 0';
    } elseif ($filters['usage'] === 'without-products') {
        $conditions[] = '(COALESCE(p_counts.total_products, 0) + COALESCE(pc_counts.total_products, 0)) = 0';
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $collectionsSql = <<<SQL
        SELECT
            c.id,
            c.name,
            c.slug,
            c.description,
            c.image,
            c.launch_date,
            c.is_active,
            c.created_at,
            c.updated_at,
            COALESCE(p_counts.total_products, 0) AS direct_product_count,
            COALESCE(pc_counts.total_products, 0) AS pivot_product_count,
            COALESCE(p_counts.total_products, 0) + COALESCE(pc_counts.total_products, 0) AS product_count
        FROM collections c
        LEFT JOIN (
            SELECT collection_id, COUNT(*) AS total_products
            FROM products
            WHERE collection_id IS NOT NULL
            GROUP BY collection_id
        ) AS p_counts ON p_counts.collection_id = c.id
        LEFT JOIN (
            SELECT collection_id, COUNT(*) AS total_products
            FROM product_collections
            GROUP BY collection_id
        ) AS pc_counts ON pc_counts.collection_id = c.id
        $whereClause
        ORDER BY c.created_at DESC
    SQL;

    $stmt = $conn->prepare($collectionsSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultsCount = count($collections);
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
    $collections = [];
    $resultsCount = 0;
    error_log('Error loading collections: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colecciones - Panel Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/collections.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-base-url="<?= htmlspecialchars(BASE_URL) ?>">
    <div class="admin-container">
        <?php include __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php include __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content collections-dashboard">
                <div class="page-header">
                    <div>
                        <h1>
                            <i class="fas fa-layer-group"></i>
                            Gestión de Colecciones
                        </h1>
                        <div class="breadcrumb">
                            <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Colecciones</span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="add_collection.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Colección
                        </a>
                    </div>
                </div>

                <div class="card filters-card">
                    <div class="filters-header">
                        <div class="filters-title">
                            <i class="fas fa-sliders-h"></i>
                            <h3>Filtros de búsqueda</h3>
                        </div>
                        <div class="filters-meta">
                            <span id="active-filters-count"><?= $activeFilters ?> <?= $activeFilters === 1 ? 'filtro activo' : 'filtros activos' ?></span>
                        </div>
                    </div>
                    <form id="collections-filter-form" class="filters-form" method="GET">
                        <div class="search-bar">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text"
                                       id="collection-search"
                                       name="search"
                                       class="search-input"
                                       placeholder="Buscar por nombre, slug o descripción"
                                       value="<?= htmlspecialchars($filters['search']) ?>"
                                       autocomplete="off">
                                <button type="button" class="search-clear" id="clear-search" style="<?= $filters['search'] !== '' ? '' : 'display: none;' ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="submit" class="search-submit-btn">
                                <i class="fas fa-search"></i>
                                <span>Buscar</span>
                            </button>
                        </div>

                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="status-filter" class="filter-label">
                                    <i class="fas fa-toggle-on"></i>
                                    Estado
                                </label>
                                <select id="status-filter" name="status" class="filter-select">
                                    <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>Todos los estados</option>
                                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activas</option>
                                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactivas</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="usage-filter" class="filter-label">
                                    <i class="fas fa-link"></i>
                                    Uso en productos
                                </label>
                                <select id="usage-filter" name="usage" class="filter-select">
                                    <option value="" <?= $filters['usage'] === '' ? 'selected' : '' ?>>Todas</option>
                                    <option value="with-products" <?= $filters['usage'] === 'with-products' ? 'selected' : '' ?>>Con productos</option>
                                    <option value="without-products" <?= $filters['usage'] === 'without-products' ? 'selected' : '' ?>>Sin productos</option>
                                </select>
                            </div>
                        </div>

                        <div class="filters-actions-bar">
                            <button type="button" class="btn-clear-filters" id="clear-filters">
                                <i class="fas fa-times-circle"></i>
                                Limpiar filtros
                            </button>
                            <button type="submit" class="btn-apply-filters">
                                <i class="fas fa-check-circle"></i>
                                Aplicar filtros
                            </button>
                        </div>
                    </form>
                </div>

                <div class="results-summary">
                    <div class="results-info">
                        <p id="results-count">
                            <?= $resultsCount === 1 ? '1 colección encontrada' : $resultsCount . ' colecciones encontradas' ?>
                        </p>
                        <span class="results-helper">Total registradas: <?= (int) $stats['total'] ?></span>
                    </div>
                    <div class="quick-actions">
                        <a href="add_collection.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Crear colección
                        </a>
                        <a href="<?= BASE_URL ?>/docs/GUIA_RAPIDA_ANUNCIOS.md" class="btn btn-outline" target="_blank">
                            <i class="fas fa-book"></i>
                            Guía rápida
                        </a>
                    </div>
                </div>

                <div class="card stats-card">
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon total">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= (int) $stats['total'] ?></h3>
                                    <p>Total de colecciones</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= (int) $stats['active'] ?></h3>
                                    <p>Colecciones activas</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= (int) $stats['inactive'] ?></h3>
                                    <p>Colecciones inactivas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body table-container">
                        <table class="data-table collections-table">
                            <thead>
                                <tr>
                                    <th>Colección</th>
                                    <th>Productos asociados</th>
                                    <th>Lanzamiento</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($collections)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-layer-group"></i>
                                                <p>No se encontraron colecciones con los criterios seleccionados.</p>
                                                <a href="add_collection.php" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i>
                                                    Crear primera colección
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($collections as $collection): ?>
                                        <?php
                                            $productCount = (int) $collection['product_count'];
                                            $inUse = $productCount > 0;
                                            $launchDate = $collection['launch_date'] ? date('d/m/Y', strtotime($collection['launch_date'])) : 'Sin fecha';
                                            $updatedAt = $collection['updated_at'] ?: $collection['created_at'];
                                        ?>
                                        <tr data-collection-id="<?= $collection['id'] ?>">
                                            <td>
                                            <div class="collection-info">
                                                <div class="collection-thumb">
                                                    <?php if (!empty($collection['image'])): ?>
                                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($collection['image']) ?>" alt="<?= htmlspecialchars($collection['name']) ?>">
                                                    <?php else: ?>
                                                        <span class="thumb-placeholder"><i class="fas fa-image"></i></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="collection-meta">
                                                    <div class="collection-name-row">
                                                        <strong><?= htmlspecialchars($collection['name']) ?></strong>
                                                        <span class="collection-id">ID #<?= $collection['id'] ?></span>
                                                    </div>
                                                    <div class="collection-slug">Slug: <code><?= htmlspecialchars($collection['slug']) ?></code></div>
                                                    <small class="collection-created">Creada el <?= date('d/m/Y', strtotime($collection['created_at'])) ?></small>
                                                </div>
                                            </div>
                                            </td>
                                            <td>
                                                <div class="products-meta">
                                                    <span class="badge badge-info">
                                                        <?= $productCount ?> <?= $productCount === 1 ? 'producto' : 'productos' ?>
                                                    </span>
                                                    <small>Directos: <?= (int) $collection['direct_product_count'] ?> · Asociados: <?= (int) $collection['pivot_product_count'] ?></small>
                                                    <?php if ($inUse): ?>
                                                        <small class="usage-note"><i class="fas fa-link"></i> En uso - no se puede eliminar</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="launch-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= $launchDate ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="status-cell">
                                                    <span class="status-badge <?= $collection['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                        <?= $collection['is_active'] ? 'Activa' : 'Inactiva' ?>
                                                    </span>
                                                    <small>Actualizada <?= date('d/m/Y H:i', strtotime($updatedAt)) ?></small>
                                                </div>
                                            </td>
                                            <td class="actions text-right">
                                                <div class="table-actions">
                                                    <a href="edit_collection.php?id=<?= $collection['id'] ?>" class="btn btn-sm btn-edit" title="Editar colección">
                                                        <i class="fas fa-edit"></i>
                                                        Editar
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-status js-collection-toggle"
                                                            data-id="<?= $collection['id'] ?>"
                                                            data-active="<?= $collection['is_active'] ? '1' : '0' ?>">
                                                        <i class="fas fa-power-off"></i>
                                                        <span><?= $collection['is_active'] ? 'Desactivar' : 'Activar' ?></span>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-delete js-collection-delete"
                                                            data-id="<?= $collection['id'] ?>"
                                                            data-name="<?= htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-in-use="<?= $inUse ? '1' : '0' ?>">
                                                        <i class="fas fa-trash"></i>
                                                        <span>Eliminar</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <?php require_once __DIR__ . '/../../alertas/confirmation_modal.php'; ?>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/components/confirmationModal.js"></script>
    <script src="<?= BASE_URL ?>/js/admin/collections/collections.js"></script>
</body>
</html>
