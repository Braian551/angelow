<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

$rangeDays = isset($_GET['range']) ? max(7, min(180, (int) $_GET['range'])) : 90;

try {
    $stats = getCustomerStats($conn, $rangeDays);
    $segments = getCustomerSegments($conn);
    $trend = getCustomerAcquisitionTrend($conn, 8);
    $engagement = getCustomerEngagementMatrix($conn);
    $topCustomers = getTopCustomers($conn, 5);

    echo json_encode([
        'success' => true,
        'generated_at' => date('c'),
        'stats' => $stats,
        'segments' => $segments,
        'acquisition_trend' => $trend,
        'engagement_matrix' => $engagement,
        'top_customers' => $topCustomers
    ]);
} catch (Throwable $e) {
    error_log('customers.overview error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo generar el resumen de clientes']);
}

function getCustomerStats(PDO $conn, int $rangeDays): array {
    $totalCustomers = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role IN ('customer','user')");
    $newCustomers = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role IN ('customer','user') AND created_at >= DATE_SUB(NOW(), INTERVAL :range DAY)", ['range' => $rangeDays]);
    $activeCustomers = scalarQuery($conn, "SELECT COUNT(DISTINCT user_id) FROM orders WHERE user_id IS NOT NULL AND status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $repeatCustomers = scalarQuery($conn, "SELECT COUNT(*) FROM (SELECT user_id FROM orders WHERE user_id IS NOT NULL AND status != 'cancelled' GROUP BY user_id HAVING COUNT(*) >= 2) AS repeaters");
    $lifetimeAverage = (float) scalarQuery($conn, "SELECT COALESCE(AVG(customer_total), 0) FROM (SELECT COALESCE(SUM(total), 0) AS customer_total FROM orders WHERE user_id IS NOT NULL AND status != 'cancelled' GROUP BY user_id) AS totals");
    $ticketAverage = (float) scalarQuery($conn, "SELECT COALESCE(AVG(total), 0) FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)");

    return [
        'total_customers' => (int) $totalCustomers,
        'new_customers' => (int) $newCustomers,
        'active_customers' => (int) $activeCustomers,
        'repeat_rate' => percentage($repeatCustomers, $totalCustomers),
        'lifetime_value' => round($lifetimeAverage, 2),
        'avg_ticket' => round($ticketAverage, 2)
    ];
}

function getCustomerSegments(PDO $conn): array {
    $segments = [
        'vip' => [
            'label' => 'Clientes VIP',
            'description' => 'Ticket acumulado mayor a 600000',
            'query' => "SELECT COUNT(*) FROM (SELECT u.id FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled' WHERE u.role IN ('customer','user') GROUP BY u.id HAVING COALESCE(SUM(o.total),0) >= 600000) AS vip"
        ],
        'at_risk' => [
            'label' => 'Inactivos 90+ dias',
            'description' => 'Clientes con pedido pero sin compras en 90 dias',
            'query' => "SELECT COUNT(*) FROM (SELECT u.id, MAX(o.created_at) AS last_order FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled' WHERE u.role IN ('customer','user') GROUP BY u.id HAVING last_order IS NOT NULL AND last_order < DATE_SUB(NOW(), INTERVAL 90 DAY)) AS risk"
        ],
        'new' => [
            'label' => 'Nuevos 30d',
            'description' => 'Clientes registrados en los ultimos 30 dias',
            'query' => "SELECT COUNT(*) FROM users WHERE role IN ('customer','user') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ],
        'no_orders' => [
            'label' => 'Sin pedidos',
            'description' => 'Clientes sin ninguna orden registrada',
            'query' => "SELECT COUNT(*) FROM users u WHERE u.role IN ('customer','user') AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled')"
        ]
    ];

    $results = [];
    foreach ($segments as $key => $segment) {
        $count = (int) scalarQuery($conn, $segment['query']);
        $results[] = [
            'key' => $key,
            'label' => $segment['label'],
            'description' => $segment['description'],
            'count' => $count
        ];
    }

    return $results;
}

function getCustomerAcquisitionTrend(PDO $conn, int $weeks): array {
    $data = [];
    for ($i = $weeks - 1; $i >= 0; $i--) {
        $start = date('Y-m-d', strtotime("-{$i} week"));
        $end = date('Y-m-d', strtotime($start . ' +6 day'));
        $count = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role IN ('customer','user') AND DATE(created_at) BETWEEN :start AND :end", ['start' => $start, 'end' => $end]);
        $data[] = [
            'label' => date('d M', strtotime($start)),
            'week_start' => $start,
            'week_end' => $end,
            'count' => (int) $count
        ];
    }

    return $data;
}

function getCustomerEngagementMatrix(PDO $conn): array {
    $orderGroups = $conn->query("SELECT bucket, COUNT(*) AS customers FROM (
        SELECT CASE
            WHEN order_count = 0 THEN 'sin_pedidos'
            WHEN order_count = 1 THEN '1 pedido'
            WHEN order_count BETWEEN 2 AND 4 THEN '2-4 pedidos'
            ELSE '5+ pedidos'
        END AS bucket
        FROM (
            SELECT u.id, COUNT(o.id) AS order_count
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
            WHERE u.role IN ('customer','user')
            GROUP BY u.id
        ) AS summary
    ) AS buckets GROUP BY bucket")->fetchAll(PDO::FETCH_KEY_PAIR);

    $recencyGroups = $conn->query("SELECT bucket, COUNT(*) AS customers FROM (
        SELECT CASE
            WHEN last_order IS NULL THEN 'sin_historial'
            WHEN last_order >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN '0-30 dias'
            WHEN last_order >= DATE_SUB(NOW(), INTERVAL 60 DAY) THEN '31-60 dias'
            WHEN last_order >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN '61-90 dias'
            ELSE '90+ dias'
        END AS bucket
        FROM (
            SELECT u.id, MAX(o.created_at) AS last_order
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
            WHERE u.role IN ('customer','user')
            GROUP BY u.id
        ) last_orders
    ) recency GROUP BY bucket")->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'orders' => array_map('intval', $orderGroups ?: []),
        'recency' => array_map('intval', $recencyGroups ?: [])
    ];
}

function getTopCustomers(PDO $conn, int $limit): array {
    $stmt = $conn->prepare("SELECT 
            u.id,
            COALESCE(u.name, u.email) AS name,
            u.email,
            u.phone,
            COALESCE(SUM(o.total), 0) AS total_spent,
            COUNT(o.id) AS orders_count,
            MAX(o.created_at) AS last_order
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
        WHERE u.role IN ('customer','user')
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return array_map(function ($row) {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'total_spent' => round((float) $row['total_spent'], 2),
            'orders_count' => (int) $row['orders_count'],
            'last_order' => $row['last_order']
        ];
    }, $rows);
}

function scalarQuery(PDO $conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $paramKey = is_int($key) ? $key + 1 : ':' . $key;
        $stmt->bindValue($paramKey, $value);
    }
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
}

function percentage($part, $total): float {
    $total = (float) $total;
    if ($total <= 0) {
        return 0.0;
    }
    return round(((float) $part / $total) * 100, 2);
}
