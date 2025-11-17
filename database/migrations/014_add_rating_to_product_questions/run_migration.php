<?php
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

$sqlFile = __DIR__ . '/001_add_rating_to_product_questions.sql';
if (!file_exists($sqlFile)) {
    echo "Archivo $sqlFile no encontrado\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

try {
    echo "Ejecutando migración para agregar rating a product_questions...\n";
    $conn->exec($sql);
    echo "✅ Campo rating agregado (o ya existía).\n";
} catch (PDOException $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
