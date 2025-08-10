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
            $min_price = filter_input(INPUT_POST, 'min_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $max_price = filter_input(INPUT_POST, 'max_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $shipping_cost = filter_input(INPUT_POST, 'shipping_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            if ($min_price === false || $shipping_cost === false) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Los valores numéricos son requeridos'];
                header("Location: shipping_rules.php?action=add");
                exit();
            }

            try {
                $stmt = $conn->prepare("INSERT INTO shipping_price_rules (min_price, max_price, shipping_cost, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$min_price, $max_price, $shipping_cost, $is_active]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de envío creada exitosamente'];
                header("Location: shipping_rules.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al crear regla de envío: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al crear la regla de envío. Por favor intenta nuevamente.'];
                header("Location: shipping_rules.php?action=add");
                exit();
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $min_price = filter_input(INPUT_POST, 'min_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $max_price = filter_input(INPUT_POST, 'max_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $shipping_cost = filter_input(INPUT_POST, 'shipping_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($min_price === false || $shipping_cost === false) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Los valores numéricos son requeridos'];
                header("Location: shipping_rules.php?action=edit&id=" . $id);
                exit();
            }

            try {
                $stmt = $conn->prepare("UPDATE shipping_price_rules SET min_price = ?, max_price = ?, shipping_cost = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$min_price, $max_price, $shipping_cost, $is_active, $id]);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de envío actualizada exitosamente'];
                header("Location: shipping_rules.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar regla de envío: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar la regla de envío. Por favor intenta nuevamente.'];
                header("Location: shipping_rules.php?action=edit&id=" . $id);
                exit();
            }
        }
        break;

    case 'delete':
        try {
            $stmt = $conn->prepare("DELETE FROM shipping_price_rules WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Regla de envío eliminada exitosamente'];
            header("Location: shipping_rules.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar regla de envío: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar la regla de envío. Por favor intenta nuevamente.'];
            header("Location: shipping_rules.php");
            exit();
        }
        break;

    case 'toggle-status':
        try {
            $stmt = $conn->prepare("UPDATE shipping_price_rules SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado de la regla actualizado'];
            header("Location: shipping_rules.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de regla: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado de la regla.'];
            header("Location: shipping_rules.php");
            exit();
        }
        break;
}

// Obtener todas las reglas de envío
function obtenerReglasEnvio($conn)
{
    $sql = "SELECT * FROM shipping_price_rules ORDER BY min_price";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$reglas = obtenerReglasEnvio($conn);

// Obtener datos de una regla específica para edición
$reglaActual = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM shipping_price_rules WHERE id = ?");
        $stmt->execute([$id]);
        $reglaActual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reglaActual) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Regla no encontrada'];
            header("Location: shipping_rules.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al obtener regla: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar la regla.'];
        header("Location: shipping_rules.php");
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
    <title>Reglas de Envío - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/envio/ruleshopping.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-truck"></i> Reglas de Envío por Precio
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Envíos</span> / <span>Reglas por precio</span>
                    </div>
                </div>

                <?php if (in_array($action, ['add', 'edit'])): ?>
                    <!-- Formulario para agregar/editar reglas -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= $action === 'add' ? 'Agregar Nueva Regla' : 'Editar Regla' ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="shipping-rule-form" method="POST" action="shipping_rules.php?action=<?= $action ?><?= $action === 'edit' ? '&id=' . $id : '' ?>">
                                <div class="form-group">
                                    <label for="min_price">Precio mínimo (COP)*</label>
                                    <input type="number" id="min_price" name="min_price" class="form-control" 
                                        value="<?= htmlspecialchars($reglaActual['min_price'] ?? '') ?>" min="0" step="0.01" required>
                                </div>

                                <div class="form-group">
                                    <label for="max_price">Precio máximo (COP)</label>
                                    <input type="number" id="max_price" name="max_price" class="form-control" 
                                        value="<?= htmlspecialchars($reglaActual['max_price'] ?? '') ?>" min="0" step="0.01">
                                    <small class="text-muted">Dejar vacío para "sin límite"</small>
                                </div>

                                <div class="form-group">
                                    <label for="shipping_cost">Costo de envío (COP)*</label>
                                    <input type="number" id="shipping_cost" name="shipping_cost" class="form-control" 
                                        value="<?= htmlspecialchars($reglaActual['shipping_cost'] ?? '') ?>" min="0" step="0.01" required>
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
                                    <a href="shipping_rules.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de reglas -->
                    <div class="actions-bar">
                        <a href="shipping_rules.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Regla
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($reglas)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-truck"></i>
                                    <p>No hay reglas de envío configuradas</p>
                                    <a href="shipping_rules.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera regla
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Rango de Precio (COP)</th>
                                                <th>Costo de Envío</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reglas as $regla): ?>
                                                <tr>
                                                    <td>
                                                        <?= number_format($regla['min_price'], 0, ',', '.') ?> 
                                                        <?= $regla['max_price'] ? ' - ' . number_format($regla['max_price'], 0, ',', '.') : ' o más' ?>
                                                    </td>
                                                    <td>$<?= number_format($regla['shipping_cost'], 0, ',', '.') ?></td>
                                                    <td>
                                                        <span class="status-badge <?= $regla['is_active'] ? 'active' : 'inactive' ?>">
                                                            <?= $regla['is_active'] ? 'Activa' : 'Inactiva' ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions">
                                                        <a href="shipping_rules.php?action=edit&id=<?= $regla['id'] ?>" 
                                                            class="btn btn-sm btn-edit" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="shipping_rules.php?action=delete&id=<?= $regla['id'] ?>" 
                                                            class="btn btn-sm btn-delete" title="Eliminar"
                                                            onclick="return confirm('¿Estás seguro de eliminar esta regla?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <a href="shipping_rules.php?action=toggle-status&id=<?= $regla['id'] ?>" 
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
        const form = document.getElementById('shipping-rule-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const minPrice = parseFloat(document.getElementById('min_price').value);
                const maxPrice = document.getElementById('max_price').value ? parseFloat(document.getElementById('max_price').value) : null;
                const shippingCost = parseFloat(document.getElementById('shipping_cost').value);
                
                if (maxPrice !== null && minPrice >= maxPrice) {
                    e.preventDefault();
                    showAlert('El precio mínimo debe ser menor que el precio máximo', 'error');
                    return false;
                }
                
                if (shippingCost < 0) {
                    e.preventDefault();
                    showAlert('El costo de envío no puede ser negativo', 'error');
                    return false;
                }
                
                return true;
            });
        }
    });
    </script>
</body>

</html>