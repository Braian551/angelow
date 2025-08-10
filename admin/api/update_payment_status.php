<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    // Verificar rol de administrador
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos']);
    exit();
}

// Obtener datos del POST
$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$newPaymentStatus = isset($_POST['new_payment_status']) ? $_POST['new_payment_status'] : '';
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$paymentNotes = isset($_POST['payment_notes']) ? $_POST['payment_notes'] : '';

// Validar datos
if ($orderId <= 0 || !in_array($newPaymentStatus, ['pending', 'paid', 'failed'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit();
}

try {
    // Actualizar estado de pago en la base de datos
    $updateQuery = "UPDATE orders SET 
        payment_status = ?,
        payment_method = ?,
        updated_at = NOW()
        WHERE id = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$newPaymentStatus, $paymentMethod, $orderId]);

    // Registrar el cambio en el historial de pagos
    $historyQuery = "INSERT INTO payment_history 
        (order_id, status, method, notes, changed_by)
        VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($historyQuery);
    $stmt->execute([
        $orderId,
        $newPaymentStatus,
        $paymentMethod,
        $paymentNotes,
        $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Estado de pago actualizado correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el estado de pago: ' . $e->getMessage()]);
}
?>