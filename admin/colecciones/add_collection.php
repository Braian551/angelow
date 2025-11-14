<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/formuser.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Colección - Angelow Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/collections.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php include __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content collections-dashboard collections-page">
            <div class="page-header">
                <div class="header-content">
                    <div class="collections-header-left">
                        <a href="collections_list.php" class="back-button">
                            <i class="fas fa-arrow-left"></i>
                            Volver
                        </a>
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle"></i>
                            Nueva Colección
                        </h1>
                        <p class="page-subtitle">Crea una nueva colección de productos</p>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <form id="collectionForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <!-- Información básica -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </h3>
                            
                            <div class="form-group">
                                <label for="name" class="required">Nombre de la Colección</label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="form-control" 
                                       placeholder="Ej: Verano Mágico" 
                                       required
                                       maxlength="100">
                                <small class="form-text">Nombre único para la colección</small>
                            </div>

                            <div class="form-group">
                                <label for="slug" class="required">Slug (URL amigable)</label>
                                <input type="text" 
                                       id="slug" 
                                       name="slug" 
                                       class="form-control" 
                                       placeholder="verano-magico" 
                                       required
                                       pattern="[a-z0-9-]+"
                                       maxlength="100">
                                <small class="form-text">Solo letras minúsculas, números y guiones. Se genera automáticamente del nombre.</small>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <textarea id="description" 
                                          name="description" 
                                          class="form-control" 
                                          rows="4" 
                                          placeholder="Describe la colección..."
                                          maxlength="500"></textarea>
                                <small class="form-text">Máximo 500 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="launch_date">Fecha de Lanzamiento</label>
                                <input type="date" 
                                       id="launch_date" 
                                       name="launch_date" 
                                       class="form-control">
                                <small class="form-text">Fecha en que la colección estará disponible</small>
                            </div>

                            <div class="form-group">
                                <label class="toggle-label">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           id="is_active" 
                                           checked>
                                    <span>Colección activa</span>
                                </label>
                                <small class="form-text">Desactiva para ocultar temporalmente la colección</small>
                            </div>
                        </div>

                        <!-- Imagen de la colección -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-image"></i>
                                Imagen de la Colección
                            </h3>
                            
                            <div class="image-upload-container">
                                <div class="image-preview" id="imagePreview">
                                    <div class="preview-placeholder">
                                        <i class="fas fa-layer-group"></i>
                                        <p>Sin imagen</p>
                                    </div>
                                </div>
                                
                                <div class="upload-controls">
                                    <input type="file" 
                                           id="image" 
                                           name="image" 
                                           accept="image/jpeg,image/jpg,image/png,image/webp,image/avif" 
                                           class="file-input">
                                    <label for="image" class="btn btn-secondary">
                                        <i class="fas fa-upload"></i>
                                        Seleccionar Imagen
                                    </label>
                                    <button type="button" 
                                            class="btn btn-outline" 
                                            id="removeImage" 
                                            style="display: none;">
                                        <i class="fas fa-times"></i>
                                        Quitar
                                    </button>
                                </div>
                                
                                <small class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Formatos: JPG, PNG, WebP, AVIF. Tamaño máximo: 15MB. Recomendado: 1200x600px
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='collections_list.php'">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Guardar Colección
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </main>
    </div>

    <!-- Toast de notificaciones -->
    <div id="toast" class="toast"></div>

    <script src="<?= BASE_URL ?>/js/admin/collections/add_collection.js"></script>
</body>
</html>
