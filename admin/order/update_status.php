<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar esta acción']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al verificar permisos']);
    exit();
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_ids']) || !isset($data['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$orderIds = $data['order_ids'];
$newStatus = $data['new_status'];

// Validar estado
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit();
}

// Actualizar estado de las órdenes
try {
    // Crear marcadores de posición para la consulta IN
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    // Los parámetros son: nuevo estado + IDs de órdenes
    $params = array_merge([$newStatus], $orderIds);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Estado de $affectedRows órdenes actualizado correctamente"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron órdenes para actualizar'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error al actualizar estado de órdenes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estado de las órdenes'
    ]);
}