<?php
ob_start();
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../alertas/alerta1.php';

// Función para redireccionar con mensaje JSON
function jsonRedirect($message, $redirectUrl)
{
    ob_clean(); // Limpiar buffer antes de enviar JSON
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $message,
        'redirect' => $redirectUrl
    ]);
    exit();
}

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Obtener categorías, tallas y colores para los select
$categoriesQuery = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
$sizesQuery = "SELECT id, name FROM sizes WHERE is_active = 1 ORDER BY 
              CASE 
                WHEN name = 'XS' THEN 1
                WHEN name = 'S' THEN 2
                WHEN name = 'M' THEN 3
                WHEN name = 'L' THEN 4
                WHEN name = 'XL' THEN 5
                WHEN name = 'XXL' THEN 6
                WHEN name = '3XL' THEN 7
                ELSE 8
              END, name";
$colorsQuery = "SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name";

$categories = $conn->query($categoriesQuery)->fetchAll(PDO::FETCH_ASSOC);
$sizes = $conn->query($sizesQuery)->fetchAll(PDO::FETCH_ASSOC);
$colors = $conn->query($colorsQuery)->fetchAll(PDO::FETCH_ASSOC);

// Obtener el ID del producto a editar
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar si el producto existe
if ($productId > 0) {
    $productQuery = "SELECT * FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        jsonRedirect('El producto no existe.', BASE_URL . '/admin/productos.php');
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); // Limpiar buffer antes de enviar JSON
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];

    try {
        $conn->beginTransaction();

        // Validar y sanitizar los datos del producto principal
        $name = htmlspecialchars(trim($_POST['name']));
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $brand = htmlspecialchars(trim($_POST['brand'] ?? ''));
        $gender = in_array($_POST['gender'], ['niño', 'niña', 'bebe', 'unisex']) ? $_POST['gender'] : 'unisex';
        $collection = htmlspecialchars(trim($_POST['collection'] ?? ''));
        $material = htmlspecialchars(trim($_POST['material'] ?? ''));
        $careInstructions = htmlspecialchars(trim($_POST['care_instructions'] ?? ''));
        $categoryId = (int)$_POST['category_id'];
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Validaciones básicas
        if (empty($name)) {
            throw new Exception("El nombre del producto es obligatorio.");
        }

        if (empty($brand)) {
            throw new Exception("La marca es obligatoria.");
        }

        if ($categoryId <= 0) {
            throw new Exception("Debe seleccionar una categoría.");
        }

        // Generar slug único
        $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = $baseSlug;
        $suffix = 0;
        
        do {
            if ($suffix > 0) {
                $slug = $baseSlug . '-' . $suffix;
            }
            
            $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $productId]);
            $exists = $stmt->fetch();

            if ($exists) {
                $suffix++;
            }
        } while ($exists);

        // Actualizar o insertar el producto principal
        if ($productId > 0) {
            // Actualizar producto existente
            $productQuery = "UPDATE products 
                            SET name = ?, slug = ?, description = ?, brand = ?, gender = ?, 
                            collection = ?, material = ?, care_instructions = ?, category_id = ?, 
                            is_featured = ?, is_active = ?, updated_at = NOW()
                            WHERE id = ?";
            $productStmt = $conn->prepare($productQuery);
            $productStmt->execute([
                $name,
                $slug,
                $description,
                $brand,
                $gender,
                $collection,
                $material,
                $careInstructions,
                $categoryId,
                $isFeatured,
                $isActive,
                $productId
            ]);
        } else {
            // Insertar nuevo producto
            $productQuery = "INSERT INTO products 
                            (name, slug, description, brand, gender, collection, 
                             material, care_instructions, category_id, is_featured, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $productStmt = $conn->prepare($productQuery);
            $productStmt->execute([
                $name,
                $slug,
                $description,
                $brand,
                $gender,
                $collection,
                $material,
                $careInstructions,
                $categoryId,
                $isFeatured,
                $isActive
            ]);
            $productId = $conn->lastInsertId();
        }

        // Procesar imágenes nuevas
        $uploadedImages = [];
        if (!empty($_FILES['images'])) {
            $uploadDir = __DIR__ . '/../uploads/productos/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['images']['name'] as $key => $filename) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;

                $tmpName = $_FILES['images']['tmp_name'][$key];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) continue;

                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $newFilename = 'product_' . $productId . '_' . uniqid() . '.' . strtolower($ext);
                $destination = $uploadDir . $newFilename;

                if (move_uploaded_file($tmpName, $destination)) {
                    $imageQuery = "INSERT INTO product_images 
                                  (product_id, image_path, is_primary, alt_text, `order`)
                                  VALUES (?, ?, ?, ?, ?)";
                    $imageStmt = $conn->prepare($imageQuery);
                    $imageStmt->execute([
                        $productId,
                        'uploads/productos/' . $newFilename,
                        0, // Todas las imágenes como no primarias inicialmente
                        htmlspecialchars(trim($_POST['image_alt'][$key] ?? '')),
                        $key
                    ]);

                    $uploadedImages[$key] = $conn->lastInsertId();
                }
            }
        }

        // Eliminar imágenes marcadas para borrar
        if (!empty($_POST['delete_images'])) {
            $deleteImages = array_map('intval', $_POST['delete_images']);
            $placeholders = implode(',', array_fill(0, count($deleteImages), '?'));

            // Obtener rutas de las imágenes a eliminar
            $getImagesQuery = "SELECT image_path FROM product_images WHERE id IN ($placeholders) AND product_id = ?";
            $getImagesStmt = $conn->prepare($getImagesQuery);
            $getImagesStmt->execute(array_merge($deleteImages, [$productId]));
            $imagesToDelete = $getImagesStmt->fetchAll(PDO::FETCH_COLUMN);

            // Eliminar de la base de datos
            $deleteQuery = "DELETE FROM product_images WHERE id IN ($placeholders) AND product_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->execute(array_merge($deleteImages, [$productId]));

            // Eliminar archivos físicos
            foreach ($imagesToDelete as $imagePath) {
                $fullPath = __DIR__ . '/../' . $imagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }

        // Actualizar orden de imágenes
        if (!empty($_POST['image_order'])) {
            foreach ($_POST['image_order'] as $order => $imageId) {
                $updateOrderQuery = "UPDATE product_images SET `order` = ? WHERE id = ? AND product_id = ?";
                $updateOrderStmt = $conn->prepare($updateOrderQuery);
                $updateOrderStmt->execute([$order, $imageId, $productId]);
            }
        }

        // Procesar variantes existentes y nuevas
        if (isset($_POST['variants'])) {
            $existingVariantIds = [];
            $hasDefaultVariant = false;

            foreach ($_POST['variants'] as $variantIndex => $variant) {
                $isDefault = isset($variant['is_default']) ? 1 : 0;

                // Manejar solo una variante por defecto
                if ($isDefault) {
                    if ($hasDefaultVariant) {
                        $isDefault = 0; // Ya hay una variante principal, marcar esta como no principal
                    } else {
                        $hasDefaultVariant = true;
                    }
                }

                if (empty($variant['size_id']) && empty($variant['color_id'])) continue;

                $sizeId = !empty($variant['size_id']) ? (int)$variant['size_id'] : null;
                $colorId = !empty($variant['color_id']) ? (int)$variant['color_id'] : null;
                $quantity = (int)$variant['quantity'];
                $price = !empty($variant['price']) ? (float)$variant['price'] : null;
                $comparePrice = !empty($variant['compare_price']) ? (float)$variant['compare_price'] : null;
                $variantActive = isset($variant['is_active']) ? 1 : 0;
                $barcode = htmlspecialchars(trim($variant['barcode'] ?? ''));
                $sku = htmlspecialchars(trim($variant['sku'] ?? ''));

                // Si es una variante existente (tiene ID)
                if (!empty($variant['id'])) {
                    $variantId = (int)$variant['id'];
                    $existingVariantIds[] = $variantId;

                    $variantQuery = "UPDATE product_variants 
                                    SET size_id = ?, color_id = ?, sku = ?, barcode = ?, 
                                    price = ?, compare_price = ?, quantity = ?, 
                                    is_active = ?, is_default = ?, updated_at = NOW()
                                    WHERE id = ? AND product_id = ?";
                    $variantStmt = $conn->prepare($variantQuery);
                    $variantStmt->execute([
                        $sizeId,
                        $colorId,
                        $sku,
                        $barcode,
                        $price,
                        $comparePrice,
                        $quantity,
                        $variantActive,
                        $isDefault,
                        $variantId,
                        $productId
                    ]);

                    // Eliminar relaciones de imágenes antiguas
                    $deleteVariantImagesQuery = "DELETE FROM variant_images WHERE variant_id = ?";
                    $deleteVariantImagesStmt = $conn->prepare($deleteVariantImagesQuery);
                    $deleteVariantImagesStmt->execute([$variantId]);
                } else {
                    // Es una nueva variante
                    // Obtener info de talla y color para SKU si no se proporcionó
                    if (empty($sku)) {
                        $sizeName = $sizeId ? $conn->query("SELECT name FROM sizes WHERE id = $sizeId")->fetchColumn() : '';
                        $colorName = $colorId ? $conn->query("SELECT name FROM colors WHERE id = $colorId")->fetchColumn() : '';

                        // Generar SKU único
                        $brandAbbr = substr(preg_replace('/[^A-Z]/', '', strtoupper($brand)), 0, 3);
                        $productAbbr = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($name)), 0, 4);
                        $sizeAbbr = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($sizeName)), 0, 3);
                        $colorAbbr = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($colorName)), 0, 3);

                        $sku = "{$brandAbbr}-{$productAbbr}-{$sizeAbbr}-{$colorAbbr}-{$productId}";
                    }

                    // Insertar la nueva variante
                    $variantQuery = "INSERT INTO product_variants 
                                    (product_id, size_id, color_id, sku, barcode, 
                                     price, compare_price, quantity, is_active, is_default)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $variantStmt = $conn->prepare($variantQuery);
                    $variantStmt->execute([
                        $productId,
                        $sizeId,
                        $colorId,
                        $sku,
                        $barcode,
                        $price,
                        $comparePrice,
                        $quantity,
                        $variantActive,
                        $isDefault
                    ]);

                    $variantId = $conn->lastInsertId();
                    $existingVariantIds[] = $variantId;
                }

                // Asociar imágenes a esta variante si se especificaron
                if (!empty($variant['images'])) {
                    foreach ($variant['images'] as $imageKey) {
                        if (isset($uploadedImages[$imageKey])) {
                            $imageId = $uploadedImages[$imageKey];
                        } else {
                            $imageId = (int)$imageKey;
                        }

                        $variantImageQuery = "INSERT INTO variant_images 
                                            (variant_id, image_id, is_primary)
                                            VALUES (?, ?, ?)";
                        $variantImageStmt = $conn->prepare($variantImageQuery);
                        $variantImageStmt->execute([
                            $variantId,
                            $imageId,
                            ($imageKey === 0) ? 1 : 0 // Primera imagen como principal
                        ]);
                    }
                }
            }

            // Eliminar variantes que no están en el formulario
            if (!empty($existingVariantIds)) {
                $placeholders = implode(',', array_fill(0, count($existingVariantIds), '?'));
                $deleteQuery = "DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ($placeholders)";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->execute(array_merge([$productId], $existingVariantIds));
            }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Producto actualizado correctamente';
        $response['redirect'] = BASE_URL . '/admin/productos.php';
        $response['productId'] = $productId;
    } catch (Exception $e) {
        $conn->rollBack();
        $response['message'] = 'Error al guardar: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Obtener información del producto si estamos editando
if ($productId > 0) {
    // Obtener imágenes del producto
    $imagesQuery = "SELECT * FROM product_images WHERE product_id = ? ORDER BY `order`";
    $imagesStmt = $conn->prepare($imagesQuery);
    $imagesStmt->execute([$productId]);
    $productImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener variantes del producto
    $variantsQuery = "SELECT pv.*, 
                     s.name as size_name, 
                     c.name as color_name, 
                     c.hex_code as color_hex
                     FROM product_variants pv
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     WHERE pv.product_id = ?";
    $variantsStmt = $conn->prepare($variantsQuery);
    $variantsStmt->execute([$productId]);
    $productVariants = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener imágenes de las variantes
    $variantImages = [];
    if (!empty($productVariants)) {
        $variantIds = array_column($productVariants, 'id');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));

        $variantImagesQuery = "SELECT vi.*, pi.image_path 
                              FROM variant_images vi
                              JOIN product_images pi ON vi.image_id = pi.id
                              WHERE vi.variant_id IN ($placeholders)";
        $variantImagesStmt = $conn->prepare($variantImagesQuery);
        $variantImagesStmt->execute($variantIds);
        $variantImagesResults = $variantImagesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($variantImagesResults as $vi) {
            $variantImages[$vi['variant_id']][] = $vi;
        }
    }
}

