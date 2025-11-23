<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Helper functions
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

// Fetch dependencies
try {
    $categories = $conn->query("SELECT id FROM categories WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    $collections = $conn->query("SELECT id FROM collections WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    $colors = $conn->query("SELECT id FROM colors WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    $sizes = $conn->query("SELECT id FROM sizes WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($categories) || empty($colors) || empty($sizes)) {
        die("Error: Missing required data (categories, colors, or sizes) in database.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Product Data (COP Prices)
$productsData = [
    [
        'name' => 'Camiseta Básica Premium',
        'description' => 'Camiseta de algodón 100% de alta calidad, perfecta para el uso diario.',
        'price' => 50000,
        'compare_price' => 75000,
        'gender' => 'unisex',
        'material' => 'Algodón',
        'care' => 'Lavar a máquina en frío',
    ],
    [
        'name' => 'Jeans Slim Fit',
        'description' => 'Pantalones vaqueros de corte ajustado, modernos y cómodos.',
        'price' => 120000,
        'compare_price' => 160000,
        'gender' => 'unisex',
        'material' => 'Denim',
        'care' => 'Lavar del revés',
    ],
    [
        'name' => 'Chaqueta Denim Vintage',
        'description' => 'Chaqueta de mezclilla con estilo retro, ideal para cualquier temporada.',
        'price' => 180000,
        'compare_price' => 220000,
        'gender' => 'unisex',
        'material' => 'Denim',
        'care' => 'Lavado en seco recomendado',
    ],
    [
        'name' => 'Sudadera con Capucha Urban',
        'description' => 'Sudadera cómoda con capucha ajustable y bolsillo canguro.',
        'price' => 95000,
        'compare_price' => 125000,
        'gender' => 'unisex',
        'material' => 'Poliéster/Algodón',
        'care' => 'Lavar a máquina',
    ],
    [
        'name' => 'Vestido Verano Floral',
        'description' => 'Vestido ligero con estampado floral, fresco y elegante.',
        'price' => 85000,
        'compare_price' => 110000,
        'gender' => 'niña',
        'material' => 'Viscosa',
        'care' => 'Lavar a mano',
    ],
    [
        'name' => 'Camisa Oxford Clásica',
        'description' => 'Camisa formal de corte clásico, ideal para oficina o eventos.',
        'price' => 70000,
        'compare_price' => 95000,
        'gender' => 'niño',
        'material' => 'Algodón',
        'care' => 'Planchar a temperatura media',
    ],
    [
        'name' => 'Pantalón Chino Beige',
        'description' => 'Pantalones tipo chino, versátiles y elegantes.',
        'price' => 115000,
        'compare_price' => 145000,
        'gender' => 'niño',
        'material' => 'Algodón/Elastano',
        'care' => 'Lavar a máquina',
    ],
    [
        'name' => 'Falda Plisada Midi',
        'description' => 'Falda de longitud media con pliegues elegantes.',
        'price' => 90000,
        'compare_price' => 120000,
        'gender' => 'niña',
        'material' => 'Poliéster',
        'care' => 'Lavar en ciclo delicado',
    ],
    [
        'name' => 'Abrigo de Lana Invierno',
        'description' => 'Abrigo cálido de lana para los días más fríos.',
        'price' => 250000,
        'compare_price' => 320000,
        'gender' => 'unisex',
        'material' => 'Lana/Sintético',
        'care' => 'Solo limpieza en seco',
    ],
    [
        'name' => 'Shorts Deportivos Active',
        'description' => 'Pantalones cortos ligeros para entrenamiento y deporte.',
        'price' => 45000,
        'compare_price' => 60000,
        'gender' => 'unisex',
        'material' => 'Poliéster técnico',
        'care' => 'Secado rápido',
    ],
    [
        'name' => 'Blusa de Seda Elegante',
        'description' => 'Blusa suave y sofisticada para ocasiones especiales.',
        'price' => 130000,
        'compare_price' => 170000,
        'gender' => 'niña',
        'material' => 'Seda sintética',
        'care' => 'Lavar a mano con cuidado',
    ],
    [
        'name' => 'Conjunto Bebé Recién Nacido',
        'description' => 'Set de ropa suave y segura para los más pequeños.',
        'price' => 65000,
        'compare_price' => 85000,
        'gender' => 'bebe',
        'material' => 'Algodón orgánico',
        'care' => 'Lavar con jabón neutro',
    ]
];

echo "Starting product insertion (COP)...\n";

foreach ($productsData as $index => $pData) {
    try {
        $conn->beginTransaction();

        $slug = generarSlugUnico($pData['name'], $conn);
        $categoryId = $categories[array_rand($categories)];
        $collectionId = !empty($collections) ? $collections[array_rand($collections)] : null;

        // 1. Insert Product
        $stmt = $conn->prepare("INSERT INTO products (
            name, slug, description, brand, gender, collection_id, material, 
            care_instructions, category_id, price, compare_price, is_featured, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $pData['name'],
            $slug,
            $pData['description'],
            'Angelow Brand',
            $pData['gender'],
            $collectionId,
            $pData['material'],
            $pData['care'],
            $categoryId,
            $pData['price'],
            $pData['compare_price'],
            ($index < 3) ? 1 : 0, // Feature first 3
            1
        ]);
        $productId = $conn->lastInsertId();

        // Assign a random image from the downloaded set (1-5)
        $randomImageNum = rand(1, 5);
        $imagePath = "uploads/productos/real_product_{$randomImageNum}.jpg";

        // 2. Insert Main Image
        $stmt = $conn->prepare("INSERT INTO product_images (
            product_id, image_path, alt_text, `order`, is_primary
        ) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $productId,
            $imagePath,
            $pData['name'],
            0,
            1
        ]);

        // 3. Insert Variants (2 Random Colors per product)
        $randomColors = array_rand(array_flip($colors), 2);
        if (!is_array($randomColors)) $randomColors = [$randomColors];

        foreach ($randomColors as $cIndex => $colorId) {
            $isDefault = ($cIndex === 0) ? 1 : 0;
            
            $stmt = $conn->prepare("INSERT INTO product_color_variants (
                product_id, color_id, is_default
            ) VALUES (?, ?, ?)");
            $stmt->execute([$productId, $colorId, $isDefault]);
            $colorVariantId = $conn->lastInsertId();

            // Variant Images
            $stmt = $conn->prepare("INSERT INTO variant_images (
                color_variant_id, product_id, image_path, alt_text, `order`, is_primary
            ) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $colorVariantId,
                $productId,
                $imagePath,
                $pData['name'] . ' - Variant',
                0,
                1
            ]);

            // 4. Insert Sizes (All available sizes)
            foreach ($sizes as $sizeId) {
                $sku = generarSKU($pData['name'], $colorId, $sizeId, $conn);
                $stmt = $conn->prepare("INSERT INTO product_size_variants (
                    color_variant_id, size_id, sku, price, compare_price, quantity, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $colorVariantId,
                    $sizeId,
                    $sku,
                    $pData['price'],
                    $pData['compare_price'],
                    rand(10, 100), // Random stock
                    1
                ]);
            }
        }

        $conn->commit();
        echo "Inserted: {$pData['name']} - Price: $" . number_format($pData['price']) . "\n";

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error inserting {$pData['name']}: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
