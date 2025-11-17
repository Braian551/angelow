<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>

<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="clients-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-users"></i> Clientes</h1>
                    <p>Seguimiento unificado de segmentos, salud y oportunidades por cliente.</p>
                </div>
                <div class="actions">
                    <button class="btn-soft" data-action="refresh" id="clients-refresh-btn"><i class="fas fa-rotate"></i> Actualizar</button>
                    <button class="btn-soft primary" data-action="export"><i class="fas fa-file-export"></i> Exportar CSV</button>
                </div>
            </div>

            <section class="insights-grid" id="clients-insights">
                <article class="stat-card" data-metric="total">
                    <h2>Total clientes</h2>
                    <strong>--</strong>
                    <div class="delta muted">vs periodo anterior</div>
                </article>
                <article class="stat-card" data-metric="new">
                    <h2>Nuevos (30d)</h2>
                    <strong>--</strong>
                    <div class="delta positive">--%</div>
                </article>
                <article class="stat-card" data-metric="active">
                    <h2>Activos (90d)</h2>
                    <strong>--</strong>
                    <div class="delta muted">Clientes con orden reciente</div>
                </article>
                <article class="stat-card" data-metric="repeat">
                    <h2>Tasa recompra</h2>
                    <strong>--%</strong>
                    <div class="delta positive">Clientes con +2 pedidos</div>
                </article>
                <article class="stat-card" data-metric="ltv">
                    <h2>Valor de vida</h2>
                    <strong>--</strong>
                    <div class="delta muted">Promedio global</div>
                </article>
                <article class="stat-card" data-metric="ticket">
                    <h2>Valor promedio por pedido (COP)</h2>
                    <strong>--</strong>
                    <div class="delta muted">Ultimos 60 dias</div>
                </article>
            </section>

            <section class="split-grid">
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Segmentos accionables</h2>
                            <p class="text-muted">Prioriza acciones de contacto según la salud del cliente (ej. recuperar inactivos, fidelizar clientes leales).</p>
                        </div>
                    </header>
                    <div class="segment-grid" id="client-segments"></div>
                </article>
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Adquisicion semanal</h2>
                            <p class="text-muted">Comparativo ultimas 8 semanas.</p>
                        </div>
                    </header>
                    <ul class="timeline" id="acquisition-trend"></ul>
                </article>
            </section>

            <section class="split-grid">
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Matriz de interacción</h2>
                            <p class="text-muted">Cruce de frecuencia vs recencia (visitas/pedidos vs. tiempo desde último pedido).</p>
                        </div>
                    </header>
                    <div class="segment-grid" id="engagement-matrix"></div>
                </article>
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Top clientes</h2>
                            <p class="text-muted">Valor acumulado ultimos 30 dias.</p>
                        </div>
                    </header>
                    <ul class="timeline" id="top-customers"></ul>
                </article>
            </section>

            <section class="split-grid">
                <article class="table-card">
                    <header>
                        <div class="filter-group">
                            <label for="clients-search" class="sr-only">Buscar</label>
                            <input type="search" id="clients-search" placeholder="Buscar nombre, correo o telefono">
                        </div>
                        <div class="filter-group">
                            <select id="clients-segment">
                                <option value="all">Todos los segmentos</option>
                                <option value="vip">VIP</option>
                                <option value="loyal">Leales</option>
                                <option value="new">Nuevos</option>
                                <option value="no_orders">Sin pedidos</option>
                                <option value="at_risk">Inactivos 90d</option>
                                <option value="active">Activos 60d</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select id="clients-sort">
                                <option value="recent">Mas recientes</option>
                                <option value="value">Mayor valor</option>
                                <option value="orders">Mas pedidos</option>
                                <option value="name">Orden alfabetico</option>
                            </select>
                        </div>
                    </header>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Pedidos</th>
                                    <th>Ultimo pedido</th>
                                    <th>Valor promedio (COP)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="clients-table-body">
                                <tr><td colspan="5">Cargando clientes...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="filter-bar" id="clients-pagination">
                        <span class="text-muted" data-role="meta">-- resultados</span>
                        <div class="actions">
                            <button class="btn-soft" data-page="prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-soft" data-page="next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>

                <aside class="detail-panel" id="client-detail-panel">
                    <div class="empty-state" data-state="empty">
                        <h3>Selecciona un cliente</h3>
                        <p>Veras historial y actividad contextual.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <div class="detail-name"></div>
                            <span class="badge-ghost" data-role="segment"></span>
                        </header>
                        <div class="meta-line"><span>Correo</span><strong data-role="email"></strong></div>
                        <div class="meta-line"><span>Telefono</span><strong data-role="phone"></strong></div>
                        <div class="meta-line"><span>Registrado</span><strong data-role="created"></strong></div>
                        <hr>
                        <ul class="timeline" id="client-activity"></ul>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</div>

<script>
window.CLIENTS_DASHBOARD_CONFIG = {
    baseUrl: '<?= BASE_URL ?>',
    endpoints: {
        overview: '<?= BASE_URL ?>/admin/api/customers/overview.php',
        list: '<?= BASE_URL ?>/admin/api/customers/list.php',
        detail: '<?= BASE_URL ?>/admin/api/customers/detail.php'
    }
};
</script>
<script src="<?= BASE_URL ?>/js/admin/clients/clients-dashboard.js"></script>
</body>
</html>
