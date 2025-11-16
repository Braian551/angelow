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
$answer_text = isset($input['answer']) ? trim($input['answer']) : '';

if (!$question_id || !$answer_text) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Only admin can answer
$user_role = $_SESSION['user_role'] ?? '';
if ($user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden responder preguntas']);
    exit;
}

try {
    // Insert answer
    $stmt = $conn->prepare('INSERT INTO question_answers (question_id, user_id, answer, is_seller) VALUES (?, ?, ?, ?)');
    $stmt->execute([$question_id, $_SESSION['user_id'], $answer_text, 1]);

    $answerId = $conn->lastInsertId();
    // fetch user's name and image
    $userStmt = $conn->prepare('SELECT name, image FROM users WHERE id = ?');
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'message' => 'Respuesta guardada', 'data' => [ 'id' => $answerId, 'question_id' => $question_id, 'user_id' => $_SESSION['user_id'], 'user_name' => $user['name'] ?? 'Administrador', 'user_image' => $user['image'] ?? null, 'answer' => $answer_text, 'created_at' => date('Y-m-d H:i:s') ] ]);
} catch (PDOException $e) {
    error_log('submit_answer.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la respuesta']);
}
