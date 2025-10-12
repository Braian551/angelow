<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

try {
    // Verificar si la tabla existe
    $stmt = $conn->query("SHOW TABLES LIKE 'order_views'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'message' => 'La tabla order_views no existe. Ejecuta la migración primero.'
        ]);
        exit;
    }

    // Contar registros
    $stmt = $conn->query("SELECT COUNT(*) as total FROM order_views");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'message' => 'Tabla encontrada',
        'count' => (int)$count
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
