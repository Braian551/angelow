<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../admin/order/order_notification_service.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$orderId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $conn->beginTransaction();

    // Verificar que la orden sea del usuario y cancelable
    $stmt = $conn->prepare("SELECT status, payment_status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Orden no encontrada');
    }

    if (!in_array($order['status'], ['pending', 'processing'])) {
        throw new Exception('Esta orden no puede ser cancelada');
    }

    $currentPaymentStatus = $order['payment_status'] ?: 'pending';
    $newPaymentStatus = $currentPaymentStatus;
    if (in_array($currentPaymentStatus, ['paid', 'pending'], true)) {
        $newPaymentStatus = 'refunded';
    }

    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', payment_status = :payment_status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':payment_status' => $newPaymentStatus,
        ':id' => $orderId,
    ]);

    $conn->commit();

    $notificationResult = notifyOrderCancelled($conn, $orderId, 'user');
    $responseMessage = $notificationResult['ok']
        ? 'Pedido cancelado. Iniciaremos el reembolso y te enviaremos un correo con los detalles.'
        : 'Pedido cancelado. ' . ($notificationResult['message'] ?? 'No fue posible enviar las notificaciones.');

    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'status' => 'cancelled',
        'payment_status' => $newPaymentStatus,
    ]);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error al cancelar orden: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}