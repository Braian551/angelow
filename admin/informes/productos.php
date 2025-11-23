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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
        
        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
            
            <div class="management-hub" id="products-hub">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-fire"></i> Productos Populares</h1>
                        <div class="breadcrumb">
                            <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Informes</span> / <span>Productos</span>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-soft" onclick="resetFilters()"><i class="fas fa-undo"></i> Restablecer</button>
                        <button class="btn-soft primary" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Exportar CSV</button>
                    </div>
                </div>

                <!-- Filtros -->
                <section class="surface-card" style="margin-bottom: 2rem; padding: 1.5rem;">
                    <div class="filters-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="filter-group">
                            <label for="start-date" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Fecha Inicio</label>
                            <input type="date" id="start-date" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                        </div>
                        <div class="filter-group">
                            <label for="end-date" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Fecha Fin</label>
                            <input type="date" id="end-date" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                        </div>
                        <div class="filter-group">
                            <label for="limit-select" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Mostrar</label>
                            <select id="limit-select" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <option value="10">Top 10</option>
                                <option value="20">Top 20</option>
                                <option value="50" selected>Top 50</option>
                                <option value="100">Top 100</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button class="btn-primary" onclick="applyFilters()" style="width: 100%; justify-content: center;">
                                <i class="fas fa-filter"></i> Aplicar
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Charts Grid -->
                <section class="client-charts" style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
                    <article class="chart-card chart-card-large" style="grid-column: 1 / -1;">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-bar"></i> Top 10 Productos por Ingresos</h3>
                                <p class="chart-subtitle">Productos con mayor rendimiento financiero.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="topProductsChart" height="320"></canvas>
                        </div>
                    </article>

                    <article class="chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-pie"></i> Ventas por Categoría</h3>
                                <p class="chart-subtitle">Distribución de ingresos por categoría.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="categoriesChart" height="280"></canvas>
                        </div>
                    </article>

                    <article class="chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-boxes"></i> Más Vendidos por Cantidad</h3>
                                <p class="chart-subtitle">Productos con mayor volumen de ventas.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="quantityChart" height="280"></canvas>
                        </div>
                    </article>
                </section>

                <!-- Table Section -->
                <section class="split-grid" style="margin-top: 2rem; grid-template-columns: 1fr;">
                    <article class="table-card">
                        <header>
                            <h3><i class="fas fa-table"></i> Ranking de Productos</h3>
                        </header>
                        <div class="table-wrapper">
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
                    </article>
                </section>

            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/admin/informes-productos.js"></script>
</body>
</html>

