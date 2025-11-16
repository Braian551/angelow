<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

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
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => "Estados de pago actualizados: $affected"]); 
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('ERR update_payment_status: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
