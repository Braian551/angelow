<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$reviewId = isset($input['review_id']) ? intval($input['review_id']) : 0;
$title = isset($input['title']) ? trim($input['title']) : '';
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$rating = isset($input['rating']) ? intval($input['rating']) : null;

if (!$reviewId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reseña inválida']);
    exit;
}

if (strlen($title) < 3 || strlen($comment) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El título o comentario no cumplen con los requisitos mínimos']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para editar tu reseña']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT user_id FROM product_reviews WHERE id = ?');
    $stmt->execute([$reviewId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reseña no encontrada']);
        exit;
    }

    if ($row['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar esta reseña']);
        exit;
    }

    if (!is_null($rating) && ($rating < 1 || $rating > 5)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Calificación inválida']);
        exit;
    }

    // Update review
    $update = $conn->prepare('UPDATE product_reviews SET rating = ?, title = ?, comment = ? WHERE id = ?');
    $update->execute([ $rating, $title, $comment, $reviewId ]);

    echo json_encode(['success' => true, 'message' => 'Reseña actualizada']);
} catch (PDOException $e) {
    error_log('edit_review.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la reseña']);
}
