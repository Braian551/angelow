<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

requireRole('admin');

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
    <title>Gestión de Sliders - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/sliders.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-images"></i> Gestión de Sliders</h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                    <a href="#">Configuración</a> / 
                    <span>Sliders</span>
                </div>
            </div>

            <div class="actions-bar">
                <div class="drag-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Arrastra para reordenar los slides</span>
                </div>
                <a href="<?= BASE_URL ?>/admin/sliders/add_slider.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar Slider
                </a>
            </div>

            <div class="card">
                <div id="sliders-container" class="table-responsive">
                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando sliders...</div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal confirmar eliminación -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Eliminación</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Eliminar este slider? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="confirm-delete">Eliminar</button>
            <button class="btn btn-secondary modal-close">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
<script src="<?= BASE_URL ?>/js/alerta.js"></script>
<?php require_once __DIR__ . '/../../js/admin/sliders/slidersadmin.php'; ?>
</body>
</html>
