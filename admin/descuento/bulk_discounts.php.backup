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
            $min_quantity = (int)$_POST['min_quantity'];
            $max_quantity = !empty($_POST['max_quantity']) ? (int)$_POST['max_quantity'] : null;
            $discount_percentage = (float)$_POST['discount_percentage'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            if ($min_quantity <= 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'La cantidad mínima debe ser mayor a 0'];
                header("Location: bulk_discounts.php?action=add");
                exit();
            }

            if ($max_quantity !== null && $max_quantity <= $min_quantity) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'La cantidad máxima debe ser mayor a la mínima'];
                header("Location: bulk_discounts.php?action=add");
                exit();
            }

            if ($discount_percentage <= 0 || $discount_percentage > 100) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El porcentaje de descuento debe estar entre 1 y 100'];
                header("Location: bulk_discounts.php?action=add");
                exit();
            }

            try {
                $stmt = $conn->prepare("INSERT INTO bulk_discount_rules (min_quantity, max_quantity, discount_percentage, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$min_quantity, $max_quantity, $discount_percentage, $is_active]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de descuento creada exitosamente'];
                header("Location: bulk_discounts.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al crear regla de descuento: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al crear la regla de descuento. Por favor intenta nuevamente.'];
                header("Location: bulk_discounts.php?action=add");
                exit();
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $min_quantity = (int)$_POST['min_quantity'];
            $max_quantity = !empty($_POST['max_quantity']) ? (int)$_POST['max_quantity'] : null;
            $discount_percentage = (float)$_POST['discount_percentage'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            if ($min_quantity <= 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'La cantidad mínima debe ser mayor a 0'];
                header("Location: bulk_discounts.php?action=edit&id=" . $id);
                exit();
            }

            if ($max_quantity !== null && $max_quantity <= $min_quantity) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'La cantidad máxima debe ser mayor a la mínima'];
                header("Location: bulk_discounts.php?action=edit&id=" . $id);
                exit();
            }

            if ($discount_percentage <= 0 || $discount_percentage > 100) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'El porcentaje de descuento debe estar entre 1 y 100'];
                header("Location: bulk_discounts.php?action=edit&id=" . $id);
                exit();
            }

            try {
                $stmt = $conn->prepare("UPDATE bulk_discount_rules SET min_quantity = ?, max_quantity = ?, discount_percentage = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$min_quantity, $max_quantity, $discount_percentage, $is_active, $id]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de descuento actualizada exitosamente'];
                header("Location: bulk_discounts.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar regla de descuento: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar la regla de descuento. Por favor intenta nuevamente.'];
                header("Location: bulk_discounts.php?action=edit&id=" . $id);
                exit();
            }
        }
        break;

    case 'delete':
        try {
            $stmt = $conn->prepare("DELETE FROM bulk_discount_rules WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de descuento eliminada exitosamente'];
            header("Location: bulk_discounts.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar regla de descuento: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar la regla de descuento. Por favor intenta nuevamente.'];
            header("Location: bulk_discounts.php");
            exit();
        }
        break;

    case 'toggle-status':
        try {
            $stmt = $conn->prepare("UPDATE bulk_discount_rules SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado de la regla de descuento actualizado'];
            header("Location: bulk_discounts.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de regla de descuento: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado de la regla de descuento.'];
            header("Location: bulk_discounts.php");
            exit();
        }
        break;
}

// Obtener todas las reglas de descuento por cantidad
function obtenerReglasDescuento($conn) {
    $sql = "SELECT * FROM bulk_discount_rules ORDER BY min_quantity";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$reglasDescuento = obtenerReglasDescuento($conn);

// Obtener datos de una regla específica para edición
$reglaActual = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM bulk_discount_rules WHERE id = ?");
        $stmt->execute([$id]);
        $reglaActual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reglaActual) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Regla de descuento no encontrada'];
            header("Location: bulk_discounts.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al obtener regla de descuento: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar la regla de descuento.'];
        header("Location: bulk_discounts.php");
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
    <title>Descuentos por Cantidad - Panel de Administración</title>
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
                        <i class="fas fa-percentage"></i> Descuentos por Cantidad
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Descuentos</span>
                    </div>
                </div>

                <?php if (in_array($action, ['add', 'edit'])): ?>
                    <!-- Formulario para agregar/editar reglas de descuento -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= $action === 'add' ? 'Agregar Nueva Regla' : 'Editar Regla de Descuento' ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="discount-form" method="POST" action="bulk_discounts.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . $id : '' ?>">
                                <div class="form-group">
                                    <label for="min_quantity">Cantidad Mínima*</label>
                                    <input type="number" id="min_quantity" name="min_quantity" class="form-control"
                                        value="<?= htmlspecialchars($reglaActual['min_quantity'] ?? '') ?>" min="1" required>
                                </div>

                                <div class="form-group">
                                    <label for="max_quantity">Cantidad Máxima (opcional)</label>
                                    <input type="number" id="max_quantity" name="max_quantity" class="form-control"
                                        value="<?= htmlspecialchars($reglaActual['max_quantity'] ?? '') ?>" min="1">
                                    <small class="form-text text-muted">Dejar vacío para aplicar a cantidades superiores al mínimo</small>
                                </div>

                                <div class="form-group">
                                    <label for="discount_percentage">Porcentaje de Descuento*</label>
                                    <div class="input-group">
                                        <input type="number" id="discount_percentage" name="discount_percentage" class="form-control"
                                            value="<?= htmlspecialchars($reglaActual['discount_percentage'] ?? '') ?>" min="1" max="100" step="0.01" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input"
                                            <?= isset($reglaActual['is_active']) && $reglaActual['is_active'] ? 'checked' : '' ?>>
                                        <label for="is_active" class="form-check-label">Activa</label>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Crear Regla' : 'Actualizar Regla' ?>
                                    </button>
                                    <a href="bulk_discounts.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de reglas de descuento -->
                    <div class="actions-bar">
                        <a href="bulk_discounts.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Regla
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($reglasDescuento)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-percentage"></i>
                                    <p>No hay reglas de descuento configuradas</p>
                                    <a href="bulk_discounts.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera regla
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Rango de Cantidad</th>
                                                <th>Descuento</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reglasDescuento as $regla): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($regla['min_quantity']) ?>
                                                        <?php if ($regla['max_quantity']): ?>
                                                            - <?= htmlspecialchars($regla['max_quantity']) ?>
                                                        <?php else: ?>
                                                            o más
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($regla['discount_percentage']) ?>%</td>
                                                    <td>
                                                        <span class="status-badge <?= $regla['is_active'] ? 'active' : 'inactive' ?>">
                                                            <?= $regla['is_active'] ? 'Activa' : 'Inactiva' ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions">
                                                        <a href="bulk_discounts.php?action=edit&id=<?= $regla['id'] ?>"
                                                            class="btn btn-sm btn-edit" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="bulk_discounts.php?action=delete&id=<?= $regla['id'] ?>"
                                                            class="btn btn-sm btn-delete" title="Eliminar"
                                                            onclick="return confirm('¿Estás seguro de eliminar esta regla de descuento?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <a href="bulk_discounts.php?action=toggle-status&id=<?= $regla['id'] ?>"
                                                            class="btn btn-sm btn-status" title="<?= $regla['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación del formulario
        const form = document.getElementById('discount-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const minQty = parseInt(document.getElementById('min_quantity').value);
                const maxQty = document.getElementById('max_quantity').value;
                const discount = parseFloat(document.getElementById('discount_percentage').value);
                
                if (minQty <= 0) {
                    alert('La cantidad mínima debe ser mayor a 0');
                    e.preventDefault();
                    return false;
                }
                
                if (maxQty && parseInt(maxQty) <= minQty) {
                    alert('La cantidad máxima debe ser mayor a la mínima');
                    e.preventDefault();
                    return false;
                }
                
                if (discount <= 0 || discount > 100) {
                    alert('El porcentaje de descuento debe estar entre 1 y 100');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        }
    });
    </script>
</body>

</html>