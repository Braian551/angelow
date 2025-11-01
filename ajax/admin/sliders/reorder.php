<?php
ob_start();
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['order']) || !is_array($data['order'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    $conn->beginTransaction();
    $stmt = $conn->prepare('UPDATE sliders SET order_position = ? WHERE id = ?');
    foreach ($data['order'] as $item) {
        $stmt->execute([$item['order_position'], $item['id']]);
    }
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('reorder error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al reordenar']);
}
ob_end_flush();
