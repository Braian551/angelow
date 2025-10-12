<?php
// Verificar estructura de payment_transactions
require_once __DIR__ . '/../../config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Estructura de la Tabla payment_transactions</h2>";
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
    // Obtener estructura de payment_transactions
    $stmt = $conn->query("DESCRIBE payment_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3 class='success'>✓ Columnas de la tabla 'payment_transactions':</h3>";
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
    
    // Muestra de datos
    echo "<hr><h3>Muestra de Datos (Primera transacción):</h3>";
    $stmt = $conn->query("SELECT * FROM payment_transactions LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    } else {
        echo "<p>No hay transacciones en la base de datos</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
