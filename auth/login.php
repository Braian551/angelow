<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar cookies de "Recordar mi cuenta"
function checkRememberMe($conn) {
    if (isset($_COOKIE['remember_me']) && !isset($_SESSION['user_id'])) {
        $token = $_COOKIE['remember_me'];
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE remember_token = ? AND token_expiry > NOW()");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            createUserSession($user['id'], $conn);
            
            // Refrescar el token para mayor seguridad
            $newToken = bin2hex(random_bytes(32));
            $newExpiry = time() + 60 * 60 * 24 * 30; // 30 días
            
            $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$newToken, date('Y-m-d H:i:s', $newExpiry), $user['id']]);
            
            setcookie(
                'remember_me',
                $newToken,
                $newExpiry,
                '/',
                parse_url(BASE_URL, PHP_URL_HOST),
                true,  // Solo HTTPS
                true   // HttpOnly
            );
            
            // Redirigir al dashboard si es necesario
            if (basename($_SERVER['PHP_SELF']) === 'login.php') {
                header("Location: " . BASE_URL . "/users/dashboarduser.php");
                exit();
            }
        } else {
            // Token inválido - borrar cookie
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }
}

// Verificar cookie al cargar la página
checkRememberMe($conn);

function createUserSession($userId, $conn = null) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    if ($conn) {
        $stmt = $conn->prepare("UPDATE users SET last_access = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
}

function handleLogin($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit2'])) {
        return;
    }

    $username = trim($_POST['correo_login']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

    // Validación de campos vacíos
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Todos los campos son obligatorios";
        header("Location: " . BASE_URL . "/users/formlogin.php");
        exit();
    }

    // Determinar si es email o teléfono
    $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
    $isPhone = preg_match('/^[0-9]{10,15}$/', $username);

    if (!$isEmail && !$isPhone) {
        $_SESSION['login_error'] = "Ingresa un correo electrónico o teléfono válido";
        header("Location: " . BASE_URL . "/users/formlogin.php");
        exit();
    }

    // Buscar usuario por email o teléfono
    $query = "SELECT id, password, is_blocked FROM users WHERE ";
    $params = [];
    
    if ($isEmail) {
        $query .= "email = ?";
        $params[] = $username;
    } else {
        $query .= "phone = ?";
        $params[] = $username;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['login_error'] = "Credenciales incorrectas";
        header("Location: " . BASE_URL . "/users/formlogin.php");
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['is_blocked']) {
        $_SESSION['login_error'] = "Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.";
        header("Location: " . BASE_URL . "/users/formlogin.php");
        exit();
    }

    if (!password_verify($password, $user['password'])) {
        registerFailedAttempt($username, $conn);
        $_SESSION['login_error'] = "Credenciales incorrectas";
        header("Location: " . BASE_URL . "/users/formlogin.php");
        exit();
    }

    // Login exitoso
    createUserSession($user['id'], $conn);

    // Crear cookie de "Recordar mi cuenta" si está marcado
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 60 * 60 * 24 * 30; // 30 días
        
        $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
        
        setcookie(
            'remember_me',
            $token,
            $expiry,
            '/',
            parse_url(BASE_URL, PHP_URL_HOST),
            true,  // Solo HTTPS
            true   // HttpOnly
        );
    }

    // Redirigir según el rol del usuario
    require_once __DIR__ . '/role_redirect.php';
    
    // Si hay una página de redirección específica y el usuario tiene acceso, usarla
    if (isset($_SESSION['redirect_to'])) {
        $redirect = $_SESSION['redirect_to'];
        unset($_SESSION['redirect_to']);
        
        // Obtener rol del usuario
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData && checkRoleAccess($userData['role'], $redirect)) {
            header("Location: " . $redirect);
            exit();
        }
    }
    
    // Redirigir al dashboard correspondiente según el rol
    redirectToDashboard($user['id'], $conn);
}

// Función para registrar intentos fallidos
function registerFailedAttempt($username, $conn) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, attempt_date) VALUES (?, ?, NOW())");
    $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
    
    // Bloquear después de 5 intentos fallidos en 1 hora
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                           WHERE (username = ? OR ip_address = ?) 
                           AND attempt_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['attempts'] >= 5) {
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE email = ? OR phone = ?");
        $stmt->execute([$username, $username]);
        
        $_SESSION['login_error'] = "Demasiados intentos fallidos. Tu cuenta ha sido bloqueada temporalmente.";
    }
}

// Ejecutar el manejo del login si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit2'])) {
    handleLogin($conn);
}

// Si no es POST, redirigir al formulario de login
header("Location: " . BASE_URL . "/users/formlogin.php");
exit();