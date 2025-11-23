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
            
            <div class="management-hub" id="customers-hub">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-users"></i> Clientes Recurrentes</h1>
                        <div class="breadcrumb">
                            <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Informes</span> / <span>Clientes</span>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-soft primary" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Exportar CSV</button>
                    </div>
                </div>

                <!-- Filtros -->
                <section class="surface-card" style="margin-bottom: 2rem; padding: 1.5rem;">
                    <div class="filters-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="filter-group">
                            <label for="min-orders" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Mínimo de Órdenes</label>
                            <select id="min-orders" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <option value="2" selected>2+ órdenes</option>
                                <option value="3">3+ órdenes</option>
                                <option value="5">5+ órdenes</option>
                                <option value="10">10+ órdenes</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button class="btn-primary" onclick="applyFilters()" style="width: 100%; justify-content: center;">
                                <i class="fas fa-filter"></i> Aplicar
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Insights Grid -->
                <section class="insights-grid" id="customer-insights">
                    <article class="stat-card" data-metric="customers">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-users"></i></span>
                            <h2>Total Clientes</h2>
                        </div>
                        <strong class="stat-value" id="total-customers">0</strong>
                        <div class="delta muted stat-subtext">Clientes registrados</div>
                    </article>

                    <article class="stat-card" data-metric="active">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-shopping-bag"></i></span>
                            <h2>Con Compras</h2>
                        </div>
                        <strong class="stat-value" id="customers-with-orders">0</strong>
                        <div class="delta positive stat-subtext" id="order-percentage">0%</div>
                    </article>

                    <article class="stat-card" data-metric="recurring">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-repeat"></i></span>
                            <h2>Recurrentes</h2>
                        </div>
                        <strong class="stat-value" id="recurring-customers">0</strong>
                        <div class="delta muted stat-subtext">2+ órdenes</div>
                    </article>

                    <article class="stat-card" data-metric="avg">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                            <h2>Promedio Órdenes</h2>
                        </div>
                        <strong class="stat-value" id="avg-orders">0</strong>
                        <div class="delta muted stat-subtext">Por cliente activo</div>
                    </article>
                </section>

                <!-- Charts Grid -->
                <section class="client-charts" style="margin-top: 2rem;">
                    <article class="chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-pie"></i> Distribución de Clientes</h3>
                                <p class="chart-subtitle">Por número de órdenes.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="distributionChart" height="280"></canvas>
                        </div>
                    </article>

                    <article class="chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-bar"></i> Top 10 Clientes por Valor</h3>
                                <p class="chart-subtitle">Clientes con mayor gasto histórico.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="topCustomersChart" height="280"></canvas>
                        </div>
                    </article>
                </section>

                <!-- Table Section -->
                <section class="split-grid" style="margin-top: 2rem; grid-template-columns: 1fr;">
                    <article class="table-card">
                        <header>
                            <h3><i class="fas fa-table"></i> Ranking de Clientes</h3>
                        </header>
                        <div class="table-wrapper">
                            <table class="data-table" id="customers-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Total Órdenes</th>
                                        <th>Total Gastado</th>
                                        <th>Valor Promedio</th>
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
                    </article>
                </section>

            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/admin/informes-clientes.js"></script>
</body>
</html>

