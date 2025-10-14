<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

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
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Orden no encontrada');
    }

    if (!in_array($order['status'], ['pending', 'processing'])) {
        throw new Exception('Esta orden no puede ser cancelada');
    }

    // Actualizar status
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$orderId]);

    // Log (opcional: inserta en una tabla de logs si existe)
    // $stmtLog = $conn->prepare("INSERT INTO order_logs (order_id, action) VALUES (?, 'cancelled by user')");
    // $stmtLog->execute([$orderId]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Orden cancelada exitosamente']);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error al cancelar orden: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}