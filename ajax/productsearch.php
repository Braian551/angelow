<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Debes iniciar sesión para acceder a esta página']);
    exit();
}

// Obtener información del usuario
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

  
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Error al verificar permisos. Por favor intenta nuevamente.']);
    exit();
}

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$paymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$paymentStatus = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15; // Número de pedidos por página

// Construir consulta base
$query = "SELECT 
    o.id, 
    o.order_number, 
    CONCAT(u.name) AS user_name,
    o.created_at,
    o.total,
    o.status,
    o.payment_method,
    o.payment_status
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
WHERE 1=1";

$params = [];
$types = '';

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($paymentMethod)) {
    $query .= " AND o.payment_method = ?";
    $params[] = $paymentMethod;
    $types .= 's';
}

if (!empty($paymentStatus)) {
    $query .= " AND o.payment_status = ?";
    $params[] = $paymentStatus;
    $types .= 's';
}

if (!empty($fromDate)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $fromDate;
    $types .= 's';
}

if (!empty($toDate)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $toDate;
    $types .= 's';
}

// Contar total de resultados
$countQuery = "SELECT COUNT(*) as total FROM ($query) AS subquery";
$stmt = $conn->prepare($countQuery);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$totalOrders = $stmt->fetchColumn();

// Aplicar paginación
$offset = ($page - 1) * $perPage;
$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

// Ejecutar consulta final
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear respuesta
$response = [
    'success' => true,
    'orders' => $orders,
    'meta' => [
        'total' => $totalOrders,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($totalOrders / $perPage)
    ]
];

header('Content-Type: application/json');
echo json_encode($response);