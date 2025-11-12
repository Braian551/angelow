<?php
require_once 'conexion.php';
try {
    $stmt = $conn->query('SELECT COUNT(*) as total FROM products');
    $result = $stmt->fetch();
    echo 'Total productos: ' . $result['total'] . PHP_EOL;

    $stmt = $conn->query('SELECT id, name FROM products LIMIT 5');
    $products = $stmt->fetchAll();
    echo 'Primeros 5 productos:' . PHP_EOL;
    foreach ($products as $p) {
        echo $p['id'] . ': ' . $p['name'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>