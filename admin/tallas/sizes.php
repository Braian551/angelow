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
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            if (empty($name)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El nombre de la talla es requerido'];
                header("Location: sizes.php?action=add");
                exit();
            }

            try {
                $stmt = $conn->prepare("INSERT INTO sizes (name, description, is_active) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $is_active]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Talla creada exitosamente'];
                header("Location: sizes.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al crear talla: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al crear la talla. Por favor intenta nuevamente.'];
                header("Location: sizes.php?action=add");
                exit();
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($name)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El nombre de la talla es requerido'];
                header("Location: sizes.php?action=edit&id=" . $id);
                exit();
            }

            try {
                $stmt = $conn->prepare("UPDATE sizes SET name = ?, description = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $is_active, $id]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Talla actualizada exitosamente'];
                header("Location: sizes.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar talla: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar la talla. Por favor intenta nuevamente.'];
                header("Location: sizes.php?action=edit&id=" . $id);
                exit();
            }
        }
        break;

    case 'delete':
        try {
            // Verificar si la talla está en uso
            $stmt = $conn->prepare("SELECT COUNT(*) FROM product_size_variants WHERE size_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'No se puede eliminar la talla porque está asociada a productos'];
                header("Location: sizes.php");
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM sizes WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Talla eliminada exitosamente'];
            header("Location: sizes.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar talla: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar la talla. Por favor intenta nuevamente.'];
            header("Location: sizes.php");
            exit();
        }
        break;

    case 'toggle-status':
        try {
            $stmt = $conn->prepare("UPDATE sizes SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado de la talla actualizado'];
            header("Location: sizes.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de talla: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado de la talla.'];
            header("Location: sizes.php");
            exit();
        }
        break;
}

// Obtener todas las tallas para listar
function obtenerTallas($conn)
{
    $sql = "SELECT s.*, 
               (SELECT COUNT(*) FROM product_size_variants WHERE size_id = s.id) as product_count
            FROM sizes s
            ORDER BY name";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$tallas = obtenerTallas($conn);

// Obtener datos de una talla específica para edición
$tallaActual = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM sizes WHERE id = ?");
        $stmt->execute([$id]);
        $tallaActual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tallaActual) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Talla no encontrada'];
            header("Location: sizes.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al obtener talla: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar la talla.'];
        header("Location: sizes.php");
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
    <title>Gestión de Tallas - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/size/size.css">

</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-ruler"></i> Gestión de Tallas
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Tallas</span>
                    </div>
                </div>

                <?php if (in_array($action, ['add', 'edit'])): ?>
                    <!-- Formulario para agregar/editar tallas -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= $action === 'add' ? 'Agregar Nueva Talla' : 'Editar Talla' ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="size-form" method="POST" action="sizes.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . $id : '' ?>">
                                <div class="form-group">
                                    <label for="name">Nombre de la talla*</label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        value="<?= htmlspecialchars($tallaActual['name'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="description">Descripción</label>
                                    <textarea id="description" name="description" class="form-control"
                                        rows="3"><?= htmlspecialchars($tallaActual['description'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input"
                                            <?= isset($tallaActual['is_active']) && $tallaActual['is_active'] ? 'checked' : '' ?>>
                                        <label for="is_active" class="form-check-label">Activa</label>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Crear Talla' : 'Actualizar Talla' ?>
                                    </button>
                                    <a href="sizes.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de tallas -->
                    <div class="actions-bar">
                        <a href="sizes.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Talla
                        </a>
                        <div class="search-box2">
                            <input type="text" placeholder="Buscar tallas..." id="search-sizes">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($tallas)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-ruler"></i>
                                    <p>No hay tallas registradas</p>
                                    <a href="sizes.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera talla
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Productos</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tallas as $talla): ?>
                                                <tr data-searchable="<?= htmlspecialchars(strtolower($talla['name'])) ?>">
                                                    <td><?= htmlspecialchars($talla['name']) ?></td>
                                                    <td><?= htmlspecialchars($talla['description']) ?></td>
                                                    <td><?= $talla['product_count'] ?></td>
                                                    <td>
                                                        <span class="status-badge <?= $talla['is_active'] ? 'active' : 'inactive' ?>">
                                                            <?= $talla['is_active'] ? 'Activa' : 'Inactiva' ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions">
                                                        <a href="sizes.php?action=edit&id=<?= $talla['id'] ?>"
                                                            class="btn btn-sm btn-edit" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($talla['product_count'] == 0): ?>
                                                            <a href="sizes.php?action=delete&id=<?= $talla['id'] ?>"
                                                                class="btn btn-sm btn-delete js-size-delete" title="Eliminar"
                                                                data-size-name="<?= htmlspecialchars($talla['name']) ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="sizes.php?action=toggle-status&id=<?= $talla['id'] ?>"
                                                            class="btn btn-sm btn-status" title="<?= $talla['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div id="no-results" class="empty-state" style="display: none;">
                                        <i class="fas fa-search-minus"></i>
                                        <p>No se encontraron tallas que coincidan con tu búsqueda</p>
                                  
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php require_once __DIR__ . '/../../alertas/confirmation_modal.php'; ?>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/components/confirmationModal.js"></script>
    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // Buscador de tallas
    const searchInput = document.getElementById('search-sizes');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            let hasResults = false;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-searchable');
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar u ocultar mensaje de no resultados
            const noResults = document.getElementById('no-results');
            if (noResults) {
                if (!hasResults && searchTerm.length > 0) {
                    noResults.style.display = 'flex';
                } else {
                    noResults.style.display = 'none';
                }
            }
        });
    }

    const confirmationAvailable = typeof window.openConfirmationModal === 'function';
    const escapeHtml = (value = '') => value.replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[char] || char));

    document.querySelectorAll('.js-size-delete').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const deleteUrl = this.getAttribute('href');
            const sizeName = this.dataset.sizeName || 'esta talla';
            const confirmDeletion = () => {
                window.location.href = deleteUrl;
            };

            if (confirmationAvailable) {
                window.openConfirmationModal({
                    title: 'Eliminar talla',
                    message: `¿Deseas eliminar la talla <strong>${escapeHtml(sizeName)}</strong>?`,
                    confirmText: 'Eliminar',
                    cancelText: 'Cancelar',
                    type: 'warning',
                    onConfirm: confirmDeletion
                });
            } else if (confirm(`¿Estás seguro de eliminar la talla ${sizeName}?`)) {
                confirmDeletion();
            }
        });
    });
});
    </script>
</body>

</html>