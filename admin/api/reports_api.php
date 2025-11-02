<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'sales_summary':
            echo json_encode(getSalesSummary($conn));
            break;
        
        case 'sales_by_period':
            $period = $_GET['period'] ?? 'month'; // day, week, month, year
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            echo json_encode(getSalesByPeriod($conn, $period, $startDate, $endDate));
            break;
        
        case 'sales_by_status':
            echo json_encode(getSalesByStatus($conn));
            break;
        
        case 'top_products':
            $limit = intval($_GET['limit'] ?? 10);
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            echo json_encode(getTopProducts($conn, $limit, $startDate, $endDate));
            break;
        
        case 'product_categories_sales':
            echo json_encode(getProductCategoriesSales($conn));
            break;
        
        case 'recurring_customers':
            $minOrders = intval($_GET['min_orders'] ?? 2);
            echo json_encode(getRecurringCustomers($conn, $minOrders));
            break;
        
        case 'customer_stats':
            echo json_encode(getCustomerStats($conn));
            break;
        
        case 'customer_lifetime_value':
            $limit = intval($_GET['limit'] ?? 10);
            echo json_encode(getCustomerLifetimeValue($conn, $limit));
            break;
        
        case 'revenue_comparison':
            echo json_encode(getRevenueComparison($conn));
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

// ==================== FUNCIONES DE VENTAS ====================

