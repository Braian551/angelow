<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONEXIÓN A BASE DE DATOS
try {
    // Configuración de la conexión a la base de datos
    $db_name = 'mysql:host=sql213.infinityfree.com;dbname=if0_39303070_angelow';
    $db_user_name = 'if0_39303070';
    $db_user_pass = 'braian805279';

    // Crear la conexión PDO
    $conn = new PDO($db_name, $db_user_name, $db_user_pass);

    // Establecer el modo de error de PDO para que lance excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Manejo de errores de conexión
    echo "Error de conexión: " . $e->getMessage();
    die(); // Detiene la ejecución si hay un error
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