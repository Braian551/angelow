<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar rol admin
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Anuncios - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/announcements.css">
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="dashboard-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-bullhorn"></i> Gestión de Anuncios
                </h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Anuncios</span>
                </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="card filters-card">
                <div class="filcen">
                    <a href="<?= BASE_URL ?>/admin/announcements/add.php" class="btn btn-success" id="add-announcement-btn">
                        <i class="fas fa-plus"></i> Agregar anuncio
                    </a>
                </div>
            </div>

            <div class="results-summary">
                <p id="results-count">Cargando anuncios...</p>
            </div>

            <!-- Listado de anuncios -->
            <div class="products-grid" id="announcements-container">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando anuncios...</div>
            </div>

            <div class="pagination" id="pagination-container"></div>
        </div>
    </main>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Eliminación</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Eliminar este anuncio? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="confirm-delete">Eliminar</button>
            <button class="btn btn-secondary modal-close">Cancelar</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
<script src="<?= BASE_URL ?>/js/alerta.js"></script>
<script src="<?= BASE_URL ?>/js/admin/announcements/announcementsadmin.js"></script>
</body>
</html>
