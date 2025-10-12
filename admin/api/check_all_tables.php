<?php
// Verificar todas las tablas de la base de datos
require_once __DIR__ . '/../../config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Tablas en la Base de Datos 'angelow'</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
</style>";

try {
    // Obtener todas las tablas
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3 class='success'>✓ Tablas encontradas: " . count($tables) . "</h3>";
    echo "<table>";
    echo "<tr><th>#</th><th>Nombre de Tabla</th><th>Registros</th></tr>";
    
    $i = 1;
    foreach ($tables as $table) {
        // Contar registros
        $countStmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        $count = $countStmt->fetchColumn();
        
        echo "<tr>";
        echo "<td>" . $i++ . "</td>";
        echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
        echo "<td>" . number_format($count) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Buscar tablas relacionadas con productos
    echo "<hr><h3>Tablas relacionadas con Productos:</h3>";
    $productTables = array_filter($tables, function($table) {
        return stripos($table, 'product') !== false || 
               stripos($table, 'variant') !== false ||
               stripos($table, 'item') !== false;
    });
    
    if (!empty($productTables)) {
        echo "<ul>";
        foreach ($productTables as $table) {
            echo "<li><strong>" . htmlspecialchars($table) . "</strong>";
            
            // Mostrar estructura de la tabla
            $descStmt = $conn->query("DESCRIBE `$table`");
            $columns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
            echo " <small>(" . count($columns) . " columnas: " . implode(', ', array_slice($columns, 0, 5)) . "...)</small>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>No se encontraron tablas relacionadas con productos</p>";
    }
    
    // Verificar estructura de order_items
    echo "<hr><h3>Estructura de 'order_items':</h3>";
    if (in_array('order_items', $tables)) {
        $stmt = $conn->query("DESCRIBE order_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Muestra de datos
        echo "<h4>Muestra de datos:</h4>";
        $stmt = $conn->query("SELECT * FROM order_items LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($sample) {
            echo "<pre>";
            print_r($sample);
            echo "</pre>";
        }
    } else {
        echo "<p class='error'>Tabla 'order_items' no encontrada</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
