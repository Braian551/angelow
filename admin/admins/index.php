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
    <title>Administradores | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/administrators.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-user-shield"></i> Administradores</h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                    <span>Administradores</span>
                </div>
            </div>

            <div class="actions-bar">
                <div class="drag-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Gestión de cuentas y roles</span>
                </div>
                <a href="<?= BASE_URL ?>/admin/admins/create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo administrador
                </a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="admins-table">
                            <tr><td colspan="5" class="loading-row"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando administradores...</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
window.ADMINS_HUB_CONFIG = {
    endpoints: {
        list: '<?= BASE_URL ?>/admin/api/admins/list.php',
        update: '<?= BASE_URL ?>/admin/api/admins/update_status.php'
    }
}
window.ADMINS_HUB_CONFIG.baseUrl = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/js/admin/admins/admins-dashboard.js"></script>
</body>
</html>
