<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONEXIÓN A BASE DE DATOS
try {
    $db_host = 'localhost';
    $db_port = '3306';
    $db_name = 'angelow';
    $db_user_name = 'root';
    $db_user_pass = '';

    $conn = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8", $db_user_name, $db_user_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    die();
}

// FUNCIÓN PARA ID ÚNICO (declarada una sola vez)
if (!function_exists('create_unique_id')) {
    function create_unique_id() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for($i = 0; $i < 20; $i++) {
            $random_string .= $characters[mt_rand(0, $characters_length - 1)];
        }
        return $random_string;
    }
}

// OBTENER ID DEL USUARIO
$user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';
?>