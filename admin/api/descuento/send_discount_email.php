<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendDiscountEmail($userId, $code, $discount_type, $discount_value, $expiry_date) {
    global $conn;
    
    // Obtener información del usuario
    try {
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Usuario no encontrado para enviar correo de descuento");
            return false;
        }
        
        $email = $user['email'];
        $name = $user['name'];
    } catch (PDOException $e) {
        error_log("Error al obtener usuario para descuento: " . $e->getMessage());
        return false;
    }

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
        $mail->Subject = '¡Tienes un descuento especial en Angelow!';

        $expiry_text = $expiry_date ? 'Válido hasta: ' . date('d/m/Y', strtotime($expiry_date)) : 'Sin fecha de expiración';

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>¡Descuento Especial!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2968c8; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; background-color: #f9f9f9; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
        .discount-code { 
            background-color: #2968c8; color: white; 
            padding: 10px 20px; font-size: 24px; 
            font-weight: bold; text-align: center;
            display: inline-block; margin: 15px 0;
            border-radius: 5px;
        }
        .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; border-top: 1px solid #ddd; }
        .btn { 
            background-color: #2968c8; color: white; 
            padding: 10px 20px; text-decoration: none; 
            border-radius: 5px; display: inline-block;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Descuento Especial!</h1>
            <p>Hola '.htmlspecialchars($name).',</p>
        </div>
        <div class="content">
            <p>Hemos preparado un descuento especial para ti:</p>
            
            <div style="text-align: center;">
                <div class="discount-code">'.htmlspecialchars($code).'</div>
                <p style="font-size: 18px; margin: 5px 0;">'.htmlspecialchars($discount_type).' del '.$discount_value.'%</p>
                <p style="color: #666;">'.$expiry_text.'</p>
            </div>
            
            <p>Para usar tu código de descuento:</p>
            <ol>
                <li>Agrega productos a tu carrito</li>
                <li>Ingresa el código en el campo correspondiente</li>
                <li>¡Disfruta de tu descuento!</li>
            </ol>
            
            <p style="text-align: center;">
                <a href="'.BASE_URL.'" class="btn">Ir a la tienda</a>
            </p>
        </div>
        <div class="footer">
            <p>© '.date('Y').' Angelow. Todos los derechos reservados.</p>
            <p>Este código es personal e intransferible.</p>
        </div>
    </div>
</body>
</html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de descuento: " . $e->getMessage());
        return false;
    }
}