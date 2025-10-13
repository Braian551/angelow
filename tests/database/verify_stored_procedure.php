<?php
require_once 'conexion.php';

try {
    // Verificar si el procedimiento StartNavigation existe
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM information_schema.ROUTINES
        WHERE ROUTINE_SCHEMA = DATABASE()
        AND ROUTINE_TYPE = 'PROCEDURE'
        AND ROUTINE_NAME = 'StartNavigation'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "✓ El procedimiento StartNavigation existe en la base de datos.\n\n";
        
        // Mostrar los parámetros del procedimiento
        $stmt = $conn->prepare("
            SELECT PARAMETER_NAME, DATA_TYPE, PARAMETER_MODE
            FROM information_schema.PARAMETERS
            WHERE SPECIFIC_NAME = 'StartNavigation'
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute();
        $params = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Parámetros del procedimiento:\n";
        foreach ($params as $param) {
            echo "  - {$param['PARAMETER_MODE']} {$param['PARAMETER_NAME']} ({$param['DATA_TYPE']})\n";
        }
    } else {
        echo "✗ El procedimiento StartNavigation NO existe en la base de datos.\n";
        echo "Ejecuta la migración 007_EJECUTAR_DIRECTAMENTE.sql para crearlo.\n";
    }
    
    echo "\n";
    
    // Verificar la tabla navigation_events
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'navigation_events'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "✓ La tabla navigation_events existe.\n";
    } else {
        echo "✗ La tabla navigation_events NO existe.\n";
    }
    
    // Verificar columnas necesarias en order_deliveries
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'order_deliveries'
        AND COLUMN_NAME IN ('navigation_started_at', 'navigation_route', 'route_distance', 'route_duration', 'distance_remaining', 'eta_seconds')
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['navigation_started_at', 'navigation_route', 'route_distance', 'route_duration', 'distance_remaining', 'eta_seconds'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "✓ Todas las columnas necesarias existen en order_deliveries.\n";
    } else {
        echo "✗ Faltan columnas en order_deliveries: " . implode(', ', $missingColumns) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
