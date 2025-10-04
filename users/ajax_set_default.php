<?php
require_once __DIR__ . '/../config.php';
// Intentar incluir la conexión; si falla devolveremos JSON controlado
try {
    require_once __DIR__ . '/../conexion.php';
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    error_log('Error incluyendo conexion.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de servidor (conexion)']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

try {
    if (!isset($conn) || !$conn) {
        throw new Exception('Sin conexión a la base de datos');
    }

    // Verificar que la dirección pertenece al usuario
    $stmt = $conn->prepare('SELECT id FROM user_addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$address) {
        echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
        exit();
    }

    // Desmarcar todas y marcar la seleccionada
    $conn->beginTransaction();
    $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    $stmt = $conn->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Dirección establecida como principal']);
    exit();
} catch (Exception $e) {
    // Rollback si es necesario
    try {
        if (isset($conn) && $conn && $conn->inTransaction()) $conn->rollBack();
    } catch (Throwable $te) {
        // ignorar
    }
    error_log('Error AJAX set_default: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . ($e->getMessage())]);
    exit();
}
