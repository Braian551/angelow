<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST ?? [];
}

$action = isset($input['action']) ? trim($input['action']) : '';

try {
    switch ($action) {
        case 'request_code':
            handleRequestCode($input, $conn);
            break;
        case 'resend_code':
            handleRequestCode($input, $conn, true);
            break;
        case 'verify_code':
            handleVerifyCode($input, $conn);
            break;
        case 'reset_password':
            handleResetPassword($input, $conn);
            break;
        default:
            respondError('Acción inválida.');
    }
} catch (Throwable $e) {
    error_log('[PASSWORD_RESET] Excepción no controlada: ' . $e->getMessage());
    respondError('Ocurrió un error inesperado. Inténtalo nuevamente en unos minutos.');
}

function handleRequestCode(array $payload, PDO $conn, bool $isResend = false): void
{
    $identifier = normalizeIdentifier($payload['identifier'] ?? '');
    if (!$identifier) {
        respondError('Debes ingresar el correo electrónico o teléfono asociado a tu cuenta.');
    }

    $user = findUserByIdentifier($conn, $identifier);
    if (!$user) {
        respondError('No encontramos una cuenta asociada a esos datos. Verifica la información e inténtalo de nuevo.');
    }

    $cooldown = secondsUntilNextCode($conn, $user['id']);
    if ($cooldown > 0) {
        respondError('Ya enviamos un código recientemente. Intenta de nuevo en ' . $cooldown . ' segundos.');
    }

    $code = (string) random_int(100000, 999999);
    $tokenHash = password_hash($code, PASSWORD_BCRYPT);
    $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutos

    $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at, is_used) VALUES (?, ?, ?, 0)');
    $stmt->execute([$user['id'], $tokenHash, $expiresAt]);

    $mailSent = sendRecoveryEmail($user, $code, $expiresAt);
    if (!$mailSent) {
        respondError('No pudimos enviar el correo de verificación en este momento. Inténtalo nuevamente.');
    }

    $message = $isResend
        ? 'Generamos un nuevo código y lo enviamos a tu correo.'
        : 'Te enviamos un código de verificación a tu correo.';

    $expiresIn = max(0, strtotime($expiresAt) - time());

    respondSuccess([
        'expires_at' => $expiresAt,
        'expires_in' => $expiresIn,
        'identifier' => maskIdentifier($identifier),
        'resend_cooldown' => 60
    ], $message);
}

function handleVerifyCode(array $payload, PDO $conn): void
{
    $identifier = normalizeIdentifier($payload['identifier'] ?? '');
    $code = trim($payload['code'] ?? '');

    if (!$identifier || !$code) {
        respondError('Debes ingresar el código enviado a tu correo.');
    }

    if (!preg_match('/^[0-9]{6}$/', $code)) {
        respondError('El código debe tener 6 dígitos.');
    }

    $record = fetchLatestResetRecord($conn, $identifier);
    if (!$record) {
        respondError('No encontramos solicitudes activas para esos datos.');
    }

    if ((int) $record['is_used'] === 1) {
        respondError('Ese código ya fue utilizado. Solicita uno nuevo.');
    }

    if (strtotime($record['expires_at']) < time()) {
        respondError('El código expiró. Solicita uno nuevo para continuar.');
    }

    if (!password_verify($code, $record['token'])) {
        respondError('El código ingresado no es válido.');
    }

    $sessionToken = bin2hex(random_bytes(32));
    $_SESSION['password_reset_session'] = [
        'user_id' => $record['user_id'],
        'reset_id' => $record['id'],
        'token' => $sessionToken,
        'expires' => time() + 900,
        'identifier' => $identifier
    ];

    respondSuccess([
        'session_token' => $sessionToken
    ], 'Código verificado. Ahora crea tu nueva contraseña.');
}