// Limpiar buffer antes de mostrar HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/subproducto.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <style>
        .replace-image-btn {
            margin-bottom: 10px;
            padding: 14px 16px;
            font-size: 12px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .replace-image-btn:hover {
            background-color: #e0e0e0;
        }

        .replace-image-input {
            display: none;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <a href="<?= BASE_URL ?>/admin/productos.php" class="back-link">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <?= $productId ? 'Editar Producto' : 'Agregar Nuevo Producto' ?>
                    </h1>
                </div>

                <div class="card">
                    <form id="product-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="id" value="<?= $productId ?>">

                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> Información Básica <small class="text-muted">(Campos marcados con <span class="required-badge">*</span> son obligatorios)</small></h3>

                            <div class="form-group">
                                <label for="name">Nombre del Producto <span class="required-badge">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required
                                    placeholder="Ej: Vestido de verano con flores" value="<?= htmlspecialchars($product['name'] ?? '') ?>">
                                <small class="form-text text-muted">Nombre descriptivo del producto (máx. 255 caracteres)</small>
                            </div>

                            <div class="form-group">
                                <label for="brand">Marca <span class="required-badge">*</span></label>
                                <input type="text" name="brand" id="brand" class="form-control" required
                                    placeholder="Ej: Angelow Kids, Carter's, etc." value="<?= htmlspecialchars($product['brand'] ?? '') ?>">
                                <small class="form-text text-muted">Las primeras 3 letras se usarán en el SKU</small>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción <span class="optional-badge">Opcional</span></label>
                                <textarea name="description" id="description" class="form-control" rows="3"
                                    placeholder="Describa el producto, materiales, características especiales..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-grid">
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="gender">Para <span class="required-badge">*</span></label>
                                        <select name="gender" id="gender" class="form-control" required>
                                            <option value="niño" <?= ($product['gender'] ?? '') === 'niño' ? 'selected' : '' ?>>Niño</option>
                                            <option value="niña" <?= ($product['gender'] ?? '') === 'niña' ? 'selected' : '' ?>>Niña</option>
                                            <option value="bebe" <?= ($product['gender'] ?? '') === 'bebe' ? 'selected' : '' ?>>Bebé</option>
                                            <option value="unisex" <?= empty($product['gender']) || ($product['gender'] ?? '') === 'unisex' ? 'selected' : '' ?>>Unisex</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="collection">Colección <span class="optional-badge">Opcional</span></label>
                                        <select name="collection" id="collection" class="form-control">
                                            <option value="">Seleccione una colección</option>
                                            <option value="primavera" <?= ($product['collection'] ?? '') === 'primavera' ? 'selected' : '' ?>>Primavera</option>
                                            <option value="verano" <?= ($product['collection'] ?? '') === 'verano' ? 'selected' : '' ?>>Verano</option>
                                            <option value="otoño" <?= ($product['collection'] ?? '') === 'otoño' ? 'selected' : '' ?>>Otoño</option>
                                            <option value="invierno" <?= ($product['collection'] ?? '') === 'invierno' ? 'selected' : '' ?>>Invierno</option>
                                            <option value="escolar" <?= ($product['collection'] ?? '') === 'escolar' ? 'selected' : '' ?>>Escolar</option>
                                            <option value="navidad" <?= ($product['collection'] ?? '') === 'navidad' ? 'selected' : '' ?>>Navidad</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="category_id">Categoría <span class="required-badge">*</span></label>
                                        <select name="category_id" id="category_id" class="form-control" required>
                                            <option value="">Seleccione una categoría</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="material">Materiales <span class="optional-badge">Opcional</span></label>
                                        <input type="text" name="material" id="material" class="form-control"
                                            placeholder="Ej: 100% algodón, poliéster, etc." value="<?= htmlspecialchars($product['material'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="care_instructions">Instrucciones de Cuidado <span class="optional-badge">Opcional</span></label>
                                <textarea name="care_instructions" id="care_instructions" class="form-control" rows="2"
                                    placeholder="Ej: Lavar a máquina a 30°C, no usar lejía, planchar a baja temperatura..."><?= htmlspecialchars($product['care_instructions'] ?? '') ?></textarea>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                <label for="is_featured" class="form-check-label">Destacar este producto</label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label for="is_active" class="form-check-label">Producto activo</label>
                            </div>
                        </div>

                        <!-- Imágenes del producto -->
                        <div class="form-section">
                            <h3><i class="fas fa-images"></i> Imágenes del Producto <small class="text-muted">(Agregue al menos una imagen)</small></h3>
                            <p class="section-description">Suba imágenes del producto. Podrá asignarlas a las variantes de color más adelante.</p>

                            <div class="image-upload-container" id="image-upload-container">
                                <input type="hidden" name="delete_images[]" id="delete-images-input" value="">

                                <?php if (!empty($productImages)): ?>
                                    <?php foreach ($productImages as $index => $image): ?>
                                        <div class="image-upload-item" data-image-id="<?= $image['id'] ?>">
                                            <div class="image-preview">
                                                <img src="<?= BASE_URL . '/' . $image['image_path'] ?>" alt="Preview" class="preview-image active">
                                                <button type="button" class="remove-image-btn" data-image-id="<?= $image['id'] ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="image-info">
                                                <!-- Este input es para reemplazar la imagen existente -->
                                                <input type="file" name="replace_images[<?= $image['id'] ?>]" class="replace-image-input" accept="image/*" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-secondary replace-image-btn">Reemplazar imagen</button>
                                                <input type="hidden" name="existing_images[]" value="<?= $image['id'] ?>">
                                                <input type="hidden" name="image_order[]" value="<?= $image['id'] ?>">
                                                <input type="text" name="image_alt[]" placeholder="Descripción de la imagen (para SEO)" class="form-control"
                                                    value="<?= htmlspecialchars($image['alt_text']) ?>">
                                                <small class="form-text text-muted">Ej: Vestido azul para niña talla M</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Template para nuevas imágenes (oculto) -->
                                    <div class="image-upload-item template" style="display: none;">
                                        <div class="image-preview">
                                            <img src="" alt="Preview" class="preview-image">
                                            <button type="button" class="remove-image-btn">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="image-info">
                                            <input type="file" name="images[]" class="image-file-input" accept="image/*">
                                            <input type="text" name="image_alt[]" placeholder="Descripción de la imagen (para SEO)" class="form-control">
                                            <small class="form-text text-muted">Ej: Vestido azul para niña talla M</small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="button" id="add-image-btn" class="btn btn-secondary">
                                    <i class="fas fa-plus"></i> Agregar imagen
                                </button>
                            </div>
                        </div>

                        <!-- Variantes del producto -->
                        <div class="form-section">
                            <h3><i class="fas fa-list-alt"></i> Variantes del Producto <small class="text-muted">(Agregue al menos una variante)</small></h3>
                            <p class="section-description">Agregue las diferentes combinaciones de tallas y colores disponibles para este producto.</p>

                            <div id="variants-container">
                                <?php if (!empty($productVariants)): ?>
                                    <?php foreach ($productVariants as $variantIndex => $variant): ?>
                                        <div class="variant-item" data-index="<?= $variantIndex ?>" data-variant-id="<?= $variant['id'] ?>">
                                            <button type="button" class="remove-variant">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <input type="hidden" name="variants[<?= $variantIndex ?>][id]" value="<?= $variant['id'] ?>">

                                            <div class="form-grid">
                                                <div class="form-column">
                                                    <div class="form-group">
                                                        <label>Talla <span class="required-badge">*</span></label>
                                                        <select name="variants[<?= $variantIndex ?>][size_id]" class="form-control variant-size" required>
                                                            <option value="">Seleccione una talla</option>
                                                            <?php foreach ($sizes as $size): ?>
                                                                <option value="<?= $size['id'] ?>" <?= $variant['size_id'] == $size['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($size['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="form-text text-muted">Seleccione la talla disponible</small>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Color <span class="required-badge">*</span></label>
                                                        <select name="variants[<?= $variantIndex ?>][color_id]" class="form-control variant-color" required>
                                                            <option value="">Seleccione un color</option>
                                                            <?php foreach ($colors as $color): ?>
                                                                <option value="<?= $color['id'] ?>"
                                                                    <?= $variant['color_id'] == $color['id'] ? 'selected' : '' ?>
                                                                    <?php if (!empty($color['hex_code'])): ?>
                                                                    style="background-color: <?= $color['hex_code'] ?>; color: <?= getContrastColor($color['hex_code']) ?>"
                                                                    <?php endif; ?>>
                                                                    <?= htmlspecialchars($color['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Imágenes para esta variante</label>
                                                        <div class="variant-images-grid" id="variant-images-grid-<?= $variantIndex ?>">
                                                            <?php if (!empty($productImages)): ?>
                                                                <?php foreach ($productImages as $imgIndex => $image):
                                                                    $isSelected = isset($variantImages[$variant['id']]) &&
                                                                        in_array($image['id'], array_column($variantImages[$variant['id']], 'image_id'));
                                                                ?>
                                                                    <div class="variant-image-thumb-container <?= $isSelected ? 'selected' : '' ?>"
                                                                        data-image-id="<?= $image['id'] ?>">
                                                                        <img src="<?= BASE_URL . '/' . $image['image_path'] ?>"
                                                                            alt="Imagen <?= $imgIndex + 1 ?>" class="variant-image-thumb">
                                                                        <div class="selected-check">
                                                                            <i class="fas fa-check"></i>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <p class="text-muted">Suba imágenes primero</p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <input type="hidden" name="variants[<?= $variantIndex ?>][images]" class="variant-images-input"
                                                            value="<?= isset($variantImages[$variant['id']]) ? implode(',', array_column($variantImages[$variant['id']], 'image_id')) : '' ?>">
                                                        <small class="form-text text-muted">Haga clic en las imágenes para seleccionarlas para esta variante</small>
                                                    </div>
                                                </div>

                                                <div class="form-column">
                                                    <div class="form-group">
                                                        <label>Cantidad en stock <span class="required-badge">*</span></label>
                                                        <input type="number" name="variants[<?= $variantIndex ?>][quantity]" class="form-control" min="0" value="<?= $variant['quantity'] ?>" required
                                                            placeholder="Ej: 10">
                                                        <small class="form-text text-muted">Unidades disponibles</small>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Precio <span class="required-badge">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" name="variants[<?= $variantIndex ?>][price]" class="form-control" min="0" step="0.01" required
                                                                placeholder="Ej: 59.900" value="<?= $variant['price'] ?>">
                                                        </div>
                                                        <small class="form-text text-muted">Precio actual de venta</small>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Precio regular <span class="optional-badge">Opcional</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" name="variants[<?= $variantIndex ?>][compare_price]" class="form-control" min="0" step="0.01"
                                                                placeholder="Ej: 79.900" value="<?= $variant['compare_price'] ?>">
                                                        </div>
                                                        <small class="form-text text-muted">Precio anterior (para mostrar descuentos)</small>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>SKU <span class="optional-badge">Opcional</span></label>
                                                        <input type="text" name="variants[<?= $variantIndex ?>][sku]" class="form-control variant-sku"
                                                            placeholder="Código único del producto" value="<?= htmlspecialchars($variant['sku']) ?>">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Código de barras <span class="optional-badge">Opcional</span></label>
                                                        <input type="text" name="variants[<?= $variantIndex ?>][barcode]" class="form-control"
                                                            placeholder="Código de barras" value="<?= htmlspecialchars($variant['barcode']) ?>">
                                                    </div>

                                                    <div class="form-check">
                                                        <input type="checkbox" name="variants[<?= $variantIndex ?>][is_default]" class="form-check-input" <?= $variant['is_default'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Variante principal</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input type="checkbox" name="variants[<?= $variantIndex ?>][is_active]" class="form-check-input" <?= $variant['is_active'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Disponible para venta</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Mostrar una variante vacía si no hay variantes -->
                                    <div class="variant-item" data-index="0">
                                        <button type="button" class="remove-variant">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <div class="form-grid">
                                            <div class="form-column">
                                                <div class="form-group">
                                                    <label>Talla <span class="required-badge">*</span></label>
                                                    <select name="variants[0][size_id]" class="form-control variant-size" required>
                                                        <option value="">Seleccione una talla</option>
                                                        <?php foreach ($sizes as $size): ?>
                                                            <option value="<?= $size['id'] ?>"><?= htmlspecialchars($size['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="form-text text-muted">Seleccione la talla disponible</small>
                                                </div>

                                                <div class="form-group">
                                                    <label>Color <span class="required-badge">*</span></label>
                                                    <select name="variants[0][color_id]" class="form-control variant-color" required>
                                                        <option value="">Seleccione un color</option>
                                                        <?php foreach ($colors as $color): ?>
                                                            <option value="<?= $color['id'] ?>"
                                                                <?php if (!empty($color['hex_code'])): ?>
                                                                style="background-color: <?= $color['hex_code'] ?>; color: <?= getContrastColor($color['hex_code']) ?>"
                                                                <?php endif; ?>>
                                                                <?= htmlspecialchars($color['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>Imágenes para esta variante</label>
                                                    <div class="variant-images-grid" id="variant-images-grid-0">
                                                        <!-- Las miniaturas se agregarán aquí dinámicamente -->
                                                        <p class="text-muted">Suba imágenes primero</p>
                                                    </div>
                                                    <input type="hidden" name="variants[0][images]" class="variant-images-input" value="">
                                                    <small class="form-text text-muted">Haga clic en las imágenes para seleccionarlas para esta variante</small>
                                                </div>
                                            </div>

                                            <div class="form-column">
                                                <div class="form-group">
                                                    <label>Cantidad en stock <span class="required-badge">*</span></label>
                                                    <input type="number" name="variants[0][quantity]" class="form-control" min="0" value="0" required
                                                        placeholder="Ej: 10">
                                                    <small class="form-text text-muted">Unidades disponibles</small>
                                                </div>

                                                <div class="form-group">
                                                    <label>Precio <span class="required-badge">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="variants[0][price]" class="form-control" min="0" step="0.01" required
                                                            placeholder="Ej: 59.900">
                                                    </div>
                                                    <small class="form-text text-muted">Precio actual de venta</small>
                                                </div>

                                                <div class="form-group">
                                                    <label>Precio regular <span class="optional-badge">Opcional</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="variants[0][compare_price]" class="form-control" min="0" step="0.01"
                                                            placeholder="Ej: 79.900">
                                                    </div>
                                                    <small class="form-text text-muted">Precio anterior (para mostrar descuentos)</small>
                                                </div>

                                                <div class="form-group">
                                                    <label>SKU <span class="optional-badge">Opcional</span></label>
                                                    <input type="text" name="variants[0][sku]" class="form-control variant-sku"
                                                        placeholder="Código único del producto">
                                                </div>

                                                <div class="form-group">
                                                    <label>Código de barras <span class="optional-badge">Opcional</span></label>
                                                    <input type="text" name="variants[0][barcode]" class="form-control" placeholder="Código de barras">
                                                </div>

                                                <div class="form-check">
                                                    <input type="checkbox" name="variants[0][is_default]" class="form-check-input">
                                                    <label class="form-check-label">Variante principal</label>
                                                </div>

                                                <div class="form-check">
                                                    <input type="checkbox" name="variants[0][is_active]" class="form-check-input" checked>
                                                    <label class="form-check-label">Disponible para venta</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="button" id="add-variant-btn" class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Agregar Variante
                            </button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-cancel">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Template para variantes (oculto) -->
    <div id="variant-template" style="display: none;">
        <div class="variant-item">
            <button type="button" class="remove-variant">
                <i class="fas fa-trash"></i>
            </button>

            <div class="form-grid">
                <div class="form-column">
                    <div class="form-group">
                        <label>Talla <span class="required-badge">*</span></label>
                        <select name="variants[0][size_id]" class="form-control variant-size" required>
                            <option value="">Seleccione una talla</option>
                            <?php foreach ($sizes as $size): ?>
                                <option value="<?= $size['id'] ?>"><?= htmlspecialchars($size['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Seleccione la talla disponible</small>
                    </div>

                    <div class="form-group">
                        <label>Color <span class="required-badge">*</span></label>
                        <select name="variants[0][color_id]" class="form-control variant-color" required>
                            <option value="">Seleccione un color</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?= $color['id'] ?>"
                                    <?php if (!empty($color['hex_code'])): ?>
                                    style="background-color: <?= $color['hex_code'] ?>; color: <?= getContrastColor($color['hex_code']) ?>"
                                    <?php endif; ?>>
                                    <?= htmlspecialchars($color['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Imágenes para esta variante</label>
                        <div class="variant-images-grid" id="variant-images-grid-0">
                            <!-- Las miniaturas se agregarán aquí dinámicamente -->
                        </div>
                        <input type="hidden" name="variants[0][images]" class="variant-images-input" value="">
                        <small class="form-text text-muted">Haga clic en las imágenes para seleccionarlas para esta variante</small>
                    </div>
                </div>

                <div class="form-column">
                    <div class="form-group">
                        <label>Cantidad en stock <span class="required-badge">*</span></label>
                        <input type="number" name="variants[0][quantity]" class="form-control" min="0" value="0" required
                            placeholder="Ej: 10">
                        <small class="form-text text-muted">Unidades disponibles</small>
                    </div>

                    <div class="form-group">
                        <label>Precio <span class="required-badge">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="variants[0][price]" class="form-control" min="0" step="0.01" required
                                placeholder="Ej: 59.900">
                        </div>
                        <small class="form-text text-muted">Precio actual de venta</small>
                    </div>

                    <div class="form-group">
                        <label>Precio regular <span class="optional-badge">Opcional</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="variants[0][compare_price]" class="form-control" min="0" step="0.01"
                                placeholder="Ej: 79.900">
                        </div>
                        <small class="form-text text-muted">Precio anterior (para mostrar descuentos)</small>
                    </div>

                    <div class="form-group">
                        <label>SKU <span class="optional-badge">Opcional</span></label>
                        <input type="text" name="variants[0][sku]" class="form-control variant-sku"
                            placeholder="Código único del producto">
                    </div>

                    <div class="form-group">
                        <label>Código de barras <span class="optional-badge">Opcional</span></label>
                        <input type="text" name="variants[0][barcode]" class="form-control" placeholder="Código de barras">
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="variants[0][is_default]" class="form-check-input">
                        <label class="form-check-label">Variante principal</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="variants[0][is_active]" class="form-check-input" checked>
                        <label class="form-check-label">Disponible para venta</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>

    <script>
        // Versión modificada del subproducto.js para edición
        document.addEventListener('DOMContentLoaded', function() {
            // Objeto para almacenar las selecciones de imágenes por variante
            const variantImageSelections = {};

            // --------------------------
            // 1. MÓDULO DE IMÁGENES (MODIFICADO PARA EDICIÓN)
            // --------------------------
            const imagesModule = (function() {
                const container = document.getElementById('image-upload-container');
                const deleteImagesInput = document.getElementById('delete-images-input');
                let imageCounter = <?= !empty($productImages) ? count($productImages) : 0 ?>;
                let uploadedImages = [];
                let deletedImages = [];

                // 1. Encontrar o crear el template
                let template = container.querySelector('.template');
                if (!template) {
                    // Si no hay template, crear uno basado en la estructura existente
                    template = document.createElement('div');
                    template.className = 'image-upload-item template';
                    template.style.display = 'none';
                    template.innerHTML = `
                        <div class="image-preview">
                            <img src="" alt="Preview" class="preview-image">
                            <button type="button" class="remove-image-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="image-info">
                            <input type="file" name="images[]" class="image-file-input" accept="image/*">
                            <input type="text" name="image_alt[]" placeholder="Descripción de la imagen (para SEO)" class="form-control">
                            <small class="form-text text-muted">Ej: Vestido azul para niña talla M</small>
                        </div>
                    `;
                    container.appendChild(template);
                }

                // 2. Configurar imágenes existentes
                function setupExistingImages() {
                    const existingItems = container.querySelectorAll('.image-upload-item[data-image-id]');
                    
                    existingItems.forEach(item => {
                        const imageId = item.dataset.imageId;
                        const replaceBtn = item.querySelector('.replace-image-btn');
                        const replaceInput = item.querySelector('.replace-image-input');
                        const previewImg = item.querySelector('.preview-image');
                        const removeBtn = item.querySelector('.remove-image-btn');
                        
                        // Registrar la imagen existente
                        uploadedImages.push({
                            id: parseInt(imageId),
                            element: item,
                            preview: previewImg.src,
                            alt: item.querySelector('input[name="image_alt[]"]').value
                        });

                        // Configurar evento para reemplazar imagen
                        if (replaceBtn && replaceInput) {
                            replaceBtn.addEventListener('click', () => replaceInput.click());
                            
                            replaceInput.addEventListener('change', function() {
                                if (this.files && this.files[0]) {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        previewImg.src = e.target.result;
                                        
                                        // Actualizar en el array de imágenes subidas
                                        const imgIndex = uploadedImages.findIndex(img => img.id === parseInt(imageId));
                                        if (imgIndex !== -1) {
                                            uploadedImages[imgIndex].preview = e.target.result;
                                        }
                                        
                                        updateVariantImageOptions();
                                    };
                                    reader.readAsDataURL(this.files[0]);
                                }
                            });
                        }

                        // Configurar evento para eliminar imagen
                        if (removeBtn) {
                            removeBtn.addEventListener('click', function() {
                                // Agregar a la lista de imágenes a eliminar
                                if (!deletedImages.includes(parseInt(imageId))) {
                                    deletedImages.push(parseInt(imageId));
                                    deleteImagesInput.value = deletedImages.join(',');
                                }
                                
                                // Eliminar de selecciones de variantes
                                Object.keys(variantImageSelections).forEach(variantIndex => {
                                    variantImageSelections[variantIndex] = variantImageSelections[variantIndex].filter(
                                        imgIndex => imgIndex !== parseInt(imageId)
                                    );
                                    // Llamar a la función del módulo de variantes
                                    if (variantsModule && variantsModule.updateVariantImagesInput) {
                                        variantsModule.updateVariantImagesInput(variantIndex);
                                    }
                                });
                                
                                // Eliminar del DOM
                                item.remove();
                                updateVariantImageOptions();
                            });
                        }
                    });
                }

                // 3. Función para agregar nueva imagen
                function addImage(file = null, previewUrl = null) {
                    const newImage = template.cloneNode(true);
                    newImage.style.display = 'flex';
                    newImage.dataset.index = imageCounter;
                    
                    const fileInput = newImage.querySelector('.image-file-input');
                    const previewImg = newImage.querySelector('.preview-image');
                    const removeBtn = newImage.querySelector('.remove-image-btn');
                    const altInput = newImage.querySelector('input[name="image_alt[]"]');

                    // Configurar vista previa inicial
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewImg.src = e.target.result;
                            previewImg.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else if (previewUrl) {
                        previewImg.src = previewUrl;
                        previewImg.style.display = 'block';
                    }

                    // Configurar evento para cambiar imagen
                    fileInput.addEventListener('change', function() {
                        if (this.files?.[0]) {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                previewImg.src = e.target.result;
                                previewImg.style.display = 'block';

                                // Actualizar en el array de imágenes subidas
                                if (uploadedImages[newImage.dataset.index]) {
                                    uploadedImages[newImage.dataset.index].preview = e.target.result;
                                }

                                updateVariantImageOptions();
                            };
                            reader.readAsDataURL(this.files[0]);
                        }
                    });

                    // Configurar evento para eliminar imagen
                    removeBtn.addEventListener('click', function() {
                        // Eliminar de selecciones de variantes
                        Object.keys(variantImageSelections).forEach(variantIndex => {
                            variantImageSelections[variantIndex] = variantImageSelections[variantIndex].filter(
                                imgIndex => imgIndex !== parseInt(newImage.dataset.index)
                            );
                            if (variantsModule && variantsModule.updateVariantImagesInput) {
                                variantsModule.updateVariantImagesInput(variantIndex);
                            }
                        });
                        
                        // Eliminar del array y del DOM
                        delete uploadedImages[newImage.dataset.index];
                        newImage.remove();
                        updateVariantImageOptions();
                    });

                    // Insertar en el contenedor antes del botón de agregar
                    const addBtn = document.getElementById('add-image-btn');
                    container.insertBefore(newImage, addBtn);

                    // Registrar la nueva imagen
                    uploadedImages[imageCounter] = {
                        id: null, // Será asignado por el servidor
                        element: newImage,
                        preview: previewUrl || (file ? URL.createObjectURL(file) : null),
                        alt: ''
                    };

                    imageCounter++;
                    return newImage;
                }

                // 4. Funciones auxiliares
                function getImageOptions() {
                    return uploadedImages
                        .filter(img => img !== undefined && img.preview)
                        .map((img, idx) => ({
                            id: img.id || idx,
                            text: `Imagen ${idx + 1}`,
                            preview: img.preview
                        }));
                }

                function getImagePreviews() {
                    return uploadedImages
                        .filter(img => img !== undefined && img.preview)
                        .map(img => img.preview);
                }

                function hasValidImages() {
                    return uploadedImages.some(img => img !== undefined && img.preview);
                }

                // Inicialización
                setupExistingImages();
                
                // Si no hay imágenes, agregar una inicial
                if (uploadedImages.length === 0) {
                    addImage();
                }

                // Configurar botón para agregar imágenes
                document.getElementById('add-image-btn').addEventListener('click', () => {
                    addImage();
                    updateVariantImageOptions();
                });

                // API pública del módulo
                return {
                    add: addImage,
                    getImageOptions: getImageOptions,
                    getImagePreviews: getImagePreviews,
                    hasValidImages: hasValidImages
                };
            })();

            // --------------------------
            // 2. MÓDULO DE VARIANTES (MEJORADO PARA EDICIÓN)
            // --------------------------
            const variantsModule = (function() {
                const container = document.getElementById('variants-container');
                const template = document.getElementById('variant-template');
                const addBtn = document.getElementById('add-variant-btn');
                let counter = <?= !empty($productVariants) ? count($productVariants) : 1 ?>;

                // Inicializar contador basado en variantes existentes
                const variantItems = container.querySelectorAll('.variant-item');
                if (variantItems.length > 0) {
                    const lastIndex = parseInt(variantItems[variantItems.length - 1].dataset.index);
                    counter = lastIndex + 1;
                }

                function updateImageGrids() {
                    const imageOptions = imagesModule.getImageOptions();
                    const variantItems = container.querySelectorAll('.variant-item');

                    variantItems.forEach(variant => {
                        const variantIndex = variant.dataset.index;
                        const grid = variant.querySelector('.variant-images-grid');
                        const input = variant.querySelector('.variant-images-input');

                        if (grid) {
                            variantImageSelections[variantIndex] = variantImageSelections[variantIndex] || [];
                            const currentSelection = variantImageSelections[variantIndex];
                            grid.innerHTML = '';

                            if (imageOptions.length === 0) {
                                grid.innerHTML = '<p class="text-muted">Suba imágenes primero</p>';
                                return;
                            }

                            imageOptions.forEach(opt => {
                                if (!opt.preview) return;

                                const thumbContainer = document.createElement('div');
                                thumbContainer.className = 'variant-image-thumb-container';
                                thumbContainer.title = `Imagen ${opt.id}`;
                                thumbContainer.dataset.imageId = opt.id;

                                const thumb = document.createElement('img');
                                thumb.className = 'variant-image-thumb';
                                thumb.src = opt.preview;
                                thumb.alt = `Imagen ${opt.id}`;

                                const checkIcon = document.createElement('div');
                                checkIcon.className = 'selected-check';
                                checkIcon.innerHTML = '<i class="fas fa-check"></i>';

                                thumbContainer.appendChild(thumb);
                                thumbContainer.appendChild(checkIcon);

                                if (variantImageSelections[variantIndex].includes(parseInt(opt.id))) {
                                    thumbContainer.classList.add('selected');
                                }

                                thumbContainer.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    const imageId = parseInt(this.dataset.imageId);
                                    const variantSelections = variantImageSelections[variantIndex] || [];

                                    if (this.classList.contains('selected')) {
                                        this.classList.remove('selected');
                                        variantImageSelections[variantIndex] = variantSelections.filter(
                                            id => id !== imageId
                                        );
                                    } else {
                                        this.classList.add('selected');
                                        if (!variantSelections.includes(imageId)) {
                                            variantImageSelections[variantIndex] = [...variantSelections, imageId];
                                        }
                                    }

                                    updateVariantImagesInput(variantIndex);
                                });

                                grid.appendChild(thumbContainer);
                            });
                        }
                    });
                }

                function updateVariantImagesInput(variantIndex) {
                    const variant = document.querySelector(`.variant-item[data-index="${variantIndex}"]`);
                    if (!variant) return;

                    const input = variant.querySelector('.variant-images-input');
                    const selectedImages = variantImageSelections[variantIndex] || [];
                    input.value = selectedImages.join(',');
                }

                function generateSKU(variantElement) {
                    const brandInput = document.getElementById('brand');
                    const productNameInput = document.getElementById('name');
                    const sizeSelect = variantElement.querySelector('[name*="size_id"]');
                    const colorSelect = variantElement.querySelector('[name*="color_id"]');
                    const skuInput = variantElement.querySelector('[name*="sku"]');

                    if (!brandInput || !productNameInput || !skuInput) return;

                    const brand = brandInput.value.trim().toUpperCase();
                    const productName = productNameInput.value.trim().toUpperCase();
                    const brandAbbr = brand.replace(/[^A-Z]/g, '').substring(0, 3);

                    if (brandAbbr.length < 3) {
                        console.warn('La marca necesita al menos 3 letras para generar SKU');
                        return;
                    }

                    const productAbbr = productName.replace(/[^A-Z0-9]/g, '').substring(0, 4);
                    const sizeAbbr = sizeSelect && sizeSelect.value ?
                        sizeSelect.selectedOptions[0].text.replace(/[^A-Z0-9]/g, '').substring(0, 3).toUpperCase() : 'TAL';

                    let colorAbbr = 'COL';
                    if (colorSelect && colorSelect.value) {
                        const colorName = colorSelect.selectedOptions[0].text;
                        const cleanColorName = colorName.replace(/\s+/g, '');
                        colorAbbr = cleanColorName.substring(0, 3).toUpperCase();

                        if (colorName.includes(' ')) {
                            const colorParts = colorName.split(' ');
                            if (colorParts.length >= 2) {
                                colorAbbr = (colorParts[0].substring(0, 2) + colorParts[1].substring(0, 1)).toUpperCase();
                            }
                        }
                    }

                    skuInput.value = `${brandAbbr}-${productAbbr}-${sizeAbbr}-${colorAbbr}-<?= $productId ?? 'TEMP' ?>`;
                }

                function addVariant() {
                    const newVariant = template.cloneNode(true);
                    newVariant.style.display = 'block';
                    const variantIndex = counter;
                    newVariant.dataset.index = variantIndex;
                    newVariant.innerHTML = newVariant.innerHTML.replace(/variants\[0\]/g, `variants[${variantIndex}]`);

                    const sizeSelect = newVariant.querySelector('[name*="size_id"]');
                    const colorSelect = newVariant.querySelector('[name*="color_id"]');
                    const removeBtn = newVariant.querySelector('.remove-variant');
                    const imageInput = newVariant.querySelector('.variant-images-input');
                    const isDefaultCheckbox = newVariant.querySelector('[name*="is_default"]');

                    // Inicializar selecciones para esta variante
                    variantImageSelections[variantIndex] = [];

                    // Configurar eventos para generar SKU
                    const skuGenerationHandler = () => generateSKU(newVariant);
                    sizeSelect?.addEventListener('change', skuGenerationHandler);
                    colorSelect?.addEventListener('change', skuGenerationHandler);
                    document.getElementById('brand')?.addEventListener('input', skuGenerationHandler);
                    document.getElementById('name')?.addEventListener('input', skuGenerationHandler);

                    // Manejar la selección de variante principal
                    isDefaultCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            // Desmarcar todas las otras variantes principales
                            document.querySelectorAll('[name*="is_default"]').forEach(checkbox => {
                                if (checkbox !== this) {
                                    checkbox.checked = false;
                                }
                            });
                        }
                    });

                    removeBtn.addEventListener('click', () => {
                        // Si es una variante existente, agregar su ID al campo hidden para eliminación
                        const variantId = newVariant.dataset.variantId;
                        if (variantId) {
                            const deleteInput = document.createElement('input');
                            deleteInput.type = 'hidden';
                            deleteInput.name = 'delete_variants[]';
                            deleteInput.value = variantId;
                            document.getElementById('product-form').appendChild(deleteInput);
                        }

                        delete variantImageSelections[variantIndex];
                        container.removeChild(newVariant);
                        reindexVariants();
                        updateImageGrids();
                    });

                    container.appendChild(newVariant);
                    counter++;
                    updateImageGrids();

                    const imageGrid = newVariant.querySelector('.variant-images-grid');
                    if (imageGrid) {
                        imageGrid.querySelectorAll('.variant-image-thumb-container').forEach(thumb => {
                            thumb.classList.remove('selected');
                        });
                    }

                    skuGenerationHandler();
                    return newVariant;
                }

                function reindexVariants() {
                    const variants = container.querySelectorAll('.variant-item');
                    counter = variants.length;
                    const newSelections = {};

                    variants.forEach((variant, newIndex) => {
                        const oldIndex = variant.dataset.index;
                        variant.dataset.index = newIndex;

                        if (variantImageSelections[oldIndex]) {
                            newSelections[newIndex] = variantImageSelections[oldIndex];
                        }

                        variant.querySelectorAll('input, select').forEach(input => {
                            input.name = input.name.replace(/variants\[\d+\]/, `variants[${newIndex}]`);
                        });
                    });

                    Object.keys(variantImageSelections).forEach(key => delete variantImageSelections[key]);
                    Object.assign(variantImageSelections, newSelections);

                    return variants;
                }

                function validateVariants() {
                    const variants = container.querySelectorAll('.variant-item');
                    const errors = [];

                    variants.forEach((variant, index) => {
                        const size = variant.querySelector('[name*="size_id"]')?.value;
                        const color = variant.querySelector('[name*="color_id"]')?.value;
                        const quantity = variant.querySelector('[name*="quantity"]')?.value;
                        const price = variant.querySelector('[name*="price"]')?.value;

                        if (!size) errors.push(`Variante ${index+1}: Falta talla`);
                        if (!color) errors.push(`Variante ${index+1}: Falta color`);
                        if (!quantity || isNaN(quantity)) errors.push(`Variante ${index+1}: Cantidad inválida`);
                        if (!price || isNaN(price) || price <= 0) errors.push(`Variante ${index+1}: Precio inválido`);
                    });

                    return {
                        isValid: errors.length === 0,
                        errors
                    };
                }

                // Si no hay variantes, agregar una inicial
                if (container.querySelectorAll('.variant-item').length === 0) {
                    addVariant();
                }

                // API pública del módulo
                return {
                    add: addVariant,
                    validate: validateVariants,
                    updateImageSelects: updateImageGrids,
                    updateVariantImagesInput: updateVariantImagesInput // Añadido para exponer esta función
                };
            })();

            // Función para actualizar opciones de imágenes en variantes
            function updateVariantImageOptions() {
                variantsModule.updateImageSelects();
            }

            // --------------------------
            // 3. MANEJO DEL FORMULARIO (MODIFICADO PARA EDICIÓN)
            // --------------------------
            document.getElementById('product-form').addEventListener('submit', function(e) {
                e.preventDefault();

                // Validar variantes
                const variantValidation = variantsModule.validate();
                if (!variantValidation.isValid) {
                    return showAlert('Corrija estos errores:<br>' + variantValidation.errors.join('<br>'), 'error');
                }

                // Validar marca
                const brand = document.getElementById('brand').value.trim();
                const brandLetters = brand.replace(/[^A-Za-z]/g, '');
                if (brandLetters.length < 3) {
                    return showAlert('La marca debe contener al menos 3 letras para generar SKUs válidos', 'error');
                }

                // Validar imágenes
                if (!imagesModule.hasValidImages()) {
                    return showAlert('Debe subir al menos una imagen del producto', 'error');
                }

                // Validar que cada variante tenga al menos una imagen seleccionada
                const variants = document.querySelectorAll('.variant-item');
                for (let i = 0; i < variants.length; i++) {
                    const variantIndex = variants[i].dataset.index;
                    if (!variantImageSelections[variantIndex] || variantImageSelections[variantIndex].length === 0) {
                        return showAlert(`La variante ${i+1} debe tener al menos una imagen seleccionada`, 'error');
                    }
                }

                // Validar que solo una variante sea principal
                const defaultVariants = document.querySelectorAll('[name*="is_default"]:checked');
                if (defaultVariants.length > 1) {
                    return showAlert('Solo puede haber una variante principal', 'error');
                }

                // Enviar formulario
                showAlert('Guardando producto...', 'info', {
                    showButtons: false
                });

                const formData = new FormData(this);

                // Agregar datos de variantes
                variants.forEach((variant, index) => {
                    variant.querySelectorAll('input, select').forEach(input => {
                        const name = input.name.replace(/variants\[\d+\]/, `variants[${index}]`);
                        if (input.type === 'checkbox') {
                            formData.append(name, input.checked ? '1' : '0');
                        } else if (input.type !== 'file') {
                            if (input.multiple) {
                                Array.from(input.selectedOptions).forEach(option => {
                                    formData.append(name, option.value);
                                });
                            } else {
                                // Para campos de precio, enviar el valor numérico limpio
                                if (input.name.includes('[price]') || input.name.includes('[compare_price]')) {
                                    const numericValue = input.value.replace(/[^0-9]/g, '');
                                    formData.append(name, numericValue);
                                } else {
                                    formData.append(name, input.value);
                                }
                            }
                        }
                    });
                });

              fetch(this.action, {
    method: 'POST',
    body: formData,
    headers: {
        'X-Requested-With': 'XMLHttpRequest' // Identificar como AJAX
    }
})
                    .then(response => response.json())
                    .then(data => {
                        closeAlert();
                        if (data.success) {
                            showAlert(data.message, 'success', {
                                onConfirm: () => window.location.href = data.redirect
                            });
                        } else {
                            showAlert(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        closeAlert();
                        showAlert('Error al enviar: ' + error.message, 'error');
                    });
            });

            // --------------------------
            // 4. CONFIGURACIÓN DE BOTONES
            // --------------------------
            document.getElementById('add-variant-btn').addEventListener('click', variantsModule.add);

            // --------------------------
            // 5. FORMATO VISUAL DE PRECIOS (SOLO FRONTEND)
            // --------------------------
            function formatPriceInput(input) {
                // Obtener valor actual
                let value = input.value;

                // Eliminar todos los caracteres no numéricos
                let numericValue = value.replace(/[^0-9]/g, '');

                // Guardar posición del cursor
                const cursorPosition = input.selectionStart - (value.length - numericValue.length);

                // Formatear con puntos cada 3 dígitos
                if (numericValue.length > 0) {
                    // Convertir a número para eliminar ceros a la izquierda
                    numericValue = parseInt(numericValue, 10).toString();

                    // Aplicar formato de miles
                    let formattedValue = '';
                    for (let i = numericValue.length - 1, j = 0; i >= 0; i--, j++) {
                        if (j > 0 && j % 3 === 0) {
                            formattedValue = '.' + formattedValue;
                        }
                        formattedValue = numericValue[i] + formattedValue;
                    }

                    // Actualizar el valor en el input
                    input.value = formattedValue;

                    // Restaurar posición del cursor
                    const newCursorPosition = cursorPosition + (input.value.length - value.length);
                    input.setSelectionRange(newCursorPosition, newCursorPosition);
                } else {
                    input.value = '';
                }
            }

            // Manejar el evento input para los campos de precio
            document.addEventListener('input', function(e) {
                if (e.target.matches('input[name*="[price]"], input[name*="[compare_price]"]')) {
                    formatPriceInput(e.target);
                }
            });

            // Aplicar formato inicial a los campos de precio existentes
            document.querySelectorAll('input[name*="[price]"], input[name*="[compare_price]"]').forEach(input => {
                formatPriceInput(input);
            });
        });
    </script>
</body>

</html>

<?php
// Función para determinar el color de texto contrastante (para los select de colores)
function getContrastColor($hexColor)
{
    // Convertir hex a RGB
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));

    // Calcular luminosidad
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}
?>