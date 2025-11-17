<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/header3.php';

if (isset($_SESSION['register_error'])) {
    $error_message = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
    includeFromRoot('alertas/alerta1.php');
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert(".json_encode($error_message)."); });</script>";
}
// Mostrar una advertencia si no se pudo enviar correo pero el registro fue exitoso
if (isset($_SESSION['register_warning'])) {
    $warning_message = $_SESSION['register_warning'];
    unset($_SESSION['register_warning']);
    includeFromRoot('alertas/alerta1.php');
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert(".json_encode($warning_message).", 'info'); });</script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="Angelow">
            </div>
            <h1>Crea tu cuenta</h1>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Nombre</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Correo</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title">Teléfono</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-title">Contraseña</div>
            </div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </div>

        <!-- Formulario por pasos -->
        <form method="POST" action="<?= BASE_URL ?>/auth/register.php" class="register-form" id="registerForm" novalidate>
            <!-- Paso 1: Nombre -->
            <div class="form-step active" data-step="1">
                <div class="form-group">
                    <label for="username">Nombre completo</label>
                    <input type="text" id="username" name="username" placeholder="Ej: Juan Pérez" required>
                    <div class="form-hint">Así aparecerás en Angelow</div>
                </div>
                <button type="button" class="btn-primary next-step">Continuar</button>
            </div>

            <!-- Paso 2: Correo -->
            <div class="form-step" data-step="2">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ej: juan@email.com" required>
                    <div class="form-hint">Usaremos este correo para contactarte</div>
                </div>
                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">Atrás</button>
                    <button type="button" class="btn-primary next-step">Continuar</button>
                </div>
            </div>

            <!-- Paso 3: Teléfono -->
            <div class="form-step" data-step="3">
                <div class="form-group">
                    <label for="phone">Teléfono (opcional)</label>
                    <input type="tel" id="phone" name="phone" placeholder="Ej: 3001234567">
                    <div class="form-hint">Podrás usarlo para iniciar sesión</div>
                </div>
                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">Atrás</button>
                    <button type="button" class="btn-primary next-step">Continuar</button>
                </div>
            </div>

            <!-- Paso 4: Contraseña -->
            <div class="form-step" data-step="4">
                <div class="form-group password-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" placeholder="Crea tu contraseña" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-hint">Debe tener entre 6 y 20 caracteres</div>
                    <div id="password-strength-bar"></div>
                </div>
                
                <div class="terms-container">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">Acepto los <a href="<?= BASE_URL ?>/informacion/terminos.php" target="_blank">Términos y condiciones</a> y las <a href="<?= BASE_URL ?>/informacion/privacidad.php" target="_blank">Políticas de privacidad</a> de Angelow</label>
                </div>
                
                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">Atrás</button>
                    <button type="submit" class="btn-primary" name="registrar">Crear cuenta</button>
                </div>
            </div>
        </form>

        <div class="social-login">
            <p>También puedes registrarte con:</p>
            <div class="social-buttons">
                <a href="<?= BASE_URL ?>/auth/google_auth.php" class="social-btn google">
                    <i class="fab fa-google"></i> Google
                </a>
            </div>
        </div>

        <div class="login-redirect">
            ¿Ya tienes una cuenta? <a href="<?= BASE_URL ?>/users/formlogin.php" class="text-link">Inicia sesión</a>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/form.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script src="<?= BASE_URL ?>/js/validacionregister.js"></script>
</body>
</html>