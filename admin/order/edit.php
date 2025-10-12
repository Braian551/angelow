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
        $requiredFields = ['status', 'payment_status', 'shipping_address', 'shipping_city'];
        
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Todos los campos son requeridos");
            }
        }
        
        // Validar valores de estados
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $validPaymentStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($_POST['status'], $validStatuses)) {
            throw new Exception("Estado de orden inválido");
        }
        
        if (!in_array($_POST['payment_status'], $validPaymentStatuses)) {
            throw new Exception("Estado de pago inválido");
        }
        
        // Obtener nombre del usuario actual para el historial
        $stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmtUser->execute([$_SESSION['user_id']]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $currentUserName = $currentUser['name'] ?? 'Administrador';
        
        // Configurar variables de sesión MySQL para el trigger
        $conn->exec("SET @current_user_id = '{$_SESSION['user_id']}'");
        $conn->exec("SET @current_user_name = '{$currentUserName}'");
        $conn->exec("SET @current_user_ip = '{$_SERVER['REMOTE_ADDR']}'");
        
        // Actualizar la orden (los triggers registrarán automáticamente los cambios)
        $query = "UPDATE orders SET 
            status = :status,
            payment_status = :payment_status,
            shipping_address = :shipping_address,
            shipping_city = :shipping_city,
            delivery_notes = :delivery_notes,
            notes = :notes,
            updated_at = NOW()
            WHERE id = :id";
            
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':status' => $_POST['status'],
            ':payment_status' => $_POST['payment_status'],
            ':shipping_address' => trim($_POST['shipping_address']),
            ':shipping_city' => trim($_POST['shipping_city']),
            ':delivery_notes' => trim($_POST['delivery_notes'] ?? ''),
            ':notes' => trim($_POST['notes'] ?? ''),
            ':id' => $orderId
        ]);
        
        // Limpiar variables de sesión MySQL
        $conn->exec("SET @current_user_id = NULL");
        $conn->exec("SET @current_user_name = NULL");
        $conn->exec("SET @current_user_ip = NULL");
        
        if ($result) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Orden actualizada correctamente'];
            header("Location: " . BASE_URL . "/admin/order/detail.php?id=$orderId");
            exit();
        } else {
            throw new Exception("Error al actualizar la orden");
        }
        
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

// Obtener información de la orden para editar
try {
    $query = "SELECT 
        o.*, 
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        u.identification_number,
        u.identification_type
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
    
    // Obtener items de la orden
    $itemsQuery = "SELECT 
        oi.*,
        p.slug AS product_slug,
        pi.image_path AS product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener detalles de la orden: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar los detalles de la orden'];
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit();
}

