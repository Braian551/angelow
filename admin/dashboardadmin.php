<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../alertas/alerta1.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {\n        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');\n    });</script>";
    unset($_SESSION['alert']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Panel de Administración - Angelow</title>
    <meta name="description" content="Resumen operativo de Angelow: ventas, órdenes, clientes e inventario en tiempo real.">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content" id="dashboard-root">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-chart-line"></i> Panel de control</h1>
                        <p class="page-subtitle">Monitorea ventas, órdenes, clientes e inventario en tiempo real.</p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-secondary">
                            <i class="fas fa-receipt"></i>
                            <span>Órdenes</span>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-secondary">
                            <i class="fas fa-boxes"></i>
                            <span>Productos</span>
                        </a>
                        <button class="btn btn-primary" id="refresh-dashboard" type="button">
                            <i class="fas fa-rotate"></i>
                            <span>Actualizar</span>
                        </button>
                    </div>
                </div>

                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Resumen</span>
                </div>

                <div class="dashboard-error" id="dashboard-error" role="alert" style="display: none;"></div>

                <section class="stats-summary" id="stats-section">
                    <article class="stat-card is-loading" data-stat-card="orders">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Órdenes hoy</p>
                            <p class="stat-value" data-stat-value>--</p>
                            <div class="stat-meta">
                                <span class="stat-change" data-stat-change>--</span>
                                <span class="stat-helper">vs. ayer</span>
                            </div>
                        </div>
                    </article>

                    <article class="stat-card is-loading" data-stat-card="revenue">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Ingresos hoy</p>
                            <p class="stat-value" data-stat-value>--</p>
                            <div class="stat-meta">
                                <span class="stat-change" data-stat-change>--</span>
                                <span class="stat-helper">vs. ayer</span>
                            </div>
                        </div>
                    </article>

                    <article class="stat-card is-loading" data-stat-card="customers">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Nuevos clientes</p>
                            <p class="stat-value" data-stat-value>--</p>
                            <div class="stat-meta">
                                <span class="stat-change" data-stat-change>--</span>
                                <span class="stat-helper">vs. últimos 7 días</span>
                            </div>
                        </div>
                    </article>

                    <article class="stat-card is-loading" data-stat-card="inventory">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                        <div class="stat-info">
                            <p class="stat-label">Inventario activo</p>
                            <p class="stat-value" data-stat-value>--</p>
                            <div class="stat-pills">
                                <span class="stat-pill pill-success">Activos <strong data-stat-extra="active">--</strong></span>
                                <span class="stat-pill pill-warning">Bajo stock <strong data-stat-extra="low">--</strong></span>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="metrics-grid" id="metrics-grid">
                    <article class="metric-card is-loading" data-metric-card="avg_ticket">
                            <p class="metric-label">Valor promedio por pedido (30 días)</p>
                        <h3 class="metric-value" data-metric-value>--</h3>
                        <p class="metric-helper">Órdenes completadas</p>
                    </article>

                    <article class="metric-card is-loading" data-metric-card="pending_orders">
                        <p class="metric-label">Órdenes pendientes</p>
                        <h3 class="metric-value" data-metric-value>--</h3>
                        <p class="metric-helper">Pendiente / En proceso</p>
                    </article>

                    <article class="metric-card is-loading" data-metric-card="revenue_month">
                        <p class="metric-label">Ingresos del mes</p>
                        <h3 class="metric-value" data-metric-value>--</h3>
                        <p class="metric-helper">Variación vs. mes anterior</p>
                        <span class="metric-change" data-metric-change>--</span>
                    </article>
                </section>

                <section class="dashboard-grid main-charts">
                    <article class="chart-card chart-card-large" id="sales-chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-chart-area"></i> Rendimiento de ventas</h3>
                                <p class="chart-subtitle">Ingresos y órdenes del período seleccionado.</p>
                            </div>
                            <div class="chart-controls" role="group" aria-label="Intervalo de tiempo">
                                <button type="button" class="chart-range active" data-range="7">7D</button>
                                <button type="button" class="chart-range" data-range="14">14D</button>
                                <button type="button" class="chart-range" data-range="30">30D</button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="sales-trend-chart" height="320"></canvas>
                            <div class="chart-skeleton shimmer"></div>
                        </div>
                    </article>

                    <article class="chart-card" id="status-chart-card">
                        <div class="chart-header">
                            <div>
                                <h3><i class="fas fa-tags"></i> Estado de las órdenes</h3>
                                <p class="chart-subtitle">Distribución actual por estado.</p>
                            </div>
                        </div>
                        <div class="chart-body doughnut">
                            <canvas id="status-chart" height="280"></canvas>
                            <div class="chart-skeleton shimmer"></div>
                        </div>
                        <div class="status-list" id="status-list"></div>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="dashboard-card recent-orders-card">
                        <div class="section-header">
                            <div>
                                <h3><i class="fas fa-list-ul"></i> Órdenes recientes</h3>
                                <p>Últimas actualizaciones registradas.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/orders.php" class="btn-link">Ver todas</a>
                        </div>
                        <div class="table-responsive">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Orden</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Pago</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-orders-body">
                                    <tr class="skeleton-row">
                                        <td colspan="6"><div class="skeleton-line w-100"></div></td>
                                    </tr>
                                    <tr class="skeleton-row">
                                        <td colspan="6"><div class="skeleton-line w-95"></div></td>
                                    </tr>
                                    <tr class="skeleton-row">
                                        <td colspan="6"><div class="skeleton-line w-90"></div></td>
                                    </tr>
                                    <tr class="skeleton-row">
                                        <td colspan="6"><div class="skeleton-line w-80"></div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="dashboard-card inventory-card">
                        <div class="section-header">
                            <div>
                                <h3><i class="fas fa-warehouse"></i> Inventario en riesgo</h3>
                                <p>Productos activos con stock crítico.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/inventario/inventory.php" class="btn-link">Ver inventario</a>
                        </div>
                        <div class="inventory-summary" id="inventory-summary">
                            <div class="inventory-pill">
                                <p>Total de productos</p>
                                <strong data-inventory="total">--</strong>
                            </div>
                            <div class="inventory-pill">
                                <p>Sin stock</p>
                                <strong data-inventory="zero">--</strong>
                            </div>
                        </div>
                        <div class="low-stock-list" id="low-stock-list">
                            <div class="low-stock-item skeleton">
                                <div class="low-stock-image shimmer"></div>
                                <div class="low-stock-info">
                                    <div class="skeleton-line w-70"></div>
                                    <div class="skeleton-line w-50"></div>
                                </div>
                                <div class="low-stock-meta">
                                    <span class="skeleton-pill"></span>
                                </div>
                            </div>
                            <div class="low-stock-item skeleton">
                                <div class="low-stock-image shimmer"></div>
                                <div class="low-stock-info">
                                    <div class="skeleton-line w-60"></div>
                                    <div class="skeleton-line w-40"></div>
                                </div>
                                <div class="low-stock-meta">
                                    <span class="skeleton-pill"></span>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="dashboard-card top-products-card">
                        <div class="section-header">
                            <div>
                                <h3><i class="fas fa-trophy"></i> Productos destacados</h3>
                                <p>Más vendidos en los últimos 30 días.</p>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/informes/ventas.php" class="btn-link">Ver informe</a>
                        </div>
                        <div class="top-products-list" id="top-products-list">
                            <div class="top-product-item skeleton">
                                <div class="skeleton-line w-50"></div>
                                <div class="skeleton-line w-30"></div>
                            </div>
                            <div class="top-product-item skeleton">
                                <div class="skeleton-line w-45"></div>
                                <div class="skeleton-line w-25"></div>
                            </div>
                            <div class="top-product-item skeleton">
                                <div class="skeleton-line w-60"></div>
                                <div class="skeleton-line w-35"></div>
                            </div>
                        </div>
                    </article>

                    <article class="dashboard-card activity-card">
                        <div class="section-header">
                            <div>
                                <h3><i class="fas fa-bolt"></i> Actividad reciente</h3>
                                <p>Últimos eventos del sistema.</p>
                            </div>
                        </div>
                        <div class="activity-feed" id="activity-feed">
                            <div class="activity-item skeleton">
                                <div class="skeleton-icon shimmer"></div>
                                <div class="activity-content">
                                    <div class="skeleton-line w-70"></div>
                                    <div class="skeleton-line w-50"></div>
                                </div>
                            </div>
                            <div class="activity-item skeleton">
                                <div class="skeleton-icon shimmer"></div>
                                <div class="activity-content">
                                    <div class="skeleton-line w-60"></div>
                                    <div class="skeleton-line w-40"></div>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_URL ?>/js/admin/dashboard/dashboard.js"></script>
</body>

</html>
