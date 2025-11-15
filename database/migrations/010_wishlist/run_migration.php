<?php
/**
 * Script de migración para la tabla wishlist
 * @date 2025-11-15
 */

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

// Leer SQL
$sqlFile = __DIR__ . '/001_create_wishlist_table.sql';
if (!file_exists($sqlFile)) {
    echo "Archivo $sqlFile no encontrado\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

try {
    echo "Ejecutando migración wishlist...\n";
    $conn->exec($sql);
    echo "✅ Tabla wishlist creada o ya existía.\n";

    // Mostrar estructura
    $stmt = $conn->query("DESCRIBE wishlist");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " ({$col['Type']})" . PHP_EOL;
    }

    echo "\nMigración completada.\n";
} catch (PDOException $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
