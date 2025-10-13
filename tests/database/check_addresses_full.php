<?php
require_once 'conexion.php';

echo "=== ESTRUCTURA user_addresses ===\n\n";
$result = $conn->query('DESCRIBE user_addresses');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    printf("%-30s | %-30s\n", $row['Field'], $row['Type']);
}

echo "\n=== EJEMPLO user_addresses ===\n\n";
$result = $conn->query('SELECT * FROM user_addresses LIMIT 1');
$data = $result->fetch(PDO::FETCH_ASSOC);
if($data) {
    print_r($data);
}

echo "\n\n=== ESTRUCTURA order_deliveries ===\n\n";
$result = $conn->query('DESCRIBE order_deliveries');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    printf("%-30s | %-30s\n", $row['Field'], $row['Type']);
}

echo "\n\n=== EJEMPLO order_deliveries ===\n\n";
$result = $conn->query('SELECT * FROM order_deliveries LIMIT 1');
$data = $result->fetch(PDO::FETCH_ASSOC);
if($data) {
    print_r($data);
}
