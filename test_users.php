<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';

echo "<h2>Usuarios en la base de datos</h2>";
echo "<pre>";

try {
    $stmt = $conn->query("SELECT id, email, role FROM users ORDER BY id LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";
?>
