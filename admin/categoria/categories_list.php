<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Verificar rol de administrador
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No tienes permisos para acceder a esta área'];
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al verificar permisos. Por favor intenta nuevamente.'];
    header("Refresh:0");
    exit();
}

// Procesar acciones CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Validar y sanitizar entrada
$id = filter_var($id, FILTER_VALIDATE_INT);

// Manejar acciones
switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            $errors = [];
            if (empty($name)) {
                $errors[] = 'El nombre de la categoría es requerido';
            }
            
            if (empty($slug)) {
                $errors[] = 'El slug es requerido';
            } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                $errors[] = 'El slug solo puede contener letras minúsculas, números y guiones';
            }

            if (!empty($errors)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                header("Location: categories_list.php?action=add");
                exit();
            }

            try {
                // Procesar imagen si se subió
                $imagePath = null;
                if (!empty($_FILES['image']['name'])) {
                    $uploadDir = __DIR__ . '/../../uploads/categories/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = 'category_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/categories/' . $fileName;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, is_active) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $imagePath, $is_active]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Categoría creada exitosamente'];
                header("Location: categories_list.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al crear categoría: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al crear la categoría. Por favor intenta nuevamente.'];
                header("Location: categories_list.php?action=add");
                exit();
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validaciones
            $errors = [];
            if (empty($name)) {
                $errors[] = 'El nombre de la categoría es requerido';
            }
            
            if (empty($slug)) {
                $errors[] = 'El slug es requerido';
            } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                $errors[] = 'El slug solo puede contener letras minúsculas, números y guiones';
            }

            if (!empty($errors)) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                header("Location: categories_list.php?action=edit&id=".$id);
                exit();
            }

            try {
                // Obtener categoría actual para la imagen
                $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $currentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
                $imagePath = $currentCategory['image'];

                // Procesar nueva imagen si se subió
                if (!empty($_FILES['image']['name'])) {
                    $uploadDir = __DIR__ . '/../../uploads/categories/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Eliminar imagen anterior si existe
                    if ($imagePath && file_exists(__DIR__ . '/../../' . $imagePath)) {
                        unlink(__DIR__ . '/../../' . $imagePath);
                    }
                    
                    $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = 'category_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/categories/' . $fileName;
                    }
                }

                $stmt = $conn->prepare("UPDATE categories SET 
                    name = ?, slug = ?, description = ?, image = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $imagePath, $is_active, $id]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Categoría actualizada exitosamente'];
                header("Location: categories_list.php");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar categoría: " . $e->getMessage());
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar la categoría. Por favor intenta nuevamente.'];
                header("Location: categories_list.php?action=edit&id=".$id);
                exit();
            }
        }
        break;

    case 'delete':
        try {
            // Verificar si la categoría tiene productos asociados
            $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            $productCount = $stmt->fetchColumn();

            if ($productCount > 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'No se puede eliminar la categoría porque tiene productos asociados'];
                header("Location: categories_list.php");
                exit();
            }

            // Obtener imagen para eliminarla
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category['image'] && file_exists(__DIR__ . '/../../' . $category['image'])) {
                unlink(__DIR__ . '/../../' . $category['image']);
            }

            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Categoría eliminada exitosamente'];
            header("Location: categories_list.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al eliminar categoría: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al eliminar la categoría. Por favor intenta nuevamente.'];
            header("Location: categories_list.php");
            exit();
        }
        break;

    case 'toggle-status':
        try {
            $stmt = $conn->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Estado de la categoría actualizado'];
            header("Location: categories_list.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de categoría: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cambiar el estado de la categoría.'];
            header("Location: categories_list.php");
            exit();
        }
        break;

    case 'remove-image':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id > 0) {
            try {
                // Obtener imagen actual
                $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category && $category['image']) {
                    // Eliminar archivo físico
                    $imagePath = __DIR__ . '/../../' . $category['image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    // Actualizar base de datos
                    $stmt = $conn->prepare("UPDATE categories SET image = NULL WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit();
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Imagen no encontrada']);
                exit();
            } catch (PDOException $e) {
                error_log("Error al eliminar imagen: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al eliminar imagen']);
                exit();
            }
        }
        break;
}

// Función para obtener todas las categorías
function obtenerCategorias($conn) {
    $sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
            FROM categories c
            ORDER BY name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener todas las categorías para listar
$categorias = obtenerCategorias($conn);

// Obtener datos de una categoría específica para edición
$categoriaActual = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $categoriaActual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoriaActual) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Categoría no encontrada'];
            header("Location: categories_list.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al obtener categoría: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al cargar la categoría.'];
        header("Location: categories_list.php");
        exit();
    }
}

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/categoria/categories.css">

</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-tags"></i> Gestión de Categorías
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Categorías</span>
                    </div>
                </div>

                <?php if (in_array($action, ['add', 'edit'])): ?>
                    <!-- Formulario para agregar/editar categorías -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= $action === 'add' ? 'Agregar Nueva Categoría' : 'Editar Categoría' ?></h3>
                        </div>
                        <div class="card-body">
                            <form id="category-form" method="POST" action="categories_list.php?action=<?= $action ?><?= $action === 'edit' ? '&id='.$id : '' ?>" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="name">Nombre de la categoría*</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?= htmlspecialchars($categoriaActual['name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="slug">Slug*</label>
                                    <input type="text" id="slug" name="slug" class="form-control" 
                                           value="<?= htmlspecialchars($categoriaActual['slug'] ?? '') ?>" required>
                                    <small class="form-text text-muted">URL amigable (solo letras minúsculas, números y guiones)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Descripción</label>
                                    <textarea id="description" name="description" class="form-control" 
                                              rows="3"><?= htmlspecialchars($categoriaActual['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Imagen</label>
                                    <div class="image-upload-container">
                                        <?php if (!empty($categoriaActual['image'])): ?>
                                            <div class="current-image" id="current-image-container">
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($categoriaActual['image']) ?>" alt="Imagen actual">
                                                <a href="#" class="remove-image" data-id="<?= $id ?>" title="Eliminar imagen">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="file-input-wrapper">
                                            <label for="image" class="file-input-label">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <span id="file-label-text">Seleccionar imagen</span>
                                            </label>
                                            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                        </div>
                                        
                                        <div class="image-info">
                                            <small>Formatos permitidos: JPG, PNG, GIF, WebP. Tamaño máximo: 2MB</small>
                                        </div>
                                        
                                        <div id="image-preview">
                                            <span class="preview-label">Vista previa:</span>
                                            <div class="preview-container">
                                                <img id="preview-image" src="#" alt="Preview"/>
                                                <button type="button" class="remove-preview" title="Quitar preview">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" 
                                            <?= (!isset($categoriaActual) || $categoriaActual['is_active']) ? 'checked' : '' ?>>
                                        <label for="is_active" class="form-check-label">Activa</label>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Crear Categoría' : 'Actualizar Categoría' ?>
                                    </button>
                                    <a href="categories_list.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Listado de categorías -->
                    <div class="actions-bar">
                        <a href="categories_list.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Categoría
                        </a>
                        <div class="search-box">
                            <input type="text" placeholder="Buscar categorías..." id="search-categories">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($categorias)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-tags"></i>
                                    <p>No hay categorías registradas</p>
                                    <a href="categories_list.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera categoría
                                    </a>
                                </div>
                            <?php else: ?>
                               <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Productos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categorias as $categoria): ?>
                <tr data-searchable="<?= htmlspecialchars(strtolower($categoria['name'])) ?>">
                    <td><?= htmlspecialchars($categoria['name']) ?></td>
                    <td><?= htmlspecialchars($categoria['slug']) ?></td>
                    <td><?= $categoria['product_count'] ?></td>
                    <td>
                        <span class="status-badge <?= $categoria['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $categoria['is_active'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="categories_list.php?action=edit&id=<?= $categoria['id'] ?>" 
                           class="btn btn-sm btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($categoria['product_count'] == 0): ?>
                            <a href="categories_list.php?action=delete&id=<?= $categoria['id'] ?>" 
                               class="btn btn-sm btn-delete js-category-delete" 
                               data-category-name="<?= htmlspecialchars($categoria['name']) ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                        <a href="categories_list.php?action=toggle-status&id=<?= $categoria['id'] ?>" 
                           class="btn btn-sm btn-status" title="<?= $categoria['is_active'] ? 'Desactivar' : 'Activar' ?>">
                            <i class="fas fa-power-off"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div id="no-results" class="empty-state" style="display: none;">
        <i class="fas fa-search-minus"></i>
        <p>No se encontraron categorías que coincidan con tu búsqueda</p>
    </div>
</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php require_once __DIR__ . '/../../alertas/confirmation_modal.php'; ?>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/components/confirmationModal.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const escapeHtml = (value = '') => value.replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char] || char));
        const confirmationAvailable = typeof window.openConfirmationModal === 'function';

        // Confirmación para eliminar categorías
        document.querySelectorAll('.js-category-delete').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const deleteUrl = this.getAttribute('href');
                const categoryName = this.dataset.categoryName || 'esta categoría';
                const message = `¿Estás seguro de eliminar la categoría <strong>${escapeHtml(categoryName)}</strong>?`;

                if (confirmationAvailable) {
                    window.openConfirmationModal({
                        title: 'Eliminar categoría',
                        message,
                        confirmText: 'Eliminar',
                        cancelText: 'Cancelar',
                        type: 'warning',
                        onConfirm: () => {
                            window.location.href = deleteUrl;
                        }
                    });
                } else if (confirm(`¿Estás seguro de eliminar la categoría ${categoryName}?`)) {
                    window.location.href = deleteUrl;
                }
            });
        });

        // Buscador de categorías
        const searchInput = document.getElementById('search-categories');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.data-table tbody tr');
                let hasResults = false;
                
                rows.forEach(row => {
                    const name = row.getAttribute('data-searchable');
                    if (name.includes(searchTerm)) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                const noResults = document.getElementById('no-results');
                if (noResults) {
                    if (!hasResults && searchTerm.length > 0) {
                        noResults.style.display = 'flex';
                    } else {
                        noResults.style.display = 'none';
                    }
                }
            });
        }

        // Generar slug automáticamente desde el nombre
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        
        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function() {
                if (!slugInput.value || slugInput.dataset.auto !== 'false') {
                    const slug = this.value.toLowerCase()
                        .replace(/[áàäâ]/g, 'a')
                        .replace(/[éèëê]/g, 'e')
                        .replace(/[íìïî]/g, 'i')
                        .replace(/[óòöô]/g, 'o')
                        .replace(/[úùüû]/g, 'u')
                        .replace(/[ñ]/g, 'n')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                }
            });
            
            // Detectar si el usuario está editando manualmente el slug
            slugInput.addEventListener('input', function() {
                this.dataset.auto = 'false';
            });
        }

        // Eliminar imagen actual
        const removeImageBtn = document.querySelector('.remove-image');
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryId = this.dataset.id;
                const handleRemoval = () => {
                    fetch(`categories_list.php?action=remove-image&id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const container = document.getElementById('current-image-container');
                                if (container) {
                                    container.remove();
                                }
                                const imageField = document.getElementById('image');
                                if (imageField) {
                                    imageField.value = '';
                                }
                                showAlert('Imagen eliminada correctamente', 'success');
                            } else {
                                showAlert('Error al eliminar la imagen', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('Error al eliminar la imagen', 'error');
                        });
                };

                if (confirmationAvailable) {
                    window.openConfirmationModal({
                        title: 'Eliminar imagen',
                        message: '¿Deseas eliminar la imagen asociada a esta categoría?',
                        confirmText: 'Eliminar',
                        cancelText: 'Cancelar',
                        type: 'warning',
                        onConfirm: handleRemoval
                    });
                } else if (confirm('¿Estás seguro de eliminar esta imagen?')) {
                    handleRemoval();
                }
            });
        }

        // Preview de imagen antes de subir - FUNCIONALIDAD MEJORADA
        const imageInput = document.getElementById('image');
        const previewContainer = document.getElementById('image-preview');
        const previewImage = document.getElementById('preview-image');
        const fileLabelText = document.getElementById('file-label-text');
        const removePreviewBtn = document.querySelector('.remove-preview');
        
        if (imageInput && previewContainer && previewImage) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Validar tipo de archivo
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        showAlert('Por favor selecciona un archivo de imagen válido (JPG, PNG, GIF)', 'error');
                        this.value = '';
                        return;
                    }
                    
                    // Validar tamaño (2MB máximo)
                    const maxSize = 2 * 1024 * 1024; // 2MB en bytes
                    if (file.size > maxSize) {
                        showAlert('La imagen es demasiado grande. El tamaño máximo es 2MB', 'error');
                        this.value = '';
                        return;
                    }
                    
                    // Crear FileReader para mostrar preview
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.style.display = 'block';
                        
                        // Actualizar texto del label
                        if (fileLabelText) {
                            fileLabelText.textContent = file.name;
                        }
                    }
                    
                    reader.onerror = function() {
                        showAlert('Error al cargar la imagen', 'error');
                        imageInput.value = '';
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    // Si no hay archivo seleccionado, ocultar preview
                    previewContainer.style.display = 'none';
                    if (fileLabelText) {
                        fileLabelText.textContent = 'Seleccionar imagen';
                    }
                }
            });
        }

        // Botón para quitar preview
        if (removePreviewBtn) {
            removePreviewBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (imageInput) {
                    imageInput.value = '';
                }
                if (previewContainer) {
                    previewContainer.style.display = 'none';
                }
                if (fileLabelText) {
                    fileLabelText.textContent = 'Seleccionar imagen';
                }
            });
        }

        // Drag and drop functionality
        const fileInputWrapper = document.querySelector('.file-input-wrapper');
        if (fileInputWrapper) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileInputWrapper.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileInputWrapper.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileInputWrapper.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                fileInputWrapper.classList.add('drag-highlight');
            }

            function unhighlight() {
                fileInputWrapper.classList.remove('drag-highlight');
            }

            fileInputWrapper.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    imageInput.files = files;
                    // Disparar evento change manualmente
                    const changeEvent = new Event('change', { bubbles: true });
                    imageInput.dispatchEvent(changeEvent);
                }
            }
        }

        // Validación del formulario antes de enviar
        const categoryForm = document.getElementById('category-form');
        if (categoryForm) {
            categoryForm.addEventListener('submit', function(e) {
                const name = document.getElementById('name').value.trim();
                const slug = document.getElementById('slug').value.trim();
                
                if (!name) {
                    e.preventDefault();
                    showAlert('El nombre de la categoría es requerido', 'error');
                    document.getElementById('name').focus();
                    return;
                }
                
                if (!slug) {
                    e.preventDefault();
                    showAlert('El slug es requerido', 'error');
                    document.getElementById('slug').focus();
                    return;
                }
                
                // Validar formato del slug
                const slugPattern = /^[a-z0-9-]+$/;
                if (!slugPattern.test(slug)) {
                    e.preventDefault();
                    showAlert('El slug solo puede contener letras minúsculas, números y guiones', 'error');
                    document.getElementById('slug').focus();
                    return;
                }
                
                // Mostrar indicador de carga
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    submitBtn.disabled = true;
                    
                    // Restaurar botón si hay error en el cliente
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    }, 10000);
                }
            });
        }

        // Auto-resize del textarea de descripción
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Función helper para mostrar alertas (si no existe)
        if (typeof showAlert === 'undefined') {
            window.showAlert = function(message, type) {
                // Crear alerta simple si la función no existe
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;
                alertDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'error' ? '#dc3545' : '#28a745'};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    z-index: 9999;
                    max-width: 400px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                `;
                alertDiv.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 10px;">&times;</button>
                    </div>
                `;
                
                document.body.appendChild(alertDiv);
                
                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 5000);
            };
        }
    });
    </script>
    
  
    </style>
</body>
</html>