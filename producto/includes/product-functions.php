<?php
function getProductBySlug($conn, $slug) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, 
                   pi.image_path as primary_image,
                   c.name as category_name,
                   c.slug as category_slug,
                   col.name as collection_name,
                   col.slug as collection_slug
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN collections col ON p.collection_id = col.id
            WHERE p.slug = ? AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener producto: " . $e->getMessage());
        return false;
    }
}







function getProductVariants($conn, $productId) {
    try {
        // Get color variants
        $stmt = $conn->prepare("
            SELECT pcv.id as color_variant_id, pcv.product_id, pcv.color_id, pcv.is_default,
                   c.name as color_name, c.hex_code as color_hex
            FROM product_color_variants pcv
            LEFT JOIN colors c ON pcv.color_id = c.id
            WHERE pcv.product_id = ?
            ORDER BY pcv.is_default DESC
        ");
        $stmt->execute([$productId]);
        $colorVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get size variants for each color variant
        $variantsByColor = [];
        $availableColors = [];
        $availableSizes = [];
        
        foreach ($colorVariants as $colorVariant) {
            $colorId = $colorVariant['color_id'];
            
            $stmt = $conn->prepare("
                SELECT psv.id, psv.color_variant_id, psv.size_id, psv.sku, psv.barcode,
                       psv.price, psv.compare_price, psv.quantity, psv.is_active,
                       s.name as size_name
                FROM product_size_variants psv
                LEFT JOIN sizes s ON psv.size_id = s.id
                WHERE psv.color_variant_id = ? AND psv.is_active = 1
                ORDER BY s.id
            ");
            $stmt->execute([$colorVariant['color_variant_id']]);
            $sizeVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get images for this color variant
            $stmt = $conn->prepare("
                SELECT image_path, alt_text, is_primary 
                FROM variant_images 
                WHERE color_variant_id = ? AND product_id = ?
                ORDER BY is_primary DESC, `order` ASC
            ");
            $stmt->execute([$colorVariant['color_variant_id'], $productId]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $variantsByColor[$colorId] = [
                'color_variant_id' => $colorVariant['color_variant_id'],
                'color_id' => $colorId,
                'color_name' => $colorVariant['color_name'],
                'color_hex' => $colorVariant['color_hex'],
                'sizes' => [],
                'images' => $images
            ];
            
            $availableColors[$colorId] = $colorVariant['color_name'];
            
            foreach ($sizeVariants as $sizeVariant) {
                $variantsByColor[$colorId]['sizes'][$sizeVariant['size_id']] = [
                    'size_id' => $sizeVariant['size_id'],
                    'size_name' => $sizeVariant['size_name'],
                    'variant_id' => $sizeVariant['id'],
                    'sku' => $sizeVariant['sku'],
                    'price' => $sizeVariant['price'],
                    'compare_price' => $sizeVariant['compare_price'],
                    'quantity' => $sizeVariant['quantity']
                ];
                $availableSizes[$sizeVariant['size_id']] = $sizeVariant['size_name'];
            }
        }
        
        // Determine default variant
        $defaultColorId = null;
        $defaultSizeId = null;
        $defaultVariant = null;
        
        if (!empty($variantsByColor)) {
            // First try to find default color
            foreach ($variantsByColor as $colorId => $colorData) {
                if ($colorData['sizes']) {
                    // Find first available size
                    foreach ($colorData['sizes'] as $sizeId => $sizeData) {
                        if ($sizeData['quantity'] > 0) {
                            $defaultColorId = $colorId;
                            $defaultSizeId = $sizeId;
                            $defaultVariant = $sizeData;
                            break 2;
                        }
                    }
                }
            }
            
            // If no available variants, just pick the first one
            if (!$defaultColorId) {
                $defaultColorId = key($variantsByColor);
                $defaultSizeId = key($variantsByColor[$defaultColorId]['sizes']);
                $defaultVariant = $variantsByColor[$defaultColorId]['sizes'][$defaultSizeId];
            }
        }
        
       return [
            'variantsByColor' => $variantsByColor,
            'availableColors' => $availableColors,
            'availableSizes' => $availableSizes,
            'defaultColorId' => $defaultColorId,
            'defaultSizeId' => $defaultSizeId,
            'defaultVariant' => $defaultVariant
        ];
    } catch (PDOException $e) {
        error_log("Error al obtener variantes: " . $e->getMessage());
        return [
            'variantsByColor' => [],
            'availableColors' => [],
            'availableSizes' => [],
            'defaultColorId' => null,
            'defaultSizeId' => null,
            'defaultVariant' => null
        ];
    }
}
function getAdditionalImages($conn, $productId) {
    try {
        $stmt = $conn->prepare("
            SELECT image_path, alt_text 
            FROM product_images 
            WHERE product_id = ? AND (is_primary IS NULL OR is_primary = 0)
            ORDER BY `order` ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener imágenes adicionales: " . $e->getMessage());
        return [];
    }
}

function getProductReviews($conn, $productId, $currentUserId = null) {
    try {
         // Allow including whether the current user already marked a review as helpful
         $userVoteSelect = $currentUserId !== null ? ", (SELECT rv.is_helpful FROM review_votes rv WHERE rv.review_id = pr.id AND rv.user_id = ? LIMIT 1) as user_has_voted" : ", 0 as user_has_voted";

            $sql = "SELECT pr.*, u.name as user_name, u.image as user_image,"
                . " (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.is_helpful = 1) as helpful_count"
                . $userVoteSelect
                . " FROM product_reviews pr"
                . " LEFT JOIN users u ON pr.user_id = u.id"
                . " WHERE pr.product_id = ? AND pr.is_approved = 1"
                . " ORDER BY pr.is_verified DESC, helpful_count DESC, pr.created_at DESC"
                . " LIMIT 10";

        $stmt = $conn->prepare($sql);
        if ($currentUserId !== null) {
            $stmt->execute([$currentUserId, $productId]);
        } else {
            $stmt->execute([$productId]);
        }
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas de reseñas
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM product_reviews
            WHERE product_id = ? AND is_approved = 1
        ");
        $stmt->execute([$productId]);
        $reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar valores por defecto
        $reviewStats = $reviewStats ?: [
            'total_reviews' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ];
        
        // Calcular porcentajes para barras de rating
        $reviewStats['five_star_percent'] = $reviewStats['total_reviews'] > 0 ? round(($reviewStats['five_star'] / $reviewStats['total_reviews'] * 100)) : 0;
        $reviewStats['four_star_percent'] = $reviewStats['total_reviews'] > 0 ? round(($reviewStats['four_star'] / $reviewStats['total_reviews'] * 100)) : 0;
        $reviewStats['three_star_percent'] = $reviewStats['total_reviews'] > 0 ? round(($reviewStats['three_star'] / $reviewStats['total_reviews'] * 100)) : 0;
        $reviewStats['two_star_percent'] = $reviewStats['total_reviews'] > 0 ? round(($reviewStats['two_star'] / $reviewStats['total_reviews'] * 100)) : 0;
        $reviewStats['one_star_percent'] = $reviewStats['total_reviews'] > 0 ? round(($reviewStats['one_star'] / $reviewStats['total_reviews'] * 100)) : 0;
        
        return [
            'reviews' => $reviews,
            'stats' => $reviewStats
        ];
    } catch (PDOException $e) {
        error_log("Error al obtener reseñas: " . $e->getMessage());
        return [
            'reviews' => [],
            'stats' => [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0,
                'five_star_percent' => 0,
                'four_star_percent' => 0,
                'three_star_percent' => 0,
                'two_star_percent' => 0,
                'one_star_percent' => 0
            ]
        ];
    }
}

function getProductQuestions($conn, $productId) {
    try {
        $stmt = $conn->prepare("
            SELECT pq.*, u.name as user_name, u.image as user_image,
                   (SELECT COUNT(*) FROM question_answers qa WHERE qa.question_id = pq.id) as answer_count
            FROM product_questions pq
            LEFT JOIN users u ON pq.user_id = u.id
            WHERE pq.product_id = ?
            ORDER BY pq.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$productId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener respuestas para cada pregunta
        foreach ($questions as &$question) {
            $stmt = $conn->prepare("
                SELECT qa.*, u.name as user_name, u.image as user_image
                FROM question_answers qa
                LEFT JOIN users u ON qa.user_id = u.id
                WHERE qa.question_id = ?
                ORDER BY qa.is_seller DESC, qa.created_at ASC
            ");
            $stmt->execute([$question['id']]);
            $question['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $questions;
    } catch (PDOException $e) {
        error_log("Error al obtener preguntas: " . $e->getMessage());
        return [];
    }
}

function getRelatedProducts($conn, $productId, $categoryId) {
    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.slug, p.price, p.compare_price, pi.image_path
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
            ORDER BY p.is_featured DESC, RAND()
            LIMIT 4
        ");
        $stmt->execute([$categoryId, $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener productos relacionados: " . $e->getMessage());
        return [];
    }
}

function canUserReviewProduct($conn, $userId, $productId) {
    if (!$userId) return false;
    
    try {
        $stmt = $conn->prepare("
            SELECT o.id 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
            LIMIT 1
        ");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error al verificar compra: " . $e->getMessage());
        return false;
    }
}
?>