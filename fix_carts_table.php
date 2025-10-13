<?php
require_once 'conexion.php';

try {
    echo "=== ESTRUCTURA DE LA TABLA CARTS ===\n\n";
    $stmt = $conn->query('DESCRIBE carts');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($cols as $col) {
        echo "{$col['Field']} - {$col['Type']} - NULL:{$col['Null']} - Default:{$col['Default']}\n";
    }
    
    echo "\n=== MODIFICAR TABLA CARTS ===\n";
    echo "Permitiendo user_id NULL para carritos de sesión...\n";
    
    $conn->exec("ALTER TABLE carts MODIFY COLUMN user_id VARCHAR(50) NULL");
    
    echo "✓ Tabla carts modificada correctamente\n";
    echo "  - Ahora user_id puede ser NULL para sesiones anónimas\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
