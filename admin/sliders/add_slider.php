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
    <title>Agregar Slider</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-plus"></i> Agregar Slider</h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                    <a href="<?= BASE_URL ?>/admin/sliders/sliders_list.php">Sliders</a> / 
                    <span>Agregar</span>
                </div>
            </div>

            <div class="form-card">
                <form action="<?= BASE_URL ?>/admin/sliders/save_slider.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="">

                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Información del Slider</h3>
                        
                        <div class="form-group">
                            <label for="title">Título <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" required maxlength="255" placeholder="Ej: Nueva Colección 2024">
                        </div>

                        <div class="form-group">
                            <label for="subtitle">Subtítulo</label>
                            <input type="text" id="subtitle" name="subtitle" class="form-control" maxlength="255" placeholder="Ej: Descubre los mejores estilos">
                        </div>

                        <div class="form-group">
                            <label for="link">Enlace (URL opcional)</label>
                            <input type="text" id="link" name="link" class="form-control" placeholder="https://ejemplo.com o /pagina-interna">
                            <small class="form-text text-muted">URL completa (https://...) o ruta relativa (/pagina). Dejar vacío si no hay enlace</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-image"></i> Multimedia</h3>
                        
                        <div class="form-group">
                            <label for="image">Imagen <span class="text-danger">*</span></label>
                            <div class="custom-file-upload">
                                <input type="file" id="image" name="image" accept="image/*" class="form-control" required>
                                <small class="form-text text-muted">Formatos: jpg, jpeg, png, webp, avif. Máx 15MB. Recomendado: 1920x800px</small>
                            </div>
                            <div class="mt-3">
                                <img id="preview" class="img-preview" style="display:none; max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" />
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Configuración</h3>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="order_position">Orden</label>
                                <input type="number" id="order_position" name="order_position" class="form-control" value="1" min="1">
                                <small class="form-text text-muted">Posición de visualización (menor número = primero)</small>
                            </div>

                            <div class="form-group col-md-6 d-flex align-items-center">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                    <label class="custom-control-label" for="is_active">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Slider</button>
                        <a href="<?= BASE_URL ?>/admin/sliders/sliders_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('preview');
    imageInput.addEventListener('change', function() {
        const f = this.files && this.files[0];
        if (!f) { preview.style.display='none'; return; }
        const url = URL.createObjectURL(f);
        preview.src = url; preview.style.display='block';
    });
</script>
</body>
</html>
