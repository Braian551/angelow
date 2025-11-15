<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de Google OAuth
$google_client_id = '312746725205-s2jkhq4r9j0mviu8ar4g8q4d43ub4qtt.apps.googleusercontent.com';
$google_client_secret = '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';
$google_redirect_uri = BASE_URL . '/auth/google_auth.php';

// Paso 1: Redirección a Google
if (!isset($_GET['code'])) {
    $auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'response_type' => 'code',
        'client_id' => $google_client_id,
        'redirect_uri' => $google_redirect_uri,
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ]);
    header("Location: " . $auth_url);
    exit();
}

// Paso 2: Intercambiar código por token
$code = $_GET['code'];
$token_url = 'https://oauth2.googleapis.com/token';

$post_data = [
    'code' => $code,
    'client_id' => $google_client_id,
    'client_secret' => $google_client_secret,
    'redirect_uri' => $google_redirect_uri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    $_SESSION['login_error'] = "Error en autenticación con Google: " . ($token_data['error'] ?? 'Error desconocido');
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

// Paso 3: Obtener información del usuario
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_data['access_token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$userinfo_response = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($userinfo_response, true);

if (!isset($userinfo['email'])) {
    $_SESSION['login_error'] = "No se pudo obtener información del usuario de Google";
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

// Paso 4: Buscar o crear usuario
try {
    $conn->beginTransaction();
    
    // Verificar si el usuario ya existe
    $stmt = $conn->prepare("SELECT id, is_blocked FROM users WHERE email = ?");
    $stmt->execute([$userinfo['email']]);
    
    $isNewUser = false;
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['is_blocked']) {
            $_SESSION['login_error'] = "Tu cuenta ha sido bloqueada. Contacta al administrador.";
            header("Location: " . BASE_URL . "/users/formuser.php");
            exit();
        }
        
        $user_id = $user['id'];
    } else {
        // Crear nuevo usuario
        $isNewUser = true;
        $user_id = uniqid();
        $name = $userinfo['name'] ?? explode('@', $userinfo['email'])[0];
        
        // Insertar usuario sin el campo is_google_auth que no existe en tu tabla
        $stmt = $conn->prepare("INSERT INTO users (id, name, email) VALUES (?, ?, ?)");
        $insertSuccess = $stmt->execute([$user_id, $name, $userinfo['email']]);
        
        if (!$insertSuccess) {
            throw new PDOException("No se pudo insertar el usuario en la tabla users");
        }
    }
    
    // Guardar/actualizar datos de Google Auth
    $stmt = $conn->prepare("INSERT INTO google_auth 
        (user_id, google_id, access_token) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        access_token = VALUES(access_token)");
    
    $insertSuccess = $stmt->execute([
        $user_id,
        $userinfo['id'],
        $token_data['access_token']
    ]);
    
    if (!$insertSuccess) {
        throw new PDOException("No se pudo insertar en google_auth");
    }
    
    $conn->commit();
    
    // Enviar correo de bienvenida si es nuevo usuario
    if ($isNewUser) {
        sendWelcomeEmail($userinfo['email'], $name);
    }
    
    // Crear sesión
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $userinfo['email'];
    $_SESSION['access_token'] = $token_data['access_token'];
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Redirigir al dashboard
    header("Location: " . BASE_URL . "/users/dashboarduser.php");
    exit();
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error en Google Auth: " . $e->getMessage());
    $_SESSION['login_error'] = "Error en el sistema. Por favor intenta más tarde. Detalles: " . $e->getMessage();
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

function sendWelcomeEmail($email, $name) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('angelow2025sen@gmail.com', 'Angelow');
        $mail->addAddress($email, $name);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido/a a nuestra tienda!';

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>¡Bienvenido/a a nuestra tienda de ropa infantil Angelow!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            background-color: #0077b6;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header img {
            max-width: 200px;
            height: auto;
        }
        .header h1 {
            color: white;
            margin-top: 15px;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .welcome-text {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555555;
        }
        .highlight {
            color: #0077b6;
            font-weight: bold;
        }
        .cta-button {
            display: inline-block;
            background-color: #48cae4;
            color: white !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: bold;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background-color: #00b4d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .benefits {
            margin: 25px 0;
        }
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .benefit-icon {
            color: #48cae4;
            margin-right: 15px;
            font-size: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #777777;
            border-top: 1px solid #e0e0e0;
        }
        .social-icons {
            margin: 20px 0;
        }
        .social-icon {
            display: inline-block;
            margin: 0 10px;
            color: #0077b6;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido/a, '.htmlspecialchars($name).'!</h1>
        </div>
        
        <div class="content">
            <p class="welcome-text">Gracias por registrarte en <span class="highlight">Angelow</span>, tu destino favorito para encontrar las prendas más adorables y de la mejor calidad para los más pequeños.</p>
            
            <p class="welcome-text">Ahora formas parte de nuestra comunidad y podrás disfrutar de:</p>
            
            <div class="benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span>Descuentos exclusivos para miembros</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span>Primer acceso a nuestras nuevas colecciones</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span>Envíos especiales y promociones</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span>Soporte prioritario</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="'.BASE_URL.'" class="cta-button">Entra y descubre</a>
            </div>
            
            <p class="welcome-text">Si tienes alguna pregunta, no dudes en contactarnos respondiendo a este correo o visitando nuestra página de contacto.</p>
            
            <p class="welcome-text">¡Te has registrado usando tu cuenta de Google!</p>
        </div>
        
        <div class="footer">
            <p>© '.date('Y').' Angelow. Todos los derechos reservados.</p>
            <p>Si no realizaste este registro, por favor ignora este mensaje.</p>
        </div>
    </div>
</body>
</html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de bienvenida Google: " . $e->getMessage());
        return false;
    }
}
?>