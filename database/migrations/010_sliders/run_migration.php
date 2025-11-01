<?php
/**
 * Script de migración para el Sistema de Sliders
 * 
 * Este script ejecuta la migración para crear la tabla sliders
 * que permite gestionar las imágenes del carousel del index.
 * 
 * @author Angelow System
 * @date 2025-11-01
 */

// Configuración manual para CLI
$host = 'localhost';
$dbname = 'angelow';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Colores para la consola
$colors = [
    'success' => "\033[32m",
    'error' => "\033[31m",
    'info' => "\033[36m",
    'warning' => "\033[33m",
    'reset' => "\033[0m"
];

// Función para imprimir con colores
function printLine($message, $type = 'info') {
    global $colors;
    if (php_sapi_name() === 'cli') {
        echo $colors[$type] . $message . $colors['reset'] . "\n";
    } else {
        $colorMap = ['success' => 'green', 'error' => 'red', 'info' => 'blue', 'warning' => 'orange'];
        echo "<div style='color: " . ($colorMap[$type] ?? 'black') . ";'>" . htmlspecialchars($message) . "</div>";
    }
}

try {
    printLine("==============================================", 'info');
    printLine("  MIGRACIÓN: Sistema de Sliders", 'info');
    printLine("==============================================", 'info');
    printLine("");

    // Verificar si la tabla ya existe
    printLine("🔍 Verificando si la tabla ya existe...", 'info');
    $stmt = $conn->query("SHOW TABLES LIKE 'sliders'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        printLine("⚠️  La tabla 'sliders' ya existe.", 'warning');
        printLine("ℹ️  No se realizará ninguna acción.", 'info');
        printLine("✅ Migración completada (tabla ya existe).", 'success');
        exit(0);
    }

    // Leer el archivo SQL
    printLine("📄 Leyendo archivo de migración...", 'info');
    $sqlFile = __DIR__ . '/001_create_sliders_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    printLine("✅ Archivo leído correctamente.", 'success');
    printLine("");

    // Ejecutar la migración
    printLine("⚙️  Ejecutando migración...", 'info');
    $conn->exec($sql);
    printLine("✅ Migración ejecutada exitosamente.", 'success');
    printLine("");

    // Verificar que la tabla se creó correctamente
    printLine("🔍 Verificando tabla creada...", 'info');
    $stmt = $conn->query("DESCRIBE sliders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printLine("✅ Tabla 'sliders' creada con " . count($columns) . " columnas:", 'success');
    foreach ($columns as $column) {
        printLine("   - " . $column['Field'] . " (" . $column['Type'] . ")", 'info');
    }
    printLine("");

    // Verificar datos de ejemplo
    $stmt = $conn->query("SELECT COUNT(*) as count FROM sliders");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    printLine("📊 Registros insertados: " . $count, 'info');
    printLine("");

    // Resumen
    printLine("==============================================", 'success');
    printLine("✅ MIGRACIÓN COMPLETADA EXITOSAMENTE", 'success');
    printLine("==============================================", 'success');
    printLine("");
    printLine("📊 Resumen:", 'info');
    printLine("   - Tabla: sliders", 'info');
    printLine("   - Columnas: " . count($columns), 'info');
    printLine("   - Registros de ejemplo: " . $count, 'info');
    printLine("   - Estado: Activa y lista para usar", 'info');
    printLine("");
    printLine("🎯 Siguiente paso:", 'info');
    printLine("   Accede a: Configuración > Sliders en el panel de administración", 'info');
    printLine("");
    printLine("⚠️  Nota importante:", 'warning');
    printLine("   Los slides de ejemplo apuntan a imágenes que aún no existen.", 'warning');
    printLine("   Sube tus propias imágenes desde el panel de administración.", 'warning');
    printLine("");

} catch (PDOException $e) {
    printLine("", 'error');
    printLine("❌ ERROR EN LA MIGRACIÓN", 'error');
    printLine("==============================================", 'error');
    printLine("Mensaje: " . $e->getMessage(), 'error');
    printLine("Código: " . $e->getCode(), 'error');
    printLine("", 'error');
    printLine("💡 Solución:", 'info');
    printLine("   1. Verifica que la base de datos 'angelow' exista", 'info');
    printLine("   2. Verifica los permisos de tu usuario MySQL", 'info');
    printLine("   3. Verifica que no haya errores de sintaxis en el SQL", 'info');
    printLine("", 'info');
    exit(1);
} catch (Exception $e) {
    printLine("", 'error');
    printLine("❌ ERROR: " . $e->getMessage(), 'error');
    printLine("", 'error');
    exit(1);
}
