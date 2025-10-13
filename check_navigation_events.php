<?php
require_once 'conexion.php';

try {
    // Ver estructura de navigation_events
    $stmt = $conn->query("DESCRIBE navigation_events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estructura de navigation_events:\n\n";
    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} {$col['Null']} {$col['Key']} {$col['Default']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
