<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
// Normalización local de ruta de avatar para no forzar includes que redireccionen en páginas públicas
if (!function_exists('normalizeUserImagePath')) {
    function normalizeUserImagePath($image) {
        $image = trim((string)$image);
        if ($image === '') return 'images/default-avatar.png';
        if (strpos($image, '/') !== false) return $image;
        return 'uploads/users/' . $image;
    }
}

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id requerido']);
    exit;
}

$productId = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id inválido']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT pq.id, pq.product_id, pq.user_id, pq.question, pq.created_at, pq.rating, u.name as user_name, u.image as user_image
        FROM product_questions pq
        LEFT JOIN users u ON pq.user_id = u.id
        WHERE pq.product_id = ?
        ORDER BY pq.created_at DESC
        LIMIT 50");
    $stmt->execute([$productId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach answers
    foreach ($questions as &$q) {
        $ansStmt = $conn->prepare("SELECT qa.id, qa.question_id, qa.user_id, qa.answer, qa.created_at, qa.is_seller, u.name as user_name, u.image as user_image
            FROM question_answers qa
            LEFT JOIN users u ON qa.user_id = u.id
            WHERE qa.question_id = ?
            ORDER BY qa.created_at ASC");
        $ansStmt->execute([$q['id']]);
        $q['answers'] = $ansStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Normalizar avatares de preguntas y respuestas
    foreach ($questions as &$q) {
        $q['user_image'] = normalizeUserImagePath($q['user_image'] ?? '');
        if (!empty($q['answers'])) {
            foreach ($q['answers'] as &$ans) {
                $ans['user_image'] = normalizeUserImagePath($ans['user_image'] ?? '');
            }
        }
    }

    // Also include whether the current user already asked a question for this product
    $userHasQuestion = false;
    if (isset($_SESSION['user_id'])) {
        $check = $conn->prepare('SELECT 1 FROM product_questions WHERE product_id = ? AND user_id = ? LIMIT 1');
        $check->execute([$productId, $_SESSION['user_id']]);
        $userHasQuestion = (bool)$check->fetchColumn();
    }

    echo json_encode(['success' => true, 'data' => $questions, 'user_has_question' => $userHasQuestion]);
} catch (PDOException $e) {
    error_log('Error get_questions.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en la consulta']);
}
