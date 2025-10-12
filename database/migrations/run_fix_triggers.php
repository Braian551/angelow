<?php
/**
 * Script para aplicar el fix de triggers de order_status_history
 * Soluciona el problema de foreign key constraint
 */

require_once __DIR__ . '/../../conexion.php';

echo "=========================================\n";
echo "Fix de Triggers - order_status_history\n";
echo "=========================================\n\n";

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/fix_order_history_triggers.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encuentra el archivo: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Error al leer el archivo SQL");
    }
    
    echo "📄 Archivo SQL cargado correctamente\n\n";
    
    // Ejecutar cada statement separadamente
    // Primero, procesar los comandos DROP y CREATE
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    $inDelimiter = false;
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignorar comentarios y líneas vacías
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Detectar cambio de delimitador
        if (strpos($line, 'DELIMITER') === 0) {
            if ($currentStatement) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
            
            if (strpos($line, 'DELIMITER $$') !== false) {
                $delimiter = '$$';
            } else {
                $delimiter = ';';
            }
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Si encontramos el delimitador actual, guardamos el statement
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            // Remover el delimitador
            $stmt = substr(trim($currentStatement), 0, -strlen($delimiter));
            if (!empty($stmt)) {
                $statements[] = trim($stmt);
            }
            $currentStatement = '';
        }
    }
    
    // Agregar el último statement si existe
    if (!empty($currentStatement)) {
        $statements[] = trim($currentStatement);
    }
    
    echo "📋 Se ejecutarán " . count($statements) . " comandos SQL\n\n";
    
    // Ejecutar cada statement
    $executed = 0;
    foreach ($statements as $index => $statement) {
        if (empty($statement) || $statement === 'USE angelow') {
            continue;
        }
        
        try {
            // Mostrar qué se está ejecutando
            $preview = substr($statement, 0, 60);
            if (strlen($statement) > 60) {
                $preview .= '...';
            }
            
            echo "⚙️  Ejecutando: $preview\n";
            
            $conn->exec($statement);
            $executed++;
            echo "   ✅ Ejecutado correctamente\n\n";
            
        } catch (PDOException $e) {
            echo "   ⚠️  Error: " . $e->getMessage() . "\n\n";
            
            // Algunos errores son aceptables (como DROP de algo que no existe)
            if (strpos($e->getMessage(), 'Unknown') === false && 
                strpos($e->getMessage(), 'doesn\'t exist') === false) {
                throw $e;
            }
        }
    }
    
    echo "=========================================\n";
    echo "✅ MIGRACIÓN COMPLETADA\n";
    echo "   Total de comandos ejecutados: $executed\n";
    echo "=========================================\n\n";
    
    // Verificar que los triggers se crearon correctamente
    echo "📊 Verificando triggers creados:\n\n";
    $stmt = $conn->query("
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = 'angelow' 
        AND EVENT_OBJECT_TABLE = 'orders'
    ");
    
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) > 0) {
        foreach ($triggers as $trigger) {
            echo "   ✅ {$trigger['TRIGGER_NAME']} - {$trigger['EVENT_MANIPULATION']} on {$trigger['EVENT_OBJECT_TABLE']}\n";
        }
    } else {
        echo "   ⚠️  No se encontraron triggers\n";
    }
    
    echo "\n=========================================\n";
    echo "Ahora puedes probar la actualización masiva\n";
    echo "de estado de órdenes sin errores.\n";
    echo "=========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
    exit(1);
}
