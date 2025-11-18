<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../alertas/alerta1.php';
require_once __DIR__ . '/../layouts/functions.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Obtener ID del producto a editar
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: " . BASE_URL . "/admin/products.php");
    exit();
}

// Obtener datos del producto
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception("Producto no encontrado");
    }
} catch (Exception $e) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Producto no encontrado'];
    header("Location: " . BASE_URL . "/admin/products.php");
    exit();
}

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

// Obtener variantes de color
$color_variants = [];
try {
    $stmt = $conn->prepare("SELECT pcv.*, c.name as color_name, c.hex_code FROM product_color_variants pcv JOIN colors c ON pcv.color_id = c.id WHERE pcv.product_id = ? ORDER BY pcv.id");
    $stmt->execute([$product_id]);
    $color_variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $color_variants = [];
}

// Obtener imágenes principales
$main_images = [];
try {
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY `order`");
    $stmt->execute([$product_id]);
    $main_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $main_images = [];
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

function normalizarNumeroFormulario($valor) {
    if ($valor === null) {
        return null;
    }

    if (is_numeric($valor)) {
        return (float) $valor;
    }

    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    $valor = str_replace(' ', '', $valor);

    $tieneComa = strpos($valor, ',') !== false;
    $tienePunto = strpos($valor, '.') !== false;
    $ultimaComa = strrpos($valor, ',');
    $ultimoPunto = strrpos($valor, '.');

    if ($tieneComa && $tienePunto) {
        if ($ultimaComa !== false && $ultimoPunto !== false && $ultimaComa > $ultimoPunto) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }
    } elseif ($tieneComa) {
        $decimal = substr($valor, $ultimaComa + 1);
        if ($decimal !== '' && strlen($decimal) <= 2) {
            $entero = substr($valor, 0, $ultimaComa);
            $entero = str_replace(['.', ','], '', $entero);
            $valor = ($entero === '' ? '0' : $entero) . '.' . $decimal;
        } else {
            $valor = str_replace(',', '', $valor);
        }
    } elseif ($tienePunto) {
        $decimal = substr($valor, $ultimoPunto + 1);
        if ($decimal !== '' && strlen($decimal) <= 2) {
            $entero = substr($valor, 0, $ultimoPunto);
            $entero = str_replace('.', '', $entero);
            $valor = ($entero === '' ? '0' : $entero) . '.' . $decimal;
        } else {
            $valor = str_replace('.', '', $valor);
        }
    }

    $valor = preg_replace('/[^0-9\.\-]/', '', $valor);
    if ($valor === '' || $valor === '.' || $valor === '-.' || $valor === '-') {
        return null;
    }

    return (float) $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Handle deletions
        if (!empty($_POST['delete_main_images'])) {
            foreach ($_POST['delete_main_images'] as $image_id) {
                $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
                $stmt->execute([$image_id, $product_id]);
                $img = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($img) {
                    $file_path = __DIR__ . '/../' . $img['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $conn->prepare("DELETE FROM product_images WHERE id = ?")->execute([$image_id]);
                }
            }
        }

        if (!empty($_POST['delete_variant_images'])) {
            foreach ($_POST['delete_variant_images'] as $image_id) {
                $stmt = $conn->prepare("SELECT image_path FROM variant_images WHERE id = ?");
                $stmt->execute([$image_id]);
                $img = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($img) {
                    $file_path = __DIR__ . '/../' . $img['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $conn->prepare("DELETE FROM variant_images WHERE id = ?")->execute([$image_id]);
                }
            }
        }

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

        $price = normalizarNumeroFormulario($_POST['price'] ?? null);
        if ($price === null) {
            throw new Exception("Formato de precio inválido. Use números con punto o coma decimal");
        }
        if ($price <= 0) {
            throw new Exception("El precio debe ser mayor que cero");
        }

        $compare_price = null;
        if (!empty($_POST['compare_price'])) {
            $compare_price = normalizarNumeroFormulario($_POST['compare_price']);
            if ($compare_price === null) {
                throw new Exception("Formato de precio de comparación inválido");
            }
            if ($compare_price <= 0) {
                throw new Exception("El precio de comparación debe ser mayor que cero");
            }
        }
        // Validar que el precio de comparación sea mayor que el precio base para mostrar descuento
        if ($compare_price !== null && isset($price) && $compare_price <= $price) {
            throw new Exception("El precio de comparación debe ser mayor que el precio base para que el descuento se muestre.");
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

        $slug = generarSlugUnico($_POST['name'], $conn, $product_id);

        // 1. Actualizar el producto principal
        $stmt = $conn->prepare("UPDATE products SET
            name = ?, slug = ?, description = ?, brand = ?, gender = ?, collection_id = ?, material = ?, 
            care_instructions = ?, compare_price = ?, category_id = ?, price = ?,
            is_featured = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?");

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
            $is_active,
            $product_id
        ]);

        // 2. Procesar imagen principal del producto (si se sube nueva)
        if (!empty($_FILES['main_image']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/productos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . basename($_FILES['main_image']['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $file_path)) {
                // Marcar la anterior como no primaria
                $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ? AND is_primary = 1")->execute([$product_id]);

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

        // 3. Eliminar variantes existentes y recrearlas (simplificación)
        // Antes de eliminar, obtener imágenes existentes agrupadas por color_id para poder preservarlas
        $existingVariantImagesByColor = [];
        try {
            $stmt = $conn->prepare("SELECT vi.*, pcv.color_id FROM variant_images vi JOIN product_color_variants pcv ON vi.color_variant_id = pcv.id WHERE vi.product_id = ?");
            $stmt->execute([$product_id]);
            $oldImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($oldImages as $img) {
                $existingVariantImagesByColor[$img['color_id']][] = $img;
            }
        } catch (PDOException $e) {
            $existingVariantImagesByColor = [];
        }

        // Lista de imágenes marcadas para eliminación por el usuario
        $deleteVariantImages = $_POST['delete_variant_images'] ?? [];

        // Borrar las variantes y sus tallas y luego recrearlas. Las imágenes que no fueron marcadas
        // para eliminación se reinsertarán más abajo en el loop para preservar fotos existentes.
        $conn->prepare("DELETE FROM product_size_variants WHERE color_variant_id IN (SELECT id FROM product_color_variants WHERE product_id = ?)")->execute([$product_id]);
        $conn->prepare("DELETE FROM variant_images WHERE product_id = ?")->execute([$product_id]);
        $conn->prepare("DELETE FROM product_color_variants WHERE product_id = ?")->execute([$product_id]);

        // Recrear variantes
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
            // Si existen imágenes antiguas para este color y el usuario no las marcó para eliminar,
            // las reinsertamos para preservar las fotos al actualizar.
            if (!empty($existingVariantImagesByColor[$color_id])) {
                foreach ($existingVariantImagesByColor[$color_id] as $oldImg) {
                    if (in_array($oldImg['id'], $deleteVariantImages)) continue;

                    // Reinsertar imagen existente con el nuevo color_variant_id
                    $stmt = $conn->prepare("INSERT INTO variant_images (
                        color_variant_id, product_id, image_path, alt_text, `order`, is_primary
                    ) VALUES (?, ?, ?, ?, ?, ?)");

                    $stmt->execute([
                        $color_variant_id,
                        $product_id,
                        $oldImg['image_path'],
                        $oldImg['alt_text'] ?? '',
                        $oldImg['order'] ?? 0,
                        $oldImg['is_primary'] ?? 0
                    ]);
                }
            }

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
                if (isset($variant_compare_prices[$size_id]) && $variant_compare_prices[$size_id] !== '') {
                    $variant_compare_price = normalizarNumeroFormulario($variant_compare_prices[$size_id]);
                    if ($variant_compare_price === null) {
                        throw new Exception("Formato de precio de comparación inválido para la talla $size_id (color: $color_id).");
                    }
                }
                // Validar que compare_price de variante sea mayor que su precio para que se muestre
                $variant_price_value = normalizarNumeroFormulario($variant_prices[$size_id] ?? null);
                if ($variant_price_value === null || $variant_price_value <= 0) {
                    throw new Exception("Debes especificar un precio válido para la talla $size_id (color: $color_id).");
                }
                if ($variant_compare_price !== null && $variant_compare_price <= $variant_price_value) {
                    throw new Exception("El precio de comparación de la talla debe ser mayor que su precio para que el descuento se muestre (color: $color_id, talla: $size_id).");
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
                    $variant_price_value,
                    $variant_compare_price,
                    (int) ($variant_quantities[$size_id] ?? 0),
                    1
                ]);
            }
        }

        // Preparar notificación si el precio de comparación fue añadido o modificado
        $oldComparePrice = $product['compare_price'] ?? null;
        $newComparePrice = $compare_price;

        $conn->commit();

        if ($newComparePrice !== null && ($oldComparePrice === null || $oldComparePrice != $newComparePrice)) {
            // Notificar a usuarios interesados
            $productName = $_POST['name'];
            $displayPrice = number_format($price, 0, ',', '.');
            $displayCompare = number_format($newComparePrice, 0, ',', '.');
            $discountPercent = (int) round((($newComparePrice - $price) / max($newComparePrice, 1)) * 100);
            $title = "Oferta: {$productName} - {$discountPercent}% OFF";
            $message = "¡{$productName} ahora por $" . $displayPrice . " (antes $" . $displayCompare . ") — {$discountPercent}% de descuento!";

            notifyUsersOfProductPromotion($conn, $product_id, $title, $message);
        }

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Producto actualizado exitosamente!'];
        header("Location: " . BASE_URL . "/admin/products.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al actualizar producto: " . $e->getMessage());

        $mensajeError = 'Error al guardar el producto. Por favor intenta nuevamente.';
        if ($e->getCode() == 23000) {
            $mensajeError = 'Error: Ya existe un producto con un identificador similar. Intenta con un nombre diferente.';
        }

        $_SESSION['alert'] = ['type' => 'error', 'message' => $mensajeError];
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error general al actualizar producto: " . $e->getMessage());
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
    <title>Editar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/orders/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/subproducto.css">
</head>
<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-edit"></i> Editar Producto
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> /
                        <a href="<?= BASE_URL ?>/admin/products.php">Productos</a> /
                        <span>Editar</span>
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
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="category_id">Categoría *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="gender">Género *</label>
                                <select id="gender" name="gender" required>
                                    <option value="unisex" <?= $product['gender'] == 'unisex' ? 'selected' : '' ?>>Unisex</option>
                                    <option value="niño" <?= $product['gender'] == 'niño' ? 'selected' : '' ?>>Niño</option>
                                    <option value="niña" <?= $product['gender'] == 'niña' ? 'selected' : '' ?>>Niña</option>
                                    <option value="bebe" <?= $product['gender'] == 'bebe' ? 'selected' : '' ?>>Bebé</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="brand">Marca</label>
                                <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="collection_id">Colección</label>
                                <select id="collection_id" name="collection_id">
                                    <option value="">Seleccione una colección</option>
                                    <?php foreach ($collections as $collection): ?>
                                        <option value="<?= $collection['id'] ?>" <?= $collection['id'] == $product['collection_id'] ? 'selected' : '' ?>><?= htmlspecialchars($collection['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="price">Precio Base *</label>
                                <input type="text" id="price" name="price" value="<?= number_format($product['price'], 2, '.', '') ?>" required class="price-input" placeholder="0">
                                <small>Este será el precio base para todas las variantes</small>
                            </div>

                            <div class="form-group">
                                <label for="compare_price">Precio de comparación</label>
                                <input type="text" id="compare_price" name="compare_price" value="<?= $product['compare_price'] ? number_format($product['compare_price'], 2, '.', '') : '' ?>" class="price-input" min="0" step="0.01">
                                <small>El precio tachado que muestra el descuento. Debe ser mayor que el precio base. Formato: miles con puntos, decimales con coma o punto (ej: 2.500.000 o 12.499,99).</small>
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
                                    <div class="preview-container" id="main-preview-container">
                                        <?php if (!empty($main_images)): ?>
                                            <?php foreach ($main_images as $image): ?>
                                                <div class="image-preview existing-image" data-image-id="<?= $image['id'] ?>">
                                                    <img src="<?= BASE_URL ?>/<?= $image['image_path'] ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                                                    <button type="button" class="remove-image" title="Eliminar imagen">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small>Esta imagen se usará como principal en listados y destacados</small>
                            </div>

                            <div class="form-group full-width">
                                <label for="material">Material</label>
                                <textarea id="material" name="material" rows="3"><?= htmlspecialchars($product['material'] ?? '') ?></textarea>
                                <small>Describe los materiales principales del producto</small>
                            </div>

                            <div class="form-group full-width">
                                <label for="description">Descripción</label>
                                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="care_instructions">Instrucciones de cuidado</label>
                                <textarea id="care_instructions" name="care_instructions" rows="3"><?= htmlspecialchars($product['care_instructions'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                                <label for="is_featured">Destacar este producto</label>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>>
                                <label for="is_active">Producto activo</label>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="variants-tab">
                        <div class="variants-container" id="variants-container">
                            <?php foreach ($color_variants as $index => $variant): ?>
                                <div class="variant-card" data-variant-index="<?= $index ?>">
                                    <div class="variant-header">
                                        <h3><i class="fas fa-palette"></i> Variante #<?= $index + 1 ?></h3>
                                        <button type="button" class="btn btn-danger remove-variant" style="display: <?= count($color_variants) > 1 ? 'block' : 'none' ?>;">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>

                                    <div class="variant-body">
                                        <div class="variant-combination" id="combination-display-<?= $index ?>">
                                            Color: <?= htmlspecialchars($variant['color_name']) ?>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Color *</label>
                                                <select name="variant_color[]" class="color-select" required>
                                                    <option value="">Seleccione color</option>
                                                    <?php foreach ($colors as $color): ?>
                                                        <option value="<?= $color['id'] ?>" data-hex="<?= $color['hex_code'] ?? '' ?>" <?= $color['id'] == $variant['color_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($color['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group full-width">
                                                <label><i class="fas fa-ruler"></i> Tallas y Stock *</label>
                                                <div class="sizes-grid" id="size-container-<?= $index ?>">
                                                    <?php 
                                                    $size_variants = [];
                                                    try {
                                                        $stmt = $conn->prepare("SELECT * FROM product_size_variants WHERE color_variant_id = ?");
                                                        $stmt->execute([$variant['id']]);
                                                        $size_variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    } catch (PDOException $e) {
                                                        $size_variants = [];
                                                    }
                                                    $size_data = [];
                                                    foreach ($size_variants as $sv) {
                                                        $size_data[$sv['size_id']] = $sv;
                                                    }
                                                    ?>
                                                    <?php foreach ($sizes as $size): ?>
                                                        <div class="size-option <?= isset($size_data[$size['id']]) ? 'selected' : '' ?>" data-size-id="<?= $size['id'] ?>">
                                                            <input type="hidden" name="variant_size[<?= $size['id'] ?>][<?= $index ?>]" value="<?= isset($size_data[$size['id']]) ? $size['id'] : '' ?>">
                                                            <input type="hidden" name="variant_price[<?= $index ?>][<?= $size['id'] ?>]" value="<?= isset($size_data[$size['id']]) ? $size_data[$size['id']]['price'] : '' ?>">
                                                            <input type="hidden" name="variant_quantity[<?= $index ?>][<?= $size['id'] ?>]" value="<?= isset($size_data[$size['id']]) ? $size_data[$size['id']]['quantity'] : '' ?>">
                                                            <input type="hidden" name="variant_compare_price[<?= $index ?>][<?= $size['id'] ?>]" value="<?= isset($size_data[$size['id']]) ? $size_data[$size['id']]['compare_price'] : '' ?>">
                                                            <input type="hidden" name="variant_sku[<?= $index ?>][<?= $size['id'] ?>]" value="<?= isset($size_data[$size['id']]) ? $size_data[$size['id']]['sku'] : '' ?>">
                                                            <input type="hidden" name="variant_barcode[<?= $index ?>][<?= $size['id'] ?>]" value="<?= isset($size_data[$size['id']]) ? $size_data[$size['id']]['barcode'] : '' ?>">

                                                            <div class="size-label"><?= htmlspecialchars($size['name']) ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>SKU Base</label>
                                                <input type="text" name="variant_sku[<?= $index ?>]" class="sku-input" readonly value="<?= htmlspecialchars($size_variants[0]['sku'] ?? '') ?>">
                                                <small class="sku-generate-text">Se generará automáticamente</small>
                                            </div>

                                            <div class="form-group">
                                                <label>Código de barras base</label>
                                                <input type="text" name="variant_barcode[<?= $index ?>]" value="<?= htmlspecialchars($size_variants[0]['barcode'] ?? '') ?>">
                                            </div>

                                            <div class="form-group checkbox-group">
                                                <?php $defaultRadioId = 'variant_default_' . $index; ?>
                                                <input type="radio" name="variant_is_default" value="<?= $index ?>" id="<?= $defaultRadioId ?>" <?= $variant['is_default'] ? 'checked' : '' ?>>
                                                <label for="<?= $defaultRadioId ?>">Hacer variante principal</label>
                                            </div>

                                            <div class="form-group full-width">
                                                <label><i class="fas fa-images"></i> Imágenes de la variante *</label>
                                                <div class="image-uploader">
                                                    <div class="upload-area" id="upload-area-<?= $index ?>">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <p>Arrastra y suelta imágenes aquí o haz clic para seleccionar</p>
                                                        <div class="file-info"></div>
                                                        <input type="file" name="variant_images[<?= $index ?>][]" multiple accept="image/*" class="file-input">
                                                    </div>
                                                    <div class="preview-container" id="preview-container-<?= $index ?>">
                                                        <?php 
                                                        $variant_images = [];
                                                        try {
                                                            $stmt = $conn->prepare("SELECT * FROM variant_images WHERE color_variant_id = ? ORDER BY `order`");
                                                            $stmt->execute([$variant['id']]);
                                                            $variant_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        } catch (PDOException $e) {
                                                            $variant_images = [];
                                                        }
                                                        ?>
                                                        <?php foreach ($variant_images as $image): ?>
                                                            <div class="image-preview existing-image" data-image-id="<?= $image['id'] ?>">
                                                                <img src="<?= BASE_URL ?>/<?= $image['image_path'] ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                                                                <button type="button" class="remove-image" title="Eliminar imagen">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <small>Estas imágenes se asociarán a este color para todas las tallas</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" id="add-variant-btn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Agregar otra variante
                        </button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Producto
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
