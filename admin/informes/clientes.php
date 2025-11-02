<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../layouts/headeradmin2.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Recurrentes - Angelow</title>
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
                        <h1><i class="fas fa-users"></i> Clientes Recurrentes</h1>
                    </div>
                    <div class="export-buttons">
                        <button class="btn-export" onclick="exportToCSV()">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="report-filters">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="min-orders">Mínimo de Órdenes</label>
                            <select id="min-orders" class="form-control">
                                <option value="2" selected>2+ órdenes</option>
                                <option value="3">3+ órdenes</option>
                                <option value="5">5+ órdenes</option>
                                <option value="10">10+ órdenes</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>

                <!-- Estadísticas generales -->
                <div class="summary-cards">
                    <div class="summary-card blue">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Clientes</h3>
                            <p class="card-value" id="total-customers">0</p>
                            <span class="card-subtitle">Clientes registrados</span>
                        </div>
                    </div>

                    <div class="summary-card green">
                        <div class="card-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="card-content">
                            <h3>Con Compras</h3>
                            <p class="card-value" id="customers-with-orders">0</p>
                            <span class="card-subtitle" id="order-percentage">0%</span>
                        </div>
                    </div>

                    <div class="summary-card purple">
                        <div class="card-icon">
                            <i class="fas fa-repeat"></i>
                        </div>
                        <div class="card-content">
                            <h3>Clientes Recurrentes</h3>
                            <p class="card-value" id="recurring-customers">0</p>
                            <span class="card-subtitle">2+ órdenes</span>
                        </div>
                    </div>

                    <div class="summary-card orange">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-content">
                            <h3>Promedio Órdenes</h3>
                            <p class="card-value" id="avg-orders">0</p>
                            <span class="card-subtitle">Por cliente activo</span>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Distribución de Clientes</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-bar"></i> Top 10 Clientes por Valor</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="topCustomersChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabla de clientes -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Ranking de Clientes</h3>
                    </div>
                    <div class="table-body">
                        <table class="data-table" id="customers-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Total Órdenes</th>
                                    <th>Total Gastado</th>
                                    <th>Ticket Promedio</th>
                                    <th>Primera Compra</th>
                                    <th>Última Compra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="loading">Cargando datos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/admin/informes-clientes.js"></script>
</body>
</html>
