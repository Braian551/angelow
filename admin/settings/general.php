<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraci&oacute;n general | Panel Angelow</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../../layouts/headeradmin1.php'; ?>

        <div class="management-hub" id="settings-hub">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-cog"></i> General</h1>
                    <p>Preferencias globales de la tienda</p>
                </div>
            </div>

            <section class="surface-card">
                <header class="filter-bar">
                    <div>
                        <h2>Opciones generales</h2>
                        <p class="text-muted">Nombre de tienda, logo y otras opciones.</p>
                    </div>
                </header>
                <form id="settings-form">
                    <div id="settings-fields"></div>
                    <div class="actions">
                        <button type="submit" class="btn-soft primary">Guardar</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>

<script>
window.SITE_SETTINGS_ENDPOINTS = {
    url: '<?= BASE_URL ?>/admin/api/settings/general.php'
};
</script>
<script src="<?= BASE_URL ?>/js/admin/settings/general.js"></script>
</body>
</html>