<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../auth/role_redirect.php';
require_once __DIR__ . '/../../services/admin_profiles.php';

header('Content-Type: application/json');

requireRole('admin');
ensure_admin_profiles_table($conn);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'active';
$department = trim($_GET['department'] ?? '');

try {
    $conditions = ["u.role = 'admin'"];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($department !== '') {
        $conditions[] = 'ap.department = :department';
        $params['department'] = $department;
    }

    switch ($status) {
        case 'blocked':
            $conditions[] = 'u.is_blocked = 1';
            break;
        case 'all':
            break;
        default:
            $conditions[] = '(u.is_blocked = 0 OR u.is_blocked IS NULL)';
            break;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $sql = "SELECT u.id, u.name, u.email, u.phone, u.image, u.created_at, u.last_access, u.is_blocked,
            ap.job_title, ap.department, ap.responsibilities, ap.emergency_contact
        FROM users u
        LEFT JOIN admin_profiles ap ON ap.user_id = u.id
        $where
        ORDER BY COALESCE(u.last_access, u.created_at) DESC";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = formatAdminRow($row);
    }

    $summary = adminSummary($conn);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'summary' => $summary
    ]);
} catch (Throwable $e) {
    error_log('admins.list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el equipo administrativo']);
}

function formatAdminRow(array $row): array {
    $lastAccess = $row['last_access'] ? date('Y-m-d H:i:s', strtotime($row['last_access'])) : null;
    return [
        'id' => $row['id'],
        'name' => $row['name'] ?? $row['email'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'image' => $row['image'],
        'created_at' => $row['created_at'],
        'last_access' => $lastAccess,
        'is_blocked' => (bool) $row['is_blocked'],
        'job_title' => $row['job_title'],
        'department' => $row['department'],
        'responsibilities' => $row['responsibilities'],
        'emergency_contact' => $row['emergency_contact']
    ];
}

function adminSummary(PDO $conn): array {
    $total = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $active = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND (is_blocked = 0 OR is_blocked IS NULL)")->fetchColumn();
    $blocked = $total - $active;
    $recent = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND last_access >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

    return [
        'total' => $total,
        'active' => $active,
        'blocked' => max(0, $blocked),
        'recent_access' => $recent
    ];
}
