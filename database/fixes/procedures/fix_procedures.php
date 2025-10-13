<?php
/**
 * Script para corregir los procedimientos almacenados
 * Ejecuta el archivo fix_procedures_parameters.sql
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Corrección de Procedimientos - AngelOW</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .step-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Corrección de Procedimientos Almacenados</h1>
";

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/migrations/fix_procedures_parameters.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo: $sqlFile");
    }
    
    echo "<div class='info'>📄 Leyendo archivo: fix_procedures_parameters.sql</div>";
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    // Separar las consultas por delimitador
    $queries = [];
    $tempQuery = '';
    $delimiter = ';';
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Saltar comentarios y líneas vacías
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Cambiar delimitador
        if (stripos($line, 'DELIMITER') === 0) {
            $parts = explode(' ', $line);
            $delimiter = isset($parts[1]) ? trim($parts[1]) : ';';
            continue;
        }
        
        $tempQuery .= $line . "\n";
        
        // Si la línea termina con el delimitador actual
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            $query = trim(substr($tempQuery, 0, -strlen($delimiter)));
            if (!empty($query)) {
                $queries[] = $query;
            }
            $tempQuery = '';
        }
    }
    
    echo "<div class='info'>📋 Se encontraron " . count($queries) . " consultas para ejecutar</div>";
    
    // Ejecutar cada consulta
    $executed = 0;
    $errors = 0;
    
    echo "<div class='step'>";
    echo "<div class='step-title'>Ejecutando consultas:</div>";
    
    foreach ($queries as $index => $query) {
        try {
            // Saltar USE database
            if (stripos($query, 'USE ') === 0) {
                continue;
            }
            
            // Ejecutar la consulta
            $conn->exec($query);
            $executed++;
            
            // Mostrar procedimientos creados
            if (stripos($query, 'CREATE PROCEDURE') !== false) {
                preg_match('/CREATE PROCEDURE\s+`?(\w+)`?/i', $query, $matches);
                if (isset($matches[1])) {
                    echo "<div class='success'>✅ Procedimiento creado: {$matches[1]}</div>";
                }
            }
            
        } catch (PDOException $e) {
            $errors++;
            echo "<div class='error'>❌ Error en consulta " . ($index + 1) . ": " . $e->getMessage() . "</div>";
            // No detener la ejecución, continuar con las demás consultas
        }
    }
    
    echo "</div>";
    
    // Verificar procedimientos instalados
    echo "<div class='step'>";
    echo "<div class='step-title'>📊 Procedimientos en la base de datos:</div>";
    
    $stmt = $conn->query("
        SELECT 
            ROUTINE_NAME as nombre,
            ROUTINE_TYPE as tipo,
            CREATED as creado
        FROM information_schema.ROUTINES
        WHERE ROUTINE_SCHEMA = 'angelow'
        AND (ROUTINE_NAME LIKE 'Driver%' 
             OR ROUTINE_NAME LIKE '%Delivery%' 
             OR ROUTINE_NAME LIKE 'Assign%')
        ORDER BY ROUTINE_NAME
    ");
    
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($procedures) > 0) {
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th>Procedimiento</th>";
        echo "<th>Tipo</th>";
        echo "<th>Fecha Creación</th>";
        echo "</tr>";
        
        foreach ($procedures as $proc) {
            echo "<tr>";
            echo "<td><strong>{$proc['nombre']}</strong></td>";
            echo "<td>{$proc['tipo']}</td>";
            echo "<td>{$proc['creado']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
    
    // Resumen final
    echo "<div class='success'>";
    echo "<h3>✅ Migración Completada</h3>";
    echo "<p>✔️ Consultas ejecutadas exitosamente: <strong>$executed</strong></p>";
    echo "<p>✔️ Procedimientos activos: <strong>" . count($procedures) . "</strong></p>";
    
    if ($errors > 0) {
        echo "<p>⚠️ Errores encontrados: <strong>$errors</strong> (algunos pueden ser normales si los procedimientos ya existían)</p>";
    }
    
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🎯 Próximos Pasos:</h3>";
    echo "<ol>";
    echo "<li>Los procedimientos almacenados han sido actualizados</li>";
    echo "<li>Ahora retornan resultados mediante SELECT en lugar de parámetros OUT</li>";
    echo "<li>Prueba la funcionalidad de 'Iniciar Recorrido' en el dashboard del delivery</li>";
    echo "<li>Verifica que la redirección a navigation.php funcione correctamente</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error Fatal</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "
    </div>
</body>
</html>";
?>
