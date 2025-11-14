<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../alertas/alerta1.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Obtener datos para formulario
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $colors = $conn->query("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $sizes = $conn->query("SELECT id, name FROM sizes WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $collections = $conn->query("SELECT id, name FROM collections WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener datos para formulario: " . $e->getMessage());
    $categories = $colors = $sizes = $collections = [];
}

function generarSlugUnico($nombre, $conn, $productoId = null) {
    $slug = strtolower($nombre);
    $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');

    $slugBase = $slug;
    $contador = 1;

    do {
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?" . ($productoId ? " AND id != ?" : ""));
        $params = [$slug];
        if ($productoId) {
            $params[] = $productoId;
        }
        $stmt->execute($params);
        $existe = $stmt->fetch();

        if ($existe) {
            $slug = $slugBase . '-' . $contador;
            $contador++;
        }
    } while ($existe);

    return $slug;
}

function generarSKU($nombre, $color_id, $size_id, $conn) {
    $nombre_limpio = preg_replace('/[^A-Za-z0-9]/', '', $nombre);
    $iniciales = substr(strtoupper($nombre_limpio), 0, 3);

    $color_code = 'GEN';
    if ($color_id) {
        $stmt = $conn->prepare("SELECT name FROM colors WHERE id = ?");
        $stmt->execute([$color_id]);
        $color_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($color_data) {
            $color_name = preg_replace('/[^A-Za-z0-9]/', '', $color_data['name']);
            $color_code = substr(strtoupper($color_name), 0, 3);
        }
    }

    $size_code = 'GEN';
    if ($size_id) {
        $stmt = $conn->prepare("SELECT name FROM sizes WHERE id = ?");
        $stmt->execute([$size_id]);
        $size_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($size_data) {
            $size_name = preg_replace('/[^A-Za-z0-9]/', '', $size_data['name']);
            $size_code = substr(strtoupper($size_name), 0, 3);
        }
    }

    $sku = $iniciales . '-' . $color_code . '-' . $size_code . '-' . substr(uniqid(), -4);
    return $sku;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Validaciones básicas
        if (empty($_POST['name'])) {
            throw new Exception("El nombre del producto es requerido");
        }

        if (empty($_POST['category_id'])) {
            throw new Exception("La categoría es requerida");
        }

        if (empty($_POST['price'])) {
            throw new Exception("El precio base es requerido");
        }

        try {
            $price = (float) str_replace(['.', ','], ['', '.'], $_POST['price']);
            if ($price <= 0) {
                throw new Exception("El precio debe ser mayor que cero");
            }
        } catch (Exception $e) {
            throw new Exception("Formato de precio inválido. Use números con punto o coma decimal");
        }

        $compare_price = null;
        if (!empty($_POST['compare_price'])) {
            $compare_price = (float) str_replace(['.', ','], ['', '.'], $_POST['compare_price']);
            if ($compare_price <= 0) {
                throw new Exception("El precio de comparación debe ser mayor que cero");
            }
        }

        if (empty($_POST['variant_color']) || !is_array($_POST['variant_color'])) {
            throw new Exception("Debes agregar al menos una variante para el producto");
        }

        $hasDefaultVariant = false;
        foreach ($_POST['variant_color'] as $index => $color_id) {
            if (isset($_POST['variant_is_default']) && $_POST['variant_is_default'] == $index) {
                $hasDefaultVariant = true;
                break;
            }
        }
        if (!$hasDefaultVariant) {
            throw new Exception("Debes seleccionar una variante como principal");
        }

        $slug = generarSlugUnico($_POST['name'], $conn);

        // 1. Insertar el producto principal
        $stmt = $conn->prepare("INSERT INTO products (
            name, slug, description, brand, gender, collection_id, material, 
            care_instructions, compare_price, category_id, price,
            is_featured, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt->execute([
            $_POST['name'],
            $slug,
            $_POST['description'],
            $_POST['brand'],
            $_POST['gender'],
            $_POST['collection_id'] ?: null,
            $_POST['material'],
            $_POST['care_instructions'],
            $compare_price,
            $_POST['category_id'],
            $price,
            $is_featured,
            $is_active
        ]);

        $product_id = $conn->lastInsertId();

        // 2. Procesar imagen principal del producto
        if (!empty($_FILES['main_image']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/productos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . basename($_FILES['main_image']['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("INSERT INTO product_images (
                    product_id, image_path, alt_text, `order`, is_primary
                ) VALUES (?, ?, ?, ?, ?)");

                $stmt->execute([
                    $product_id,
                    'uploads/productos/' . $file_name,
                    $_POST['name'] . ' - Imagen principal',
                    0,
                    1
                ]);
            }
        }

        // 3. Procesar variantes de color
        $variant_colors = $_POST['variant_color'] ?? [];
        $variant_is_default = $_POST['variant_is_default'] ?? 0;

        foreach ($variant_colors as $index => $color_id) {
            if (empty($color_id)) continue;

            $is_default = ($variant_is_default == $index) ? 1 : 0;

            // Insertar variante de color
            $stmt = $conn->prepare("INSERT INTO product_color_variants (
                product_id, color_id, is_default
            ) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $color_id, $is_default]);
            $color_variant_id = $conn->lastInsertId();

            // Procesar imágenes para esta variante de color
            if (!empty($_FILES['variant_images']['name'][$index])) {
                foreach ($_FILES['variant_images']['tmp_name'][$index] as $key => $tmp_name) {
                    if ($_FILES['variant_images']['error'][$index][$key] !== UPLOAD_ERR_OK) continue;

                    $file_name = uniqid() . '_' . basename($_FILES['variant_images']['name'][$index][$key]);
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $conn->prepare("INSERT INTO variant_images (
                            color_variant_id, product_id, image_path, alt_text, `order`, is_primary
                        ) VALUES (?, ?, ?, ?, ?, ?)");

                        $is_primary = ($key === 0) ? 1 : 0;
                        $alt_text = $_POST['name'] . ' - Imagen ' . ($key + 1);

                        $stmt->execute([
                            $color_variant_id,
                            $product_id,
                            'uploads/productos/' . $file_name,
                            $alt_text,
                            $key,
                            $is_primary
                        ]);
                    }
                }
            }

            // Procesar tallas para esta variante de color
            $variant_prices = $_POST['variant_price'][$index] ?? [];
            $variant_quantities = $_POST['variant_quantity'][$index] ?? [];
            $variant_compare_prices = $_POST['variant_compare_price'][$index] ?? [];
            $variant_skus = $_POST['variant_sku'][$index] ?? [];
            $variant_barcodes = $_POST['variant_barcode'][$index] ?? [];
            
            // Obtener las tallas seleccionadas para esta variante
            $variant_sizes = [];
            foreach ($_POST['variant_size'] as $size_id => $size_data) {
                if (isset($size_data[$index])) {
                    $variant_sizes[$size_id] = $size_data[$index];
                }
            }

            foreach ($variant_sizes as $size_id => $size_value) {
                if (empty($size_value)) continue;

                // Generar SKU automático si no se proporcionó uno
                $sku = $variant_skus[$size_id] ?? generarSKU(
                    $_POST['name'],
                    $color_id,
                    $size_id,
                    $conn
                );

                // Validar y formatear el precio de comparación
                $variant_compare_price = null;
                if (!empty($variant_compare_prices[$size_id])) {
                    $variant_compare_price = (float) str_replace(['.', ','], ['', '.'], $variant_compare_prices[$size_id]);
                }

                $stmt = $conn->prepare("INSERT INTO product_size_variants (
                    color_variant_id, size_id, sku, barcode, price, 
                    compare_price, quantity, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $color_variant_id,
                    $size_id,
                    $sku,
                    $variant_barcodes[$size_id] ?? null,
                    (float) str_replace(['.', ','], ['', '.'], $variant_prices[$size_id] ?? 0),
                    $variant_compare_price,
                    (int) ($variant_quantities[$size_id] ?? 0),
                    1
                ]);
            }
        }

        $conn->commit();

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Producto agregado exitosamente!'];
        header("Location: " . BASE_URL . "/admin/products.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al agregar producto: " . $e->getMessage());

        $mensajeError = 'Error al guardar el producto. Por favor intenta nuevamente.';
        if ($e->getCode() == 23000) {
            $mensajeError = 'Error: Ya existe un producto con un identificador similar. Intenta con un nombre diferente.';
        }

        $_SESSION['alert'] = ['type' => 'error', 'message' => $mensajeError];
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error general al agregar producto: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

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
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Agregar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/subproducto.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
</head>
<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Producto
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> /
                        <a href="<?= BASE_URL ?>/admin/products.php">Productos</a> /
                        <span>Nuevo</span>
                    </div>
                </div>

                <form id="product-form" method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-tabs">
                        <button type="button" class="tab-btn active" data-tab="general">Información General</button>
                        <button type="button" class="tab-btn" data-tab="variants">Variantes</button>
                    </div>

                    <div class="tab-content active" id="general-tab">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Nombre del Producto *</label>
                                <input type="text" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="category_id">Categoría *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="gender">Género *</label>
                                <select id="gender" name="gender" required>
                                    <option value="unisex">Unisex</option>
                                    <option value="niño">Niño</option>
                                    <option value="niña">Niña</option>
                                    <option value="bebe">Bebé</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="brand">Marca</label>
                                <input type="text" id="brand" name="brand">
                            </div>

                            <div class="form-group">
                                <label for="collection_id">Colección</label>
                                <select id="collection_id" name="collection_id">
                                    <option value="">Seleccione una colección</option>
                                    <?php foreach ($collections as $collection): ?>
                                        <option value="<?= $collection['id'] ?>"><?= htmlspecialchars($collection['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="price">Precio Base *</label>
                                <input type="text" id="price" name="price" required class="price-input" placeholder="0">
                                <small>Este será el precio base para todas las variantes</small>
                            </div>

                            <div class="form-group">
                                <label for="compare_price">Precio de comparación</label>
                                <input type="text" id="compare_price" name="compare_price" class="price-input" min="0" step="0.01">
                                <small>El precio tachado que muestra el descuento</small>
                            </div>

                            <div class="form-group full-width">
                                <label><i class="fas fa-image"></i> Imagen principal del producto</label>
                                <div class="image-uploader">
                                    <div class="upload-area" id="main-upload-area">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Arrastra y suelta la imagen principal aquí o haz clic para seleccionar</p>
                                        <div class="file-info"></div>
                                        <input type="file" id="main_image" name="main_image" accept="image/*" class="file-input">
                                    </div>
                                    <div class="preview-container" id="main-preview-container"></div>
                                </div>
                                <small>Esta imagen se usará como principal en listados y destacados</small>
                            </div>

                            <div class="form-group full-width">
                                <label for="material">Material</label>
                                <textarea id="material" name="material" rows="3"></textarea>
                                <small>Describe los materiales principales del producto</small>
                            </div>

                            <div class="form-group full-width">
                                <label for="description">Descripción</label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="care_instructions">Instrucciones de cuidado</label>
                                <textarea id="care_instructions" name="care_instructions" rows="3"></textarea>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1">
                                <label for="is_featured">Destacar este producto</label>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label for="is_active">Producto activo</label>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="variants-tab">
                        <div class="variants-container" id="variants-container">
                            <!-- Variante por defecto -->
                            <div class="variant-card" data-variant-index="0">
                                <div class="variant-header">
                                    <h3><i class="fas fa-palette"></i> Variante #1</h3>
                                    <button type="button" class="btn btn-danger remove-variant" style="display: none;">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>

                                <div class="variant-body">
                                    <div class="variant-combination" id="combination-display-0">
                                        Seleccione color y tallas
                                    </div>

                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Color *</label>
                                            <select name="variant_color[]" class="color-select" required>
                                                <option value="">Seleccione color</option>
                                                <?php foreach ($colors as $color): ?>
                                                    <option value="<?= $color['id'] ?>" data-hex="<?= $color['hex_code'] ?? '' ?>">
                                                        <?= htmlspecialchars($color['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group full-width">
                                            <label><i class="fas fa-ruler"></i> Tallas y Stock *</label>
                                            <div class="sizes-grid" id="size-container-0">
                                                <?php foreach ($sizes as $size): ?>
                                                    <div class="size-option" data-size-id="<?= $size['id'] ?>">
                                                        <input type="hidden" name="variant_size[<?= $size['id'] ?>][0]" value="">
                                                        <input type="hidden" name="variant_price[0][<?= $size['id'] ?>]" value="">
                                                        <input type="hidden" name="variant_quantity[0][<?= $size['id'] ?>]" value="">
                                                        <input type="hidden" name="variant_compare_price[0][<?= $size['id'] ?>]" value="">
                                                        <input type="hidden" name="variant_sku[0][<?= $size['id'] ?>]" value="">
                                                        <input type="hidden" name="variant_barcode[0][<?= $size['id'] ?>]" value="">

                                                        <div class="size-label"><?= htmlspecialchars($size['name']) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>SKU Base</label>
                                            <input type="text" name="variant_sku[0]" class="sku-input" readonly>
                                            <small class="sku-generate-text">Se generará automáticamente</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Código de barras base</label>
                                            <input type="text" name="variant_barcode[0]">
                                        </div>

                                        <div class="form-group checkbox-group">
                                            <input type="radio" name="variant_is_default" value="0" id="variant_default_0" checked>
                                            <label for="variant_default_0">Hacer variante principal</label>
                                        </div>

                                        <div class="form-group full-width">
                                            <label><i class="fas fa-images"></i> Imágenes de la variante *</label>
                                            <div class="image-uploader">
                                                <div class="upload-area" id="upload-area-0">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <p>Arrastra y suelta imágenes aquí o haz clic para seleccionar</p>
                                                    <div class="file-info"></div>
                                                    <input type="file" name="variant_images[0][]" multiple accept="image/*" class="file-input" required>
                                                </div>
                                                <div class="preview-container" id="preview-container-0"></div>
                                            </div>
                                            <small>Estas imágenes se asociarán a este color para todas las tallas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="add-variant-btn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Agregar otra variante
                        </button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Producto
                        </button>
                        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php require_once __DIR__ . '/../alertas/confirmation_modal.php'; ?>
    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/components/confirmationModal.js"></script>
    <?php require_once __DIR__ . '/../js/admin/subp/subprductojs.php' ?>
</body>
</html>