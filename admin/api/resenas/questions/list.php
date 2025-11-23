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
$answered = (isset($_GET['answered']) && $_GET['answered'] !== '') ? (int) $_GET['answered'] : null; // 1=answered, 0=unanswered
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

try {
    $conditions = ['1=1'];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(pq.question LIKE :search OR p.name LIKE :search OR u.name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    // Use a direct LEFT JOIN to question_answers and GROUP BY pq.id. This is more reliable
    // when using HAVING to filter by presence (answered) or absence (unanswered) of answers.
    $baseFrom = " FROM product_questions pq
        LEFT JOIN products p ON p.id = pq.product_id
        LEFT JOIN users u ON u.id = pq.user_id
        LEFT JOIN question_answers qa ON qa.question_id = pq.id
        WHERE " . implode(' AND ', $conditions);

    // Count distinct product_questions when using JOINs to avoid duplication due to answered rows
    $countSql = 'SELECT COUNT(DISTINCT pq.id)' . $baseFrom;
    
    // For counting, we need to apply the answered filter
    if ($answered !== null) {
        if ($answered == 1) {
            $countSql .= ' AND EXISTS (SELECT 1 FROM question_answers qa2 WHERE qa2.question_id = pq.id)';
        } else {
            $countSql .= ' AND NOT EXISTS (SELECT 1 FROM question_answers qa2 WHERE qa2.question_id = pq.id)';
        }
    }
    
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue(':' . $k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $dataSql = 'SELECT pq.id, pq.product_id, pq.user_id, pq.question, pq.created_at, p.name AS product_name, u.name AS customer_name, COUNT(qa.id) AS answer_count'
        . $baseFrom . ' GROUP BY pq.id';

    // Append filter for answered/unanswered using HAVING clause (operates on grouped row counts)
    if ($answered !== null) {
        if ($answered == 1) {
            $dataSql .= ' HAVING COUNT(qa.id) > 0';
        } else {
            $dataSql .= ' HAVING COUNT(qa.id) = 0';
        }
    }
    $dataSql .= ' ORDER BY pq.created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = $conn->prepare($dataSql);
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Count number of distinct questions that have answers for debugging/meta
    $answeredQuestionsTotal = 0;
    try {
        $answeredQuestionsTotal = (int) $conn->query('SELECT COUNT(DISTINCT question_id) FROM question_answers')->fetchColumn();
    } catch (Throwable $e) {
        // ignore errors here
    }

    $payload = [
        'success' => true,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => $total > 0 ? ceil($total / $perPage) : 1
        ],
        'items' => $items,
        'meta' => [
            'answered_questions_total' => $answeredQuestionsTotal
        ]
    ];
    if ($debug) {
        $payload['debug'] = [ 'countSql' => $countSql, 'dataSql' => $dataSql, 'params' => $params ];
        try {
            $sampleIds = $conn->query('SELECT DISTINCT question_id FROM question_answers LIMIT 50')->fetchAll(PDO::FETCH_COLUMN);
            $payload['debug']['sample_answered_question_ids'] = $sampleIds ?: [];
        } catch (Throwable $e) {
            $payload['debug']['sample_answered_question_ids'] = [];
        }
    }
    echo json_encode($payload);
} catch (Throwable $e) {
    error_log('admin/questions/list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener las preguntas']);
}
