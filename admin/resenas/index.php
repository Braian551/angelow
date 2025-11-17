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
    <title>Resenas | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>

<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="reviews-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-star"></i> Resenas</h1>
                    <p>Moderacion centralizada y visibilidad de reputacion.</p>
                </div>
                <div class="actions">
                    <button class="btn-soft" id="reviews-refresh"><i class="fas fa-rotate"></i> Actualizar</button>
                </div>
            </div>

            <section class="insights-grid" id="reviews-insights">
                <article class="stat-card" data-metric="pending">
                    <h2>Pendientes</h2>
                    <strong>--</strong>
                </article>
                <article class="stat-card" data-metric="approved">
                    <h2>Publicadas</h2>
                    <strong>--</strong>
                </article>
                <article class="stat-card" data-metric="average">
                    <h2>Rating promedio</h2>
                    <strong>--</strong>
                </article>
                <article class="stat-card" data-metric="verified">
                    <h2>Compras verificadas</h2>
                    <strong>--</strong>
                </article>
            </section>

            <section class="split-grid">
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Distribucion de rating</h2>
                            <p class="text-muted">Vista rapida por estrellas.</p>
                        </div>
                    </header>
                    <ul class="timeline" id="rating-distribution"></ul>
                </article>
                <article class="surface-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Ultimas reseñas</h2>
                            <p class="text-muted">Monitorea tono y urgencia.</p>
                        </div>
                    </header>
                    <ul class="timeline" id="reviews-highlights"></ul>
                </article>
            </section>

            <section class="split-grid">
                <article class="table-card">
                    <header>
                        <div class="filter-group">
                            <input type="search" id="reviews-search" placeholder="Buscar titulo, texto o producto">
                        </div>
                        <div class="filter-group">
                            <select id="reviews-status">
                                <option value="pending">Pendientes</option>
                                <option value="approved">Publicadas</option>
                                <option value="all">Todas</option>
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
                            <select id="reviews-verified">
                                <option value="">Todos</option>
                                <option value="1">Solo verificados</option>
                                <option value="0">Sin verificar</option>
                            </select>
                        </div>
                    </header>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Resena</th>
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
                    <div class="filter-bar" id="reviews-pagination">
                        <span class="text-muted" data-role="meta">-- resultados</span>
                        <div class="actions">
                            <button class="btn-soft" data-page="prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-soft" data-page="next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>

                <aside class="detail-panel" id="review-detail">
                    <div class="empty-state" data-state="empty">
                        <h3>Selecciona una reseña</h3>
                        <p>Veras el detalle completo para responder o moderar.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <div class="detail-name" data-role="title"></div>
                            <span class="badge-ghost" data-role="rating"></span>
                        </header>
                        <div class="meta-line"><span>Cliente</span><strong data-role="customer"></strong></div>
                        <div class="meta-line"><span>Producto</span><strong data-role="product"></strong></div>
                        <div class="meta-line"><span>Fecha</span><strong data-role="date"></strong></div>
                        <hr>
                        <p data-role="comment" class="text-muted"></p>
                        <div class="actions" data-role="detail-actions">
                            <button class="btn-soft primary" data-action="approve"><i class="fas fa-check"></i> Aprobar</button>
                            <button class="btn-soft" data-action="reject"><i class="fas fa-ban"></i> Rechazar</button>
                            <button class="btn-soft" data-action="verify"><i class="fas fa-shield-check"></i> Marcar verificada</button>
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
<script src="<?= BASE_URL ?>/js/admin/reviews/reviews-dashboard.js"></script>
</body>
</html>
