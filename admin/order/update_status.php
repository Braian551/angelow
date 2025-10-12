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
$notes = isset($data['notes']) ? $data['notes'] : null;

// Validar estado
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit();
}

// Función para obtener la IP real del usuario
function getRealUserIP() {
    // Verificar diferentes headers que pueden contener la IP real
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Puede contener múltiples IPs, obtener la primera
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Normalizar IPv6 localhost a un formato más legible
    if ($ip === '::1') {
        $ip = '127.0.0.1 (localhost)';
    } elseif ($ip === '127.0.0.1' || strpos($ip, '127.0.') === 0) {
        $ip = $ip . ' (localhost)';
    }
    
    return $ip;
}

// Actualizar estado de las órdenes
try {
    // Obtener información del usuario actual
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
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
    
    // Actualizar cada orden individualmente para que los triggers funcionen correctamente
    foreach ($orderIds as $orderId) {
        // Obtener el estado actual antes de actualizar
        $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentOrder && $currentOrder['status'] !== $newStatus) {
            // Actualizar el estado de la orden (el trigger registrará el cambio)
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            
            if ($stmt->rowCount() > 0) {
                $affectedRows++;
                
                // Si hay notas adicionales, registrarlas como un cambio separado
                if ($notes && trim($notes) !== '') {
                    $stmt = $conn->prepare("
                        INSERT INTO order_status_history 
                        (order_id, changed_by, changed_by_name, change_type, field_changed, description, ip_address, created_at)
                        VALUES (?, ?, ?, 'notes', 'admin_notes', ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $orderId,
                        $currentUser['id'],
                        $currentUser['name'],
                        "Nota del administrador: " . $notes,
                        $userIp
                    ]);
                }
            }
        }
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Limpiar variables de sesión MySQL
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = NULL");
    $conn->exec("SET @current_user_ip = NULL");
    
    if ($affectedRows > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Estado de $affectedRows orden(es) actualizado correctamente"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron órdenes para actualizar o ya tenían ese estado'
        ]);
    }
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Limpiar variables de sesión MySQL
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = NULL");
    $conn->exec("SET @current_user_ip = NULL");
    
    error_log("Error al actualizar estado de órdenes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estado de las órdenes: ' . $e->getMessage()
    ]);
}