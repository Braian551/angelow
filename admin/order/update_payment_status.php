<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/order_notification_service.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Check auth
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error permisos']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['order_ids']) || !isset($data['new_payment_status'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$orderIds = $data['order_ids'];
$newStatus = $data['new_payment_status'];
$notes = $data['notes'] ?? null;

$valid = ['pending', 'paid', 'failed', 'refunded'];
if (!in_array($newStatus, $valid)) {
    echo json_encode(['success' => false, 'message' => 'Estado de pago no válido']);
    exit();
}

// Set session variables for triggers
try {
    // Find current user
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        $conn->exec("SET @current_user_id = NULL");
        $conn->exec("SET @current_user_name = 'Usuario desconocido'");
    } else {
        $conn->exec("SET @current_user_id = " . $conn->quote($currentUser['id']));
        $conn->exec("SET @current_user_name = " . $conn->quote($currentUser['name']));
    }
    $conn->exec("SET @current_user_ip = " . $conn->quote($_SERVER['REMOTE_ADDR']));

    $conn->beginTransaction();

    $affected = 0;
    $ordersToConfirmRefund = [];
    $notifications = [];
    foreach ($orderIds as $orderId) {
        $stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) continue;
        if ($row['payment_status'] === $newStatus) continue;

        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        if ($stmt->rowCount() > 0) {
            $affected++;
            // Insert into history if available
            try {
                $stmtH = $conn->prepare("INSERT INTO order_status_history (order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address, created_at) VALUES (?, ?, ?, 'payment_status', 'payment_status', ?, ?, ?, ?, NOW())");
                $stmtH->execute([$orderId, $currentUser['id'] ?? null, $currentUser['name'] ?? 'Sistema', $row['payment_status'], $newStatus, 'Cambio de estado de pago', $_SERVER['REMOTE_ADDR']]);
            } catch (PDOException $e) {
                error_log('Error saving history payment status: ' . $e->getMessage());
            }
            if ($notes) {
                $stmtNotes = $conn->prepare("INSERT INTO order_status_history (order_id, changed_by, changed_by_name, change_type, field_changed, description, ip_address, created_at) VALUES (?, ?, ?, 'notes', 'admin_notes', ?, ?, NOW())");
                $stmtNotes->execute([$orderId, $currentUser['id'] ?? null, $currentUser['name'] ?? 'Sistema', $notes, $_SERVER['REMOTE_ADDR']]);
            }

            if ($newStatus === 'refunded') {
                $ordersToConfirmRefund[] = (int) $orderId;
            } else {
                try {
                    $ok = createPaymentNotification($conn, (int)$orderId, $newStatus);
                    $notifications[] = [
                        'order_id' => (int)$orderId,
                        'ok' => $ok,
                        'message' => $ok ? 'Notificación de pago creada' : 'No fue posible crear la notificación de pago'
                    ];
                } catch (Throwable $e) {
                    error_log('[ORDER_NOTIFY] Error al crear notificación de pago: ' . $e->getMessage());
                    $notifications[] = ['order_id' => (int)$orderId, 'ok' => false, 'message' => 'Excepción al crear notificación: ' . $e->getMessage()];
                }
            }
        }
    }

    $conn->commit();
    $refundSummary = '';
        if (!empty($ordersToConfirmRefund)) {
        $successRefunds = 0;
        $failedRefunds = 0;
        foreach ($ordersToConfirmRefund as $refundOrderId) {
            $adminId = isset($currentUser['id']) ? (int)$currentUser['id'] : null;
            $result = notifyRefundCompleted($conn, $refundOrderId, [
                'admin_id' => $adminId
            ]);
            if ($result['ok']) {
                $successRefunds++;
            } else {
                $failedRefunds++;
                error_log('[ORDER_NOTIFY] Error notifyRefundCompleted order ' . $refundOrderId . ': ' . $result['message']);
            }
                // Add notification result to response so client can show it
                $notifications[] = [
                    'order_id' => (int)$refundOrderId,
                    'ok' => $result['ok'] ?? false,
                    'message' => $result['message'] ?? null
                ];
        }
        $refundSummary = ' · Reembolsos confirmados: ' . $successRefunds;
        if ($failedRefunds > 0) {
            $refundSummary .= " · Fallidos: $failedRefunds";
        }
    }

    echo json_encode(['success' => true, 'message' => "Estados de pago actualizados: $affected$refundSummary", 'notifications' => array_values($notifications)]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('ERR update_payment_status: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
