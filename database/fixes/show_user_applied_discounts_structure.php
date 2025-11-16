<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

try {
    $stmt = $conn->query('SHOW CREATE TABLE user_applied_discounts');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "SHOW CREATE TABLE user_applied_discounts\n";
        echo $row['Create Table'] . "\n";
    } else {
        echo "No se encontró la tabla user_applied_discounts\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
