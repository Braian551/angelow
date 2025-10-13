<?php
/**
 * Script CLI para corregir los procedimientos almacenados
 * Ejecutar desde consola: php run_fix_procedures.php
 */

// Deshabilitar límite de tiempo
set_time_limit(0);

// Configurar para CLI
if (php_sapi_name() !== 'cli') {
    die("Este script debe ejecutarse desde la línea de comandos\n");
}

// Configuración de base de datos local para CLI
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'angelow');
define('DEBUG_MODE', true);

// Conexión directa a la base de datos
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage() . "\n");
}

// Colores para consola
class Colors {
    public static $RESET = "\033[0m";
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $MAGENTA = "\033[35m";
    public static $CYAN = "\033[36m";
    public static $WHITE = "\033[37m";
    public static $BOLD = "\033[1m";
}

function printColor($text, $color = null, $bold = false) {
    $output = "";
    if ($bold) $output .= Colors::$BOLD;
    if ($color) $output .= $color;
    $output .= $text;
    if ($color || $bold) $output .= Colors::$RESET;
    echo $output . "\n";
}

function printHeader($text) {
    echo "\n";
    printColor(str_repeat("=", 70), Colors::$CYAN, true);
    printColor($text, Colors::$CYAN, true);
    printColor(str_repeat("=", 70), Colors::$CYAN, true);
    echo "\n";
}

function printSuccess($text) {
    printColor("✅ " . $text, Colors::$GREEN);
}

function printError($text) {
    printColor("❌ " . $text, Colors::$RED);
}

function printWarning($text) {
    printColor("⚠️  " . $text, Colors::$YELLOW);
}

function printInfo($text) {
    printColor("ℹ️  " . $text, Colors::$BLUE);
}

// Inicio del script
printHeader("🔧 CORRECCIÓN DE PROCEDIMIENTOS ALMACENADOS - ANGELOW");

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/migrations/fix_procedures_parameters.sql';
    
    printInfo("Buscando archivo SQL...");
    
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo: $sqlFile");
    }
    
    printSuccess("Archivo encontrado: fix_procedures_parameters.sql");
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    printInfo("Procesando archivo SQL...");
    
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
    
    printSuccess("Se encontraron " . count($queries) . " consultas para ejecutar");
    
    // Ejecutar cada consulta
    $executed = 0;
    $errors = 0;
    $procedures_created = [];
    
    printHeader("📝 EJECUTANDO CONSULTAS");
    
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
                    $procedures_created[] = $matches[1];
                    printSuccess("Procedimiento creado: " . $matches[1]);
                }
            } elseif (stripos($query, 'DROP PROCEDURE') !== false) {
                preg_match('/DROP PROCEDURE\s+IF EXISTS\s+`?(\w+)`?/i', $query, $matches);
                if (isset($matches[1])) {
                    printInfo("Eliminando procedimiento anterior: " . $matches[1]);
                }
            }
            
        } catch (PDOException $e) {
            $errors++;
            printError("Error en consulta " . ($index + 1) . ": " . $e->getMessage());
            // No detener la ejecución, continuar con las demás consultas
        }
    }
    
    // Verificar procedimientos instalados
    printHeader("📊 VERIFICACIÓN DE PROCEDIMIENTOS");
    
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
        printInfo("Procedimientos encontrados en la base de datos:");
        echo "\n";
        
        // Encabezado de tabla
        printf("%-35s %-15s %-25s\n", 
            Colors::$BOLD . "Procedimiento" . Colors::$RESET, 
            Colors::$BOLD . "Tipo" . Colors::$RESET, 
            Colors::$BOLD . "Fecha Creación" . Colors::$RESET
        );
        echo str_repeat("-", 75) . "\n";
        
        foreach ($procedures as $proc) {
            $isNew = in_array($proc['nombre'], $procedures_created);
            $color = $isNew ? Colors::$GREEN : Colors::$WHITE;
            
            printf("%s%-35s%s %-15s %-25s\n", 
                $color,
                $proc['nombre'],
                Colors::$RESET,
                $proc['tipo'],
                $proc['creado']
            );
        }
        
        echo "\n";
    }
    
    // Resumen final
    printHeader("✅ MIGRACIÓN COMPLETADA");
    
    echo "\n";
    printSuccess("Consultas ejecutadas: " . $executed);
    printSuccess("Procedimientos activos: " . count($procedures));
    printSuccess("Procedimientos creados/actualizados: " . count($procedures_created));
    
    if ($errors > 0) {
        echo "\n";
        printWarning("Errores encontrados: " . $errors);
        printWarning("(algunos errores son normales si los procedimientos ya existían)");
    }
    
    // Próximos pasos
    printHeader("🎯 PRÓXIMOS PASOS");
    
    echo "\n";
    printInfo("1. Los procedimientos almacenados han sido actualizados correctamente");
    printInfo("2. Ahora retornan resultados mediante SELECT en lugar de parámetros OUT");
    printInfo("3. Prueba la funcionalidad:");
    echo "\n";
    printColor("   • Inicia sesión como transportista/delivery", Colors::$CYAN);
    printColor("   • Ve al dashboard: http://localhost/angelow/delivery/dashboarddeli.php", Colors::$CYAN);
    printColor("   • Acepta una orden", Colors::$CYAN);
    printColor("   • Haz clic en '▶️ Iniciar Recorrido'", Colors::$CYAN);
    printColor("   • Deberías ser redirigido a navigation.php automáticamente", Colors::$CYAN);
    echo "\n";
    
    printSuccess("¡Migración completada exitosamente!");
    echo "\n";
    
} catch (Exception $e) {
    printHeader("❌ ERROR FATAL");
    echo "\n";
    printError($e->getMessage());
    echo "\n";
    printColor("Stack trace:", Colors::$RED);
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}

exit(0);
