<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

$debug = defined('DEBUG_MODE') && DEBUG_MODE;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
if ($role !== 'admin') {
    try {
        $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Error verificando rol en dashboard overview: ' . $e->getMessage());
    }
}

if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso restringido']);
    exit;
}

$range = isset($_GET['range']) ? (int) $_GET['range'] : 7;
$allowedRanges = [7, 14, 30];
if (!in_array($range, $allowedRanges, true)) {
    $range = 7;
}

try {
    $stats = fetchDashboardStats($conn);
    $trend = fetchSalesTrend($conn, $range);
    $statusBreakdown = fetchStatusBreakdown($conn);
    $recentOrders = fetchRecentOrders($conn);
    $inventory = fetchInventorySummary($conn);
    $lowStock = fetchLowStockProducts($conn);
    $topProducts = fetchTopProducts($conn);
    $activity = fetchRecentActivity($conn);

    echo json_encode([
        'success' => true,
        'range' => $range,
        'generated_at' => date('c'),
        'stats' => $stats,
        'sales_trend' => $trend,
        'status_breakdown' => $statusBreakdown,
        'recent_orders' => $recentOrders,
        'inventory' => $inventory,
        'low_stock' => $lowStock,
        'top_products' => $topProducts,
        'activity' => $activity
    ]);
} catch (Throwable $e) {
    error_log('Error generando dashboard overview: ' . $e->getMessage());
    http_response_code(500);
    $payload = ['success' => false, 'message' => 'Error interno'];
    if ($debug) {
        $payload['message'] = $e->getMessage();
        $payload['trace'] = $e->getTraceAsString();
    }
    echo json_encode($payload);
}

function fetchDashboardStats(PDO $conn): array {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $currentMonth = date('Y-m');
    $previousMonth = date('Y-m', strtotime('-1 month'));

    $ordersToday = scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'", [$today]);
    $ordersYesterday = scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'", [$yesterday]);

    $revenueToday = (float) scalarQuery($conn, "SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'", [$today]);
    $revenueYesterday = (float) scalarQuery($conn, "SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'", [$yesterday]);

    $newCustomersToday = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = ? AND role IN ('customer', 'user')", [$today]);
    $newCustomersLastWeek = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = ? AND role IN ('customer', 'user')", [date('Y-m-d', strtotime('-7 day'))]);

    $monthRevenue = (float) scalarQuery($conn, "SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'", [$currentMonth]);
    $previousMonthRevenue = (float) scalarQuery($conn, "SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'", [$previousMonth]);

    $activeProducts = scalarQuery($conn, "SELECT COUNT(*) FROM products WHERE is_active = 1");
    $lowStockCount = scalarQuery($conn, "SELECT COUNT(*) FROM (
        SELECT p.id, COALESCE(SUM(psv.quantity), 0) AS total_stock
        FROM products p
        LEFT JOIN product_color_variants pcv ON pcv.product_id = p.id
        LEFT JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
        WHERE p.is_active = 1
        GROUP BY p.id
        HAVING total_stock <= 5
    ) as low_stock");

    $pendingOrders = scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')");
    $avgTicket = (float) scalarQuery($conn, "SELECT COALESCE(AVG(total), 0) FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

    return [
        'orders_today' => (int) $ordersToday,
        'orders_change' => percentChange($ordersToday, $ordersYesterday),
        'revenue_today' => round($revenueToday, 2),
        'revenue_change' => percentChange($revenueToday, $revenueYesterday),
        'new_customers' => (int) $newCustomersToday,
        'customers_change' => percentChange($newCustomersToday, $newCustomersLastWeek),
        'month_revenue' => round($monthRevenue, 2),
        'month_revenue_change' => percentChange($monthRevenue, $previousMonthRevenue),
        'active_products' => (int) $activeProducts,
        'low_stock_products' => (int) $lowStockCount,
        'pending_orders' => (int) $pendingOrders,
        'avg_ticket' => round($avgTicket, 2)
    ];
}

function fetchSalesTrend(PDO $conn, int $days): array {
    $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' day'));

    $stmt = $conn->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
        FROM orders
        WHERE DATE(created_at) >= ? AND status != 'cancelled'
        GROUP BY day
        ORDER BY day ASC");
    $stmt->execute([$startDate]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byDay = [];
    foreach ($rows as $row) {
        $byDay[$row['day']] = [
            'orders' => (int) $row['orders'],
            'revenue' => (float) $row['revenue']
        ];
    }

    $data = [];
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime($startDate . " +{$i} day"));
        $data[] = [
            'date' => $date,
            'orders' => $byDay[$date]['orders'] ?? 0,
            'revenue' => isset($byDay[$date]) ? round($byDay[$date]['revenue'], 2) : 0
        ];
    }

    return $data;
}

