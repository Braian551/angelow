<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../helpers/admin_header_widgets.php';

try {
    ensureAdminAccess($conn);

    $query = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 5);
    $limit = max(3, min(10, $limit));

    if (mb_strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'message' => 'Ingresa al menos 2 caracteres',
            'results' => ['orders' => [], 'products' => [], 'customers' => [], 'shortcuts' => []]
        ]);
        exit;
    }

    $results = [
        'orders' => searchOrders($conn, $query, $limit),
        'products' => searchProducts($conn, $query, $limit),
        'customers' => searchCustomers($conn, $query, $limit),
        'shortcuts' => searchShortcuts($query)
    ];

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
} catch (Throwable $error) {
    http_response_code($error->getCode() === 403 ? 403 : 500);
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
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        throw new RuntimeException('Acceso restringido', 403);
    }
}

function searchOrders(PDO $conn, string $term, int $limit): array
{
    $like = '%' . escapeLikeTerm($term) . '%';
    $isNumeric = ctype_digit($term);

    $sql = "SELECT o.id, o.order_number, o.status, o.payment_status, o.total, o.created_at,
                COALESCE(u.name, u.email, 'Cliente sin nombre') AS customer_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.order_number LIKE :term
               OR u.name LIKE :term
               OR u.email LIKE :term
               OR o.status LIKE :term";

    if ($isNumeric) {
        $sql .= ' OR o.id = :order_id';
    }

    $sql .= ' ORDER BY o.created_at DESC LIMIT :limit';

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':term', $like, PDO::PARAM_STR);
    if ($isNumeric) {
        $stmt->bindValue(':order_id', (int)$term, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $baseUrl = rtrim(BASE_URL, '/');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(static function (array $row) use ($baseUrl): array {
        $orderNumber = $row['order_number'] ?: str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT);
        $title = 'Orden #' . $orderNumber;
        $subtitle = sprintf(
            '%s - %s - %s',
            $row['customer_name'] ?? 'Cliente',
            formatCop((float)($row['total'] ?? 0)),
            strtoupper((string)$row['status'])
        );

        return [
            'id' => (int)$row['id'],
            'title' => $title,
            'subtitle' => $subtitle,
            'url' => $baseUrl . '/admin/order/detail.php?id=' . $row['id']
        ];
    }, $rows);
}

function searchProducts(PDO $conn, string $term, int $limit): array
{
    $like = '%' . escapeLikeTerm($term) . '%';
    $sql = "SELECT p.id, p.name, p.slug, p.created_at, p.is_active,
                c.name AS category,
                COALESCE(SUM(psv.quantity), 0) AS stock
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_color_variants pcv ON pcv.product_id = p.id
            LEFT JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
            WHERE p.name LIKE :term OR p.slug LIKE :term
            GROUP BY p.id, p.name, p.slug, p.created_at, p.is_active, c.name
            ORDER BY p.created_at DESC
            LIMIT :limit";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':term', $like, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $baseUrl = rtrim(BASE_URL, '/');

    return array_map(static function (array $row) use ($baseUrl): array {
        $subtitle = sprintf(
            '%s - Stock: %s',
            $row['category'] ? 'Categoria ' . $row['category'] : 'Sin categoria',
            number_format((int)$row['stock'])
        );

        return [
            'id' => (int)$row['id'],
            'title' => $row['name'] ?? 'Producto',
            'subtitle' => $subtitle,
            'url' => $baseUrl . '/admin/products.php?highlight=' . $row['id']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function searchCustomers(PDO $conn, string $term, int $limit): array
{
    $like = '%' . escapeLikeTerm($term) . '%';
    $sql = "SELECT id, name, email, phone, created_at
            FROM users
            WHERE role IN ('customer', 'user')
              AND (name LIKE :term OR email LIKE :term OR phone LIKE :term";

    $params = [':term' => $like];
    if ($term !== '') {
        $sql .= ' OR id = :user_id';
        $params[':user_id'] = $term;
    }
    $sql .= ') ORDER BY created_at DESC LIMIT :limit';

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $baseUrl = rtrim(BASE_URL, '/');

    return array_map(static function (array $row) use ($baseUrl): array {
        $subtitle = sprintf(
            '%s - %s',
            $row['email'] ?? 'Sin correo',
            $row['phone'] ?: 'Sin telefono'
        );

        return [
            'id' => $row['id'],
            'title' => $row['name'] ?: 'Cliente sin nombre',
            'subtitle' => $subtitle,
            'url' => $baseUrl . '/admin/clientes/index.php?highlight=' . $row['id']
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function searchShortcuts(string $term): array
{
    $term = mb_strtolower($term);
    $baseUrl = rtrim(BASE_URL, '/');

    $matches = array_filter(getAdminSearchShortcuts(), static function (array $shortcut) use ($term): bool {
        $haystack = strtolower(($shortcut['label'] ?? '') . ' ' . ($shortcut['description'] ?? ''));
        $keywords = isset($shortcut['keywords']) ? implode(' ', (array)$shortcut['keywords']) : '';
        return strpos($haystack, $term) !== false || strpos(strtolower($keywords), $term) !== false;
    });

    return array_values(array_map(static function (array $shortcut) use ($baseUrl): array {
        return [
            'id' => $shortcut['id'],
            'title' => $shortcut['label'],
            'subtitle' => $shortcut['description'] ?? '',
            'url' => $baseUrl . $shortcut['path']
        ];
    }, $matches));
}

function escapeLikeTerm(string $term): string
{
    return str_replace(['%', '_'], ['\\%', '\\_'], $term);
}

function formatCop(float $value): string
{
    return '$' . number_format($value, 0, ',', '.');
}
