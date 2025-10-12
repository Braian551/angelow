<?php
// Iniciar la sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Log para debugging
error_log("BULK_DELETE.PHP - Usuario en sesión: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO'));
error_log("BULK_DELETE.PHP - Método: " . $_SERVER['REQUEST_METHOD']);

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar esta acción']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("BULK_DELETE.PHP - Role del usuario: " . ($user ? $user['role'] : 'NO ENCONTRADO'));

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar permisos']);
    exit();
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

error_log("BULK_DELETE.PHP - Datos recibidos: " . json_encode($data));

if (!isset($data['order_ids']) || !is_array($data['order_ids']) || empty($data['order_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar IDs de órdenes válidos']);
    exit();
}

$orderIds = array_map('intval', $data['order_ids']); // Sanitizar IDs

// Eliminar órdenes en masa
try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Preparar placeholders para la consulta IN
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    // 1. Eliminar items de las órdenes
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);
    $itemsDeleted = $stmt->rowCount();
    
    // 2. Eliminar transacciones de pago
    $stmt = $conn->prepare("DELETE FROM payment_transactions WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);
    $paymentsDeleted = $stmt->rowCount();
    
    // 3. Eliminar historial de estados (si existe)
    try {
        $stmt = $conn->prepare("DELETE FROM order_status_history WHERE order_id IN ($placeholders)");
        $stmt->execute($orderIds);
        $historyDeleted = $stmt->rowCount();
    } catch (PDOException $e) {
        // Si la tabla no existe, continuar sin error
        $historyDeleted = 0;
        error_log("Tabla order_status_history no existe o error: " . $e->getMessage());
    }
    
    // 4. Eliminar las órdenes
    $stmt = $conn->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
    $stmt->execute($orderIds);
    $ordersDeleted = $stmt->rowCount();
    
    // Confirmar transacción
    $conn->commit();
    
    error_log("BULK_DELETE - Eliminadas: $ordersDeleted órdenes, $itemsDeleted items, $paymentsDeleted pagos, $historyDeleted registros de historial");
    
    echo json_encode([
        'success' => true,
        'message' => "$ordersDeleted " . ($ordersDeleted === 1 ? 'orden eliminada' : 'órdenes eliminadas') . " correctamente",
        'deleted' => [
            'orders' => $ordersDeleted,
            'items' => $itemsDeleted,
            'payments' => $paymentsDeleted,
            'history' => $historyDeleted
        ]
    ]);
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error al eliminar órdenes en masa: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar las órdenes: ' . $e->getMessage()
    ]);
}
