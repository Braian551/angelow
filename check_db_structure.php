<?php
require_once 'conexion.php';

try {
    echo "=== TABLAS ===\n\n";
    $stmt = $conn->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo $table . "\n";
    }
    
    echo "\n\n=== PROCEDIMIENTOS ALMACENADOS ===\n\n";
    $stmt = $conn->query('SHOW PROCEDURE STATUS WHERE Db = "angelow"');
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($procedures as $proc) {
        echo $proc['Name'] . "\n";
    }
    
    echo "\n\n=== ESTRUCTURA DE products ===\n\n";
    $stmt = $conn->query('DESCRIBE products');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    echo "\n\n=== ESTRUCTURA DE cart_items ===\n\n";
    $stmt = $conn->query('DESCRIBE cart_items');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
