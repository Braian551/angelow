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
    
    // Verificar que la colección existe
    $checkSql = "SELECT * FROM collections WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$collectionId]);
    $collection = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$collection) {
        throw new Exception('Colección no encontrada');
    }
    
    // Verificar si hay productos asociados (tabla pivote y referencia directa)
    $pivotStmt = $conn->prepare("SELECT COUNT(*) FROM product_collections WHERE collection_id = ?");
    $pivotStmt->execute([$collectionId]);
    $pivotCount = (int) $pivotStmt->fetchColumn();

    $directStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE collection_id = ?");
    $directStmt->execute([$collectionId]);
    $directCount = (int) $directStmt->fetchColumn();

    if (($pivotCount + $directCount) > 0) {
        throw new Exception('No se puede eliminar la colección porque está asociada a productos.');
    }
    
    // Eliminar imagen si existe
    if (!empty($collection['image'])) {
        $imagePath = __DIR__ . '/../../../' . $collection['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Eliminar colección
    $deleteSql = "DELETE FROM collections WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->execute([$collectionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Colección eliminada exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
