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
                    <h2>Total</h2>
                    <strong>--</strong>
                </article>
                <article class="stat-card" data-metric="answered">
                    <h2>Respondidas</h2>
                    <strong>--</strong>
                </article>
                <article class="stat-card" data-metric="unanswered">
                    <h2>Sin responder</h2>
                    <strong>--</strong>
                </article>
            </section>

            <section class="split-grid">
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
                    <div class="filter-bar" id="questions-pagination">
                        <span class="text-muted" data-role="meta">-- resultados</span>
                        <div class="actions">
                            <button class="btn-soft" data-page="prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-soft" data-page="next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </article>

                <aside class="detail-panel" id="question-detail">
                    <div class="empty-state" data-state="empty">
                        <h3>Selecciona una pregunta</h3>
                        <p>Verás el hilo de respuestas y podrás moderar o responder.</p>
                    </div>
                    <div class="detail-body" data-state="content" hidden>
                        <header>
                            <div class="detail-name" data-role="title"></div>
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
<script src="<?= BASE_URL ?>/js/admin/reviews/questions.js"></script>
</body>
</html>