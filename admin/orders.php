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
    'transferencia' => 'Transferencia'
];




?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
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
                    <div class="filters-header">
                        <div class="filters-title">
                            <i class="fas fa-sliders-h"></i>
                            <h3>Filtros de búsqueda</h3>
                        </div>
                        <button type="button" class="filters-toggle collapsed" id="toggle-filters" aria-label="Mostrar/Ocultar filtros">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>

                    <form id="search-orders-form" class="filters-form">
                        <!-- Barra de búsqueda principal -->
                        <div class="search-bar">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="search-input" 
                                    name="search" 
                                    placeholder="Buscar por N° orden, cliente, email..." 
                                    class="search-input"
                                    autocomplete="off">
                                <button type="button" class="search-clear" id="clear-search" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="submit" class="search-submit-btn">
                                <i class="fas fa-search"></i>
                                <span>Buscar</span>
                            </button>
                        </div>

                        <!-- Filtros avanzados -->
                        <div class="filters-advanced" id="advanced-filters" style="display: none;">
                            <div class="filters-row">
                                <div class="filter-group">
                                    <label for="status-filter" class="filter-label">
                                        <i class="fas fa-tag"></i>
                                        Estado de orden
                                    </label>
                                    <select name="status" id="status-filter" class="filter-select">
                                        <option value="">Todos los estados</option>
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="payment-status-filter" class="filter-label">
                                        <i class="fas fa-credit-card"></i>
                                        Estado de pago
                                    </label>
                                    <select name="payment_status" id="payment-status-filter" class="filter-select">
                                        <option value="">Todos los estados</option>
                                        <?php foreach ($paymentStatuses as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="from-date" class="filter-label">
                                        <i class="fas fa-calendar-alt"></i>
                                        Fecha desde
                                    </label>
                                    <input 
                                        type="date" 
                                        name="from_date" 
                                        id="from-date" 
                                        class="filter-input">
                                </div>

                                <div class="filter-group">
                                    <label for="to-date" class="filter-label">
                                        <i class="fas fa-calendar-check"></i>
                                        Fecha hasta
                                    </label>
                                    <input 
                                        type="date" 
                                        name="to_date" 
                                        id="to-date" 
                                        class="filter-input">
                                </div>
                            </div>

                            <!-- Acciones de filtrado -->
                            <div class="filters-actions-bar">
                                <div class="active-filters" id="active-filters-count">
                                    <i class="fas fa-filter"></i>
                                    <span>0 filtros activos</span>
                                </div>

                                <div class="filters-buttons">
                                    <button type="button" class="btn-clear-filters" id="clear-all-filters">
                                        <i class="fas fa-times-circle"></i>
                                        Limpiar todo
                                    </button>
                                    <button type="submit" class="btn-apply-filters">
                                        <i class="fas fa-check-circle"></i>
                                        Aplicar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Resumen de resultados -->
                <div class="results-summary">
                    <div class="results-info">
                        <i class="fas fa-list-ul"></i>
                        <p id="results-count">Cargando órdenes...</p>
                    </div>
                    <div class="quick-actions">
                        <button class="btn-action btn-export" id="export-orders">
                            <i class="fas fa-file-export"></i>
                            <span>Exportar</span>
                        </button>
                        <button class="btn-action btn-bulk" id="bulk-actions">
                            <i class="fas fa-tasks"></i>
                            <span>Acciones masivas</span>
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