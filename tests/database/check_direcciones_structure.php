<?php
require_once 'conexion.php';

echo "=== LISTANDO TODAS LAS TABLAS ===\n\n";

$result = $conn->query('SHOW TABLES');
if ($result) {
    while($row = $result->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
}

echo "\n\n=== ESTRUCTURA DE LA TABLA ORDERS ===\n\n";

$result = $conn->query('DESCRIBE orders');
if ($result) {
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-30s | %-30s | %-5s | %-5s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
}

echo "\n\n=== COLUMNAS RELACIONADAS CON DIRECCION EN ORDERS ===\n\n";

$result = $conn->query("SELECT * FROM orders LIMIT 1");
if ($result) {
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($row as $key => $value) {
            if (stripos($key, 'address') !== false || stripos($key, 'direccion') !== false || 
                stripos($key, 'lat') !== false || stripos($key, 'lng') !== false || 
                stripos($key, 'ubicacion') !== false) {
                echo "$key => " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
        }
    }
}
