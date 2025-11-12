<?php
require_once 'config.php';
require_once 'conexion.php';

try {
    $stmt = $conn->query('SELECT COUNT(*) as count FROM products');
    $result = $stmt->fetch();
    echo 'Products count: ' . $result['count'] . PHP_EOL;

    $stmt = $conn->query('SELECT COUNT(*) as count FROM categories');
    $result = $stmt->fetch();
    echo 'Categories count: ' . $result['count'] . PHP_EOL;

    // Test the AJAX query
    $sql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch();
    echo 'Products with categories count: ' . $result[0] . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>