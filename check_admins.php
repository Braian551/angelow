<?php
require_once 'conexion.php';
try {
    $stmt = $conn->query('SELECT id, email, role FROM users WHERE role = "admin" LIMIT 5');
    $admins = $stmt->fetchAll();
    echo 'Admins encontrados:' . PHP_EOL;
    foreach ($admins as $admin) {
        echo $admin['id'] . ': ' . $admin['email'] . ' (' . $admin['role'] . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>