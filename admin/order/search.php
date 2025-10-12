<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Configurar manejadores de errores
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    error_log("Excepción no capturada: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
    exit();
});

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para acceder a esta página']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para acceder a esta área']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al verificar permisos. Por favor intenta nuevamente.']);
    exit();
}

try {
    // Obtener parámetros de búsqueda
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $paymentStatus = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
    $paymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;

    // Construir consulta base
    $query = "SELECT 
        o.id, 
        o.order_number, 
        u.name AS user_name,
        u.email AS user_email,
        o.created_at,
        o.total,
        o.status,
        o.payment_method,
        o.payment_status
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($search)) {
        $query .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $query .= " AND o.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($paymentStatus)) {
        $query .= " AND o.payment_status = :payment_status";
        $params[':payment_status'] = $paymentStatus;
    }

    if (!empty($paymentMethod)) {
        $query .= " AND o.payment_method = :payment_method";
        $params[':payment_method'] = $paymentMethod;
    }

    if (!empty($fromDate)) {
        $query .= " AND DATE(o.created_at) >= :from_date";
        $params[':from_date'] = $fromDate;
    }

    if (!empty($toDate)) {
        $query .= " AND DATE(o.created_at) <= :to_date";
        $params[':to_date'] = $toDate;
    }

    // Contar total de resultados (sin LIMIT)
    $countQuery = "SELECT COUNT(*) as total FROM ($query) AS subquery";
    $stmt = $conn->prepare($countQuery);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $totalOrders = $stmt->fetchColumn();

    // Aplicar paginación - usando valores literales en lugar de parámetros
    $offset = ($page - 1) * $perPage;
    $query .= " ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset";

    // Ejecutar consulta final
    $stmt = $conn->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear respuesta
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'meta' => [
            'total' => $totalOrders,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalOrders / $perPage)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en búsqueda de órdenes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor al buscar órdenes: ' . $e->getMessage()]);
    exit();
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()]);
    exit();
}