<?php
ob_start();
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

// Admin permission check
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['error' => 'Permisos insuficientes']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al verificar permisos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Get POST data
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$newStatus = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if (!$orderId || !$newStatus) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

try {
    // Verify order exists
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit();
    }

    // Update payment status
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // Add payment status history
    $stmt = $conn->prepare("
        INSERT INTO payment_status_history 
        (order_id, status, notes, admin_id) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $orderId,
        $newStatus,
        $notes,
        $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Error en updatepaymentstatus.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al actualizar estado de pago']);
}
ob_end_flush();
?>