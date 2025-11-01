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
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit();
    }

    $stmt = $conn->prepare('UPDATE sliders SET is_active = ? WHERE id = ?');
    $stmt->execute([$is_active, $id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('toggle_active error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
}
ob_end_flush();
