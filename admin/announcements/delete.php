<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    // Obtener imagen para eliminarla
    $stmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();

    // Eliminar el anuncio
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);

    // Eliminar imagen si existe
    if ($image && file_exists(__DIR__ . '/../../' . $image)) {
        unlink(__DIR__ . '/../../' . $image);
    }

    $_SESSION['alert'] = [
        'message' => 'Anuncio eliminado exitosamente.',
        'type' => 'success'
    ];

    echo json_encode(['success' => true, 'message' => 'Anuncio eliminado exitosamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
