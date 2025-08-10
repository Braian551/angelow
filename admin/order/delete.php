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

if (!isset($data['order_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$orderIds = $data['order_ids'];

// Eliminar órdenes (en producción sería mejor un borrado lógico)
try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // 1. Eliminar items de las órdenes
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);
    
    // 2. Eliminar transacciones de pago
    $stmt = $conn->prepare("DELETE FROM payment_transactions WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);
    
    // 3. Eliminar las órdenes
    $stmt = $conn->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
    $stmt->execute($orderIds);
    
    $affectedRows = $stmt->rowCount();
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$affectedRows órdenes eliminadas correctamente"
    ]);
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $conn->rollBack();
    error_log("Error al eliminar órdenes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar las órdenes'
    ]);
}