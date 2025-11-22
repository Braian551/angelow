<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

try {
    $stats = reviewsStats($conn);
    $distribution = ratingDistribution($conn);
    $highlights = recentReviewHighlights($conn);
    $topProducts = productsWithBestRatings($conn);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'distribution' => $distribution,
        'highlights' => $highlights,
        'top_products' => $topProducts
    ]);
} catch (Throwable $e) {
    error_log('reviews.overview error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo generar el resumen de reseñas']);
}

function reviewsStats(PDO $conn): array {
    $total = (int) scalarQuery($conn, 'SELECT COUNT(*) FROM product_reviews');
    $pending = (int) scalarQuery($conn, 'SELECT COUNT(*) FROM product_reviews WHERE is_approved = 0');
    $approved = (int) scalarQuery($conn, 'SELECT COUNT(*) FROM product_reviews WHERE is_approved = 1');
    $verified = (int) scalarQuery($conn, 'SELECT COUNT(*) FROM product_reviews WHERE is_verified = 1');
    $avg = (float) scalarQuery($conn, 'SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE is_approved = 1');

    return [
        'total_reviews' => $total,
        'pending_reviews' => $pending,
        'approved_reviews' => $approved,
        'verified_reviews' => $verified,
        'average_rating' => round($avg, 2)
    ];
}

function ratingDistribution(PDO $conn): array {
    $stmt = $conn->query('SELECT rating, COUNT(*) AS count FROM product_reviews GROUP BY rating ORDER BY rating DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    $total = array_sum($rows);
    $result = [];
    for ($rating = 5; $rating >= 1; $rating--) {
        $count = (int) ($rows[$rating] ?? 0);
        $result[] = [
            'rating' => $rating,
            'count' => $count,
            'share' => $total > 0 ? round(($count / $total) * 100, 2) : 0
        ];
    }
    return $result;
}

function recentReviewHighlights(PDO $conn): array {
    $stmt = $conn->query("SELECT pr.id, pr.rating, pr.title, pr.comment, pr.is_approved, pr.is_verified, pr.created_at, p.name AS product_name, u.name AS customer_name FROM product_reviews pr LEFT JOIN products p ON p.id = pr.product_id LEFT JOIN users u ON u.id = pr.user_id ORDER BY pr.created_at DESC LIMIT 6");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function productsWithBestRatings(PDO $conn): array {
    $stmt = $conn->query("SELECT p.id, p.name, COUNT(pr.id) AS reviews_count, COALESCE(AVG(pr.rating), 0) AS avg_rating FROM product_reviews pr INNER JOIN products p ON p.id = pr.product_id WHERE pr.is_approved = 1 GROUP BY p.id ORDER BY avg_rating DESC, reviews_count DESC LIMIT 5");
    return array_map(function ($row) {
        return [
            'product_id' => $row['id'],
            'name' => $row['name'],
            'reviews_count' => (int) $row['reviews_count'],
            'avg_rating' => round((float) $row['avg_rating'], 2)
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function scalarQuery(PDO $conn, string $sql): float {
    return (float) $conn->query($sql)->fetchColumn();
}
