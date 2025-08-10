<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

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

// Obtener ID de la orden
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Orden no especificada'];
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit();
}

$orderId = intval($_GET['id']);

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        $requiredFields = [
            'status', 'payment_status', 'shipping_address', 
            'shipping_city', 'delivery_notes'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        // Actualizar la orden
        $query = "UPDATE orders SET 
            status = :status,
            payment_status = :payment_status,
            shipping_address = :shipping_address,
            shipping_city = :shipping_city,
            delivery_notes = :delivery_notes,
            updated_at = NOW()
            WHERE id = :id";
            
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $_POST['status'],
            ':payment_status' => $_POST['payment_status'],
            ':shipping_address' => $_POST['shipping_address'],
            ':shipping_city' => $_POST['shipping_city'],
            ':delivery_notes' => $_POST['delivery_notes'],
            ':id' => $orderId
        ]);
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Orden actualizada correctamente'];
        header("Location: " . BASE_URL . "/admin/order/detail.php?id=$orderId");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

// Obtener información de la orden para editar
try {
    $query = "SELECT 
        o.*, 
        CONCAT(u.name) AS user_name,
        u.email AS user_email,
        u.phone AS user_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'La orden no existe'];
        header("Location: " . BASE_URL . "/admin/orders.php");
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Error al obtener detalles de la orden: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar los detalles de la orden'];
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit();
}

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}

// Estados disponibles
$statuses = [
    'pending' => 'Pendiente',
    'processing' => 'En proceso',
    'shipped' => 'Enviado',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
    'refunded' => 'Reembolsado'
];

$paymentStatuses = [
    'pending' => 'Pendiente',
    'paid' => 'Pagado',
    'failed' => 'Fallido',
    'refunded' => 'Reembolsado'
];

// Obtener ciudades de envío
try {
    $cities = $conn->query("SELECT city_name FROM delivery_cities WHERE is_active = 1 ORDER BY city_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $cities = [];
    error_log("Error al obtener ciudades: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Orden #<?= $order['order_number'] ?> - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/orderedit.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-edit"></i> Editar Orden #<?= $order['order_number'] ?>
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                        <a href="<?= BASE_URL ?>/admin/orders.php">Órdenes</a> / 
                        <a href="<?= BASE_URL ?>/admin/order/detail.php?id=<?= $orderId ?>">Detalle</a> / 
                        <span>Editar</span>
                    </div>
                </div>

                <div class="order-edit-container">
                    <form method="POST" class="order-edit-form">
                        <div class="form-section">
                            <h3>Información Básica</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="status">Estado de la Orden</label>
                                    <select id="status" name="status" class="form-control" required>
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $value === $order['status'] ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="payment_status">Estado de Pago</label>
                                    <select id="payment_status" name="payment_status" class="form-control" required>
                                        <?php foreach ($paymentStatuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $value === $order['payment_status'] ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Dirección de Envío</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="shipping_city">Ciudad</label>
                                    <select id="shipping_city" name="shipping_city" class="form-control" required>
                                        <option value="">Seleccione una ciudad</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>" <?= $city === $order['shipping_city'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($city) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label for="shipping_address">Dirección Completa</label>
                                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?= htmlspecialchars($order['shipping_address']) ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="delivery_notes">Instrucciones de Entrega</label>
                                    <textarea id="delivery_notes" name="delivery_notes" class="form-control" rows="3" required><?= htmlspecialchars($order['delivery_notes']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="<?= BASE_URL ?>/admin/order/detail.php?id=<?= $orderId ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
</body>
</html>