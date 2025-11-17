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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="admins-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-user-shield"></i> Administradores</h1>
                    <p>Gestión de cuentas de administración y roles.</p>
                </div>
                <div class="actions">
                    <a class="btn-soft" href="<?= BASE_URL ?>/admin/admins/create.php"><i class="fas fa-plus"></i> Nuevo administrador</a>
                </div>
            </div>

            <section class="surface-card">
                <header class="filter-bar">
                    <div>
                        <h2>Equipo administrativo</h2>
                        <p class="text-muted">Roles, estado y accesos</p>
                    </div>
                </header>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="admins-table">
                            <tr><td colspan="5">Cargando administradores...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
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
