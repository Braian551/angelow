<?php
require_once __DIR__ . '/../../conexion.php';

try {
    echo "=== Revisión de la tabla user_applied_discounts ===\n";
    $stmt = $conn->query('SHOW CREATE TABLE user_applied_discounts');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('No se encontró la tabla user_applied_discounts');
    }

    $createSql = $row['Create Table'] ?? '';
    $hasAutoIncrement = stripos($createSql, 'AUTO_INCREMENT') !== false;
    $hasPrimaryKey = stripos($createSql, 'PRIMARY KEY') !== false;

    if ($hasAutoIncrement && $hasPrimaryKey) {
        echo "La tabla ya tiene PRIMARY KEY y AUTO_INCREMENT. No es necesario cambiarla.\n";
        exit(0);
    }

    echo "Se aplicará ALTER TABLE para añadir PRIMARY KEY y AUTO_INCREMENT en la columna 'id'...\n";

    // Asegurarse de que la columna id exista
    $stmt = $conn->query("DESCRIBE user_applied_discounts id");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        throw new Exception("La columna 'id' no existe en user_applied_discounts");
    }

    // Ejecutar alter table en una sola operación para evitar errores de MySQL
    // (la columna AUTO_INCREMENT debe ser parte de una KEY al mismo tiempo que se define)
    // Realizar sin transacción; ALTER TABLE es una operación DDL que no requiere transacción
    $conn->exec("ALTER TABLE user_applied_discounts MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");

    echo "✓ user_applied_discounts actualizado: 'id' ahora AUTO_INCREMENT y PRIMARY KEY definida.\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Reindex: no necesario en la mayoría de casos, pero mostrar mensaje de resumen
echo "Ejecute 'php database/fixes/fix_user_applied_discounts.php' en su entorno para aplicar la corrección.\n";

?>