function fetchStatusBreakdown(PDO $conn): array {
    $labels = [
        'pending' => ['label' => 'Pendiente', 'color' => '#f4a261'],
        'processing' => ['label' => 'En proceso', 'color' => '#3b82f6'],
        'shipped' => ['label' => 'Enviado', 'color' => '#2dd4bf'],
        'delivered' => ['label' => 'Entregado', 'color' => '#94a3b8'],
        'cancelled' => ['label' => 'Cancelado', 'color' => '#f87171'],
        'refunded' => ['label' => 'Reembolsado', 'color' => '#a855f7']
    ];

    $stmt = $conn->query("SELECT status, COUNT(*) AS count, COALESCE(SUM(total), 0) AS revenue FROM orders GROUP BY status");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($row) use ($labels) {
        $status = $row['status'] ?? 'pending';
        $meta = $labels[$status] ?? ['label' => ucfirst($status), 'color' => '#cbd5f5'];
        return [
            'status' => $status,
            'label' => $meta['label'],
            'color' => $meta['color'],
            'count' => (int) $row['count'],
            'revenue' => round((float) $row['revenue'], 2)
        ];
    }, $rows);
}

function fetchRecentOrders(PDO $conn): array {
        $stmt = $conn->query("SELECT o.id, o.order_number, o.total, o.status, o.payment_status, o.created_at,
            COALESCE(u.name, u.email, 'Sin asignar') AS customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 8");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchInventorySummary(PDO $conn): array {
    $totalProducts = scalarQuery($conn, "SELECT COUNT(*) FROM products");
    $activeProducts = scalarQuery($conn, "SELECT COUNT(*) FROM products WHERE is_active = 1");
    $zeroStock = scalarQuery($conn, "SELECT COUNT(*) FROM (
        SELECT p.id, COALESCE(SUM(psv.quantity), 0) AS total_stock
        FROM products p
        LEFT JOIN product_color_variants pcv ON pcv.product_id = p.id
        LEFT JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
        GROUP BY p.id
        HAVING total_stock = 0
    ) as zero_items");

    return [
        'total_products' => (int) $totalProducts,
        'active_products' => (int) $activeProducts,
        'zero_stock' => (int) $zeroStock
    ];
}

function fetchLowStockProducts(PDO $conn): array {
    $stmt = $conn->query("SELECT 
            p.id,
            p.name,
            c.name AS category,
            COALESCE(SUM(psv.quantity), 0) AS total_stock,
            COALESCE(MIN(psv.price), p.price) AS min_price,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, `order` ASC LIMIT 1) AS image
        FROM products p
        LEFT JOIN product_color_variants pcv ON pcv.product_id = p.id
        LEFT JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        GROUP BY p.id, p.name, c.name
        HAVING total_stock <= 5
        ORDER BY total_stock ASC, p.name ASC
        LIMIT 6");

    return array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'category' => $row['category'] ?? 'Sin categoría',
            'total_stock' => (int) $row['total_stock'],
            'price' => round((float) $row['min_price'], 2),
            'image' => $row['image']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function fetchTopProducts(PDO $conn): array {
    $stmt = $conn->query("SELECT 
            p.id,
            p.name,
            c.name AS category,
            SUM(oi.quantity) AS total_quantity,
            COALESCE(SUM(oi.total), 0) AS total_revenue
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        INNER JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id, p.name, c.name
        ORDER BY total_revenue DESC
        LIMIT 5");

    return array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'category' => $row['category'] ?? 'Sin categoría',
            'total_quantity' => (int) $row['total_quantity'],
            'total_revenue' => round((float) $row['total_revenue'], 2)
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function fetchRecentActivity(PDO $conn): array {
    $activities = [];

    $orderStmt = $conn->query("SELECT 'order' AS type, o.order_number, o.status, o.total, o.created_at,
            COALESCE(u.name, u.email, 'Cliente') AS actor
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 6");
    foreach ($orderStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activities[] = [
            'type' => 'order',
            'title' => 'Nueva orden ' . $row['order_number'],
            'description' => $row['actor'] . ' • Estado: ' . $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    $userStmt = $conn->query("SELECT 'customer' AS type, name, email, created_at FROM users WHERE role IN ('customer','user') ORDER BY created_at DESC LIMIT 4");
    foreach ($userStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activities[] = [
            'type' => 'customer',
            'title' => 'Nuevo cliente: ' . ($row['name'] ?: 'Sin nombre'),
            'description' => $row['email'],
            'created_at' => $row['created_at']
        ];
    }

    $reviewStmt = $conn->query("SELECT 'review' AS type, pr.rating, pr.created_at, p.name AS product_name
        FROM product_reviews pr
        INNER JOIN products p ON pr.product_id = p.id
        WHERE pr.is_approved = 1
        ORDER BY pr.created_at DESC
        LIMIT 4");
    foreach ($reviewStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activities[] = [
            'type' => 'review',
            'title' => 'Nueva reseña (' . $row['rating'] . '⭐)',
            'description' => $row['product_name'],
            'created_at' => $row['created_at']
        ];
    }

    usort($activities, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    return array_slice($activities, 0, 8);
}

function scalarQuery(PDO $conn, string $query, array $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?? 0;
}

function percentChange($current, $previous): float {
    $previous = (float) $previous;
    if ($previous == 0.0) {
        return $current > 0 ? 100.0 : 0.0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}
