<?php
// Iniciar la sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Log para debugging
error_log("BULK_UPDATE_STATUS.PHP - Usuario en sesión: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO'));
error_log("BULK_UPDATE_STATUS.PHP - Método: " . $_SERVER['REQUEST_METHOD']);

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

    error_log("BULK_UPDATE_STATUS.PHP - Role del usuario: " . ($user ? $user['role'] : 'NO ENCONTRADO'));

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

error_log("BULK_UPDATE_STATUS.PHP - Datos recibidos: " . json_encode($data));

if (!isset($data['order_ids']) || !is_array($data['order_ids']) || empty($data['order_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar IDs de órdenes válidos']);
    exit();
}

if (!isset($data['new_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe especificar el nuevo estado']);
    exit();
}

$orderIds = array_map('intval', $data['order_ids']); // Sanitizar IDs
$newStatus = trim($data['new_status']);
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validar estado
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estado no válido: ' . $newStatus]);
    exit();
}

// Función para obtener la IP real del usuario
function getRealUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    if ($ip === '::1') {
        $ip = '127.0.0.1 (localhost)';
    } elseif ($ip === '127.0.0.1' || strpos($ip, '127.0.') === 0) {
        $ip = $ip . ' (localhost)';
    }
    
    return $ip;
}

// Actualizar estado de órdenes en masa
try {
    // Obtener información del usuario actual
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Obtener IP del usuario
    $userIp = getRealUserIP();
    
    // Establecer variables de sesión MySQL para los triggers
    $conn->exec("SET @current_user_id = {$currentUser['id']}");
    $conn->exec("SET @current_user_name = " . $conn->quote($currentUser['name']));
    $conn->exec("SET @current_user_ip = " . $conn->quote($userIp));
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    $affectedRows = 0;
    $skippedOrders = 0;
    $updatedOrders = [];
    
    // Actualizar cada orden individualmente para que los triggers funcionen correctamente
    foreach ($orderIds as $orderId) {
        // Obtener el estado actual antes de actualizar
        $stmt = $conn->prepare("SELECT id, order_number, status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentOrder) {
            error_log("BULK_UPDATE - Orden $orderId no encontrada");
            $skippedOrders++;
            continue;
        }
        
        if ($currentOrder['status'] === $newStatus) {
            error_log("BULK_UPDATE - Orden {$currentOrder['order_number']} ya tiene el estado $newStatus");
            $skippedOrders++;
            continue;
        }
        
        // Actualizar el estado de la orden (el trigger registrará el cambio)
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            $affectedRows++;
            $updatedOrders[] = $currentOrder['order_number'];
            
            // Si hay notas adicionales, registrarlas como un cambio separado
            if ($notes && trim($notes) !== '') {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO order_status_history 
                        (order_id, changed_by, changed_by_name, change_type, field_changed, description, ip_address, created_at)
                        VALUES (?, ?, ?, 'notes', 'admin_notes', ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $orderId,
                        $currentUser['id'],
                        $currentUser['name'],
                        "Nota de actualización masiva: " . $notes,
                        $userIp
                    ]);
                } catch (PDOException $e) {
                    // Si la tabla no existe, continuar sin error
                    error_log("No se pudo insertar nota en historial: " . $e->getMessage());
                }
            }
            
            error_log("BULK_UPDATE - Orden {$currentOrder['order_number']} actualizada de {$currentOrder['status']} a $newStatus");
        }
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Limpiar variables de sesión MySQL
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = NULL");
    $conn->exec("SET @current_user_ip = NULL");
    
    // Traducir estado para el mensaje
    $statusLabels = [
        'pending' => 'Pendiente',
        'processing' => 'En proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado'
    ];
    
    $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
    
    if ($affectedRows > 0) {
        $message = "$affectedRows " . ($affectedRows === 1 ? 'orden actualizada' : 'órdenes actualizadas') . " a estado: $statusLabel";
        if ($skippedOrders > 0) {
            $message .= " ($skippedOrders " . ($skippedOrders === 1 ? 'omitida' : 'omitidas') . " por tener el mismo estado)";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updated' => $affectedRows,
            'skipped' => $skippedOrders,
            'order_numbers' => $updatedOrders
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se actualizó ninguna orden. Todas las órdenes ya tenían el estado: ' . $statusLabel,
            'updated' => 0,
            'skipped' => $skippedOrders
        ]);
    }
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Limpiar variables de sesión MySQL
    try {
        $conn->exec("SET @current_user_id = NULL");
        $conn->exec("SET @current_user_name = NULL");
        $conn->exec("SET @current_user_ip = NULL");
    } catch (Exception $cleanupError) {
        error_log("Error al limpiar variables de sesión: " . $cleanupError->getMessage());
    }
    
    error_log("Error al actualizar estado de órdenes en masa: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estado de las órdenes: ' . $e->getMessage()
    ]);
}
