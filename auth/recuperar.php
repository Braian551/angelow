<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
// Página independiente: no usamos headers globales para evitar redirecciones automáticas.

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/formlogin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/recovery.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="Angelow">
            </div>
            <h1>Recupera tu contraseña</h1>
            <p>Usa tu correo o teléfono registrado para recibir un código seguro en segundos.</p>
        </div>

        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Identificar cuenta</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Validar código</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title">Nueva contraseña</div>
            </div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </div>

        <form id="recoveryForm" class="login-form" novalidate>
            <div class="form-step active" data-step="1">
                <div class="form-group">
                    <label for="recoveryIdentifier">Correo electrónico o teléfono</label>
                    <input type="text" id="recoveryIdentifier" name="identifier" placeholder="Ej: maria@email.com o 3001234567" required>
                    <div class="form-hint">Enviaremos un código de 6 dígitos al dato que ingreses.</div>
                    <div class="error-message" data-error-for="identifier"></div>
                </div>
                <button type="button" class="btn-primary" id="requestCodeBtn">
                    <span>Enviar código</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>

            <div class="form-step" data-step="2">
                <div class="code-meta">
                    <p id="codeInfo">Revisa tu bandeja de entrada y escribe el código que te enviamos.</p>
                    <span class="code-status-pill pending" id="codeStatus">Pendiente de validación</span>
                </div>
                <div class="form-group">
                    <label for="verificationCode">Código de verificación</label>
                    <input type="text" id="verificationCode" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code">
                    <div class="form-hint">El código vence en <span id="codeTimer">15:00</span></div>
                    <div class="error-message" data-error-for="code"></div>
                </div>
                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button type="button" class="btn-primary" id="verifyCodeBtn">
                        Validar código
                    </button>
                </div>
                <div class="resend-wrapper">
                    <span>¿No llegó el correo?</span>
                    <button type="button" class="link-button" id="resendCodeBtn" disabled>Reenviar código</button>
                </div>
            </div>

            <div class="form-step" data-step="3">
                    <div class="form-group password-group">
                    <label for="newPassword">Nueva contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="newPassword" placeholder="Mínimo 8 caracteres" required>
                        <button type="button" class="toggle-password" data-target="newPassword" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-hint">Combina letras, números y símbolos para mayor seguridad.</div>
                    <div class="error-message" data-error-for="password"></div>
                </div>

                <div class="form-group password-group">
                    <label for="confirmPassword">Confirmar contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="confirmPassword" placeholder="Repite tu contraseña" required>
                        <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" data-error-for="password_confirm"></div>
                </div>

                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button type="submit" class="btn-primary" id="resetPasswordBtn">
                        Restablecer contraseña
                    </button>
                </div>
            </div>
        </form>

        <div class="login-redirect">
            ¿Recordaste tu contraseña? <a class="text-link" href="<?= BASE_URL ?>/users/formlogin.php">Inicia sesión</a>
        </div>
        <div class="register-redirect">
            ¿Necesitas crear una cuenta? <a class="text-link" href="<?= BASE_URL ?>/users/formuser.php">Regístrate aquí</a>
        </div>
    </div>

    <script>
        window.ANGELOW_BASE_URL = <?= json_encode(BASE_URL) ?>;
        window.ANGELOW_RECOVERY_ENDPOINT = window.ANGELOW_BASE_URL + '/auth/password_reset_controller.php';
    </script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/password_recovery.js"></script>
</body>
</html>
