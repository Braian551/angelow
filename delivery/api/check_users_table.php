<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: text/plain');

echo "=== COLUMNAS DE LA TABLA USERS ===\n\n";

try {
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n\n";
    
    $stmt = $conn->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        foreach ($user as $key => $value) {
            echo "$key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
