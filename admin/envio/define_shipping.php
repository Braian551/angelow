<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Verificar rol de administrador
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No tienes permisos para acceder a esta área'];
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al verificar permisos. Por favor intenta nuevamente.'];
    header("Refresh:0");
    exit();
}

// Procesar acciones CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Validar y sanitizar entrada
$id = filter_var($id, FILTER_VALIDATE_INT);

// Manejar acciones
switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
            $base_cost = filter_input(INPUT_POST, 'base_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $delivery_time = filter_input(INPUT_POST, 'delivery_time', FILTER_SANITIZE_SPECIAL_CHARS);
            $estimated_days_min = filter_input(INPUT_POST, 'estimated_days_min', FILTER_VALIDATE_INT);
            $estimated_days_max = filter_input(INPUT_POST, 'estimated_days_max', FILTER_VALIDATE_INT);
            $free_shipping_minimum = filter_input(INPUT_POST, 'free_shipping_minimum', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $icon = filter_input(INPUT_POST, 'icon', FILTER_SANITIZE_SPECIAL_CHARS);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            if (empty($name) || $base_cost === false) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El nombre y costo son requeridos'];
                header("Location: define_shipping.php?action=add");
                exit();
            }

            try {
                $stmt = $conn->prepare("INSERT INTO shipping_methods (name, description, base_cost, delivery_time, estimated_days_min, estimated_days_max, free_shipping_minimum, icon, is_active, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Medellín')");
                $stmt->execute([$name, $description, $base_cost, $delivery_time, $estimated_days_min, $estimated_days_max, $free_shipping_minimum, $icon, $is_active]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Método de envío creado exitosamente'];
                header("Location: define_shipping.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al crear método de envío: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al crear el método de envío. Por favor intenta nuevamente.'];
                header("Location: define_shipping.php?action=add");
                exit();
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
            $base_cost = filter_input(INPUT_POST, 'base_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $delivery_time = filter_input(INPUT_POST, 'delivery_time', FILTER_SANITIZE_SPECIAL_CHARS);
            $estimated_days_min = filter_input(INPUT_POST, 'estimated_days_min', FILTER_VALIDATE_INT);
            $estimated_days_max = filter_input(INPUT_POST, 'estimated_days_max', FILTER_VALIDATE_INT);
            $free_shipping_minimum = filter_input(INPUT_POST, 'free_shipping_minimum', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $icon = filter_input(INPUT_POST, 'icon', FILTER_SANITIZE_SPECIAL_CHARS);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($name) || $base_cost === false) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El nombre y costo son requeridos'];
                header("Location: define_shipping.php?action=edit&id=" . $id);
                exit();
            }

            try {
                $stmt = $conn->prepare("UPDATE shipping_methods SET name = ?, description = ?, base_cost = ?, delivery_time = ?, estimated_days_min = ?, estimated_days_max = ?, free_shipping_minimum = ?, icon = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $base_cost, $delivery_time, $estimated_days_min, $estimated_days_max, $free_shipping_minimum, $icon, $is_active, $id]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Método de envío actualizado exitosamente'];
                header("Location: define_shipping.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar método de envío: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar el método de envío. Por favor intenta nuevamente.'];
                header("Location: define_shipping.php?action=edit&id=" . $id);
                exit();
            }
        }
        break;

    case 'delete':
        try {
            $stmt = $conn->prepare("DELETE FROM shipping_methods WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Método de envío eliminado exitosamente'];
            header("Location: define_shipping.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar método de envío: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar el método de envío. Por favor intenta nuevamente.'];
            header("Location: define_shipping.php");
            exit();
        }
        break;

    case 'toggle-status':
        try {
            $stmt = $conn->prepare("UPDATE shipping_methods SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado del método actualizado'];
            header("Location: define_shipping.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de método: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado del método.'];
            header("Location: define_shipping.php");
            exit();
        }
        break;
}

// Obtener todos los métodos de envío
function obtenerMetodosEnvio($conn)
{
    $sql = "SELECT * FROM shipping_methods ORDER BY base_cost ASC, name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$metodos = obtenerMetodosEnvio($conn);

// Obtener datos de un método específico para edición
$metodoActual = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = ?");
        $stmt->execute([$id]);
        $metodoActual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$metodoActual) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Método no encontrado'];
            header("Location: define_shipping.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al obtener método: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar el método.'];
        header("Location: define_shipping.php");
        exit();
    }
}

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir Métodos de Envío - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/envio/ruleshopping.css">
    <style>
        .icon-preview {
            font-size: 1.8rem;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .method-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-lightest);
            border-radius: var(--radius-md);
            margin-right: 12px;
        }

        .method-icon i {
            font-size: 1.6rem;
            color: var(--primary-color);
        }

        .method-header {
            display: flex;
            align-items: center;
        }

        .free-shipping-badge {
            background: var(--success-light);
            color: var(--success-color);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 1.2rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .city-badge {
            background: var(--primary-lightest);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 1.2rem;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-shipping-fast"></i> Métodos de Envío - Medellín
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <a href="<?= BASE_URL ?>/admin/envio/shipping_rules.php">Envios</a> / <span>Definir métodos</span>
                    </div>
                </div>

                <?php if (in_array($action, ['add', 'edit'])): ?>
                    <!-- Formulario para agregar/editar métodos -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= $action === 'add' ? 'Agregar Nuevo Método' : 'Editar Método' ?></h3>
                            <span class="city-badge">
                                <i class="fas fa-map-marker-alt"></i> Medellín
                            </span>
                        </div>
                        <div class="card-body">
                            <form id="shipping-method-form" method="POST" action="define_shipping.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . $id : '' ?>">
                                <div class="form-group">
                                    <label for="name">Nombre del método*</label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        value="<?= htmlspecialchars($metodoActual['name'] ?? '') ?>" required
                                        placeholder="Ej: Envío Express, Recogida en Tienda">
                                </div>

                                <div class="form-group">
                                    <label for="description">Descripción</label>
                                    <textarea id="description" name="description" class="form-control" rows="2"
                                        placeholder="Breve descripción del método de envío"><?= htmlspecialchars($metodoActual['description'] ?? '') ?></textarea>
                                </div>

                                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="base_cost">Costo de envío (COP)*</label>
                                        <input type="number" id="base_cost" name="base_cost" class="form-control"
                                            value="<?= htmlspecialchars($metodoActual['base_cost'] ?? '') ?>" min="0" step="100" required>
                                    </div>

                                    <div class="form-group" style="flex: 1;">
                                        <label for="free_shipping_minimum">Envío gratis desde (COP)</label>
                                        <input type="number" id="free_shipping_minimum" name="free_shipping_minimum" class="form-control"
                                            value="<?= htmlspecialchars($metodoActual['free_shipping_minimum'] ?? '') ?>" min="0" step="1000">
                                        <small class="text-muted">Dejar vacío si no aplica</small>
                                    </div>
                                </div>

                                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="delivery_time">Tiempo de entrega</label>
                                        <input type="text" id="delivery_time" name="delivery_time" class="form-control"
                                            value="<?= htmlspecialchars($metodoActual['delivery_time'] ?? '') ?>" placeholder="Ej: 3-5 días hábiles">
                                    </div>

                                    <div class="form-group" style="flex: 1;">
                                        <label for="city">Ciudad</label>
                                        <input type="text" id="city" name="city" class="form-control" value="Medellín" readonly style="background-color: var(--bg-hover);">
                                    </div>
                                </div>

                                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="estimated_days_min">Días mínimos estimados</label>
                                        <input type="number" id="estimated_days_min" name="estimated_days_min" class="form-control"
                                            value="<?= htmlspecialchars($metodoActual['estimated_days_min'] ?? '1') ?>" min="1" required>
                                    </div>

                                    <div class="form-group" style="flex: 1;">
                                        <label for="estimated_days_max">Días máximos estimados</label>
                                        <input type="number" id="estimated_days_max" name="estimated_days_max" class="form-control"
                                            value="<?= htmlspecialchars($metodoActual['estimated_days_max'] ?? '3') ?>" min="1" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="icon">Icono</label>
                                    <div style="display: flex; align-items: center;">
                                        <span class="icon-preview" id="icon-preview">
                                            <i class="<?= htmlspecialchars($metodoActual['icon'] ?? 'fas fa-truck') ?>"></i>
                                        </span>
                                        <select id="icon" name="icon" class="form-control" style="flex: 1;">
                                            <option value="fas fa-truck" <?= ($metodoActual['icon'] ?? 'fas fa-truck') === 'fas fa-truck' ? 'selected' : '' ?>>Envío</option>
                                            <option value="fas fa-shipping-fast" <?= ($metodoActual['icon'] ?? '') === 'fas fa-shipping-fast' ? 'selected' : '' ?>>Envío Rápido</option>
                                            <option value="fas fa-walking" <?= ($metodoActual['icon'] ?? '') === 'fas fa-walking' ? 'selected' : '' ?>>Recoger</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input"
                                            <?= isset($metodoActual['is_active']) && $metodoActual['is_active'] ? 'checked' : '' ?>>
                                        <label for="is_active" class="form-check-label">Método activo</label>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Crear Método' : 'Actualizar Método' ?>
                                    </button>
                                    <a href="define_shipping.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de métodos -->
                    <div class="actions-bar">
                        <a href="define_shipping.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Método
                        </a>
                        <div class="search-box2">
                            <input type="text" id="search-methods" placeholder="Buscar métodos...">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Métodos de Envío Disponibles</h3>
                            <span class="city-badge">
                                <i class="fas fa-map-marker-alt"></i> Medellín
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($metodos)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-shipping-fast"></i>
                                    <p>No hay métodos de envío configurados para Medellín</p>
                                    <a href="define_shipping.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear primer método
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Método</th>
                                                <th>Descripción</th>
                                                <th>Costo</th>
                                                <th>Tiempo de Entrega</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($metodos as $metodo): ?>
                                                <tr data-searchable="<?= strtolower(htmlspecialchars($metodo['name'] . ' ' . $metodo['description'])) ?>">
                                                    <td>
                                                        <div class="method-header">
                                                            <div class="method-icon">
                                                                <i class="<?= htmlspecialchars($metodo['icon']) ?>"></i>
                                                            </div>
                                                            <div>
                                                                <strong><?= htmlspecialchars($metodo['name']) ?></strong>
                                                                <?php if ($metodo['free_shipping_minimum']): ?>
                                                                    <div class="free-shipping-badge">
                                                                        <i class="fas fa-tag"></i> Gratis desde $<?= number_format($metodo['free_shipping_minimum'], 0, ',', '.') ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($metodo['description'] ?: 'Sin descripción') ?></td>
                                                    <td>
                                                        <?php if ($metodo['base_cost'] == 0): ?>
                                                            <strong class="text-success">GRATIS</strong>
                                                        <?php else: ?>
                                                            <strong>$<?= number_format($metodo['base_cost'], 0, ',', '.') ?></strong>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($metodo['delivery_time'] ?: $metodo['estimated_days_min'] . '-' . $metodo['estimated_days_max'] . ' días') ?>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge <?= $metodo['is_active'] ? 'active' : 'inactive' ?>">
                                                            <i class="fas fa-<?= $metodo['is_active'] ? 'check' : 'times' ?>"></i>
                                                            <?= $metodo['is_active'] ? 'Activo' : 'Inactivo' ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions">
                                                        <a href="define_shipping.php?action=edit&id=<?= $metodo['id'] ?>"
                                                            class="btn btn-sm btn-edit" title="Editar" data-tooltip="Editar método">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#"
                                                            class="btn btn-sm btn-delete delete-trigger"
                                                            title="Eliminar"
                                                            data-tooltip="Eliminar método"
                                                            data-id="<?= $metodo['id'] ?>"
                                                            data-name="<?= htmlspecialchars($metodo['name']) ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <a href="define_shipping.php?action=toggle-status&id=<?= $metodo['id'] ?>"
                                                            class="btn btn-sm btn-status"
                                                            title="<?= $metodo['is_active'] ? 'Desactivar' : 'Activar' ?>"
                                                            data-tooltip="<?= $metodo['is_active'] ? 'Desactivar método' : 'Activar método' ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="no-results" style="display: none;">
                                    <i class="fas fa-search"></i>
                                    <p>No se encontraron métodos que coincidan con tu búsqueda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="delete-confirm" id="delete-confirm-modal">
        <div class="delete-confirm-content">
            <div class="delete-confirm-header">
                <h3>Confirmar Eliminación</h3>
                <button class="delete-confirm-close" id="delete-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <p id="delete-modal-message">¿Estás seguro de que deseas eliminar este método de envío? Esta acción no se puede deshacer.</p>
            </div>
            <div class="delete-confirm-footer">
                <button class="btn btn-secondary" id="delete-modal-cancel">Cancelar</button>
                <a href="#" class="btn btn-danger" id="delete-modal-confirm">Eliminar</a>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('shipping-method-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const baseCost = parseFloat(document.getElementById('base_cost').value);
                    const estimatedDaysMin = parseInt(document.getElementById('estimated_days_min').value);
                    const estimatedDaysMax = parseInt(document.getElementById('estimated_days_max').value);

                    if (baseCost < 0) {
                        e.preventDefault();
                        showAlert('El costo no puede ser negativo', 'error');
                        return false;
                    }

                    if (estimatedDaysMin >= estimatedDaysMax) {
                        e.preventDefault();
                        showAlert('Los días mínimos deben ser menores que los días máximos', 'error');
                        return false;
                    }

                    return true;
                });
            }

            // Actualizar vista previa del icono
            const iconSelect = document.getElementById('icon');
            const iconPreview = document.getElementById('icon-preview');

            if (iconSelect && iconPreview) {
                iconSelect.addEventListener('change', function() {
                    const selectedIcon = this.value;
                    iconPreview.innerHTML = `<i class="${selectedIcon}"></i>`;
                });
            }

            // Funcionalidad de búsqueda
            const searchInput = document.getElementById('search-methods');
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            const noResults = document.getElementById('no-results');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let hasResults = false;

                    tableRows.forEach(row => {
                        const searchableText = row.getAttribute('data-searchable');
                        if (searchableText.includes(searchTerm)) {
                            row.style.display = '';
                            hasResults = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    noResults.style.display = hasResults ? 'none' : 'flex';
                });
            }

            // Modal de confirmación para eliminar
            const deleteModal = document.getElementById('delete-confirm-modal');
            const deleteModalClose = document.getElementById('delete-modal-close');
            const deleteModalCancel = document.getElementById('delete-modal-cancel');
            const deleteModalConfirm = document.getElementById('delete-modal-confirm');
            const deleteModalMessage = document.getElementById('delete-modal-message');

            let currentDeleteButton = null;

            // Manejar clic en botones de eliminar
            document.querySelectorAll('.delete-trigger').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentDeleteButton = this;
                    const methodId = this.getAttribute('data-id');
                    const methodName = this.getAttribute('data-name');

                    deleteModalMessage.textContent = `¿Estás seguro de que deseas eliminar el método "${methodName}"? Esta acción no se puede deshacer.`;
                    deleteModalConfirm.href = `define_shipping.php?action=delete&id=${methodId}`;

                    // Mostrar modal
                    deleteModal.style.display = 'flex';
                    setTimeout(() => {
                        deleteModal.classList.add('active');
                    }, 10);
                });
            });

            // Cerrar modal
            function closeDeleteModal() {
                deleteModal.classList.remove('active');
                setTimeout(() => {
                    deleteModal.style.display = 'none';
                }, 300);
                currentDeleteButton = null;
            }

            deleteModalClose.addEventListener('click', closeDeleteModal);
            deleteModalCancel.addEventListener('click', closeDeleteModal);

            // Cerrar modal al hacer clic fuera
            deleteModal.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>

</html>