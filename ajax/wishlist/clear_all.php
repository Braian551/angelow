<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'not_logged_in',
        'message' => 'Debes iniciar sesión'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Eliminar todos los productos de la wishlist del usuario
    $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = :user_id");
    $delete->execute([':user_id' => $userId]);

    $deletedCount = $delete->rowCount();

    echo json_encode([
        'success' => true, 
        'message' => $deletedCount > 0 
            ? "Se eliminaron $deletedCount producto(s) de tu lista de deseos" 
            : 'Tu lista de deseos ya estaba vacía',
        'deleted_count' => $deletedCount
    ]);

} catch (PDOException $e) {
    error_log("Error clearing wishlist: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al limpiar la lista de deseos'
    ]);
}
