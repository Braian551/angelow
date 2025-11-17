<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../layouts/headeradmin2.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Ventas - Angelow</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/informes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <div class="main-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <div>
                        <a href="<?= BASE_URL ?>/admin/informes/dashboard.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                        <h1><i class="fas fa-chart-line"></i> Informe Detallado de Ventas</h1>
                    </div>
                    <div class="export-buttons">
                        <button class="btn-export" onclick="exportToCSV()">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                        <button class="btn-export" onclick="printReport()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="report-filters">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="start-date">Fecha Inicio</label>
                            <input type="date" id="start-date" class="form-control">
                        </div>
                        <div class="filter-group">
                            <label for="end-date">Fecha Fin</label>
                            <input type="date" id="end-date" class="form-control">
                        </div>
                        <div class="filter-group">
                            <label for="status-filter">Estado</label>
                            <select id="status-filter" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending">Pendiente</option>
                                <option value="processing">En proceso</option>
                                <option value="shipped">Enviado</option>
                                <option value="delivered">Entregado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="period-filter">Agrupar por</label>
                            <select id="period-filter" class="form-control">
                                <option value="day">Día</option>
                                <option value="week">Semana</option>
                                <option value="month" selected>Mes</option>
                                <option value="year">Año</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Restablecer
                        </button>
                        <button class="btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>

                <!-- Métricas principales -->
                <div class="summary-cards">
                    <div class="summary-card blue">
                        <div class="card-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="card-content">
                            <h3>Ingresos Totales</h3>
                            <p class="card-value" id="total-revenue">$0</p>
                            <span class="card-subtitle" id="revenue-period">Período seleccionado</span>
                        </div>
                    </div>

                    <div class="summary-card green">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Órdenes</h3>
                            <p class="card-value" id="total-orders">0</p>
                            <span class="card-subtitle" id="avg-order-value">Valor promedio por pedido: $0</span>
                        </div>
                    </div>

                    <div class="summary-card purple">
                        <div class="card-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="card-content">
                            <h3>Costos de Envío</h3>
                            <p class="card-value" id="total-shipping">$0</p>
                            <span class="card-subtitle">Ingresos por envío</span>
                        </div>
                    </div>

                    <div class="summary-card orange">
                        <div class="card-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="card-content">
                            <h3>Descuentos Aplicados</h3>
                            <p class="card-value" id="total-discounts">$0</p>
                            <span class="card-subtitle">Total en descuentos</span>
                        </div>
                    </div>
                </div>

                <!-- Gráficos de ventas -->
                <div class="charts-grid">
                    <div class="chart-card large">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-area"></i> Evolución de Ventas</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="salesEvolutionChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-bar"></i> Comparativa Mensual</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="monthlyComparisonChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabla detallada -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Detalle de Ventas por Período</h3>
                    </div>
                    <div class="table-body">
                        <table class="data-table" id="sales-detail-table">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Órdenes</th>
                                    <th>Subtotal</th>
                                    <th>Envío</th>
                                    <th>Descuentos</th>
                                    <th>Total</th>
                                    <th>Valor promedio por pedido (COP)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="loading">Cargando datos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/admin/informes-ventas.js"></script>
</body>
</html>
