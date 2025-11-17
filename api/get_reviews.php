<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../producto/includes/product-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

try {
    $reviewsData = getProductReviews($conn, $productId);

    if (!isset($reviewsData['reviews'], $reviewsData['stats'])) {
        $reviewsData = [
            'reviews' => [],
            'stats' => [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star_percent' => 0,
                'four_star_percent' => 0,
                'three_star_percent' => 0,
                'two_star_percent' => 0,
                'one_star_percent' => 0,
            ],
        ];
    }

    foreach ($reviewsData['reviews'] as &$review) {
        $review['user_name'] = $review['user_name'] ?: 'Usuario';
        $review['user_image'] = $review['user_image'] ?: 'images/default-avatar.png';
        $review['images'] = !empty($review['images']) ? (json_decode($review['images'], true) ?: []) : [];
        $review['display_date'] = !empty($review['created_at']) ? date('d/m/Y H:i', strtotime($review['created_at'])) : '';
        $review['helpful_count'] = isset($review['helpful_count']) ? (int)$review['helpful_count'] : 0;
    }
    unset($review);

    echo json_encode([
        'success' => true,
        'data' => $reviewsData,
    ]);
} catch (Throwable $th) {
    error_log('get_reviews error: ' . $th->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudieron obtener las opiniones']);
}
