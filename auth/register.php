<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function handleRegistration($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['registrar'])) {
        return null;
    }

    // Sanitizar y validar inputs
    $name = sanitizeInput($_POST['username']);
    $email = filter_var(sanitizeInput($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : null;
    $password = $_POST['password'];
    $terms = isset($_POST['terms']) ? true : false;

    // Validaciones básicas
    if (empty($name) || empty($email) || empty($password)) {
        return "Todos los campos obligatorios deben ser completados";
    }

    if (!$terms) {
        return "Debes aceptar los términos y condiciones";
    }

    if (!$email) {
        return "El correo electrónico no es válido";
    }

    if (strlen($password) < 6) {
        return "La contraseña debe tener al menos 6 caracteres";
    }

    // Verificar si el usuario ya existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        return "Este correo ya está registrado";
    }

    // Crear usuario
    $userId = uniqid();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $email, $phone, $hashedPassword]);
        
        $conn->commit();
        
        // Enviar email de confirmación
        $mailSent = sendWelcomeEmail($email, $name);

        // Crear sesión y redirigir. No bloqueamos el acceso por falla del correo.
        createUserSession($userId);

        if (!$mailSent) {
            // Guardar advertencia en sesión para notificar al usuario
            $_SESSION['register_warning'] = "Registro completado, pero no se pudo enviar el correo de confirmación";
        }

        header("Location: " . BASE_URL . "/users/dashboarduser.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error en registro: " . $e->getMessage());
        $errorMsg = "Error al registrar el usuario. Por favor intenta nuevamente.";
        // Si estamos en modo DEBUG, mostrar el mensaje de excepción (no usar en producción)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $errorMsg .= " (" . $e->getMessage() . ")";
        }
        // Mensaje adicional para errores comunes relacionados con triggers de auditoría
        if (stripos($e->getMessage(), 'audit_users') !== false || stripos($e->getMessage(), 'audit_users.PRIMARY') !== false) {
            $errorMsg .= " — Puede ser causado por un índice AUTO_INCREMENT faltante en la tabla 'audit_users'. Ejecuta 'php database/fixes/fix_missing_auto_increment.php' desde la raíz del proyecto para intentar solucionarlo.";
        }
        return $errorMsg;
    }
}

function createUserSession($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
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
        $mail->Subject = '¡Bienvenido/a a Angelow!';

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>¡Bienvenido/a a Angelow!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2968c8; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido/a a Angelow!</h1>
        </div>
        <div class="content">
            <p>Hola '.htmlspecialchars($name).',</p>
            <p>Gracias por registrarte en nuestra tienda. Ahora puedes disfrutar de todas las ventajas de ser miembro:</p>
            <ul>
                <li>Acceso a ofertas exclusivas</li>
                <li>Seguimiento de tus pedidos</li>
                <li>Historial de compras</li>
                <li>Y mucho más...</li>
            </ul>
            <p>Para comenzar a comprar, visita nuestro sitio:</p>
            <p><a href="'.BASE_URL.'" style="background-color: #2968c8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ir a Angelow</a></p>
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
        error_log("Error al enviar correo: " . $e->getMessage());
        return false;
    }
}

// Procesar registro
$errorMessage = handleRegistration($conn);

// Si hay error, guardar en sesión para mostrar en el formulario
if ($errorMessage) {
    $_SESSION['register_error'] = $errorMessage;
    // La vista de registro real está en `users/formuser.php`.
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}
?>