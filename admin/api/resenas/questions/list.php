<?php
session_start();
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../conexion.php';
require_once __DIR__ . '/../../../../auth/role_redirect.php';

header('Content-Type: application/json');
requireRole('admin');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 12);
$perPage = max(5, min(50, $perPage));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$answered = isset($_GET['answered']) ? (int) $_GET['answered'] : null; // 1=answered, 0=unanswered

try {
    $conditions = ['1=1'];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(pq.question LIKE :search OR p.name LIKE :search OR u.name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $baseFrom = " FROM product_questions pq
        LEFT JOIN products p ON p.id = pq.product_id
        LEFT JOIN users u ON u.id = pq.user_id
        LEFT JOIN (
            SELECT question_id, COUNT(*) AS cnt FROM question_answers GROUP BY question_id
        ) qa ON qa.question_id = pq.id
        WHERE " . implode(' AND ', $conditions);

    if ($answered !== null) {
        if ($answered == 1) {
            $baseFrom .= ' AND COALESCE(qa.cnt, 0) > 0';
        } else {
            $baseFrom .= ' AND COALESCE(qa.cnt, 0) = 0';
        }
    }

    $countSql = 'SELECT COUNT(*)' . $baseFrom;
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue(':' . $k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $dataSql = 'SELECT pq.id, pq.product_id, pq.user_id, pq.question, pq.created_at, p.name AS product_name, u.name AS customer_name, COALESCE(qa.cnt, 0) AS answer_count'
        . $baseFrom . ' ORDER BY pq.created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = $conn->prepare($dataSql);
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => $total > 0 ? ceil($total / $perPage) : 1
        ],
        'items' => $items
    ]);
} catch (Throwable $e) {
    error_log('admin/questions/list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener las preguntas']);
}
