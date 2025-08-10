<?php
// config.php - Configuración optimizada para XAMPP e InfinityFree

// 1. Detección automática del entorno más robusta
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || 
           $_SERVER['SERVER_ADDR'] === '127.0.0.1');

// 2. Configuración de DEBUG_MODE
define('DEBUG_MODE', $isLocal);

// 3. Configuración de rutas base mejorada
if ($isLocal) {
    // Configuración para servidor local en puerto 3000
    define('BASE_URL', 'http://localhost:3000');
} else {
    // Configuración optimizada para InfinityFree
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    
    // En InfinityFree, el script suele estar en la raíz
    define('BASE_URL', $protocol . $host);
}

// 4. Ruta física siempre correcta (corregido _DIR_ a __DIR__)
define('BASE_PATH', __DIR__);

// 5. Configuración de errores mejorada
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    // Pero sigue registrando errores en producción
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/php_errors.log');
}

// 6. Función para includes mejorada con verificación de archivo
function includeFromRoot($path) {
    $fullPath = BASE_PATH . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        require $fullPath;
    } else {
        if (DEBUG_MODE) {
            throw new Exception("Archivo no encontrado: " . $fullPath);
        }
        error_log("Archivo no encontrado: " . $fullPath);
    }
}

// 7. Configuración de zona horaria
date_default_timezone_set('America/Bogota');


// 8. Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', !$isLocal ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

?>