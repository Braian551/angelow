<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) {
        $input = $json;
    }
}

$reviewId = isset($input['review_id']) ? (int) $input['review_id'] : 0;
$action = $input['action'] ?? 'approve';

if ($reviewId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reseña inválida']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT id FROM product_reviews WHERE id = ? LIMIT 1');
    $stmt->execute([$reviewId]);
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reseña no encontrada']);
        exit;
    }

    switch ($action) {
        case 'approve':
            $conn->prepare('UPDATE product_reviews SET is_approved = 1 WHERE id = ?')->execute([$reviewId]);
            break;
        case 'reject':
            $conn->prepare('UPDATE product_reviews SET is_approved = 0 WHERE id = ?')->execute([$reviewId]);
            break;
        case 'verify':
            // Backwards compatibility: accept `verify` action to mark as verified
            $conn->prepare('UPDATE product_reviews SET is_verified = 1 WHERE id = ?')->execute([$reviewId]);
            break;
        case 'unverify':
            // Backwards compatibility: accept `unverify` to clear verified flag
            $conn->prepare('UPDATE product_reviews SET is_verified = 0 WHERE id = ?')->execute([$reviewId]);
            break;
        case 'toggle_verified':
            $value = isset($input['value']) ? (int) $input['value'] : 0;
            $conn->prepare('UPDATE product_reviews SET is_verified = ? WHERE id = ?')->execute([$value ? 1 : 0, $reviewId]);
            break;
        case 'delete':
            $conn->prepare('DELETE FROM product_reviews WHERE id = ?')->execute([$reviewId]);
            echo json_encode(['success' => true, 'deleted' => true]);
            exit;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
            exit;
    }

    $updated = fetchReview($conn, $reviewId);

    echo json_encode(['success' => true, 'item' => $updated]);
} catch (Throwable $e) {
    error_log('reviews.update_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la reseña']);
}

function fetchReview(PDO $conn, int $reviewId): ?array {
    $stmt = $conn->prepare("SELECT pr.id, pr.product_id, pr.user_id, pr.rating, pr.title, pr.comment, pr.images, pr.is_verified, pr.is_approved, pr.created_at,
        p.name AS product_name, p.slug AS product_slug,
        u.name AS user_name, u.email AS user_email, u.image AS user_image
        FROM product_reviews pr
        LEFT JOIN products p ON p.id = pr.product_id
        LEFT JOIN users u ON u.id = pr.user_id
        WHERE pr.id = ? LIMIT 1");
    $stmt->execute([$reviewId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

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
            'slug' => $row['product_slug']
        ],
        'customer' => [
            'id' => $row['user_id'],
            'name' => $row['user_name'],
            'email' => $row['user_email'],
            'image' => $row['user_image']
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
