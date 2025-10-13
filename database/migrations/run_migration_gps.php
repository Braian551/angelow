<?php
/**
 * Script para ejecutar la migración de GPS en direcciones
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

echo "<h2>Ejecutando migración: GPS Addresses</h2>";
echo "<p>Agregando campos GPS a la tabla de direcciones...</p>";

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/migration_gps_addresses.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en sentencias individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $conn->beginTransaction();
    
    foreach ($statements as $statement) {
        // Limpiar comentarios de línea
        $statement = preg_replace('/--[^\n]*\n/', '', $statement);
        $statement = trim($statement);
        
        if (empty($statement)) continue;
        
        echo "<p>Ejecutando: " . substr($statement, 0, 100) . "...</p>";
        $conn->exec($statement);
    }
    
    $conn->commit();
    
    echo "<div style='padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✓ Migración completada exitosamente</h3>";
    echo "<p style='color: #155724; margin: 10px 0 0;'>Los campos GPS han sido agregados a la tabla user_addresses.</p>";
    echo "</div>";
    
    // Verificar la estructura
    $stmt = $conn->query("DESCRIBE user_addresses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estructura actualizada de user_addresses:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>✗ Error en la migración</h3>";
    echo "<p style='color: #721c24; margin: 10px 0 0;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>✗ Error</h3>";
    echo "<p style='color: #721c24; margin: 10px 0 0;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><a href='" . BASE_URL . "/users/addresses.php'>← Volver a direcciones</a></p>";