function handleResetPassword(array $payload, PDO $conn): void
{
    $sessionToken = trim($payload['session_token'] ?? '');
    $password = $payload['password'] ?? '';
    $confirm = $payload['password_confirm'] ?? '';

    $context = $_SESSION['password_reset_session'] ?? null;

    if (!$context || !$sessionToken || !hash_equals($context['token'], $sessionToken)) {
        respondError('La sesión de recuperación no es válida o expiró. Vuelve a solicitar un código.');
    }

    if ($context['expires'] < time()) {
        unset($_SESSION['password_reset_session']);
        respondError('El código expiró. Solicita uno nuevo para continuar.');
    }

    if ($password !== $confirm) {
        respondError('Las contraseñas ingresadas no coinciden.');
    }

    $password = trim($password);
    if (strlen($password) < 8) {
        respondError('La nueva contraseña debe tener al menos 8 caracteres.');
    }

    if (strlen($password) > 64) {
        respondError('La nueva contraseña no puede exceder 64 caracteres.');
    }

    $conn->beginTransaction();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW(), remember_token = NULL, token_expiry = NULL WHERE id = ?');
        $stmt->execute([$hash, $context['user_id']]);

        $stmt = $conn->prepare('UPDATE password_resets SET is_used = 1 WHERE id = ?');
        $stmt->execute([$context['reset_id']]);

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log('[PASSWORD_RESET] Error al actualizar clave: ' . $e->getMessage());
        respondError('No pudimos actualizar tu contraseña. Inténtalo nuevamente.');
    }

    unset($_SESSION['password_reset_session']);

    respondSuccess([], 'Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión.');
}

function findUserByIdentifier(PDO $conn, string $identifier): ?array
{
    $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE email = ? OR phone = ? LIMIT 1');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function fetchLatestResetRecord(PDO $conn, string $identifier): ?array
{
    $stmt = $conn->prepare('SELECT pr.id, pr.user_id, pr.token, pr.expires_at, pr.is_used
        FROM password_resets pr
        INNER JOIN users u ON u.id = pr.user_id
        WHERE u.email = ? OR u.phone = ?
        ORDER BY pr.id DESC
        LIMIT 1');
    $stmt->execute([$identifier, $identifier]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record ?: null;
}

function secondsUntilNextCode(PDO $conn, string $userId): int
{
    $stmt = $conn->prepare('SELECT created_at FROM password_resets WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['created_at'])) {
        return 0;
    }

    $last = strtotime($row['created_at']);
    $elapsed = time() - $last;
    $cooldown = 60;

    return $elapsed >= $cooldown ? 0 : $cooldown - $elapsed;
}

function normalizeIdentifier(string $value): string
{
    $value = trim($value);
    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return strtolower($value);
    }

    $digits = preg_replace('/[^0-9]/', '', $value);
    if ($digits && strlen($digits) >= 7) {
        return $digits;
    }

    return '';
}

function maskIdentifier(string $identifier): string
{
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        [$name, $domain] = explode('@', $identifier, 2);
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 2));
        return $maskedName . '@' . $domain;
    }

    if (preg_match('/^[0-9]+$/', $identifier)) {
        $len = strlen($identifier);
        return str_repeat('*', max($len - 4, 0)) . substr($identifier, -4);
    }

    return $identifier;
}

