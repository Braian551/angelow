<?php
/**
 * Script de migración para el Sistema de Badge de Órdenes
 * 
 * Este script ejecuta la migración para crear la tabla order_views
 * que permite rastrear qué órdenes han sido vistas por cada administrador.
 * 
 * @author Angelow System
 * @date 2025-10-12
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
    'reset' => "\033[0m"
];

// Función para imprimir con colores (solo funciona en consola)
function printLine($message, $type = 'info') {
    global $colors;
    if (php_sapi_name() === 'cli') {
        echo $colors[$type] . $message . $colors['reset'] . "\n";
    } else {
        echo "<div style='color: " . ($type === 'success' ? 'green' : ($type === 'error' ? 'red' : 'blue')) . ";'>" . htmlspecialchars($message) . "</div>";
    }
}

try {
    printLine("==============================================", 'info');
    printLine("  MIGRACIÓN: Sistema de Badge de Órdenes", 'info');
    printLine("==============================================", 'info');
    printLine("");

    // Verificar si la tabla ya existe
    printLine("🔍 Verificando si la tabla ya existe...", 'info');
    $stmt = $conn->query("SHOW TABLES LIKE 'order_views'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        printLine("⚠️  La tabla 'order_views' ya existe.", 'info');
        printLine("ℹ️  No se realizará ninguna acción.", 'info');
        printLine("✅ Migración completada (tabla ya existe).", 'success');
        exit(0);
    }

    // Leer el archivo SQL
    printLine("📄 Leyendo archivo de migración...", 'info');
    $sqlFile = __DIR__ . '/001_create_order_views_table.sql';
    
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
    $stmt = $conn->query("DESCRIBE order_views");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printLine("✅ Tabla 'order_views' creada con " . count($columns) . " columnas:", 'success');
    foreach ($columns as $column) {
        printLine("   - " . $column['Field'] . " (" . $column['Type'] . ")", 'info');
    }
    printLine("");

    // Resumen
    printLine("==============================================", 'success');
    printLine("✅ MIGRACIÓN COMPLETADA EXITOSAMENTE", 'success');
    printLine("==============================================", 'success');
    printLine("");
    printLine("📊 Resumen:", 'info');
    printLine("   - Tabla: order_views", 'info');
    printLine("   - Columnas: " . count($columns), 'info');
    printLine("   - Estado: Activa y lista para usar", 'info');
    printLine("");
    printLine("🎯 Siguiente paso:", 'info');
    printLine("   Accede al panel de administración y verifica el badge de órdenes.", 'info');
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
    printLine("   2. Verifica que las tablas 'orders' y 'users' existan", 'info');
    printLine("   3. Verifica los permisos de tu usuario MySQL", 'info');
    printLine("", 'info');
    exit(1);
} catch (Exception $e) {
    printLine("", 'error');
    printLine("❌ ERROR: " . $e->getMessage(), 'error');
    printLine("", 'error');
    exit(1);
}