function getSalesSummary($conn) {
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    $thisYear = date('Y');
    
    // Total de ventas hoy
    $stmtToday = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue
        FROM orders 
        WHERE DATE(created_at) = ? AND status != 'cancelled'
    ");
    $stmtToday->execute([$today]);
    $todayStats = $stmtToday->fetch(PDO::FETCH_ASSOC);
    
    // Total del mes actual
    $stmtMonth = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue
        FROM orders 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'
    ");
    $stmtMonth->execute([$thisMonth]);
    $monthStats = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    
    // Total del año
    $stmtYear = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue
        FROM orders 
        WHERE YEAR(created_at) = ? AND status != 'cancelled'
    ");
    $stmtYear->execute([$thisYear]);
    $yearStats = $stmtYear->fetch(PDO::FETCH_ASSOC);
    
    // Total histórico
    $stmtTotal = $conn->query("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue
        FROM orders 
        WHERE status != 'cancelled'
    ");
    $totalStats = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    // Ticket promedio
    $avgTicket = $totalStats['total_orders'] > 0 
        ? $totalStats['total_revenue'] / $totalStats['total_orders'] 
        : 0;
    
    return [
        'today' => $todayStats,
        'month' => $monthStats,
        'year' => $yearStats,
        'total' => $totalStats,
        'average_ticket' => round($avgTicket, 2)
    ];
}

function getSalesByPeriod($conn, $period, $startDate = null, $endDate = null) {
    $dateFormat = match($period) {
        'day' => '%Y-%m-%d',
        'week' => '%Y-%u',
        'month' => '%Y-%m',
        'year' => '%Y',
        default => '%Y-%m'
    };
    
    $dateLabel = match($period) {
        'day' => 'DATE(created_at)',
        'week' => "CONCAT(YEAR(created_at), '-W', LPAD(WEEK(created_at, 1), 2, '0'))",
        'month' => "DATE_FORMAT(created_at, '%Y-%m')",
        'year' => 'YEAR(created_at)',
        default => "DATE_FORMAT(created_at, '%Y-%m')"
    };
    
    $whereClause = "status != 'cancelled'";
    $params = [];
    
    if ($startDate && $endDate) {
        $whereClause .= " AND DATE(created_at) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
    }
    
    $stmt = $conn->prepare("
        SELECT 
            $dateLabel as period_label,
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue,
            COALESCE(SUM(subtotal), 0) as subtotal,
            COALESCE(SUM(shipping_cost), 0) as shipping_revenue,
            COALESCE(AVG(total), 0) as avg_order_value
        FROM orders
        WHERE $whereClause
        GROUP BY period_label
        ORDER BY period_label DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesByStatus($conn) {
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_revenue,
            CASE status
                WHEN 'pending' THEN 'Pendiente'
                WHEN 'processing' THEN 'En proceso'
                WHEN 'shipped' THEN 'Enviado'
                WHEN 'delivered' THEN 'Entregado'
                WHEN 'cancelled' THEN 'Cancelado'
                WHEN 'refunded' THEN 'Reembolsado'
                ELSE status
            END as status_label
        FROM orders
        GROUP BY status
        ORDER BY count DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== FUNCIONES DE PRODUCTOS ====================

function getTopProducts($conn, $limit = 10, $startDate = null, $endDate = null) {
    $whereClause = "o.status != 'cancelled'";
    $params = [];
    
    if ($startDate && $endDate) {
        $whereClause .= " AND DATE(o.created_at) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
    }
    
    $params[] = $limit;
    
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            p.slug,
            c.name as category_name,
            COUNT(oi.id) as times_sold,
            SUM(oi.quantity) as total_quantity,
            COALESCE(SUM(oi.total), 0) as total_revenue,
            COALESCE(AVG(oi.price), 0) as avg_price,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE $whereClause
        GROUP BY p.id, p.name, p.slug, c.name
        ORDER BY total_revenue DESC
        LIMIT ?
    ");
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductCategoriesSales($conn) {
    $stmt = $conn->query("
        SELECT 
            c.id,
            c.name,
            COUNT(DISTINCT oi.id) as items_sold,
            SUM(oi.quantity) as total_quantity,
            COALESCE(SUM(oi.total), 0) as total_revenue,
            COUNT(DISTINCT p.id) as products_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== FUNCIONES DE CLIENTES ====================

function getRecurringCustomers($conn, $minOrders = 2) {
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as total_spent,
            COALESCE(AVG(o.total), 0) as avg_order_value,
            MIN(o.created_at) as first_order,
            MAX(o.created_at) as last_order,
            DATEDIFF(MAX(o.created_at), MIN(o.created_at)) as customer_age_days
        FROM users u
        INNER JOIN orders o ON u.id = o.user_id
        WHERE o.status != 'cancelled'
        GROUP BY u.id, u.name, u.email, u.phone
        HAVING total_orders >= ?
        ORDER BY total_orders DESC, total_spent DESC
    ");
    $stmt->execute([$minOrders]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerStats($conn) {
    // Estadísticas generales de clientes
    $stmt = $conn->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_customers,
            COUNT(DISTINCT CASE WHEN o.id IS NOT NULL THEN u.id END) as customers_with_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN u.id END) as customers_with_delivered,
            COALESCE(AVG(order_counts.order_count), 0) as avg_orders_per_customer
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
        LEFT JOIN (
            SELECT user_id, COUNT(*) as order_count
            FROM orders
            WHERE status != 'cancelled'
            GROUP BY user_id
        ) order_counts ON u.id = order_counts.user_id
        WHERE u.role = 'customer'
    ");
    $generalStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Distribución por número de órdenes
    $stmt = $conn->query("
        SELECT 
            CASE 
                WHEN order_count = 1 THEN '1 orden'
                WHEN order_count BETWEEN 2 AND 5 THEN '2-5 órdenes'
                WHEN order_count BETWEEN 6 AND 10 THEN '6-10 órdenes'
                ELSE 'Más de 10 órdenes'
            END as segment,
            COUNT(*) as customer_count
        FROM (
            SELECT u.id, COUNT(o.id) as order_count
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
            WHERE u.role = 'customer'
            GROUP BY u.id
        ) customer_orders
        GROUP BY segment
        ORDER BY 
            CASE segment
                WHEN '1 orden' THEN 1
                WHEN '2-5 órdenes' THEN 2
                WHEN '6-10 órdenes' THEN 3
                ELSE 4
            END
    ");
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'general' => $generalStats,
        'distribution' => $distribution
    ];
}

function getCustomerLifetimeValue($conn, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.created_at as registration_date,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as lifetime_value,
            COALESCE(AVG(o.total), 0) as avg_order_value,
            MAX(o.created_at) as last_purchase_date,
            DATEDIFF(NOW(), MAX(o.created_at)) as days_since_last_purchase
        FROM users u
        INNER JOIN orders o ON u.id = o.user_id
        WHERE o.status != 'cancelled' AND u.role = 'customer'
        GROUP BY u.id, u.name, u.email, u.created_at
        ORDER BY lifetime_value DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== COMPARATIVAS ====================

function getRevenueComparison($conn) {
    $currentMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));
    $currentYear = date('Y');
    $lastYear = date('Y', strtotime('-1 year'));
    
    // Mes actual vs mes anterior
    $stmtCurrentMonth = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'
    ");
    $stmtCurrentMonth->execute([$currentMonth]);
    $currentMonthRevenue = $stmtCurrentMonth->fetchColumn();
    
    $stmtLastMonth = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'cancelled'
    ");
    $stmtLastMonth->execute([$lastMonth]);
    $lastMonthRevenue = $stmtLastMonth->fetchColumn();
    
    $monthGrowth = $lastMonthRevenue > 0 
        ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
        : 0;
    
    // Año actual vs año anterior
    $stmtCurrentYear = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE YEAR(created_at) = ? AND status != 'cancelled'
    ");
    $stmtCurrentYear->execute([$currentYear]);
    $currentYearRevenue = $stmtCurrentYear->fetchColumn();
    
    $stmtLastYear = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE YEAR(created_at) = ? AND status != 'cancelled'
    ");
    $stmtLastYear->execute([$lastYear]);
    $lastYearRevenue = $stmtLastYear->fetchColumn();
    
    $yearGrowth = $lastYearRevenue > 0 
        ? (($currentYearRevenue - $lastYearRevenue) / $lastYearRevenue) * 100 
        : 0;
    
    return [
        'month' => [
            'current' => $currentMonthRevenue,
            'previous' => $lastMonthRevenue,
            'growth_percentage' => round($monthGrowth, 2)
        ],
        'year' => [
            'current' => $currentYearRevenue,
            'previous' => $lastYearRevenue,
            'growth_percentage' => round($yearGrowth, 2)
        ]
    ];
}
