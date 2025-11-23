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
    <title>Agregar Anuncio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/announcements-forms.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-plus"></i> Agregar Anuncio</h1>
                <div class="breadcrumb"><a href="<?= BASE_URL ?>/admin">Dashboard</a> / <a href="<?= BASE_URL ?>/admin/announcements/list.php">Anuncios</a> / <span>Agregar</span></div>
            </div>

            <div class="form-card">
                <form action="<?= BASE_URL ?>/admin/announcements/save.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="">

                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                        
                        <div class="form-group">
                            <label for="type">Tipo de Anuncio <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-control" required onchange="toggleFields()">
                                <option value="">Seleccionar...</option>
                                <option value="top_bar">Barra Superior</option>
                                <option value="promo_banner">Banner Promocional</option>
                            </select>
                            <small class="form-text text-muted">Barra superior: aparece arriba del sitio. Banner: aparece en el contenido.</small>
                        </div>

                        <div class="form-group">
                            <label for="title">Título <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Mensaje <span class="text-danger">*</span></label>
                            <textarea id="message" name="message" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="form-section" id="banner-fields" style="display:none;">
                        <h3><i class="fas fa-image"></i> Opciones de Banner</h3>
                        
                        <div class="form-group">
                            <label for="subtitle">Subtítulo (opcional)</label>
                            <input type="text" id="subtitle" name="subtitle" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="button_text">Texto del Botón (opcional)</label>
                            <input type="text" id="button_text" name="button_text" class="form-control" placeholder="Ej: Ver oferta">
                        </div>

                        <div class="form-group">
                            <label for="button_link">URL del Botón (opcional)</label>
                            <input type="text" id="button_link" name="button_link" class="form-control" placeholder="Ej: /tienda/tienda.php?promo=verano">
                        </div>

                        <div class="form-group">
                            <label for="image">Imagen de Banner (opcional)</label>
                            <input type="file" id="image" name="image" accept="image/*" class="form-control">
                            <small class="form-text text-muted">Formatos permitidos: jpg, jpeg, png, webp. Máx 3MB.</small>
                            <div class="mt-2">
                                <img id="preview" class="img-preview" style="display:none; max-width: 200px; border-radius: 4px;" />
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-palette"></i> Apariencia</h3>
                        
                        <div class="form-group">
                            <label for="icon">Icono <span class="text-danger">*</span></label>
                            <div class="icon-select-container">
                                <select id="icon" name="icon" class="form-control" required>
                                    <option value="">Seleccionar icono...</option>
                                    <optgroup label="Ofertas y Descuentos">
                                        <option value="fa-tags">Etiquetas (Ofertas)</option>
                                        <option value="fa-percent">Porcentaje (Descuento)</option>
                                        <option value="fa-gift">Regalo</option>
                                        <option value="fa-fire">Fuego (Oferta caliente)</option>
                                    </optgroup>
                                    <optgroup label="Envíos y Entregas">
                                        <option value="fa-truck">Camión (Envío)</option>
                                        <option value="fa-shipping-fast">Envío rápido</option>
                                        <option value="fa-plane">Avión</option>
                                        <option value="fa-box">Caja</option>
                                    </optgroup>
                                    <optgroup label="Promociones">
                                        <option value="fa-star">Estrella</option>
                                        <option value="fa-crown">Corona (Premium)</option>
                                        <option value="fa-certificate">Certificado</option>
                                        <option value="fa-medal">Medalla</option>
                                    </optgroup>
                                    <optgroup label="Tiempo y Urgencia">
                                        <option value="fa-clock">Reloj</option>
                                        <option value="fa-hourglass-half">Reloj de arena</option>
                                        <option value="fa-calendar">Calendario</option>
                                        <option value="fa-bell">Campana</option>
                                    </optgroup>
                                    <optgroup label="Compras">
                                        <option value="fa-shopping-cart">Carrito</option>
                                        <option value="fa-shopping-bag">Bolsa de compras</option>
                                        <option value="fa-credit-card">Tarjeta de crédito</option>
                                        <option value="fa-heart">Corazón</option>
                                    </optgroup>
                                    <optgroup label="Información">
                                        <option value="fa-info-circle">Información</option>
                                        <option value="fa-bullhorn">Megáfono</option>
                                        <option value="fa-exclamation-circle">Advertencia</option>
                                        <option value="fa-lightbulb">Bombilla</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div id="icon-preview" class="icon-preview-box" style="display: none;">
                                <span id="selected-icon-display"></span>
                                <span id="selected-icon-text"></span>
                            </div>
                            <small class="form-text text-muted">Selecciona el icono que mejor represente tu anuncio</small>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-palette"></i>
                            <strong>Nota:</strong> Los anuncios usan colores profesionales predefinidos (Azul elegante) para mejor visibilidad.
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Configuración</h3>
                        
                        <div class="form-group">
                            <label for="priority">Prioridad (mayor número = mayor prioridad)</label>
                            <input type="number" id="priority" name="priority" class="form-control" value="0" min="0" max="100">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="start_date">Fecha de Inicio (opcional)</label>
                                <input type="datetime-local" id="start_date" name="start_date" class="form-control">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="end_date">Fecha de Fin (opcional)</label>
                                <input type="datetime-local" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="is_active">Activo</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
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
