<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/session_functions.php';

function authenticateUser($conn) {
    // Primero verificar la sesión tradicional
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    // Si no hay sesión, verificar token de cookie
    if (!isset($_COOKIE['auth_token'])) {
        return null;
    }

    $token = $_COOKIE['auth_token'];
    
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM access_tokens 
        WHERE token = ? AND is_revoked = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }

    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener información del usuario
    $stmt = $conn->prepare("SELECT id, name, email, role, image FROM users 
        WHERE id = ? AND is_blocked = 0");
    $stmt->execute([$tokenData['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Crear sesión tradicional para mayor seguridad
    createUserSession($user['id'], $conn);
    
    return $user;
}

$currentUser = authenticateUser($GLOBALS['conn']);

// Middleware para proteger rutas
function requireAuth($conn) {
    global $currentUser;
    
    if (!$currentUser) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
    
    return $currentUser;
}