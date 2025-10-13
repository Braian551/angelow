<?php
require_once 'conexion.php';

try {
    // Verificar el tipo de dato de driver_id en order_deliveries
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'order_deliveries'
        AND COLUMN_NAME = 'driver_id'
    ");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Tipo de dato de driver_id en order_deliveries:\n";
    echo "  - DATA_TYPE: {$column['DATA_TYPE']}\n";
    echo "  - COLUMN_TYPE: {$column['COLUMN_TYPE']}\n\n";
    
    // Verificar el tipo de dato de driver_id en users
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'id'
    ");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Tipo de dato de id en users:\n";
    echo "  - DATA_TYPE: {$column['DATA_TYPE']}\n";
    echo "  - COLUMN_TYPE: {$column['COLUMN_TYPE']}\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
