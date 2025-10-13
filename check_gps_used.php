<?php
/**
 * Script de verificación para el campo gps_used
 */

require_once __DIR__ . '/conexion.php';

try {
    echo "<h2>Verificación de campo gps_used en user_addresses</h2>";
    
    // Verificar estructura de la tabla
    echo "<h3>1. Estructura de la tabla:</h3>";
    $stmt = $conn->query("DESCRIBE user_addresses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'gps_used' || $col['Field'] === 'gps_latitude' || $col['Field'] === 'gps_longitude') ? 'style="background-color: yellow;"' : '';
        echo "<tr $highlight>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar datos existentes
    echo "<h3>2. Direcciones existentes:</h3>";
    $stmt = $conn->query("
        SELECT 
            id, 
            alias, 
            address,
            gps_latitude, 
            gps_longitude, 
            gps_used,
            created_at,
            updated_at
        FROM user_addresses 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($addresses)) {
        echo "<p><strong>No hay direcciones en la base de datos.</strong></p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Alias</th>";
        echo "<th>Dirección</th>";
        echo "<th>GPS Lat</th>";
        echo "<th>GPS Lng</th>";
        echo "<th>GPS Used</th>";
        echo "<th>Actualizado</th>";
        echo "</tr>";
        
        foreach ($addresses as $addr) {
            $hasCoords = !empty($addr['gps_latitude']) && !empty($addr['gps_longitude']);
            $gpsUsed = !empty($addr['gps_used']);
            
            $rowStyle = '';
            if ($hasCoords && !$gpsUsed) {
                $rowStyle = 'style="background-color: #ffcccc;"'; // Rojo - tiene coords pero no GPS used
            } elseif ($hasCoords && $gpsUsed) {
                $rowStyle = 'style="background-color: #ccffcc;"'; // Verde - todo correcto
            }
            
            echo "<tr $rowStyle>";
            echo "<td>{$addr['id']}</td>";
            echo "<td>" . htmlspecialchars($addr['alias']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($addr['address'], 0, 40)) . "...</td>";
            echo "<td>" . ($addr['gps_latitude'] ? number_format($addr['gps_latitude'], 6) : '<em>NULL</em>') . "</td>";
            echo "<td>" . ($addr['gps_longitude'] ? number_format($addr['gps_longitude'], 6) : '<em>NULL</em>') . "</td>";
            echo "<td><strong>" . ($addr['gps_used'] ? '✅ SÍ (1)' : '❌ NO (0)') . "</strong></td>";
            echo "<td>" . $addr['updated_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Leyenda:</strong></p>";
        echo "<ul>";
        echo "<li style='background-color: #ccffcc; display: inline-block; padding: 5px;'>Verde: Tiene coordenadas Y gps_used=1 ✅</li>";
        echo "<li style='background-color: #ffcccc; display: inline-block; padding: 5px; margin-left: 10px;'>Rojo: Tiene coordenadas pero gps_used=0 ❌</li>";
        echo "<li style='display: inline-block; padding: 5px; margin-left: 10px;'>Blanco: Sin coordenadas GPS</li>";
        echo "</ul>";
    }
    
    // Estadísticas
    echo "<h3>3. Estadísticas:</h3>";
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL THEN 1 ELSE 0 END) as con_coordenadas,
            SUM(CASE WHEN gps_used = 1 THEN 1 ELSE 0 END) as gps_usado,
            SUM(CASE WHEN (gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL) AND gps_used = 0 THEN 1 ELSE 0 END) as inconsistentes
        FROM user_addresses
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li><strong>Total direcciones:</strong> {$stats['total']}</li>";
    echo "<li><strong>Con coordenadas GPS:</strong> {$stats['con_coordenadas']}</li>";
    echo "<li><strong>Con gps_used = 1:</strong> {$stats['gps_usado']}</li>";
    echo "<li style='color: red;'><strong>⚠️ Inconsistentes (tienen coords pero gps_used=0):</strong> {$stats['inconsistentes']}</li>";
    echo "</ul>";
    
    if ($stats['inconsistentes'] > 0) {
        echo "<h3>4. Corregir inconsistencias:</h3>";
        echo "<p>Hay {$stats['inconsistentes']} direcciones con coordenadas pero gps_used=0</p>";
        echo "<form method='post'>";
        echo "<button type='submit' name='fix' value='1' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;'>";
        echo "🔧 Corregir ahora (actualizar gps_used=1 donde hay coordenadas)";
        echo "</button>";
        echo "</form>";
        
        if (isset($_POST['fix'])) {
            $updated = $conn->exec("
                UPDATE user_addresses 
                SET gps_used = 1 
                WHERE gps_latitude IS NOT NULL 
                AND gps_longitude IS NOT NULL
                AND gps_used = 0
            ");
            echo "<p style='background: #dff0d8; padding: 10px; margin-top: 10px;'>";
            echo "✅ <strong>Se actualizaron {$updated} direcciones correctamente.</strong>";
            echo " <a href='check_gps_used.php'>Recargar página</a>";
            echo "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
