<?php
session_start();
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../conexion.php';
require_once __DIR__ . '/../../../../auth/role_redirect.php';

header('Content-Type: application/json');
requireRole('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id requerido']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT pq.id, pq.product_id, pq.user_id, pq.question, pq.created_at, p.name AS product_name, u.name AS customer_name FROM product_questions pq
        LEFT JOIN products p ON p.id = pq.product_id
        LEFT JOIN users u ON u.id = pq.user_id WHERE pq.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$question) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pregunta no encontrada']);
        exit;
    }
    $ans = $conn->prepare('SELECT qa.id, qa.question_id, qa.user_id, qa.answer, qa.is_seller, qa.created_at, u.name as user_name FROM question_answers qa LEFT JOIN users u ON u.id = qa.user_id WHERE qa.question_id = ? ORDER BY qa.created_at ASC');
    $ans->execute([$id]);
    $answers = $ans->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'item' => $question, 'answers' => $answers]);
} catch (PDOException $e) {
    error_log('admin/questions/detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en consulta']);
}
