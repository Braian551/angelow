<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'ID inválido'];
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
}

try {
    $stmt = $conn->prepare('SELECT * FROM sliders WHERE id = ?');
    $stmt->execute([$id]);
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$slider) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Slider no encontrado'];
        header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('edit_slider fetch error: ' . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error de base de datos'];
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Slider</title>
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
                <h1><i class="fas fa-edit"></i> Editar Slider</h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                    <a href="<?= BASE_URL ?>/admin/sliders/sliders_list.php">Sliders</a> / 
                    <span>Editar</span>
                </div>
            </div>

            <div class="form-card">
                <form action="<?= BASE_URL ?>/admin/sliders/save_slider.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= (int)$slider['id'] ?>">

                    <div class="form-group">
                        <label for="title">Título <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?= htmlspecialchars($slider['title']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="subtitle">Subtítulo</label>
                        <input type="text" id="subtitle" name="subtitle" class="form-control" maxlength="255" value="<?= htmlspecialchars($slider['subtitle'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="image">Imagen (dejar vacío para mantener la actual)</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-control">
                        <?php if (!empty($slider['image'])): ?>
                            <div class="mt-2">
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($slider['image']) ?>" class="img-preview" alt="Imagen actual" onerror="this.style.display='none'" style="max-width: 100%; max-height: 300px; border-radius: 6px;">
                                <p><small class="text-muted">Imagen actual</small></p>
                            </div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Formatos: jpg, jpeg, png, webp, avif. Máx 15MB. Recomendado: 1920x800px</small>
                    </div>

                    <div class="form-group">
                        <label for="link">Enlace (URL opcional)</label>
                        <input type="text" id="link" name="link" class="form-control" placeholder="https://ejemplo.com o /pagina-interna" value="<?= htmlspecialchars($slider['link'] ?? '') ?>">
                        <small class="form-text text-muted">URL completa (https://...) o ruta relativa (/pagina). Dejar vacío si no hay enlace</small>
                    </div>

                    <div class="form-group">
                        <label for="order_position">Orden</label>
                        <input type="number" id="order_position" name="order_position" class="form-control" value="<?= (int)$slider['order_position'] ?>" min="1">
                        <small class="form-text text-muted">Posición de visualización (menor número = primero)</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" <?= $slider['is_active'] ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="is_active">Activo</label>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="<?= BASE_URL ?>/admin/sliders/sliders_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    const imageInput = document.getElementById('image');
    const preview = document.querySelector('.img-preview');
    imageInput.addEventListener('change', function() {
        const f = this.files && this.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        if (preview) { preview.src = url; preview.style.display='block'; }
    });
</script>
</body>
</html>
