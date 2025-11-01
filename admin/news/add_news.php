<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

requireRole('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Noticia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
    <style>
        .form-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
        .form-actions { display:flex; gap:10px; }
        .img-preview { max-width: 240px; border:1px solid #eee; border-radius:6px; }
    </style>
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-plus"></i> Agregar Noticia</h1>
                <div class="breadcrumb"><a href="<?= BASE_URL ?>/admin">Dashboard</a> / <a href="<?= BASE_URL ?>/admin/news/news_list.php">Noticias</a> / <span>Agregar</span></div>
            </div>

            <div class="form-card">
                <form action="<?= BASE_URL ?>/admin/news/save_news.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="">

                    <div class="form-group">
                        <label for="title">Título</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Contenido</label>
                        <textarea id="content" name="content" class="form-control" rows="8" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Imagen (opcional)</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-control">
                        <small>Formatos permitidos: jpg, jpeg, png, webp. Máx 3MB.</small>
                        <div style="margin-top:10px"><img id="preview" class="img-preview" style="display:none" /></div>
                    </div>

                    <div class="form-group" style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                        <label><input type="checkbox" name="is_active" value="1" checked> Activa</label>
                        <label><input type="checkbox" name="is_featured" value="1"> Destacada</label>
                        <div>
                            <label for="published_at">Fecha publicación (opcional)</label>
                            <input type="datetime-local" id="published_at" name="published_at" class="form-control">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="<?= BASE_URL ?>/admin/news/news_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
