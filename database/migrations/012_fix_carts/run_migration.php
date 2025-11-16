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

$sqlFile = __DIR__ . '/001_fix_carts.sql';
if (!file_exists($sqlFile)) {
    echo "Archivo de migración no encontrado: $sqlFile\n";
    exit(1);
}

try {
    echo "Ejecutando migración fix_carts...\n";
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        try {
            $conn->exec($statement . ';');
        } catch (PDOException $e) {
            echo "Nota: Error ejecutando sentencia SQL: " . $e->getMessage() . "\n";
        }
    }

    // Add AUTO_INCREMENT to id fields if not present
    try {
        $conn->exec("ALTER TABLE `carts` MODIFY `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY;");
        echo "✓ carts.id set to AUTO_INCREMENT\n";
    } catch (PDOException $e) {
        echo "Nota: no se pudo modificar carts.id: " . $e->getMessage() . "\n";
    }

    try {
        $conn->exec("ALTER TABLE `cart_items` MODIFY `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY;");
        echo "✓ cart_items.id set to AUTO_INCREMENT\n";
    } catch (PDOException $e) {
        echo "Nota: no se pudo modificar cart_items.id: " . $e->getMessage() . "\n";
    }

    // Mostrar estructura
    $stmt = $conn->query("DESCRIBE carts");
    echo "\n=== ESTRUCTURA carts ===\n";
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) echo "- " . $col['Field'] . " ({$col['Type']}) default: {$col['Default']} extra: {$col['Extra']}" . PHP_EOL;

    $stmt = $conn->query("DESCRIBE cart_items");
    echo "\n=== ESTRUCTURA cart_items ===\n";
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) echo "- " . $col['Field'] . " ({$col['Type']}) default: {$col['Default']} extra: {$col['Extra']}" . PHP_EOL;

    echo "\n✅ Migración completa. Prueba agregando un item al carrito desde la tienda.\n";
} catch (PDOException $e) {
    echo "Error en migración fix_carts: " . $e->getMessage() . "\n";
    exit(1);
}
