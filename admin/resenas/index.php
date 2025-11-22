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
    <title>Reseñas | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/size/size.css?v=<?= filemtime(__DIR__ . '/../../css/admin/size/size.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/reviews-enhanced.css?v=<?= filemtime(__DIR__ . '/../../css/admin/reviews-enhanced.css') ?>">
    <!-- Reuse client review-card styles to make admin review list match product UI -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/producto/css/product-tabs.css?v=<?= filemtime(__DIR__ . '/../../producto/css/product-tabs.css') ?>">
    <!-- Inline CSS overrides to guarantee color/application for review action buttons -->
    <style>
        /* Strongly enforce colors on review action icons and buttons */
        #reviews-hub .btn-approve, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="approve"] { background: rgba(16,185,129,0.08) !important; color: var(--hub-success) !important; border-color: rgba(16,185,129,0.12) !important; }
        #reviews-hub .btn-reject, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="reject"] { background: rgba(220,38,38,0.06) !important; color: #dc2626 !important; border-color: rgba(220,38,38,0.12) !important; }
        #reviews-hub .btn-verify, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="verify"] { background: rgba(16,185,129,0.08) !important; color: var(--hub-success) !important; border-color: rgba(16,185,129,0.12) !important; }
        #reviews-hub .btn-approve i, #reviews-hub button[data-action="approve"] i, #reviews-hub button[data-action="approve"] svg, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="approve"] i { color: var(--hub-success) !important; fill: var(--hub-success) !important; }
        #reviews-hub .btn-reject i, #reviews-hub button[data-action="reject"] i, #reviews-hub button[data-action="reject"] svg, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="reject"] i { color: #dc2626 !important; fill: #dc2626 !important; }
        #reviews-hub .btn-verify i, #reviews-hub button[data-action="verify"] i, #reviews-hub button[data-action="verify"] svg, #reviews-hub .table-wrapper table tbody#reviews-table td.actions button[data-action="verify"] i { color: var(--hub-success) !important; fill: var(--hub-success) !important; }
        /* small fallback: ensure btn-soft white background overridden for table cell buttons */
        .management-hub#reviews-hub .table-wrapper table tbody#reviews-table td .btn-soft.btn-sm[data-action] { background: transparent !important; }
        /* Show card list by default inside admin and hide the old table fallback */
        .management-hub#reviews-hub .table-wrapper .reviews-list { display: grid; grid-template-columns: 1fr; gap: 0.9rem; padding: 0.75rem; }
        .management-hub#reviews-hub .table-wrapper table { display: none; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="management-hub" id="reviews-hub">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-star"></i> Reseñas</h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>reseñas</span>
                    </div>
                    <p>Moderacion centralizada y visibilidad de reputacion.</p>
                </div>
                <div class="actions">
                    <button class="btn-soft" id="reviews-refresh"><i class="fas fa-rotate"></i> Actualizar</button>
                </div>
            </div>

            <section class="insights-grid" id="reviews-insights">
                <article class="stat-card" data-metric="pending" role="status" aria-live="polite">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        <h2>Pendientes</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">En espera de moderación</div>
                </article>
                <article class="stat-card" data-metric="approved" role="status" aria-live="polite">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <h2>Publicadas</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Reseñas visibles en tienda</div>
                </article>
                <article class="stat-card" data-metric="average" role="status" aria-live="polite">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                        <h2>Rating promedio</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="rating-stars" data-role="average-stars" aria-hidden="true"></div>
                    <div class="stat-subtext">Basado en reseñas publicadas</div>
                </article>
                <article class="stat-card" data-metric="verified" role="status" aria-live="polite">
                    <div class="stat-top">
                        <!-- Use a more widely supported icon for verified purchases and improve semantics -->
                        <!-- Preferred icon: `fa-badge-check` (better semantics). If not available in the CDN version, a JS fallback will replace it with `fa-check-circle`. -->
                        <span class="stat-icon" aria-hidden="true">
                            <!-- Prefer the FontAwesome badge-check icon with a fallback handler in the header layout. -->
                            <i class="fa-solid fa-badge-check fa-fallback" data-fallback="fa-check-circle" aria-hidden="true"></i>
                        </span>
                        <h2>Compras verificadas</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Pruebas de compra</div>
                </article>
            </section>

            <section class="client-charts split-grid" id="reviews-charts">
                <article class="chart-card chart-card-small" id="reviews-rating-card">
                    <div class="chart-header">
                        <div>
                            <h3><span class="section-icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></span> Distribucion de rating</h3>
                            <p class="chart-subtitle text-muted">Vista rapida por estrellas.</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="reviews-rating-chart" aria-label="Distribucion de rating" role="img" height="140"></canvas>
                        <div class="chart-empty" data-empty="reviews-rating" hidden>Sin datos para graficar</div>
                    </div>
                    <div class="chart-legend" id="reviews-rating-legend"></div>
                </article>
                <article class="surface-card">
                    <div class="section-header surface-header">
                        <div>
                            <h3><span class="section-icon" aria-hidden="true"><i class="fas fa-comments"></i></span> Últimas reseñas</h3>
                            <p class="text-muted">Monitorea tono y urgencia.</p>
                        </div>
                    </div>
                    <ul class="timeline detail-body" id="reviews-highlights"></ul>
                </article>
            </section>

            <section class="split-grid layout-table-detail">
                <article class="table-card">
                    <header>
                        <div class="filter-group">
                            <input type="search" id="reviews-search" placeholder="Buscar título, texto o producto">
                        </div>
                        <div class="filter-group">
                            <select id="reviews-status">
                                <option value="all" selected>Todas</option>
                                <option value="pending">Pendientes</option>
                                <option value="approved">Publicadas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select id="reviews-rating">
                                <option value="">Cualquier rating</option>
                                <option value="5">5 estrellas</option>
                                <option value="4">4 estrellas</option>
                                <option value="3">3 estrellas</option>
                                <option value="2">2 estrellas</option>
                                <option value="1">1 estrella</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn-soft" id="reviews-clear-filters" title="Limpiar filtros">Limpiar filtros</button>
                        </div>
                        <div class="filter-group">
                            <select id="reviews-verified">
                                <option value="">Todos</option>
                                <option value="1">Solo verificados</option>
                                <option value="0">Sin verificar</option>
                            </select>
                        </div>
                    </header>
                    <div class="table-wrapper">
                        <!-- Admin updated to render cards similar to product `review-card` layout. -->
                        <!-- Rendered by js/admin/reviews/reviews-dashboard.js into #reviews-list -->
                        <div id="reviews-list" class="reviews-list"></div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Reseña</th>
                                    <th>Producto</th>
                                    <th>Rating</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="reviews-table">
                                <tr><td colspan="5">Cargando reseñas...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="reviews-debug" class="text-muted small" aria-hidden="true" style="display:none; padding: 0.75rem 1rem;">Debug: <pre id="reviews-debug-pre" style="white-space:pre-wrap; word-break:break-word; margin:0"></pre></div>
                    <div class="filter-bar" id="reviews-pagination">
                        <span class="text-muted" data-role="meta">-- resultados</span>
                        <div class="actions">
                            <button class="btn-soft" data-page="prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-soft" data-page="next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>

                <aside class="detail-panel collapsed" id="review-detail" aria-hidden="true">
                    <div class="empty-state" data-state="empty">
                        <h3>Selecciona una reseña</h3>
                        <p>Verás el detalle completo para responder o moderar.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <span class="section-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                            <div class="detail-name" data-role="title"></div>
                            <div class="detail-controls">
                                <button class="btn-soft btn-icon" id="review-detail-toggle" aria-expanded="false" aria-controls="review-detail" title="Cerrar panel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.42 0L12 10.59 7.12 5.7A1 1 0 0 0 5.7 7.12L10.59 12l-4.88 4.88a1 1 0 1 0 1.42 1.42L12 13.41l4.88 4.88a1 1 0 1 0 1.42-1.42L13.41 12l4.88-4.88a1 1 0 0 0 0-1.41z"/></svg>
                                </button>
                            </div>
                            <span class="badge-ghost" data-role="rating"></span>
                        </header>
                        <div class="meta-line"><span>Cliente</span><strong data-role="customer"></strong></div>
                        <div class="meta-line"><span>Producto</span><strong data-role="product"></strong></div>
                        <div class="meta-line"><span>Fecha</span><strong data-role="date"></strong></div>
                        <hr>
                        <p data-role="comment" class="text-muted"></p>
                                <div class="actions" data-role="detail-actions">
                            <button class="btn-soft primary btn-approve" data-action="approve"><i class="fas fa-check"></i> Aprobar</button>
                            <button class="btn-soft btn-reject btn-delete" data-action="reject"><i class="fas fa-ban"></i> Rechazar</button>
                            <!-- Use same icon as stat and clearer label for the action -->
                            <button class="btn-soft btn-verify btn-status" data-action="verify">
                                <i class="fa-solid fa-badge-check fa-fallback" data-fallback="fa-check-circle" aria-hidden="true"></i>
                                Marcar como verificada
                            </button>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</div>

<script>
window.REVIEWS_INBOX_CONFIG = {
    baseUrl: '<?= BASE_URL ?>',
    endpoints: {
        overview: '<?= BASE_URL ?>/admin/api/reviews/overview.php',
        list: '<?= BASE_URL ?>/admin/api/reviews/list.php',
        update: '<?= BASE_URL ?>/admin/api/reviews/update_status.php'
    }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/admin/reviews/reviews-dashboard.js?v=<?= filemtime(__DIR__ . '/../../js/admin/reviews/reviews-dashboard.js') ?>"></script>
        </div>
    </main>
    </div>
</body>
</html>
