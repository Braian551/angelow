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
    <title>Configuraci&oacute;n general | Panel Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
    <style>
        .settings-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .settings-section {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--hub-border);
        }

        .section-header h3 {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--hub-text);
            margin: 0;
        }

        .section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--hub-primary-soft);
            color: var(--hub-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .fields-grid {
            display: grid;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--hub-muted-strong);
        }

        .input-group {
            display: flex;
            align-items: stretch;
            border: 1px solid var(--hub-border);
            border-radius: 10px;
            overflow: hidden;
            transition: var(--hub-transition);
            background: #fff;
        }

        .input-group:focus-within {
            border-color: var(--hub-primary);
            box-shadow: 0 0 0 3px var(--hub-primary-soft);
        }

        .input-group-text {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            background: var(--hub-bg);
            border-right: 1px solid var(--hub-border);
            color: var(--hub-muted);
            font-size: 1rem;
            min-width: 45px;
        }

        .form-control {
            flex: 1;
            border: none;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: var(--hub-text);
            background: transparent;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
        }

        .form-control-color {
            padding: 0.25rem;
            height: 42px;
            cursor: pointer;
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--hub-muted);
            margin-top: 0.2rem;
        }

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        .current-image-preview {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border: 1px dashed var(--hub-border);
            border-radius: 8px;
            display: inline-block;
        }

        .current-image-preview img {
            max-height: 60px;
            width: auto;
            display: block;
        }

        .file-input-wrapper {
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            padding: 0.5rem;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

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