function sendRecoveryEmail(array $user, string $code, string $expiresAt): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str) {
                error_log('[PASSWORD_RESET][SMTP_DEBUG] ' . trim($str));
            };
        }
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'angelow2025sen@gmail.com';
        $mail->Password = 'djaf tdju nyhr scgd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('seguridad@angelow.com', 'Seguridad Angelow');
        $mail->addAddress($user['email'], $user['name'] ?? 'Cliente Angelow');

        $logoPath = realpath(__DIR__ . '/../images/logo2.png');
        $logoEmbedId = 'recovery_logo';
        $logoUrl = BASE_URL . '/images/logo2.png';
        if ($logoPath && file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, $logoEmbedId);
            $logoUrl = 'cid:' . $logoEmbedId;
        }

        $formattedExpiry = date('d/m/Y H:i', strtotime($expiresAt));

        $mail->isHTML(true);
        $mail->Subject = 'Tu código para restablecer la contraseña';
        $mail->Body = buildRecoveryEmailTemplate($user['name'] ?? 'Cliente Angelow', $code, $formattedExpiry, $logoUrl);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[PASSWORD_RESET] Error al enviar correo: ' . $e->getMessage());
        try {
            $subject = 'Código de seguridad Angelow';
            $recipientName = $user['name'] ?? 'Cliente Angelow';
            $plainBody = "Hola {$recipientName},\n\n";
            $plainBody .= 'Tu código para restablecer la contraseña es: ' . $code . "\n";
            $plainBody .= 'Caduca el ' . $formattedExpiry . ".\n\n";
            $plainBody .= 'Si tú no solicitaste este cambio, ignora este mensaje.';
            $headers = 'From: seguridad@angelow.com' . "\r\n" .
                       'Reply-To: soporte@angelow.com' . "\r\n" .
                       'X-Mailer: PHP/' . phpversion();

            $fallback = @mail($user['email'], $subject, $plainBody, $headers);
            if ($fallback) {
                return true;
            }
        } catch (Throwable $fallbackError) {
            error_log('[PASSWORD_RESET] Fallback mail() error: ' . $fallbackError->getMessage());
        }
        return false;
    }
}

function buildRecoveryEmailTemplate(string $name, string $code, string $formattedExpiry, string $logoUrl): string
{
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeExpiry = htmlspecialchars($formattedExpiry, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificación Angelow</title>
    <style>
        body { font-family: "Inter", Arial, sans-serif; margin:0; padding:0; background:#f6f7fb; color:#0f172a; }
        .wrapper { max-width:600px; margin:0 auto; padding:24px; }
        .card { background:#ffffff; border-radius:18px; box-shadow:0 20px 45px rgba(15,23,42,0.12); overflow:hidden; border:1px solid #eef2ff; }
        .header { background:linear-gradient(135deg,#0f4c81,#2968c8); padding:36px 32px; text-align:center; color:#fff; }
        .header img { max-height:54px; margin-bottom:16px; }
        .header h1 { margin:0; font-size:24px; }
        .body { padding:32px; }
        .code-box { display:flex; justify-content:center; letter-spacing:12px; font-size:32px; font-weight:700; color:#0f172a; margin:24px 0; }
        .info { font-size:14px; color:#475569; line-height:1.6; }
        .pill { display:inline-block; padding:6px 14px; border-radius:999px; background:#e0f2ff; color:#0369a1; font-weight:600; font-size:12px; margin-top:16px; }
        .footer { padding:24px 32px; background:#f8fafc; font-size:12px; color:#94a3b8; text-align:center; }
        .cta { text-align:center; margin-top:24px; }
        .cta a { display:inline-block; padding:12px 20px; border-radius:999px; background:#0b6bbd; color:#fff; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <img src="' . $logoUrl . '" alt="Angelow">
                <h1>Protegemos tu cuenta</h1>
                <p style="opacity:0.85; font-size:14px; margin-top:8px;">Usa este código para restablecer tu contraseña</p>
            </div>
            <div class="body">
                <p style="font-size:16px; color:#0f172a;">Hola ' . $safeName . ',</p>
                <p class="info">Recibimos una solicitud para restablecer tu contraseña. Copia el siguiente código en la pantalla de verificación para continuar:</p>
                <div class="code-box">' . trim(chunk_split($code, 1, ' ')) . '</div>
                <p class="info">El código expira el <strong>' . $safeExpiry . '</strong>. Por seguridad, no lo compartas con nadie.</p>
                <div class="pill">Código válido por 15 minutos</div>
                <div class="cta">
                    <a href="' . BASE_URL . '/auth/recuperar.php">Abrir recuperador</a>
                </div>
                <p class="info" style="margin-top:24px;">Si tú no solicitaste este cambio, ignora este mensaje. Tu contraseña actual seguirá siendo válida.</p>
            </div>
            <div class="footer">
                © ' . date('Y') . ' Angelow · Mensaje generado automáticamente.
            </div>
        </div>
    </div>
</body>
</html>';
}

function respondSuccess(array $data = [], string $message = ''): void
{
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function respondError(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}