// Funciones helper
function formatCurrency($amount) {
    if ($amount === null) return 'N/A';
    return '$' . number_format($amount, 0, ',', '.');
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
    $cities = ['Medellín'];
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/detail.css">
    <style>
        /* Estilos adicionales específicos para editar con glassmorphism */
        .order-edit-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
        }

        .edit-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 20px;
            border: 1px solid rgba(0, 119, 182, 0.18);
            box-shadow: 0 8px 32px rgba(0, 119, 182, 0.15), 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 0;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .edit-card:hover {
            box-shadow: 0 12px 40px rgba(0, 119, 182, 0.2), 0 24px 70px rgba(0, 0, 0, 0.18);
        }

        .card-header {
            padding: 2rem 2.5rem;
            background: rgba(255, 255, 255, 0.5);
            border-bottom: 1px solid rgba(0, 119, 182, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.8rem;
            color: #334155;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .card-header h3 i {
            color: #0077b6;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 119, 182, 0.12);
            border-radius: 10px;
            padding: 8px;
        }

        .card-body {
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-grid:last-child {
            margin-bottom: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.4rem;
            font-weight: 600;
            color: #475569;
        }

        .form-group label i {
            color: #0077b6;
            font-size: 1.3rem;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem 1.4rem;
            border: 2px solid rgba(0, 119, 182, 0.15);
            border-radius: 12px;
            font-size: 1.5rem;
            color: #334155;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:hover {
            border-color: rgba(0, 119, 182, 0.3);
            background: rgba(255, 255, 255, 1);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(0, 119, 182, 0.5);
            box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.1);
            background: rgba(255, 255, 255, 1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230077b6' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.4rem center;
            padding-right: 3.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: flex-end;
            padding: 2.5rem;
            background: rgba(248, 250, 252, 0.5);
            border-top: 1px solid rgba(0, 119, 182, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .btn {
            padding: 1.2rem 2.5rem;
            border-radius: 12px;
            font-size: 1.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .btn i {
            font-size: 1.4rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0077b6 0%, #00a6fb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 119, 182, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #005a8d 0%, #0086cc 100%);
            box-shadow: 0 6px 20px rgba(0, 119, 182, 0.4);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(248, 250, 252, 0.9);
            color: #64748b;
            border: 2px solid rgba(0, 119, 182, 0.15);
        }

        .btn-secondary:hover {
            background: rgba(241, 245, 249, 1);
            border-color: rgba(0, 119, 182, 0.25);
            color: #475569;
            transform: translateY(-2px);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        /* Información del cliente */
        .client-info-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 20px;
            border: 1px solid rgba(0, 119, 182, 0.18);
            box-shadow: 0 8px 32px rgba(0, 119, 182, 0.15);
            padding: 0;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 1.3rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-label i {
            color: #0077b6;
            font-size: 1.2rem;
        }

        .info-value {
            font-size: 1.5rem;
            color: #334155;
            font-weight: 600;
        }

        /* Productos en la orden */
        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .products-table thead th {
            background: rgba(0, 119, 182, 0.08);
            padding: 1.2rem 1.5rem;
            text-align: left;
            font-size: 1.3rem;
            font-weight: 600;
            color: #334155;
            border-bottom: 2px solid rgba(0, 119, 182, 0.15);
        }

        .products-table thead th:first-child {
            border-radius: 10px 0 0 0;
        }

        .products-table thead th:last-child {
            border-radius: 0 10px 0 0;
        }

        .products-table tbody td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(0, 119, 182, 0.08);
            font-size: 1.4rem;
            color: #475569;
        }

        .products-table tbody tr:last-child td {
            border-bottom: none;
        }

        .products-table tbody tr:hover {
            background: rgba(0, 119, 182, 0.03);
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(0, 119, 182, 0.15);
        }

        .totals-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(0, 119, 182, 0.15);
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            font-size: 1.5rem;
        }

        .totals-row.total {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0077b6;
            padding-top: 1.5rem;
            margin-top: 1rem;
            border-top: 2px solid rgba(0, 119, 182, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .order-edit-container {
                padding: 1rem 0;
            }

            .card-header,
            .card-body {
                padding: 1.8rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
                padding: 1.8rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .products-table {
                font-size: 1.3rem;
            }

            .products-table thead th,
            .products-table tbody td {
                padding: 1rem;
            }

            .card-header h3 {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .card-header h3 {
                font-size: 1.5rem;
                gap: 0.6rem;
            }

            .card-header h3 i {
                width: 32px;
                height: 32px;
            }

            .form-control {
                font-size: 1.4rem;
                padding: 1rem 1.2rem;
            }

            .btn {
                font-size: 1.4rem;
                padding: 1rem 2rem;
            }
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
                    <!-- Información del cliente -->
                    <div class="client-info-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-user"></i>
                                Información del Cliente
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">
                                        <i class="fas fa-user-circle"></i>
                                        Cliente
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($order['user_name'] ?: 'Cliente no registrado') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">
                                        <i class="fas fa-envelope"></i>
                                        Email
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($order['user_email'] ?: 'N/A') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">
                                        <i class="fas fa-phone"></i>
                                        Teléfono
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($order['user_phone'] ?: 'N/A') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">
                                        <i class="fas fa-id-card"></i>
                                        Documento
                                    </span>
                                    <span class="info-value"><?= htmlspecialchars($order['identification_number'] ?: 'N/A') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos de la orden -->
                    <div class="edit-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-shopping-bag"></i>
                                Productos de la Orden
                            </h3>
                        </div>
                        <div class="card-body">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Variante</th>
                                        <th>Precio</th>
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <?php if ($item['product_image']): ?>
                                                        <img src="<?= BASE_URL . '/' . $item['product_image'] ?>" 
                                                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                             class="product-thumbnail">
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($item['variant_name'] ?: 'N/A') ?></td>
                                            <td><?= formatCurrency($item['price']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= formatCurrency($item['total']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="totals-section">
                                <div class="totals-row">
                                    <span>Subtotal:</span>
                                    <span><?= formatCurrency($order['subtotal']) ?></span>
                                </div>
                                <div class="totals-row">
                                    <span>Envío:</span>
                                    <span><?= formatCurrency($order['shipping_cost']) ?></span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="totals-row">
                                        <span>Descuento:</span>
                                        <span>-<?= formatCurrency($order['discount_amount']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="totals-row total">
                                    <span>Total:</span>
                                    <span><?= formatCurrency($order['total']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de edición -->
                    <form method="POST" class="edit-card">
                        <!-- Estados -->
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-cog"></i>
                                Estados de la Orden
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="status">
                                        <i class="fas fa-info-circle"></i>
                                        Estado de la Orden
                                    </label>
                                    <select id="status" name="status" class="form-control" required>
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $value === $order['status'] ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="payment_status">
                                        <i class="fas fa-credit-card"></i>
                                        Estado de Pago
                                    </label>
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

                        <!-- Dirección de Envío -->
                        <div class="card-header" style="border-top: 1px solid rgba(0, 119, 182, 0.1);">
                            <h3>
                                <i class="fas fa-map-marker-alt"></i>
                                Dirección de Envío
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="shipping_city">
                                        <i class="fas fa-city"></i>
                                        Ciudad
                                    </label>
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
                                    <label for="shipping_address">
                                        <i class="fas fa-home"></i>
                                        Dirección Completa
                                    </label>
                                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?= htmlspecialchars($order['shipping_address'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="delivery_notes">
                                        <i class="fas fa-sticky-note"></i>
                                        Instrucciones de Entrega
                                    </label>
                                    <textarea id="delivery_notes" name="delivery_notes" class="form-control" rows="3" placeholder="Instrucciones para el domiciliario..."><?= htmlspecialchars($order['delivery_notes'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="notes">
                                        <i class="fas fa-comment-dots"></i>
                                        Notas Administrativas (Opcional)
                                    </label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Notas internas sobre la orden..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
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
    <script>
        // Mostrar alerta si existe
        <?php if (isset($_SESSION['alert'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('<?= addslashes($_SESSION['alert']['message']) ?>', '<?= $_SESSION['alert']['type'] ?>');
            });
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['status', 'payment_status', 'shipping_address', 'shipping_city'];
            let isValid = true;
            let errorMessage = '';

            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                    errorMessage = 'Por favor completa todos los campos obligatorios';
                } else {
                    field.style.borderColor = 'rgba(0, 119, 182, 0.15)';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert(errorMessage, 'error');
            }
        });

        // Remover error al escribir
        document.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('input', function() {
                this.style.borderColor = 'rgba(0, 119, 182, 0.15)';
            });
        });

        // Confirmar antes de cancelar si hay cambios
        let formChanged = false;
        document.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('change', function() {
                formChanged = true;
            });
        });

        document.querySelector('.btn-secondary').addEventListener('click', function(e) {
            if (formChanged) {
                if (!confirm('¿Estás seguro de que deseas cancelar? Los cambios no guardados se perderán.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>