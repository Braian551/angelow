<?php
// Verificar estructura de la tabla orders
require_once __DIR__ . '/../../config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Estructura de la Tabla Orders</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .success { color: green; }
    .error { color: red; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
</style>";

try {
    // Obtener estructura de la tabla orders
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3 class='success'>✓ Columnas de la tabla 'orders':</h3>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Campos relacionados con dirección
    echo "<h3>Campos de Dirección en Orders:</h3>";
    $addressFields = array_filter($columns, function($col) {
        return stripos($col['Field'], 'shipping') !== false || 
               stripos($col['Field'], 'address') !== false ||
               stripos($col['Field'], 'city') !== false ||
               stripos($col['Field'], 'neighborhood') !== false;
    });
    
    if (!empty($addressFields)) {
        echo "<ul>";
        foreach ($addressFields as $field) {
            echo "<li><strong>" . htmlspecialchars($field['Field']) . "</strong> (" . htmlspecialchars($field['Type']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No se encontraron campos de dirección</p>";
    }
    
    // Obtener estructura de la tabla users
    echo "<hr><h3 class='success'>✓ Columnas de la tabla 'users':</h3>";
    $stmt = $conn->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    
    foreach ($userColumns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Muestra de una orden
    echo "<hr><h3>Muestra de Datos (Primera Orden):</h3>";
    $stmt = $conn->query("SELECT * FROM orders LIMIT 1");
    $sampleOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleOrder) {
        echo "<pre>";
        print_r($sampleOrder);
        echo "</pre>";
    } else {
        echo "<p>No hay órdenes en la base de datos</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
