<?php
// admin/descuento/generate_codes.php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos de admin
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
$id = filter_var($id, FILTER_VALIDATE_INT);

// Manejar acciones
switch ($action) {
    case 'generate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $discount_type = $_POST['discount_type'];
            $discount_value = $_POST['discount_value'] ?? null;
            $max_uses = $_POST['max_uses'] ?: null;
            $start_date = $_POST['start_date'] ?: null;
            $end_date = $_POST['end_date'] ?: null;
            $is_single_use = isset($_POST['is_single_use']) ? 1 : 0;
            $apply_to_all = isset($_POST['apply_to_all']) ? 1 : 0;
            $selected_products = json_decode($_POST['products'] ?? '[]', true) ?: [];
            $send_notification = isset($_POST['send_notification']) ? 1 : 0;
            $selected_users = json_decode($_POST['selected_users'] ?? '[]', true) ?: [];

            // Validaciones
            if (empty($discount_type)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Tipo de descuento es requerido'];
                header("Location: generate_codes.php?action=generate");
                exit();
            }

            // Validar valor según tipo
            if ($discount_type != 3) { // No es envío gratis
                if (empty($discount_value)) {
                    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Valor del descuento es requerido'];
                    header("Location: generate_codes.php?action=generate");
                    exit();
                }

                // Validar según tipo
                if ($discount_type == 1 && ($discount_value <= 0 || $discount_value > 100)) {
                    $_SESSION['alert'] = ['type' => 'error', 'message' => 'El porcentaje debe estar entre 1 y 100'];
                    header("Location: generate_codes.php?action=generate");
                    exit();
                }

                if ($discount_type == 2 && $discount_value <= 0) {
                    $_SESSION['alert'] = ['type' => 'error', 'message' => 'El monto fijo debe ser mayor a 0'];
                    header("Location: generate_codes.php?action=generate");
                    exit();
                }
            } else {
                $discount_value = 0; // Forzar 0 para envío gratis
            }

            // Validar fechas
            if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'La fecha de inicio no puede ser mayor a la fecha de fin'];
                header("Location: generate_codes.php?action=generate");
                exit();
            }

            // Generar código único
            $code = strtoupper(substr(md5(uniqid()), 0, 8));

            try {
                $conn->beginTransaction();

                // Insertar código de descuento principal
                $stmt = $conn->prepare("INSERT INTO discount_codes 
                    (code, discount_type_id, discount_value, max_uses, start_date, end_date, is_single_use, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $code,
                    $discount_type,
                    $discount_type == 3 ? 0 : $discount_value, // Envío gratis siempre tiene valor 0
                    $max_uses,
                    $start_date,
                    $end_date,
                    $is_single_use,
                    $_SESSION['user_id']
                ]);

                $discount_id = $conn->lastInsertId();

                // Insertar en la tabla específica según el tipo de descuento
                switch ($discount_type) {
                    case 1: // Porcentaje
                        $max_discount = $_POST['max_discount_amount'] ?? null;
                        $stmt = $conn->prepare("INSERT INTO percentage_discounts 
                            (discount_code_id, percentage, max_discount_amount) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([$discount_id, $discount_value, $max_discount]);
                        break;

                    case 2: // Monto fijo
                        $min_order = $_POST['min_order_amount'] ?? null;
                        $stmt = $conn->prepare("INSERT INTO fixed_amount_discounts 
                            (discount_code_id, amount, min_order_amount) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([$discount_id, $discount_value, $min_order]);
                        break;

                    case 3: // Envío gratis
                        $shipping_method = $_POST['shipping_method_id'] ?? null;
                        $stmt = $conn->prepare("INSERT INTO free_shipping_discounts 
                            (discount_code_id, shipping_method_id) 
                            VALUES (?, ?)");
                        $stmt->execute([$discount_id, $shipping_method]);
                        break;
                }

                // Si no aplica a todos, asignar productos seleccionados
                if (!$apply_to_all && !empty($selected_products)) {
                    $stmt = $conn->prepare("INSERT INTO discount_code_products (discount_code_id, product_id) VALUES (?, ?)");
                    foreach ($selected_products as $product_id) {
                        $stmt->execute([$discount_id, $product_id]);
                    }
                }

                $conn->commit();

                // Enviar notificación si se solicitó
                if ($send_notification && !empty($selected_users)) {
                    require_once __DIR__ . '/../admin/api/descuento/send_discount_email.php';
                    foreach ($selected_users as $user_id) {
                        sendDiscountEmail($user_id, $code, $discount_type, $discount_value, $end_date);
                    }
                }

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Código de descuento generado exitosamente'];
                header("Location: generate_codes.php");
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Error al generar código: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al generar el código. Por favor intenta nuevamente.'];
                header("Location: generate_codes.php?action=generate");
                exit();
            }
        }
        break;

    case 'delete':
        try {
            // Eliminar de las tablas específicas primero
            $stmt = $conn->prepare("DELETE FROM percentage_discounts WHERE discount_code_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM fixed_amount_discounts WHERE discount_code_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM free_shipping_discounts WHERE discount_code_id = ?");
            $stmt->execute([$id]);

            // Eliminar productos asociados
            $stmt = $conn->prepare("DELETE FROM discount_code_products WHERE discount_code_id = ?");
            $stmt->execute([$id]);

            // Finalmente eliminar el código principal
            $stmt = $conn->prepare("DELETE FROM discount_codes WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Código de descuento eliminado'];
            header("Location: generate_codes.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar código: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar el código.'];
            header("Location: generate_codes.php");
            exit();
        }
        break;
}

// Obtener todos los códigos de descuento con información de sus tipos específicos
function obtenerCodigosDescuento($conn)
{
    $sql = "SELECT dc.*, dt.name as discount_type_name,
               (SELECT COUNT(*) FROM discount_code_products WHERE discount_code_id = dc.id) as product_count,
               (SELECT COUNT(*) FROM discount_code_usage WHERE discount_code_id = dc.id) as used_count,
               pd.percentage, pd.max_discount_amount,
               fd.amount, fd.min_order_amount,
               fs.shipping_method_id
            FROM discount_codes dc
            JOIN discount_types dt ON dc.discount_type_id = dt.id
            LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id
            LEFT JOIN fixed_amount_discounts fd ON dc.id = fd.discount_code_id
            LEFT JOIN free_shipping_discounts fs ON dc.id = fs.discount_code_id
            ORDER BY dc.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$codigos = obtenerCodigosDescuento($conn);

// Obtener productos para asignar a descuentos
function obtenerProductos($conn)
{
    $sql = "SELECT id, name FROM products WHERE is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$productos = obtenerProductos($conn);

// Obtener usuarios clientes
function obtenerUsuarios($conn)
{
    $sql = "SELECT id, name, email, phone FROM users WHERE role = 'customer' ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$usuarios = obtenerUsuarios($conn);

// Obtener tipos de descuento
function obtenerTiposDescuento($conn)
{
    $sql = "SELECT * FROM discount_types WHERE is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$tiposDescuento = obtenerTiposDescuento($conn);

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
    <title>Generar Códigos de Descuento - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_codes1.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-percentage"></i> Generar Códigos de Descuento
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Descuentos</span>
                    </div>
                </div>

                <?php if ($action === 'generate'): ?>
                    <!-- Formulario para generar códigos -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Generar Nuevo Código de Descuento</h3>
                        </div>
                        <div class="card-body">
                            <form id="discount-form" method="POST" action="generate_codes.php?action=generate">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="discount_type">Tipo de Descuento*</label>
                                        <select id="discount_type" name="discount_type" class="form-control" required>
                                            <option value="">Seleccionar tipo</option>
                                            <?php foreach ($tiposDescuento as $tipo): ?>
                                                <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="discount-value-group">
                                        <label for="discount_value">Valor del Descuento*</label>
                                        <div class="input-group">
                                            <input type="number" id="discount_value" name="discount_value"
                                                class="form-control" min="1" max="100" step="0.01">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="fixed-amount-group" style="display: none;">
                                        <label for="fixed_amount">Monto Fijo*</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" id="fixed_amount" name="discount_value"
                                                class="form-control" min="1" step="0.01">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="max-discount-group" style="display: none;">
                                        <label for="max_discount_amount">Monto Máximo (opcional)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" id="max_discount_amount" name="max_discount_amount"
                                                class="form-control" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="min-order-group" style="display: none;">
                                        <label for="min_order_amount">Mínimo de Compra (opcional)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" id="min_order_amount" name="min_order_amount"
                                                class="form-control" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="shipping-method-group" style="display: none;">
                                        <label for="shipping_method_id">Método de Envío (opcional)</label>
                                        <select id="shipping_method_id" name="shipping_method_id" class="form-control">
                                            <option value="">Todos los métodos</option>
                                            <!-- Opciones de métodos de envío -->
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="max_uses">Usos Máximos (opcional)</label>
                                        <input type="number" id="max_uses" name="max_uses" class="form-control" min="1">
                                        <small class="form-text text-muted">Dejar vacío para usos ilimitados</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="is_single_use">
                                            <input type="checkbox" id="is_single_use" name="is_single_use" class="form-check-input">
                                            <span class="form-check-label">Uso único por cliente</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="start_date">Fecha de Inicio (opcional)</label>
                                        <input type="datetime-local" id="start_date" name="start_date" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="end_date">Fecha de Expiración (opcional)</label>
                                        <input type="datetime-local" id="end_date" name="end_date" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="apply_to_all" name="apply_to_all" class="form-check-input check2" checked>
                                        <span class="form-check-label">Aplicar a todos los productos</span>
                                    </label>
                                    <button type="button" id="open-products-modal" class="btn btn-outline-primary" style="display: none; margin-top: 10px;">
                                        <i class="fas fa-boxes"></i> Seleccionar productos específicos
                                    </button>
                                    <input type="hidden" name="products" id="selected-products" value="[]">
                                </div>

                                <div class="form-group notification-section">
                                    <label>
                                        <input type="checkbox" id="send_notification" name="send_notification" class="form-check-input">
                                        <span class="form-check-label">Enviar notificación por email</span>
                                    </label>
                                </div>

                                <!-- Grupo de notificación por email (inicialmente oculto) -->
                                <div class="form-group" id="notification-email-group" style="display: none;">
                                    <label>Usuarios destinatarios</label>
                                    <div class="user-selection">
                                        <button type="button" id="open-user-modal" class="btn btn-outline-primary">
                                            <i class="fas fa-user-plus"></i> Seleccionar usuarios
                                        </button>

                                        <!-- Información de usuarios seleccionados -->
                                        <div id="selected-users-info" class="selected-user-info" style="display: none; margin-top: 10px;">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                <span id="selected-users-count-display"></span>
                                                <button type="button" id="clear-selected-users" class="btn btn-sm btn-outline-danger" style="margin-left: 10px;">
                                                    <i class="fas fa-times"></i> Limpiar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="selected_users" name="selected_users" value="[]">
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-barcode"></i> Generar Código
                                    </button>
                                    <a href="generate_codes.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de códigos -->
                    <div class="actions-bar">
                        <a href="generate_codes.php?action=generate" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Generar Código
                        </a>
                        <div class="search-box2">
                            <input type="text" placeholder="Buscar códigos..." id="search-codes">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($codigos)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-percentage"></i>
                                    <p>No hay códigos de descuento generados</p>
                                    <a href="generate_codes.php?action=generate" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Generar primer código
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Tipo</th>
                                                <th>Valor</th>
                                                <th>Usos</th>
                                                <th>Válido hasta</th>
                                                <th>Productos</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($codigos as $codigo): ?>
                                                <tr data-searchable="<?= htmlspecialchars(strtolower($codigo['code'])) ?>">
                                                    <td>
                                                        <span class="discount-code"><?= htmlspecialchars($codigo['code']) ?></span>
                                                        <?php if ($codigo['is_single_use']): ?>
                                                            <span class="badge badge-info">Único</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($codigo['discount_type_name']) ?></td>
                                                    <td>
                                                        <?php if ($codigo['discount_type_id'] == 3): ?>
                                                            Envío gratis
                                                        <?php elseif ($codigo['discount_type_id'] == 1): ?>
                                                            <?= $codigo['percentage'] ?>%
                                                            <?php if ($codigo['max_discount_amount']): ?>
                                                                <br><small>Máx: $<?= number_format($codigo['max_discount_amount'], 2) ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            $<?= number_format($codigo['amount'], 2) ?>
                                                            <?php if ($codigo['min_order_amount']): ?>
                                                                <br><small>Mín: $<?= number_format($codigo['min_order_amount'], 2) ?></small>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= $codigo['used_count'] ?>
                                                        <?php if ($codigo['max_uses']): ?>
                                                            / <?= $codigo['max_uses'] ?>
                                                        <?php else: ?>
                                                            / ∞
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= $codigo['end_date'] ? date('d/m/Y', strtotime($codigo['end_date'])) : 'Sin límite' ?>
                                                    </td>
                                                    <td>
                                                        <?= $codigo['product_count'] > 0 ? $codigo['product_count'] . ' productos' : 'Todos' ?>
                                                    </td>
                                                    <td class="actions">
                                                        <a href="#" class="btn btn-sm btn-info btn-copy"
                                                            data-code="<?= htmlspecialchars($codigo['code']) ?>" title="Copiar">
                                                            <i class="fas fa-copy"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/admin/api/descuento/generate_pdf.php?id=<?= $codigo['id'] ?>"
                                                            class="btn btn-sm btn-secondary" title="Descargar PDF">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                        <a href="generate_codes.php?action=delete&id=<?= $codigo['id'] ?>"
                                                            class="btn btn-sm btn-danger" title="Eliminar"
                                                            onclick="return confirm('¿Estás seguro de eliminar este código?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div id="no-results" class="empty-state" style="display: none;">
                                        <i class="fas fa-search-minus"></i>
                                        <p>No se encontraron códigos que coincidan con tu búsqueda</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php require_once __DIR__ . '/../../admin/descuento/modals/modal_usuario.php'; ?>
    <?php require_once __DIR__ . '/../../admin/descuento/modals/modal_producto.php'; ?>



    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <?php require_once __DIR__ . '/../../js/admin/descuento/generate_codesjs.php'; ?>
</body>

</html>