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
$question_id = isset($input['question_id']) ? intval($input['question_id']) : 0;
$new_text = isset($input['question']) ? trim($input['question']) : '';
// Optional: allow updating rating on question while editing
$new_rating = isset($input['rating']) ? (is_numeric($input['rating']) ? intval($input['rating']) : null) : null;

if (!$question_id || strlen($new_text) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

try {
    // Verify ownership
    $stmt = $conn->prepare('SELECT user_id FROM product_questions WHERE id = ?');
    $stmt->execute([$question_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pregunta no encontrada']);
        exit;
    }

    if ($row['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar esta pregunta']);
        exit;
    }

    if (!is_null($new_rating) && $new_rating >= 1 && $new_rating <= 5) {
        try {
            $update = $conn->prepare('UPDATE product_questions SET question = ?, rating = ? WHERE id = ?');
            $update->execute([$new_text, $new_rating, $question_id]);
        } catch (PDOException $e) {
            // Fallback if rating column is missing
            if (stripos($e->getMessage(), 'unknown column') !== false || stripos($e->getMessage(), 'column') !== false) {
                $update = $conn->prepare('UPDATE product_questions SET question = ? WHERE id = ?');
                $update->execute([$new_text, $question_id]);
            } else {
                throw $e;
            }
        }
    } else {
        $update = $conn->prepare('UPDATE product_questions SET question = ? WHERE id = ?');
        $update->execute([$new_text, $question_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Pregunta actualizada']);
} catch (PDOException $e) {
    error_log('edit_question.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar pregunta']);
}
