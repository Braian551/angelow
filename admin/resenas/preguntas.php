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
    <title>Preguntas | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="questions-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-question-circle"></i> Preguntas</h1>
                    <p>Modera y responde preguntas de productos.</p>
                </div>
            </div>

            <section class="insights-grid" id="questions-insights">
                <article class="stat-card" data-metric="total">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-list"></i></span>
                        <h2>Total</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Preguntas en panel</div>
                </article>
                <article class="stat-card" data-metric="answered">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-reply"></i></span>
                        <h2>Respondidas</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Con al menos una respuesta</div>
                </article>
                <article class="stat-card" data-metric="unanswered">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-comments-question-check"></i></span>
                        <h2>Sin responder</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">A la espera de respuesta</div>
                </article>
            </section>

            <section class="split-grid layout-table-detail">
                <article class="chart-card chart-card-small" id="questions-status-card">
                    <header class="filter-bar">
                        <div>
                            <h2>Estado de preguntas</h2>
                            <p class="text-muted">Respondidas vs no respondidas</p>
                        </div>
                    </header>
                    <div class="chart-body">
                        <canvas id="questions-status-chart" aria-label="Estado de preguntas" role="img" height="140"></canvas>
                        <div class="chart-empty" data-empty="questions-status" hidden>Sin datos</div>
                    </div>
                    <div class="chart-legend" id="questions-status-legend"></div>
                </article>
                <article class="table-card">
                    <header>
                        <div class="filter-group">
                            <input type="search" id="questions-search" placeholder="Buscar texto o producto">
                        </div>
                        <div class="filter-group">
                            <select id="questions-filter">
                                <option value="all">Todos</option>
                                <option value="0">Sin respuesta</option>
                                <option value="1">Respondidas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn-soft" id="questions-clear-filters" title="Limpiar filtros">Limpiar filtros</button>
                        </div>
                    </header>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pregunta</th>
                                    <th>Producto</th>
                                    <th>Autor</th>
                                    <th>Respuestas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="questions-table">
                                <tr><td colspan="5">Cargando preguntas...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="questions-debug" class="text-muted small" aria-hidden="true" style="display:none; padding: 0.75rem 1rem;">Debug: <pre id="questions-debug-pre" style="white-space:pre-wrap; word-break:break-word; margin:0"></pre></div>
                    <div class="filter-bar" id="questions-pagination">
                        <span class="text-muted" data-role="meta">-- resultados</span>
                        <div class="actions">
                            <button class="btn-soft" data-page="prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-soft" data-page="next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>

                <aside class="detail-panel collapsed" id="question-detail" aria-hidden="true">
                    <div class="empty-state" data-state="empty">
                        <h3>Selecciona una pregunta</h3>
                        <p>Verás el hilo de respuestas y podrás moderar o responder.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <div class="detail-name" data-role="title"></div>
                            <div class="detail-controls">
                                <button class="btn-soft btn-icon" id="question-detail-toggle" aria-expanded="false" aria-controls="question-detail" title="Cerrar panel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.3 5.71a1 1 0 0 0-1.42 0L12 10.59 7.12 5.7A1 1 0 0 0 5.7 7.12L10.59 12l-4.88 4.88a1 1 0 1 0 1.42 1.42L12 13.41l4.88 4.88a1 1 0 1 0 1.42-1.42L13.41 12l4.88-4.88a1 1 0 0 0 0-1.41z"/></svg>
                                </button>
                            </div>
                            <div class="detail-meta">
                                <span data-role="product"></span>
                                <span data-role="date" class="text-muted"></span>
                            </div>
                        </header>
                        <hr>
                        <div class="question-block">
                            <p data-role="question" class="text-muted"></p>
                        </div>
                        <h3>Respuestas</h3>
                        <ul id="question-answers" class="timeline"></ul>
                        <form id="answer-form">
                            <div class="form-row">
                                <textarea name="answer" placeholder="Escribe una respuesta" required></textarea>
                            </div>
                            <div class="actions">
                                <button class="btn-soft primary" type="submit">Responder</button>
                                <button class="btn-soft" type="button" data-action="delete">Eliminar pregunta</button>
                            </div>
                        </form>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</div>

<script>
window.QUESTIONS_INBOX_CONFIG = {
    endpoints: {
        list: '<?= BASE_URL ?>/admin/api/resenas/questions/list.php',
        delete: '<?= BASE_URL ?>/admin/api/resenas/questions/delete.php',
        getDetails: '<?= BASE_URL ?>/api/get_questions.php',
        submitAnswer: '<?= BASE_URL ?>/api/submit_answer.php'
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/admin/reviews/questions.js"></script>
</body>
</html>