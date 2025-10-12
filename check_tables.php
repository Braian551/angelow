<?php
require 'conexion.php';

echo "=== ESTRUCTURA TABLA USERS ===\n";
$stmt = $conn->query('DESCRIBE users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null: ' . $row['Null'] . ' | Key: ' . $row['Key'] . "\n";
}

echo "\n=== ESTRUCTURA TABLA ORDER_STATUS_HISTORY ===\n";
$stmt = $conn->query('DESCRIBE order_status_history');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null: ' . $row['Null'] . ' | Key: ' . $row['Key'] . "\n";
}

echo "\n=== VERIFICAR USUARIO EN SESIÓN ===\n";
session_start();
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "Usuario encontrado:\n";
        echo "  ID: " . $user['id'] . " (Tipo: " . gettype($user['id']) . ")\n";
        echo "  Nombre: " . $user['name'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
    } else {
        echo "Usuario NO encontrado con ID: " . $_SESSION['user_id'] . "\n";
    }
} else {
    echo "No hay sesión activa\n";
}
