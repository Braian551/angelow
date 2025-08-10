<?php

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
// Eliminar token de la base de datos si existe
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Borrar cookie
setcookie('remember_me', '', time() - 3600, '/');

// Destruir sesión
session_destroy();

header("Location: " . BASE_URL . "/users/formlogin.php");
exit();
?>