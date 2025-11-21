<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';

header('Content-Type: application/json');

requireRole('admin');

$customerId = $_GET['id'] ?? null;
if (!$customerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cliente invalido']);
    exit;
}

try {
    $profileStmt = $conn->prepare('SELECT id, name, email, phone, image, role, created_at, last_access, is_blocked FROM users WHERE id = ? LIMIT 1');
    $profileStmt->execute([$customerId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        exit;
    }

    $metrics = fetchCustomerMetrics($conn, $customerId);
    $recentOrders = fetchCustomerOrders($conn, $customerId);
    $recentReviews = fetchCustomerReviews($conn, $customerId);
    $activity = buildActivityTimeline($recentOrders, $recentReviews);

    $response = [
        'success' => true,
        'profile' => formatProfile($profile),
        'metrics' => $metrics,
        'recent_orders' => $recentOrders,
        'recent_reviews' => $recentReviews,
        'activity' => $activity
    ];

    echo json_encode($response);
} catch (Throwable $e) {
    error_log('customers.detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo cargar el detalle del cliente']);
}

function fetchCustomerMetrics(PDO $conn, string $customerId): array {
    $stmt = $conn->prepare("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS total_spent, COALESCE(AVG(total), 0) AS avg_ticket, MIN(created_at) AS first_order, MAX(created_at) AS last_order, SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 120 DAY) THEN 1 ELSE 0 END) AS orders_last_120d FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$customerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['orders_count' => 0, 'total_spent' => 0, 'avg_ticket' => 0, 'first_order' => null, 'last_order' => null];

    $segments = [];
    $orders = (int) $row['orders_count'];
    $totalSpent = (float) $row['total_spent'];
    $lastOrder = $row['last_order'] ? strtotime($row['last_order']) : null;

    if ($totalSpent >= 200000) {
        $segments[] = 'Alta contribución';
    }
    if ((int)$row['orders_last_120d'] >= 2) {
        $segments[] = 'Compradores frecuentes';
    }
    if ($orders === 0) {
        $segments[] = 'Sin pedidos';
    } elseif ($lastOrder && $lastOrder < strtotime('-60 day')) {
        $segments[] = 'Inactivo';
    }

    return [
        'orders_count' => $orders,
        'total_spent' => round($totalSpent, 2),
        'avg_ticket' => round((float) $row['avg_ticket'], 2),
        'first_order' => $row['first_order'],
        'last_order' => $row['last_order'],
        'segments' => $segments
    ];
}

function fetchCustomerOrders(PDO $conn, string $customerId): array {
    $stmt = $conn->prepare("SELECT id, order_number, total, status, payment_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$customerId]);
    return array_map(function ($row) {
        return [
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'total' => round((float) $row['total'], 2),
            'status' => $row['status'],
            // Labels en español para mostrar en UI sin cambiar el valor base
            'status_label' => translateOrderStatus($row['status']),
            'payment_status' => $row['payment_status'],
            'payment_status_label' => translatePaymentStatus($row['payment_status']),
            'created_at' => $row['created_at']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function fetchCustomerReviews(PDO $conn, string $customerId): array {
    $stmt = $conn->prepare("SELECT pr.id, pr.product_id, pr.rating, pr.title, pr.comment, pr.is_approved, pr.created_at, p.name AS product_name FROM product_reviews pr LEFT JOIN products p ON p.id = pr.product_id WHERE pr.user_id = ? ORDER BY pr.created_at DESC LIMIT 4");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buildActivityTimeline(array $orders, array $reviews): array {
    $events = [];
    foreach ($orders as $order) {
        $events[] = [
            'type' => 'order',
            'title' => 'Orden ' . $order['order_number'],
            'description' => 'Estado: ' . ($order['status_label'] ?? translateOrderStatus($order['status'] ?? null)),
            'created_at' => $order['created_at'],
            'meta' => [
                'total' => $order['total'],
                'status' => $order['status'],
                'status_label' => $order['status_label'] ?? translateOrderStatus($order['status'] ?? null),
                'payment_status' => $order['payment_status'],
                'payment_status_label' => $order['payment_status_label'] ?? translatePaymentStatus($order['payment_status'] ?? null)
            ]
        ];
    }

    foreach ($reviews as $review) {
        $events[] = [
            'type' => 'review',
            'title' => 'Reseña ' . $review['rating'] . ' estrellas',
            'description' => $review['product_name'] ?? 'Producto',
            'created_at' => $review['created_at'],
            'meta' => [
                'rating' => $review['rating'],
                'is_approved' => (bool) $review['is_approved']
            ]
        ];
    }

    usort($events, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    return array_slice($events, 0, 8);
}

// Traducciones de estados de orden y pago al español (contexto colombiano)
function translateOrderStatus(?string $status): string {
    if (!$status) return 'Desconocido';
    $map = [
        'pending' => 'Pendiente',
        'processing' => 'En proceso',
        'completed' => 'Completada',
        'shipped' => 'Enviado',
        'delivered' => 'Entregada',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
        'on-hold' => 'En espera',
        'in_transit' => 'En tránsito'
    ];
    $key = strtolower($status);
    return $map[$key] ?? ucfirst($status);
}

function translatePaymentStatus(?string $payment): string {
    if (!$payment) return 'Desconocido';
    $map = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'partially_refunded' => 'Parcialmente reembolsado',
        'on-hold' => 'En espera'
    ];
    $key = strtolower($payment);
    return $map[$key] ?? ucfirst($payment);
}

function formatProfile(array $profile): array {
    return [
        'id' => $profile['id'],
        'name' => $profile['name'] ?? 'Sin nombre',
        'email' => $profile['email'],
        'phone' => $profile['phone'],
        'image' => $profile['image'],
        'role' => $profile['role'],
        'created_at' => $profile['created_at'],
        'last_access' => $profile['last_access'],
        'is_blocked' => (bool) $profile['is_blocked']
    ];
}
