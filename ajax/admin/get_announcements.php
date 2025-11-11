<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $order = isset($_GET['order']) ? trim($_GET['order']) : 'priority';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;

    // Construir query base
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(title LIKE ? OR message LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($type)) {
        $where_conditions[] = "type = ?";
        $params[] = $type;
    }

    if ($status === 'active') {
        $where_conditions[] = "is_active = 1";
    } elseif ($status === 'inactive') {
        $where_conditions[] = "is_active = 0";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Determinar orden
    $order_clause = match($order) {
        'newest' => 'ORDER BY created_at DESC',
        'title_asc' => 'ORDER BY title ASC',
        'title_desc' => 'ORDER BY title DESC',
        default => 'ORDER BY priority DESC, created_at DESC'
    };

    // Contar total de registros
    $count_sql = "SELECT COUNT(*) FROM announcements {$where_clause}";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();

    // Obtener anuncios
    $sql = "SELECT * FROM announcements {$where_clause} {$order_clause} LIMIT {$limit} OFFSET {$offset}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'announcements' => $announcements,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'limit' => $limit
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
