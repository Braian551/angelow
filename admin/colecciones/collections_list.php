<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/formuser.php');
    exit();
}

// Obtener colecciones
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT 
            c.id,
            c.name,
            c.slug,
            c.description,
            c.image,
            c.launch_date,
            c.is_active,
            c.created_at,
            COUNT(DISTINCT pc.product_id) as product_count
        FROM collections c
        LEFT JOIN product_collections pc ON c.id = pc.collection_id
        WHERE 1=1";

$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== 'all') {
    $sql .= " AND c.is_active = ?";
    $params[] = ($statusFilter === 'active') ? 1 : 0;
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
               FROM collections";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Colecciones - Angelow Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/collections.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php include __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-layer-group"></i>
                            Gestión de Colecciones
                        </h1>
                        <p class="page-subtitle">Administra las colecciones de productos de la tienda</p>
                    </div>
                    <div class="header-right">
                        <a href="add_collection.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Nueva Colección
                        </a>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total de Colecciones</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= $stats['active'] ?></h3>
                        <p>Colecciones Activas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon inactive">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= $stats['inactive'] ?></h3>
                        <p>Colecciones Inactivas</p>
                    </div>
                </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="filters-section">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar por nombre, slug o descripción..." value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                </div>
                <div class="filters-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Todos los estados</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Activas</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactivas</option>
                    </select>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de colecciones -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Fecha Lanzamiento</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($collections)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-layer-group"></i>
                                    <p>No se encontraron colecciones</p>
                                    <a href="add_collection.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Crear primera colección
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($collections as $collection): ?>
                                <tr data-collection-id="<?= $collection['id'] ?>">
                                    <td><?= $collection['id'] ?></td>
                                    <td>
                                        <div class="collection-image">
                                            <?php if (!empty($collection['image'])): ?>
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($collection['image']) ?>" 
                                                     alt="<?= htmlspecialchars($collection['name']) ?>">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-layer-group"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="collection-name">
                                            <strong><?= htmlspecialchars($collection['name']) ?></strong>
                                            <?php if (!empty($collection['description'])): ?>
                                                <small><?= htmlspecialchars(substr($collection['description'], 0, 50)) ?><?= strlen($collection['description']) > 50 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="slug-code"><?= htmlspecialchars($collection['slug']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($collection['launch_date'])): ?>
                                            <span class="launch-date">
                                                <i class="fas fa-calendar"></i>
                                                <?= date('d/m/Y', strtotime($collection['launch_date'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= $collection['product_count'] ?> productos
                                        </span>
                                    </td>
                                    <td>
                                        <label class="toggle-switch" title="Activar/Desactivar colección">
                                            <input type="checkbox" 
                                                   class="collection-toggle" 
                                                   data-id="<?= $collection['id'] ?>"
                                                   <?= $collection['is_active'] ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_collection.php?id=<?= $collection['id'] ?>" 
                                               class="btn-icon btn-edit" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-icon btn-delete" 
                                                    onclick="deleteCollection(<?= $collection['id'] ?>, '<?= htmlspecialchars(addslashes($collection['name'])) ?>')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
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

    <!-- Modal de confirmación de eliminación -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmar Eliminación
                </h3>
                <button class="close-modal" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la colección <strong id="collectionName"></strong>?</p>
                <p class="warning-text">
                    <i class="fas fa-info-circle"></i>
                    Esta acción no se puede deshacer. Los productos asociados no serán eliminados.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i>
                    Eliminar
                </button>
            </div>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/admin/collections/collections.js"></script>
</body>
</html>
