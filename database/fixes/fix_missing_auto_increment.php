<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

try {
    echo "=== Escaneando tablas para columnas 'id' sin AUTO_INCREMENT ===\n";
    $sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND COLUMN_NAME = 'id'
            AND EXTRA NOT LIKE '%auto_increment%';";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "Ninguna tabla con columna 'id' sans AUTO_INCREMENT encontrada.\n";
        exit(0);
    }

    foreach ($rows as $r) {
        echo "Tabla: {$r['TABLE_NAME']} | Tipo: {$r['DATA_TYPE']} | Extra: {$r['EXTRA']}\n";
    }

    echo "\nIntentando aplicar cambios en tablas candidatas...\n";

    foreach ($rows as $r) {
        $table = $r['TABLE_NAME'];

        // Revisar si ya tiene una PRIMARY KEY
        $keyCheck = $conn->prepare("SELECT COUNT(*) as pk_count FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_TYPE = 'PRIMARY KEY'");
        $keyCheck->execute([':table' => $table]);
        $hasPK = $keyCheck->fetch(PDO::FETCH_ASSOC)['pk_count'] > 0;

        if ($hasPK) {
            echo "- Omitiendo $table: ya tiene PRIMARY KEY (posible columna no 'id').\n";
            continue;
        }

        // Solo arreglar si el tipo es int o bigint
        if (!in_array(strtolower($r['DATA_TYPE']), ['int', 'bigint'])) {
            echo "- Omitiendo $table: id no es integer (es: {$r['DATA_TYPE']}).\n";
            continue;
        }

        echo "- Aplicando: ALTER TABLE $table MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;\n";
        try {
            $conn->exec("ALTER TABLE `$table` MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
            echo "  ✓ Actualizado $table\n";
        } catch (Exception $e) {
            echo "  ✗ Falló al actualizar $table: " . $e->getMessage() . "\n";
        }
    }

        // Asegurarse de que AUTO_INCREMENT sea mayor que MAX(id)
        try {
            $maxRow = $conn->query("SELECT COALESCE(MAX(id), 0) as maxid FROM `$table`")->fetch(PDO::FETCH_ASSOC);
            $maxId = intval($maxRow['maxid']);
            $aiRow = $conn->prepare("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
            $aiRow->execute([':table' => $table]);
            $ai = intval($aiRow->fetch(PDO::FETCH_ASSOC)['AUTO_INCREMENT']);

            if ($ai <= $maxId) {
                $newAi = $maxId + 1;
                echo "- Ajustando AUTO_INCREMENT de $table a $newAi (max id: $maxId)\n";
                $conn->exec("ALTER TABLE `$table` AUTO_INCREMENT = $newAi");
                echo "  ✓ AUTO_INCREMENT actualizado a $newAi\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Falló al ajustar AUTO_INCREMENT en $table: " . $e->getMessage() . "\n";
        }

    echo "\nProceso finalizado. Re-ejecuta las pruebas de pago.\n";
    
    // Segunda pasada: Asegurarse de que AUTO_INCREMENT > MAX(id) para todas las tablas con id int/bigint
    echo "\nVerificando AUTO_INCREMENT contra MAX(id) para todas las tablas (id int/bigint)...\n";
    $tables = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'id' AND DATA_TYPE IN ('int','bigint')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        try {
            $maxRow = $conn->query("SELECT COALESCE(MAX(id), 0) as maxid FROM `$table`")->fetch(PDO::FETCH_ASSOC);
            $maxId = intval($maxRow['maxid']);
            $aiRow = $conn->prepare("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
            $aiRow->execute([':table' => $table]);
            $ai = intval($aiRow->fetch(PDO::FETCH_ASSOC)['AUTO_INCREMENT']);

            if ($ai <= $maxId) {
                $newAi = $maxId + 1;
                echo "- Ajustando AUTO_INCREMENT de $table a $newAi (max id: $maxId)\n";
                $conn->exec("ALTER TABLE `$table` AUTO_INCREMENT = $newAi");
                echo "  ✓ AUTO_INCREMENT actualizado a $newAi\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Falló al ajustar AUTO_INCREMENT en $table: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
