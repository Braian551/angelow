<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

try {
    ensureAdminAccess($conn);
    $adminId = (string)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePost($conn, $adminId);
        exit;
    }

    $notifications = gatherNotifications($conn, $adminId);
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => count($notifications)
    ]);
} catch (Throwable $error) {
    $code = in_array($error->getCode(), [401, 403], true) ? $error->getCode() : 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $error->getMessage()
    ]);
}

function ensureAdminAccess(PDO $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        throw new RuntimeException('No autorizado', 401);
    }
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetchColumn() !== 'admin') {
        throw new RuntimeException('Acceso restringido', 403);
    }
}

function handlePost(PDO $conn, string $adminId): void
{
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $payload['action'] ?? '';

    if ($action === 'mark_read') {
        $id = (string)($payload['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Identificador requerido', 422);
        }
        markNotificationAsRead($conn, $adminId, $id);
        echo json_encode(['success' => true]);
        return;
    }

    if ($action === 'mark_all_read') {
        $current = gatherNotifications($conn, $adminId);
        foreach ($current as $item) {
            markNotificationAsRead($conn, $adminId, $item['id']);
        }
        echo json_encode(['success' => true]);
        return;
    }

    throw new RuntimeException('Accion no soportada', 400);
}

function gatherNotifications(PDO $conn, string $adminId): array
{
    $notifications = array_merge(
        getOrderAlerts($conn, $adminId),
        getPendingPaymentAlerts($conn),
        getInventoryAlerts($conn)
    );

    $notifications = filterDismissed($conn, $adminId, $notifications);

    usort($notifications, static function (array $a, array $b): int {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    return array_slice($notifications, 0, 12);
}

function getOrderAlerts(PDO $conn, string $adminId): array
{
    $sql = "SELECT o.id, o.order_number, o.total, o.created_at, COALESCE(u.name, u.email, 'Cliente') AS customer
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN order_views ov ON ov.order_id = o.id AND ov.user_id = ?
            WHERE ov.id IS NULL
            ORDER BY o.created_at DESC
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$adminId]);

    $baseUrl = rtrim(BASE_URL, '/');

    return array_map(static function (array $row) use ($baseUrl): array {
        $orderNumber = $row['order_number'] ?: str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT);
        return [
            'id' => 'order:' . $row['id'],
            'title' => 'Nueva orden #' . $orderNumber,
            'message' => $row['customer'] ?? 'Cliente',
            'tag' => 'Ordenes',
            'icon' => 'fa-receipt',
            'created_at' => $row['created_at'] ?? date('c'),
            'url' => $baseUrl . '/admin/order/detail.php?id=' . $row['id']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getPendingPaymentAlerts(PDO $conn): array
{
    $sql = "SELECT o.id, o.order_number, o.total,
                   COALESCE(MAX(pt.created_at), o.created_at) AS activity_at,
                   COUNT(pt.id) AS uploads
            FROM orders o
            LEFT JOIN payment_transactions pt ON pt.order_id = o.id
            WHERE o.payment_status = 'pending'
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)
            GROUP BY o.id, o.order_number, o.total, o.created_at
            ORDER BY activity_at DESC
            LIMIT 5";

    $stmt = $conn->query($sql);
    $baseUrl = rtrim(BASE_URL, '/');

    return array_map(static function (array $row) use ($baseUrl): array {
        $details = sprintf('Pendiente de aprobar - %s', formatCop((float)$row['total']));
        return [
            'id' => 'payment:' . $row['id'],
            'title' => 'Pago por revisar #' . ($row['order_number'] ?: $row['id']),
            'message' => $details,
            'tag' => 'Pagos',
            'icon' => 'fa-money-check-dollar',
            'created_at' => $row['activity_at'] ?? $row['created_at'] ?? date('c'),
            'url' => $baseUrl . '/admin/orders.php?filter=pending-payments&focus=' . $row['id']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getInventoryAlerts(PDO $conn): array
{
    $sql = "SELECT p.id, p.name, c.name AS category,
                   COALESCE(SUM(psv.quantity), 0) AS stock,
                   COALESCE(MAX(psv.updated_at), p.created_at) AS reference_date
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_color_variants pcv ON pcv.product_id = p.id
            LEFT JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
            WHERE p.is_active = 1
            GROUP BY p.id, p.name, c.name, p.created_at
            HAVING stock <= 5
            ORDER BY stock ASC
            LIMIT 5";

    $stmt = $conn->query($sql);

    $baseUrl = rtrim(BASE_URL, '/');

    return array_map(static function (array $row) use ($baseUrl): array {
        $message = sprintf('Stock actual: %s unidades', number_format((int)$row['stock']));
        return [
            'id' => 'inventory:' . $row['id'],
            'title' => $row['name'] ?? 'Producto sin nombre',
            'message' => $message,
            'tag' => 'Inventario',
            'icon' => 'fa-boxes-stacked',
            'created_at' => $row['reference_date'] ?? date('c'),
            'url' => $baseUrl . '/admin/inventario/inventory.php?product=' . $row['id']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function filterDismissed(PDO $conn, string $adminId, array $notifications): array
{
    if (!$notifications || !hasDismissalTable($conn)) {
        return $notifications;
    }

    $keys = array_column($notifications, 'id');
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $params = array_merge([$adminId], $keys);

    $stmt = $conn->prepare("SELECT notification_key FROM admin_notification_dismissals WHERE admin_id = ? AND notification_key IN ($placeholders)");
    $stmt->execute($params);
    $dismissed = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

    return array_values(array_filter($notifications, static function (array $item) use ($dismissed): bool {
        return !isset($dismissed[$item['id']]);
    }));
}

function markNotificationAsRead(PDO $conn, string $adminId, string $identifier): void
{
    [$type, $reference] = array_pad(explode(':', $identifier, 2), 2, null);
    if ($type === 'order' && $reference) {
        markOrderViewed($conn, $adminId, (int)$reference);
        return;
    }

    if ($reference !== null) {
        dismissNotification($conn, $adminId, $identifier);
    }
}

function markOrderViewed(PDO $conn, string $adminId, int $orderId): void
{
    $sql = "INSERT INTO order_views (order_id, user_id, viewed_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = VALUES(viewed_at)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId, $adminId]);
}

function dismissNotification(PDO $conn, string $adminId, string $key): void
{
    if (!hasDismissalTable($conn)) {
        return;
    }
    $stmt = $conn->prepare('INSERT IGNORE INTO admin_notification_dismissals (admin_id, notification_key, dismissed_at) VALUES (?, ?, NOW())');
    $stmt->execute([$adminId, $key]);
}

function hasDismissalTable(PDO $conn): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    try {
        $conn->query('SELECT 1 FROM admin_notification_dismissals LIMIT 1');
        $available = true;
    } catch (Throwable $error) {
        error_log('admin_notification_dismissals no disponible: ' . $error->getMessage());
        $available = false;
    }

    return $available;
}

function formatCop(float $value): string
{
    return '$' . number_format($value, 0, ',', '.');
}
