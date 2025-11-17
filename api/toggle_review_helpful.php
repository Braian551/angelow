<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$reviewId = isset($input['review_id']) ? intval($input['review_id']) : 0;

if (!$reviewId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Id de reseña inválido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Verificar que la reseña exista y obtener autor
    $stmt = $conn->prepare('SELECT id, user_id FROM product_reviews WHERE id = ? LIMIT 1');
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reseña no encontrada']);
        exit;
    }

    // El autor no puede votar su propia reseña
    if ($review['user_id'] == $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No puedes marcar tu propia reseña como útil']);
        exit;
    }

    // Check if the user already voted
    $stmt = $conn->prepare('SELECT id, is_helpful FROM review_votes WHERE review_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$reviewId, $userId]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vote) {
        if ((int)$vote['is_helpful'] === 1) {
            // Toggle off: remove vote
            $del = $conn->prepare('DELETE FROM review_votes WHERE id = ?');
            $del->execute([$vote['id']]);
            $userHasVoted = 0;
        } else {
            // Update to helpful
            $u = $conn->prepare('UPDATE review_votes SET is_helpful = 1 WHERE id = ?');
            $u->execute([$vote['id']]);
            $userHasVoted = 1;
        }
    } else {
        // Insert new helpful vote
        $ins = $conn->prepare('INSERT INTO review_votes (review_id, user_id, is_helpful, created_at) VALUES (?, ?, 1, NOW())');
        $ins->execute([$reviewId, $userId]);
        $userHasVoted = 1;
    }

    // Compute helpful_count
    $stmt = $conn->prepare('SELECT COUNT(*) as c FROM review_votes WHERE review_id = ? AND is_helpful = 1');
    $stmt->execute([$reviewId]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'data' => ['helpful_count' => $count, 'user_has_voted' => (int)$userHasVoted]]);

} catch (PDOException $e) {
    error_log('toggle_review_helpful error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar el voto']);
}
