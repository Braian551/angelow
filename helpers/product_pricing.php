<?php

if (!function_exists('hydrateProductsPricing')) {
    /**
     * Normaliza la información de precios para listados de productos.
     * Calcula un precio a mostrar, determina el mejor precio de comparación
     * disponible (producto o variante) y adjunta un porcentaje de descuento.
     */
    function hydrateProductsPricing(\PDO $conn, array $products): array
    {
        if (empty($products)) {
            return $products;
        }

        $productIds = [];
        $missingCompareIds = [];

        foreach ($products as $product) {
            $productId = isset($product['id']) ? (int) $product['id'] : 0;
            if ($productId > 0) {
                $productIds[] = $productId;
                if (!array_key_exists('compare_price', $product)) {
                    $missingCompareIds[] = $productId;
                }
            }
        }

        $productIds = array_values(array_unique(array_filter($productIds)));
        $missingCompareIds = array_values(array_unique(array_filter($missingCompareIds)));

        if (empty($productIds)) {
            return $products;
        }

        $compareMap = [];
        if (!empty($missingCompareIds)) {
            $placeholders = implode(',', array_fill(0, count($missingCompareIds), '?'));
            $sql = "SELECT id, compare_price FROM products WHERE id IN ($placeholders)";

            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($missingCompareIds);
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $compareMap[(int) $row['id']] = $row['compare_price'] !== null
                        ? (float) $row['compare_price']
                        : null;
                }
            } catch (\PDOException $e) {
                error_log('hydrateProductsPricing (compare map): ' . $e->getMessage());
            }
        }

        $variantPricingMap = [];
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $variantSql = "
            SELECT pcv.product_id,
                   MIN(psv.price) AS min_variant_price,
                   MIN(psv.compare_price) AS min_variant_compare,
                   MAX(psv.compare_price) AS max_variant_compare
            FROM product_color_variants pcv
            JOIN product_size_variants psv ON psv.color_variant_id = pcv.id AND psv.is_active = 1
            WHERE pcv.product_id IN ($placeholders)
            GROUP BY pcv.product_id
        ";

        try {
            $stmt = $conn->prepare($variantSql);
            $stmt->execute($productIds);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $variantPricingMap[(int) $row['product_id']] = [
                    'min_variant_price' => $row['min_variant_price'] !== null ? (float) $row['min_variant_price'] : null,
                    'min_variant_compare' => $row['min_variant_compare'] !== null ? (float) $row['min_variant_compare'] : null,
                    'max_variant_compare' => $row['max_variant_compare'] !== null ? (float) $row['max_variant_compare'] : null,
                ];
            }
        } catch (\PDOException $e) {
            error_log('hydrateProductsPricing (variant map): ' . $e->getMessage());
        }

        foreach ($products as &$product) {
            $productId = isset($product['id']) ? (int) $product['id'] : 0;
            $variantData = $variantPricingMap[$productId] ?? [];

            if (!array_key_exists('compare_price', $product)) {
                $product['compare_price'] = $compareMap[$productId] ?? null;
            } elseif ($product['compare_price'] !== null) {
                $product['compare_price'] = (float) $product['compare_price'];
            }

            $displayCandidates = [
                $product['display_price'] ?? null,
                $product['min_price'] ?? null,
                $variantData['min_variant_price'] ?? null,
                $product['price'] ?? null,
                $product['max_price'] ?? null,
            ];

            $displayPrice = 0.0;
            foreach ($displayCandidates as $candidate) {
                if ($candidate === null || $candidate === '') {
                    continue;
                }
                $value = (float) $candidate;
                if ($value > 0) {
                    $displayPrice = $value;
                    break;
                }
            }

            $compareCandidates = [];
            if ($product['compare_price'] !== null) {
                $compareCandidates[] = (float) $product['compare_price'];
            }
            if (!empty($variantData['min_variant_compare'])) {
                $compareCandidates[] = (float) $variantData['min_variant_compare'];
            }
            if (!empty($variantData['max_variant_compare'])) {
                $compareCandidates[] = (float) $variantData['max_variant_compare'];
            }

            $effectiveCompare = null;
            foreach ($compareCandidates as $candidate) {
                if ($candidate > $displayPrice) {
                    $effectiveCompare = $candidate;
                    break;
                }
            }

            $product['display_price'] = $displayPrice;
            $product['compare_price'] = $effectiveCompare;
            $product['has_discount'] = $effectiveCompare !== null;

            if ($effectiveCompare !== null && $effectiveCompare > 0 && $displayPrice >= 0) {
                $discount = (int) round((($effectiveCompare - $displayPrice) / $effectiveCompare) * 100);
                $product['discount_percentage'] = max(0, $discount);
            } else {
                $product['discount_percentage'] = 0;
            }
        }
        unset($product);

        return $products;
    }
}
