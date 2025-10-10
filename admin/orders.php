<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../alertas/alerta1.php';

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

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}

// Obtener estados para el filtro
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



$paymentMethods = [
    'transferencia' => 'Transferencia',
    'contra_entrega' => 'Contra entrega',
    'pse' => 'PSE',
    'efectivo' => 'Efectivo'
];




?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Órdenes - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/orders.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                   <div class="page-header">
                    <h1>
                        <i class="fas fa-shopping-bag"></i> Gestión de Órdenes
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Órdenes</span>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="card filters-card">
                    <form id="search-orders-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <input type="text" id="search-input" name="search" placeholder="Buscar órdenes..." class="form-control">
                                <button type="submit" class="btn btn-search">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <div class="filter-group">
                                <select name="status" id="status-filter" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select name="payment_status" id="payment-status-filter" class="form-control">
                                    <option value="">Todos los estados de pago</option>
                                    <?php foreach ($paymentStatuses as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select name="payment_method" id="payment-method-filter" class="form-control">
                                    <option value="">Todos los métodos de pago</option>
                                    <?php foreach ($paymentMethods as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <input type="date" name="from_date" id="from-date" class="form-control" placeholder="Desde">
                            </div>

                            <div class="filter-group">
                                <input type="date" name="to_date" id="to-date" class="form-control" placeholder="Hasta">
                            </div>

                            <div class="filcen">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>

                                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Resumen de resultados -->
                <div class="results-summary">
                    <p id="results-count">Cargando órdenes...</p>
                    <div class="quick-actions">
                        <button class="btn btn-icon" id="export-orders">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                        <button class="btn btn-icon" id="bulk-actions">
                            <i class="fas fa-tasks"></i> Acciones masivas
                        </button>
                    </div>
                </div>

                <!-- Listado de órdenes -->
                <div class="orders-table-container">
                    <table class="orders-table" id="orders-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th>N° Orden</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="orders-container">
                            <tr>
                                <td colspan="8" class="loading-row">
                                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando órdenes...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="pagination" id="pagination-container">
                    <a href="#" class="pagination-item" title="Primera página">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="#" class="pagination-item" title="Página anterior">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <a href="#" class="pagination-item active">1</a>
                    <a href="#" class="pagination-item">2</a>
                    <a href="#" class="pagination-item">3</a>
                    <span class="pagination-item">...</span>
                    <a href="#" class="pagination-item" title="Página siguiente">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="#" class="pagination-item" title="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            </div>
        </main>
    </div>


    <?php include __DIR__ . '/modals/modal-status-change.php'; ?>
    <?php include __DIR__ . '/modals/modal-delete-order.php'; ?>
    <?php include __DIR__ . '/modals/modal-bulk-actions.php'; ?>
    <?php require_once __DIR__ . '/../js/orderadmin.php'; ?>


    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/orderadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/modals/status-change.js"></script>
    <script src="<?= BASE_URL ?>/js/modals/delete-order.js"></script>
    <script src="<?= BASE_URL ?>/js/modals/bulk-actions.js"></script>
</body>

</html>