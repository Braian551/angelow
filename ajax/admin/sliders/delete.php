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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
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

    // Obtener imagen para eliminar el archivo físico (opcional)
    $stmt = $conn->prepare('SELECT image FROM sliders WHERE id = ?');
    $stmt->execute([$id]);
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($slider && !empty($slider['image'])) {
        $filePath = __DIR__ . '/../../../' . $slider['image'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    $del = $conn->prepare('DELETE FROM sliders WHERE id = ?');
    $del->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('delete slider error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
}
ob_end_flush();
