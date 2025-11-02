<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../layouts/headeradmin2.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Populares - Angelow</title>
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
                        <h1><i class="fas fa-fire"></i> Productos Populares</h1>
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
                            <label for="start-date">Fecha Inicio</label>
                            <input type="date" id="start-date" class="form-control">
                        </div>
                        <div class="filter-group">
                            <label for="end-date">Fecha Fin</label>
                            <input type="date" id="end-date" class="form-control">
                        </div>
                        <div class="filter-group">
                            <label for="limit-select">Mostrar</label>
                            <select id="limit-select" class="form-control">
                                <option value="10">Top 10</option>
                                <option value="20">Top 20</option>
                                <option value="50" selected>Top 50</option>
                                <option value="100">Top 100</option>
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

                <!-- Gráficos -->
                <div class="charts-grid">
                    <div class="chart-card large">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-bar"></i> Top 10 Productos por Ingresos</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Ventas por Categoría</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-boxes"></i> Más Vendidos por Cantidad</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="quantityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabla de productos -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Ranking de Productos</h3>
                    </div>
                    <div class="table-body">
                        <table class="data-table" id="products-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Veces Vendido</th>
                                    <th>Cantidad Total</th>
                                    <th>Precio Promedio</th>
                                    <th>Ingresos Totales</th>
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

    <script src="<?= BASE_URL ?>/js/admin/informes-productos.js"></script>
</body>
</html>
