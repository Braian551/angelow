<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y rol de admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

try {
    $collectionId = $_POST['id'] ?? null;
    
    if (!$collectionId) {
        throw new Exception('ID de colección no proporcionado');
    }
    
    // Obtener estado actual
    $checkSql = "SELECT is_active FROM collections WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$collectionId]);
    $collection = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$collection) {
        throw new Exception('Colección no encontrada');
    }
    
    // Cambiar el estado
    $newStatus = $collection['is_active'] ? 0 : 1;
    
    $updateSql = "UPDATE collections SET is_active = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$newStatus, $collectionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado exitosamente',
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
