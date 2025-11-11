<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

// Verificar si se recibió un ID
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($announcement_id <= 0) {
    $_SESSION['alert'] = ['message' => 'ID de anuncio inválido.', 'type' => 'error'];
    header('Location: ' . BASE_URL . '/admin/announcements/list.php');
    exit;
}

// Obtener datos del anuncio
$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$announcement_id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
    $_SESSION['alert'] = ['message' => 'Anuncio no encontrado.', 'type' => 'error'];
    header('Location: ' . BASE_URL . '/admin/announcements/list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Anuncio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
    <style>
        .form-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
        .form-actions { display:flex; gap:10px; }
        .img-preview { max-width: 240px; border:1px solid #eee; border-radius:6px; margin-top: 10px; }
        .form-section { border: 1px solid #eee; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .form-section h3 { margin-top: 0; color: #555; font-size: 1.1rem; }
        .color-input-group { display: flex; gap: 10px; align-items: center; }
        .color-preview { width: 40px; height: 40px; border-radius: 4px; border: 1px solid #ddd; }
        .current-image { max-width: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; margin-bottom: 10px; }
        /* Estilos para los iconos en el select */
        .icon-select-container {
            position: relative;
        }
        .icon-select-container select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 40px;
        }
        .icon-select-container::after {
            content: '▼';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #666;
        }
        .icon-preview {
            display: inline-block;
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-edit"></i> Editar Anuncio</h1>
                <div class="breadcrumb"><a href="<?= BASE_URL ?>/admin">Dashboard</a> / <a href="<?= BASE_URL ?>/admin/announcements/list.php">Anuncios</a> / <span>Editar</span></div>
            </div>

            <div class="form-card">
                <form action="<?= BASE_URL ?>/admin/announcements/save.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $announcement['id'] ?>">

                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                        
                        <div class="form-group">
                            <label for="type">Tipo de Anuncio <span style="color:red">*</span></label>
                            <select id="type" name="type" class="form-control" required onchange="toggleFields()">
                                <option value="">Seleccionar...</option>
                                <option value="top_bar" <?= $announcement['type'] === 'top_bar' ? 'selected' : '' ?>>Barra Superior</option>
                                <option value="promo_banner" <?= $announcement['type'] === 'promo_banner' ? 'selected' : '' ?>>Banner Promocional</option>
                            </select>
                            <small>Barra superior: aparece arriba del sitio. Banner: aparece en el contenido.</small>
                        </div>

                        <div class="form-group">
                            <label for="title">Título <span style="color:red">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($announcement['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Mensaje <span style="color:red">*</span></label>
                            <textarea id="message" name="message" class="form-control" rows="3" required><?= htmlspecialchars($announcement['message']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-section" id="banner-fields" style="display:<?= $announcement['type'] === 'promo_banner' ? 'block' : 'none' ?>;">
                        <h3><i class="fas fa-image"></i> Opciones de Banner</h3>
                        
                        <div class="form-group">
                            <label for="subtitle">Subtítulo (opcional)</label>
                            <input type="text" id="subtitle" name="subtitle" class="form-control" value="<?= htmlspecialchars($announcement['subtitle'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="button_text">Texto del Botón (opcional)</label>
                            <input type="text" id="button_text" name="button_text" class="form-control" value="<?= htmlspecialchars($announcement['button_text'] ?? '') ?>" placeholder="Ej: Ver oferta">
                        </div>

                        <div class="form-group">
                            <label for="button_link">URL del Botón (opcional)</label>
                            <input type="text" id="button_link" name="button_link" class="form-control" value="<?= htmlspecialchars($announcement['button_link'] ?? '') ?>" placeholder="Ej: /tienda/tienda.php?promo=verano">
                        </div>

                        <div class="form-group">
                            <label for="image">Imagen de Banner (opcional)</label>
                            <?php if (!empty($announcement['image'])): ?>
                                <div>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($announcement['image']) ?>" class="current-image" alt="Imagen actual">
                                    <p><small>Imagen actual. Selecciona una nueva para reemplazarla.</small></p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="image" name="image" accept="image/*" class="form-control">
                            <small>Formatos permitidos: jpg, jpeg, png, webp. Máx 3MB.</small>
                            <img id="preview" class="img-preview" style="display:none" />
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-palette"></i> Apariencia</h3>
                        
                        <div class="form-group">
                            <label for="icon">Icono <span style="color:red">*</span></label>
                            <div class="icon-select-container">
                                <select id="icon" name="icon" class="form-control" required>
                                    <option value="">Seleccionar icono...</option>
                                    <optgroup label="Ofertas y Descuentos">
                                        <option value="fa-tags" <?= ($announcement['icon'] ?? '') === 'fa-tags' ? 'selected' : '' ?>>Etiquetas (Ofertas)</option>
                                        <option value="fa-percent" <?= ($announcement['icon'] ?? '') === 'fa-percent' ? 'selected' : '' ?>>Porcentaje (Descuento)</option>
                                        <option value="fa-gift" <?= ($announcement['icon'] ?? '') === 'fa-gift' ? 'selected' : '' ?>>Regalo</option>
                                        <option value="fa-fire" <?= ($announcement['icon'] ?? '') === 'fa-fire' ? 'selected' : '' ?>>Fuego (Oferta caliente)</option>
                                    </optgroup>
                                    <optgroup label="Envíos y Entregas">
                                        <option value="fa-truck" <?= ($announcement['icon'] ?? '') === 'fa-truck' ? 'selected' : '' ?>>Camión (Envío)</option>
                                        <option value="fa-shipping-fast" <?= ($announcement['icon'] ?? '') === 'fa-shipping-fast' ? 'selected' : '' ?>>Envío rápido</option>
                                        <option value="fa-plane" <?= ($announcement['icon'] ?? '') === 'fa-plane' ? 'selected' : '' ?>>Avión</option>
                                        <option value="fa-box" <?= ($announcement['icon'] ?? '') === 'fa-box' ? 'selected' : '' ?>>Caja</option>
                                    </optgroup>
                                    <optgroup label="Promociones">
                                        <option value="fa-star" <?= ($announcement['icon'] ?? '') === 'fa-star' ? 'selected' : '' ?>>Estrella</option>
                                        <option value="fa-crown" <?= ($announcement['icon'] ?? '') === 'fa-crown' ? 'selected' : '' ?>>Corona (Premium)</option>
                                        <option value="fa-certificate" <?= ($announcement['icon'] ?? '') === 'fa-certificate' ? 'selected' : '' ?>>Certificado</option>
                                        <option value="fa-medal" <?= ($announcement['icon'] ?? '') === 'fa-medal' ? 'selected' : '' ?>>Medalla</option>
                                    </optgroup>
                                    <optgroup label="Tiempo y Urgencia">
                                        <option value="fa-clock" <?= ($announcement['icon'] ?? '') === 'fa-clock' ? 'selected' : '' ?>>Reloj</option>
                                        <option value="fa-hourglass-half" <?= ($announcement['icon'] ?? '') === 'fa-hourglass-half' ? 'selected' : '' ?>>Reloj de arena</option>
                                        <option value="fa-calendar" <?= ($announcement['icon'] ?? '') === 'fa-calendar' ? 'selected' : '' ?>>Calendario</option>
                                        <option value="fa-bell" <?= ($announcement['icon'] ?? '') === 'fa-bell' ? 'selected' : '' ?>>Campana</option>
                                    </optgroup>
                                    <optgroup label="Compras">
                                        <option value="fa-shopping-cart" <?= ($announcement['icon'] ?? '') === 'fa-shopping-cart' ? 'selected' : '' ?>>Carrito</option>
                                        <option value="fa-shopping-bag" <?= ($announcement['icon'] ?? '') === 'fa-shopping-bag' ? 'selected' : '' ?>>Bolsa de compras</option>
                                        <option value="fa-credit-card" <?= ($announcement['icon'] ?? '') === 'fa-credit-card' ? 'selected' : '' ?>>Tarjeta de crédito</option>
                                        <option value="fa-heart" <?= ($announcement['icon'] ?? '') === 'fa-heart' ? 'selected' : '' ?>>Corazón</option>
                                    </optgroup>
                                    <optgroup label="Información">
                                        <option value="fa-info-circle" <?= ($announcement['icon'] ?? '') === 'fa-info-circle' ? 'selected' : '' ?>>Información</option>
                                        <option value="fa-bullhorn" <?= ($announcement['icon'] ?? '') === 'fa-bullhorn' ? 'selected' : '' ?>>Megáfono</option>
                                        <option value="fa-exclamation-circle" <?= ($announcement['icon'] ?? '') === 'fa-exclamation-circle' ? 'selected' : '' ?>>Advertencia</option>
                                        <option value="fa-lightbulb" <?= ($announcement['icon'] ?? '') === 'fa-lightbulb' ? 'selected' : '' ?>>Bombilla</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div id="icon-preview" class="icon-preview-box" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; display: none;">
                                <span id="selected-icon-display"></span>
                                <span id="selected-icon-text"></span>
                            </div>
                            <small>Selecciona el icono que mejor represente tu anuncio</small>
                        </div>

                        <div class="color-preview-box" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 15px;">
                            <i class="fas fa-palette" style="font-size: 24px; margin-bottom: 10px;"></i>
                            <p style="margin: 0; font-weight: 500;">Los anuncios usan colores profesionales predefinidos</p>
                            <small style="opacity: 0.9;">Azul elegante para mejor visibilidad</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Configuración</h3>
                        
                        <div class="form-group">
                            <label for="priority">Prioridad (mayor número = mayor prioridad)</label>
                            <input type="number" id="priority" name="priority" class="form-control" value="<?= $announcement['priority'] ?>" min="0" max="100">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="start_date">Fecha de Inicio (opcional)</label>
                                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= !empty($announcement['start_date']) ? date('Y-m-d\TH:i', strtotime($announcement['start_date'])) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="end_date">Fecha de Fin (opcional)</label>
                                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= !empty($announcement['end_date']) ? date('Y-m-d\TH:i', strtotime($announcement['end_date'])) : '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><input type="checkbox" name="is_active" value="1" <?= $announcement['is_active'] ? 'checked' : '' ?>> Activo</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="<?= BASE_URL ?>/admin/announcements/list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    // Preview de imagen
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('preview');
    imageInput.addEventListener('change', function() {
        const f = this.files && this.files[0];
        if (!f) { preview.style.display='none'; return; }
        const url = URL.createObjectURL(f);
        preview.src = url; preview.style.display='block';
    });

    // Preview del icono seleccionado
    const iconSelect = document.getElementById('icon');
    const iconPreview = document.getElementById('icon-preview');
    const selectedIconDisplay = document.getElementById('selected-icon-display');
    const selectedIconText = document.getElementById('selected-icon-text');

    // Mostrar preview inicial si hay un icono seleccionado
    if (iconSelect.value) {
        const selectedOption = iconSelect.options[iconSelect.selectedIndex];
        const selectedText = selectedOption.text;
        selectedIconDisplay.innerHTML = `<i class="fas ${iconSelect.value}"></i>`;
        selectedIconText.textContent = selectedText;
        iconPreview.style.display = 'block';
    }

    iconSelect.addEventListener('change', function() {
        const selectedValue = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const selectedText = selectedOption.text;

        if (selectedValue) {
            selectedIconDisplay.innerHTML = `<i class="fas ${selectedValue}"></i>`;
            selectedIconText.textContent = selectedText;
            iconPreview.style.display = 'block';
        } else {
            iconPreview.style.display = 'none';
        }
    });

    // Mostrar/ocultar campos según tipo
    function toggleFields() {
        const type = document.getElementById('type').value;
        const bannerFields = document.getElementById('banner-fields');
        bannerFields.style.display = type === 'promo_banner' ? 'block' : 'none';
    }
</script>
</body>
</html>
