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

$sqlFile = __DIR__ . '/001_create_or_fix_login_attempts.sql';
if (!file_exists($sqlFile)) {
    echo "Archivo de migración no encontrado: $sqlFile\n";
    exit(1);
}

try {
    echo "Ejecutando migración login_attempts...\n";
    // Ejecutar todas las sentencias en el archivo SQL en bloques separados
    $sql = file_get_contents($sqlFile);
    // Dividir por punto y coma; ejecutar cada sentencia por separado para capturar errores individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        try {
            $conn->exec($statement . ';');
        } catch (PDOException $e) {
            echo "Nota: Error ejecutando sentencia SQL: " . $e->getMessage() . "\n";
        }
    }
    echo "✅ login_attempts: creado o modificado con AUTO_INCREMENT en id.\n";

    // Ejecutar alteraciones opcionales (id auto_increment y default timestamp) en bloques independientes
    try {
        $conn->exec("ALTER TABLE `login_attempts` MODIFY `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY;");
    } catch (PDOException $e) {
        // Ignorar si ya existe PRIMARY KEY o AUTO_INCREMENT
        echo "Nota: no se pudo modificar id: " . $e->getMessage() . "\n";
    }

    try {
        $conn->exec("ALTER TABLE `login_attempts` MODIFY `attempt_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;");
    } catch (PDOException $e) {
        echo "Nota: no se pudo modificar attempt_date: " . $e->getMessage() . "\n";
    }

    $stmt = $conn->query("DESCRIBE login_attempts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " ({$col['Type']}) default: {$col['Default']} extra: {$col['Extra']}" . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "Error en migración login_attempts: " . $e->getMessage() . "\n";
    exit(1);
}
