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

if (!$question_id) {
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
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta pregunta']);
        exit;
    }

    // Also delete answers linked to the question
    $delAns = $conn->prepare('DELETE FROM question_answers WHERE question_id = ?');
    $delAns->execute([$question_id]);

    $del = $conn->prepare('DELETE FROM product_questions WHERE id = ?');
    $del->execute([$question_id]);

    echo json_encode(['success' => true, 'message' => 'Pregunta eliminada']);
} catch (PDOException $e) {
    error_log('delete_question.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar pregunta']);
}
