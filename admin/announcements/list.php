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

            <!-- Actions Bar - Matching Categories -->
            <div class="actions-bar">
                <a href="<?= BASE_URL ?>/admin/announcements/add.php" class="btn btn-primary" id="add-announcement-btn">
                    <i class="fas fa-plus"></i> Agregar Anuncio
                </a>
                <div class="search-box">
                    <input type="text" placeholder="Buscar anuncios..." id="search-announcements">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <!-- Hidden element to prevent JS error -->
                <span id="results-count" style="display: none;"></span>
            </div>


            <!-- Listado de anuncios -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">Icono</th>
                                <th>Detalles del Anuncio</th>
                                <th>Tipo</th>
                                <th>Prioridad</th>
                                <th>Fechas</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="announcements-container">
                            <tr>
                                <td colspan="7" class="loading-row">
                                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando anuncios...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
