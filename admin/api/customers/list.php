<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 12);
$perPage = max(5, min(50, $perPage));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$segment = $_GET['segment'] ?? 'all';
$sort = $_GET['sort'] ?? 'recent';

try {
    $queryParts = buildCustomerQuery($search, $segment, $sort);

    $countSql = 'SELECT COUNT(*) FROM (' . $queryParts['select_id'] . $queryParts['body'] . $queryParts['group'] . $queryParts['having'] . ') AS counted';
    $countStmt = $conn->prepare($countSql);
    bindQueryParams($countStmt, $queryParts['params']);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $sql = $queryParts['select'] . $queryParts['body'] . $queryParts['group'] . $queryParts['having'] . $queryParts['order'] . ' LIMIT :limit OFFSET :offset';
    $stmt = $conn->prepare($sql);
    bindQueryParams($stmt, $queryParts['params']);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $items = array_map('transformCustomerRow', $rows);

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
    error_log('customers.list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el listado de clientes']);
}

function buildCustomerQuery(string $search, string $segment, string $sort): array {
    $conditions = ["u.role IN ('customer','user')"];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR u.id LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $body = " FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled' WHERE " . implode(' AND ', $conditions);

    $select = 'SELECT u.id, COALESCE(u.name, u.email) AS name, u.email, u.phone, u.created_at, u.image, u.is_blocked, ' .
        'COUNT(DISTINCT o.id) AS orders_count, COALESCE(SUM(o.total), 0) AS total_spent, ' .
        'MAX(o.created_at) AS last_order, CASE WHEN COUNT(DISTINCT o.id) > 0 THEN COALESCE(SUM(o.total), 0) / COUNT(DISTINCT o.id) ELSE 0 END AS avg_ticket';

    $selectId = 'SELECT u.id';
    $group = ' GROUP BY u.id';

    $havingParts = [];
    switch ($segment) {
        case 'vip':
            $havingParts[] = 'COALESCE(SUM(o.total), 0) >= 600000';
            break;
        case 'loyal':
            $havingParts[] = 'COUNT(DISTINCT o.id) >= 3';
            break;
        case 'at_risk':
            $havingParts[] = 'COUNT(DISTINCT o.id) > 0 AND (MAX(o.created_at) IS NULL OR MAX(o.created_at) < DATE_SUB(NOW(), INTERVAL 90 DAY))';
            break;
        case 'new':
            $havingParts[] = 'MIN(u.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
        case 'no_orders':
            $havingParts[] = 'COUNT(DISTINCT o.id) = 0';
            break;
        case 'active':
            $havingParts[] = 'MAX(o.created_at) >= DATE_SUB(NOW(), INTERVAL 60 DAY)';
            break;
        default:
            break;
    }

    $having = empty($havingParts) ? '' : ' HAVING ' . implode(' AND ', $havingParts);

    $orderMap = [
        'recent' => 'COALESCE(MAX(o.created_at), u.created_at) DESC',
        'value' => 'total_spent DESC',
        'orders' => 'orders_count DESC',
        'name' => 'name ASC'
    ];
    $order = ' ORDER BY ' . ($orderMap[$sort] ?? $orderMap['recent']);

    return [
        'select' => $select,
        'select_id' => $selectId,
        'body' => $body,
        'group' => $group,
        'having' => $having,
        'order' => $order,
        'params' => $params
    ];
}

function bindQueryParams(PDOStatement $stmt, array $params): void {
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
}

function transformCustomerRow(array $row): array {
    $orders = (int) $row['orders_count'];
    $lastOrder = $row['last_order'] ? strtotime($row['last_order']) : null;
    $status = 'Sin pedidos';
    if ($orders > 0 && $lastOrder) {
        if ($lastOrder >= strtotime('-30 day')) {
            $status = 'Activo';
        } elseif ($lastOrder >= strtotime('-90 day')) {
            $status = 'En riesgo';
        } else {
            $status = 'Inactivo';
        }
    }

    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'created_at' => $row['created_at'],
        'image' => $row['image'],
        'is_blocked' => (bool) $row['is_blocked'],
        'orders_count' => $orders,
        'total_spent' => round((float) $row['total_spent'], 2),
        'avg_ticket' => round((float) $row['avg_ticket'], 2),
        'last_order' => $row['last_order'],
        'status' => $status
    ];
}
