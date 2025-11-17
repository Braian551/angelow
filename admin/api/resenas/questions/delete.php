<?php
session_start();
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../conexion.php';
require_once __DIR__ . '/../../../../auth/role_redirect.php';

header('Content-Type: application/json');
requireRole('admin');

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$questionId = isset($input['question_id']) ? intval($input['question_id']) : 0;
if (!$questionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'question_id requerido']);
    exit;
}

try {
    // delete answers
    $delA = $conn->prepare('DELETE FROM question_answers WHERE question_id = ?');
    $delA->execute([$questionId]);
    // delete question
    $delQ = $conn->prepare('DELETE FROM product_questions WHERE id = ?');
    $delQ->execute([$questionId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('admin/questions/delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la pregunta']);
}
