<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 12);
$perPage = max(5, min(50, $perPage));
$offset = ($page - 1) * $perPage;
$status = $_GET['status'] ?? 'pending';
$rating = (isset($_GET['rating']) && $_GET['rating'] !== '') ? (int) $_GET['rating'] : null;
$verified = (isset($_GET['verified']) && $_GET['verified'] !== '') ? (int) $_GET['verified'] : null;
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : null;
$sort = $_GET['sort'] ?? 'recent';
$search = trim($_GET['search'] ?? '');

try {
    $conditions = ['1=1'];
    $params = [];

    switch ($status) {
        case 'approved':
            $conditions[] = 'pr.is_approved = 1';
            break;
        case 'all':
            break;
        default:
            $conditions[] = 'pr.is_approved = 0';
            break;
    }

    if ($rating && $rating >= 1 && $rating <= 5) {
        $conditions[] = 'pr.rating = :rating';
        $params['rating'] = $rating;
    }

    if ($verified !== null) {
        $conditions[] = 'pr.is_verified = :verified';
        $params['verified'] = $verified ? 1 : 0;
    }

    if ($productId) {
        $conditions[] = 'pr.product_id = :product_id';
        $params['product_id'] = $productId;
    }

    if ($search !== '') {
        $conditions[] = '(pr.title LIKE :search OR pr.comment LIKE :search OR p.name LIKE :search OR u.name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $base = " FROM product_reviews pr
        LEFT JOIN users u ON u.id = pr.user_id
        LEFT JOIN products p ON p.id = pr.product_id
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        LEFT JOIN (
            SELECT review_id, SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) AS helpful_votes,
                   SUM(CASE WHEN is_helpful = 0 THEN 1 ELSE 0 END) AS not_helpful
            FROM review_votes
            GROUP BY review_id
        ) votes ON votes.review_id = pr.id
        WHERE " . implode(' AND ', $conditions);

    $countSql = 'SELECT COUNT(*)' . $base;
    $countStmt = $conn->prepare($countSql);
    bindParams($countStmt, $params);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $orderMap = [
        'rating_high' => 'pr.rating DESC, pr.created_at DESC',
        'rating_low' => 'pr.rating ASC, pr.created_at DESC',
        'helpful' => 'COALESCE(votes.helpful_votes, 0) DESC, pr.created_at DESC',
        'recent' => 'pr.created_at DESC'
    ];
    $orderSql = ' ORDER BY ' . ($orderMap[$sort] ?? $orderMap['recent']);

    $dataSql = 'SELECT pr.id, pr.product_id, pr.user_id, pr.rating, pr.title, pr.comment, pr.images, pr.is_verified, pr.is_approved, pr.created_at,
        p.name AS product_name, p.slug AS product_slug,
        COALESCE(pi.image_path, "uploads/productos/default-product.jpg") AS product_image,
        u.name AS user_name, u.email AS user_email, u.image AS user_image,
        COALESCE(votes.helpful_votes, 0) AS helpful_votes, COALESCE(votes.not_helpful, 0) AS not_helpful'
        . $base . $orderSql . ' LIMIT :limit OFFSET :offset';

    $stmt = $conn->prepare($dataSql);
    bindParams($stmt, $params);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reviews = array_map('formatReview', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

    echo json_encode([
        'success' => true,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => $total > 0 ? ceil($total / $perPage) : 1
        ],
        'items' => $reviews
    ]);
} catch (Throwable $e) {
    error_log('reviews.list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener la bandeja de reseñas']);
}

function bindParams(PDOStatement $stmt, array $params): void {
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
}

function formatReview(array $row): array {
    return [
        'id' => (int) $row['id'],
        'rating' => (int) $row['rating'],
        'title' => $row['title'],
        'comment' => $row['comment'],
        'images' => decodeImages($row['images']),
        'is_verified' => (bool) $row['is_verified'],
        'is_approved' => (bool) $row['is_approved'],
        'created_at' => $row['created_at'],
        'product' => [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'slug' => $row['product_slug'],
            'image' => $row['product_image'],
            'main_image' => $row['product_image']
        ],
        'customer' => [
            'id' => $row['user_id'],
            'name' => $row['user_name'],
            'email' => $row['user_email'],
            'image' => $row['user_image']
        ],
        'votes' => [
            'helpful' => (int) $row['helpful_votes'],
            'not_helpful' => (int) $row['not_helpful']
        ]
    ];
}

function decodeImages(?string $images): array {
    if (!$images) {
        return [];
    }
    $data = json_decode($images, true);
    return is_array($data) ? $data : [];
}
