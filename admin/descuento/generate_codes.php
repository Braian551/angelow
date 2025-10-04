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
$edit_id = $_GET['edit'] ?? 0;

// Manejar acciones
switch ($action) {
    case 'generate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'includes/generate_code_process.php';
            exit();
        }
        break;

    case 'edit':
        // Redirigir al formulario de edición - CORREGIDO
        header("Location: generate_codes.php?action=generate&edit=" . $id);
        exit();
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'includes/update_code_process.php';
            exit();
        }
        break;

    case 'toggle_status':
        try {
            // Cambiar estado del código
            $stmt = $conn->prepare("SELECT is_active FROM discount_codes WHERE id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($code) {
                $new_status = $code['is_active'] ? 0 : 1;
                $stmt = $conn->prepare("UPDATE discount_codes SET is_active = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado del código actualizado correctamente'];
            } else {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Código no encontrado'];
            }
            header("Location: generate_codes.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado del código.'];
            header("Location: generate_codes.php");
            exit();
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

// Obtener datos necesarios
require_once 'includes/data_functions.php';

$codigos = obtenerCodigosDescuento($conn);
$productos = obtenerProductos($conn);
$usuarios = obtenerUsuarios($conn);
$tiposDescuento = obtenerTiposDescuento($conn);
$metodosEnvio = obtenerMetodosEnvio($conn);

// Si estamos en modo edición, obtener datos del código con consulta mejorada
$codigo_editar = null;
if ($edit_id) {
    try {
        $stmt = $conn->prepare("
            SELECT dc.*, 
                   pd.percentage, pd.max_discount_amount,
                   fad.amount, fad.min_order_amount,
                   fsd.shipping_method_id,
                   GROUP_CONCAT(dcp.product_id) as product_ids
            FROM discount_codes dc
            LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id AND dc.discount_type_id = 1
            LEFT JOIN fixed_amount_discounts fad ON dc.id = fad.discount_code_id AND dc.discount_type_id = 2
            LEFT JOIN free_shipping_discounts fsd ON dc.id = fsd.discount_code_id AND dc.discount_type_id = 3
            LEFT JOIN discount_code_products dcp ON dc.id = dcp.discount_code_id
            WHERE dc.id = ?
            GROUP BY dc.id
        ");
        $stmt->execute([$edit_id]);
        $codigo_editar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$codigo_editar) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Código no encontrado'];
            header("Location: generate_codes.php");
            exit();
        }
        
        // Pasar datos a JavaScript para inicializar campos
        echo "<script>
            window.editMode = true;
            window.editCodeData = " . json_encode($codigo_editar, JSON_UNESCAPED_UNICODE) . ";
        </script>";
    } catch (PDOException $e) {
        error_log("Error al obtener código para edición: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar los datos del código.'];
        header("Location: generate_codes.php");
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
    <title><?= $edit_id ? 'Editar' : 'Generar' ?> Códigos de Descuento - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <!-- CSS separados por módulos -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/form.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/table.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/modals/modal_base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/modals/modal_products.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/descuento/generate_code/modals/modal_users.css">
 
</head>

<body>
    <div class="admin-container">
        
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                 <div class="page-header">
                    <h1>
                        <i class="fas fa-percentage"></i> Códigos de Descuento
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin/dashboardadmin.php">Dashboard</a> / <span>Descuentos</span>
                    </div>
                </div>
                <?php if ($action === 'generate' || $edit_id): ?>
                    <?php require_once 'includes/generate_form.php'; ?>
                <?php else: ?>
                    <?php require_once 'includes/codes_list.php'; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php if ($action === 'generate' || $edit_id): ?>
        <?php require_once __DIR__ . '/../../admin/descuento/modals/modal_usuario.php'; ?>
        <?php require_once __DIR__ . '/../../admin/descuento/modals/modal_producto.php'; ?>
 
    <?php endif; ?>

    <!-- Script para pasar datos de PHP a JavaScript -->
    <script>
        // Pasar usuarios de PHP a JavaScript
        window.allUsersFromPHP = <?= json_encode($usuarios, JSON_UNESCAPED_UNICODE) ?>;
        window.allProductsFromPHP = <?= json_encode($productos, JSON_UNESCAPED_UNICODE) ?>;
        window.editMode = <?= $edit_id ? 'true' : 'false' ?>;
        window.editCodeData = <?= $codigo_editar ? json_encode($codigo_editar, JSON_UNESCAPED_UNICODE) : 'null' ?>;
        
        // Pasar base URL para las peticiones AJAX
        window.BASE_URL = '<?= BASE_URL ?>';
        
        // Inicialización mejorada para modo edición
        document.addEventListener('DOMContentLoaded', function() {
            // Si estamos en modo edición, forzar la inicialización de campos
            if (window.editMode && window.editCodeData) {
                console.log('Modo edición detectado, inicializando campos...', window.editCodeData);
                
                // Esperar a que el DOM esté completamente listo
                setTimeout(function() {
                    // Disparar evento change en el tipo de descuento para mostrar campos correctos
                    const discountTypeSelect = document.getElementById('discount_type');
                    if (discountTypeSelect) {
                        discountTypeSelect.dispatchEvent(new Event('change'));
                    }
                    
                    // Inicializar tooltips
                    initializeTooltips();
                }, 500);
            }
        });
        
        // Función para inicializar tooltips
        function initializeTooltips() {
            // Tooltips para botones de la tabla
            const buttonsWithTooltips = document.querySelectorAll('[data-tooltip]');
            buttonsWithTooltips.forEach(button => {
                button.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = this.getAttribute('data-tooltip');
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                    tooltip.style.left = (rect.left + (rect.width - tooltip.offsetWidth) / 2) + 'px';
                    
                    this._tooltip = tooltip;
                });
                
                button.addEventListener('mouseleave', function() {
                    if (this._tooltip && document.body.contains(this._tooltip)) {
                        document.body.removeChild(this._tooltip);
                    }
                });
            });
        }
    </script>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    
    <?php if ($action === 'generate' || $edit_id): ?>
        <?php require_once __DIR__ . '/../../js/admin/descuento/generate_code/generate_codesjs.php'; ?>
    <?php endif; ?>
</body>

</html>