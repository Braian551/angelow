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
            
            <div class="management-hub" id="sales-hub">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-chart-line"></i> Informe de Ventas</h1>
                        <div class="breadcrumb">
                            <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Informes</span> / <span>Ventas</span>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-soft" onclick="resetFilters()"><i class="fas fa-undo"></i> Restablecer</button>
                        <button class="btn-soft primary" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Exportar CSV</button>
                        <button class="btn-soft" onclick="printReport()"><i class="fas fa-print"></i> Imprimir</button>
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
                            <label for="status-filter" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Estado</label>
                            <select id="status-filter" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <option value="">Todos</option>
                                <option value="pending">Pendiente</option>
                                <option value="processing">En proceso</option>
                                <option value="shipped">Enviado</option>
                                <option value="delivered">Entregado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="period-filter" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Agrupar por</label>
                            <select id="period-filter" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                <option value="day">Día</option>
                                <option value="week">Semana</option>
                                <option value="month" selected>Mes</option>
                                <option value="year">Año</option>
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
                <section class="insights-grid" id="sales-insights">
                    <article class="stat-card" data-metric="revenue">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-dollar-sign"></i></span>
                            <h2>Ingresos Totales</h2>
                        </div>
                        <strong class="stat-value" id="total-revenue">$0</strong>
                        <div class="delta muted stat-subtext" id="revenue-period">Período seleccionado</div>
                    </article>

                    <article class="stat-card" data-metric="orders">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-shopping-cart"></i></span>
                            <h2>Total Órdenes</h2>
                        </div>
                        <strong class="stat-value" id="total-orders">0</strong>
                        <div class="delta positive stat-subtext" id="avg-order-value">Ticket prom: $0</div>
                    </article>

                    <article class="stat-card" data-metric="shipping">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-truck"></i></span>
                            <h2>Costos de Envío</h2>
                        </div>
                        <strong class="stat-value" id="total-shipping">$0</strong>
                        <div class="delta muted stat-subtext">Ingresos por envío</div>
                    </article>

                    <article class="stat-card" data-metric="discounts">
                        <div class="stat-top">
                            <span class="stat-icon"><i class="fas fa-percentage"></i></span>
                            <h2>Descuentos</h2>
                        </div>
                        <strong class="stat-value" id="total-discounts">$0</strong>
                        <div class="delta muted stat-subtext">Total aplicado</div>
                    </article>
                </section>

                <!-- Charts Section -->
                <section class="client-charts" style="margin-top: 2rem;">
                    <article class="chart-card chart-card-large">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-area"></i> Evolución de Ventas</h3>
                                <p class="chart-subtitle">Ingresos y órdenes en el tiempo.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="salesEvolutionChart" height="320"></canvas>
                        </div>
                    </article>

                    <article class="chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-bar"></i> Comparativa Mensual</h3>
                                <p class="chart-subtitle">Mes actual vs anterior.</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="monthlyComparisonChart" height="280"></canvas>
                        </div>
                    </article>
                </section>

                <!-- Detailed Table -->
                <section class="split-grid" style="margin-top: 2rem; grid-template-columns: 1fr;">
                    <article class="table-card">
                        <header>
                            <h3><i class="fas fa-table"></i> Detalle de Ventas por Período</h3>
                        </header>
                        <div class="table-wrapper">
                            <table class="data-table" id="sales-detail-table">
                                <thead>
                                    <tr>
                                        <th>Período</th>
                                        <th>Órdenes</th>
                                        <th>Subtotal</th>
                                        <th>Envío</th>
                                        <th>Descuentos</th>
                                        <th>Total</th>
                                        <th>Ticket Promedio</th>
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

    <script src="<?= BASE_URL ?>/js/admin/informes-ventas.js"></script>
</body>
</html>

