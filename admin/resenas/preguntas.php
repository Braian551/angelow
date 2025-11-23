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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/questions-enhanced.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="questions-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-circle-question"></i> Preguntas</h1>
                    <p class="page-subtitle">Modera y responde preguntas de productos.</p>
                </div>
                <div class="page-actions">
                    <button class="btn-soft" id="questions-refresh" title="Actualizar">
                        <i class="fas fa-rotate"></i>
                        <span>Actualizar</span>
                    </button>
                </div>
            </div>

            <section class="insights-grid" id="questions-insights">
                <article class="stat-card" data-metric="total">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-comments"></i></span>
                        <h2>Total</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Preguntas registradas</div>
                </article>
                <article class="stat-card" data-metric="answered">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-circle-check"></i></span>
                        <h2>Respondidas</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">Con al menos una respuesta</div>
                </article>
                <article class="stat-card" data-metric="unanswered">
                    <div class="stat-top">
                        <span class="stat-icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        <h2>Pendientes</h2>
                    </div>
                    <strong class="stat-value">--</strong>
                    <div class="stat-subtext">A la espera de respuesta</div>
                </article>
            </section>

            <section class="split-grid layout-table-detail">
                <article class="table-card questions-table-card">
                    <header class="table-header-enhanced">
                        <div class="header-title">
                            <h2><i class="fas fa-list"></i> Listado de preguntas</h2>
                            <p class="text-muted">Gestiona las consultas de tus clientes</p>
                        </div>
                        <div class="header-filters">
                            <div class="filter-group">
                                <i class="fas fa-search filter-icon"></i>
                                <input type="search" id="questions-search" placeholder="Buscar texto o producto">
                            </div>
                            <div class="filter-group">
                                <i class="fas fa-filter filter-icon"></i>
                                <select id="questions-filter">
                                    <option value="all">Todas</option>
                                    <option value="0">Sin responder</option>
                                    <option value="1">Respondidas</option>
                                </select>
                            </div>
                            <button class="btn-soft btn-icon" id="questions-clear-filters" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </header>
                    <div class="table-wrapper">
                        <table class="questions-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-comment"></i> Pregunta</th>
                                    <th><i class="fas fa-box"></i> Producto</th>
                                    <th><i class="fas fa-user"></i> Autor</th>
                                    <th><i class="fas fa-comments"></i> Estado</th>
                                    <th><i class="fas fa-cog"></i> Acciones</th>
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

                <aside class="detail-panel question-detail-panel collapsed" id="question-detail" aria-hidden="true">
                    <div class="empty-state" data-state="empty">
                        <i class="fas fa-circle-question empty-icon"></i>
                        <h3>Selecciona una pregunta</h3>
                        <p>Verás el hilo de respuestas y podrás moderar o responder.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <div class="detail-header-top">
                                <div class="detail-name" data-role="title"></div>
                                <button class="btn-soft btn-icon" id="question-detail-toggle" aria-expanded="false" aria-controls="question-detail" title="Cerrar panel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="detail-meta">
                                <span class="meta-item"><i class="fas fa-box"></i> <span data-role="product"></span></span>
                                <span class="meta-item"><i class="fas fa-clock"></i> <span data-role="date" class="text-muted"></span></span>
                            </div>
                        </header>
                        <div class="question-block">
                            <h4><i class="fas fa-comment"></i> Pregunta del cliente</h4>
                            <p data-role="question" class="question-text"></p>
                        </div>
                        <div class="answers-section">
                            <h4><i class="fas fa-comments"></i> Respuestas</h4>
                            <ul id="question-answers" class="timeline"></ul>
                        </div>
                        <form id="answer-form" class="answer-form">
                            <h4><i class="fas fa-reply"></i> Responder</h4>
                            <div class="form-row">
                                <textarea name="answer" placeholder="Escribe una respuesta útil y clara..." required rows="4"></textarea>
                            </div>
                            <div class="actions">
                                <button class="btn-soft primary" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                    Enviar respuesta
                                </button>
                                <button class="btn-soft danger" type="button" data-action="delete">
                                    <i class="fas fa-trash"></i>
                                    Eliminar pregunta
                                </button>
                            </div>
                        </form>
                    </div>
                </aside>

                <article class="chart-card chart-card-compact" id="questions-status-card">
                    <header class="chart-header">
                        <div>
                            <h2><i class="fas fa-chart-pie"></i> Estado de preguntas</h2>
                            <p class="text-muted">Respondidas vs pendientes</p>
                        </div>
                    </header>
                    <div class="chart-body">
                        <canvas id="questions-status-chart" aria-label="Estado de preguntas" role="img" height="200"></canvas>
                        <div class="chart-empty" data-empty="questions-status" hidden>
                            <i class="fas fa-chart-pie"></i>
                            <p>Sin datos disponibles</p>
                        </div>
                    </div>
                    <div class="chart-legend" id="questions-status-legend"></div>
                </article>
            </section>
        </div>
    </main>
</div>

<script>
window.QUESTIONS_INBOX_CONFIG = {
    endpoints: {
        list: '<?= BASE_URL ?>/admin/api/resenas/questions/list.php',
        delete: '<?= BASE_URL ?>/admin/api/resenas/questions/delete.php',
        detail: '<?= BASE_URL ?>/admin/api/resenas/questions/detail.php',
        getDetails: '<?= BASE_URL ?>/admin/api/resenas/questions/detail.php',
        submitAnswer: '<?= BASE_URL ?>/api/submit_answer.php'
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/admin/reviews/questions.js"></script>
</body>
</html>