<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
// For legacy login page keep layout minimal — no global header/footer.
// header3.php previously enforced redirects and admin checks; we will skip it here.
// require_once __DIR__ . '/../layouts/header3.php';

// Mostrar errores de login si existen
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);

    includeFromRoot('alertas/alerta1.php');

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert(" . json_encode($error_message) . ", 'error');
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/form.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/css/formlogin.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="Angelow">
            </div>
            <h1>Iniciar sesión</h1>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Correo/Teléfono</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Contraseña</div>
            </div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </div>

        <!-- Formulario por pasos -->
        <form method="POST" action="<?= BASE_URL ?>/auth/login.php" class="login-form" id="loginForm" novalidate>
            <!-- Paso 1: Correo/Teléfono -->
            <div class="form-step active" data-step="1">
                <div class="form-group">
                    <label for="username">Correo electrónico o teléfono</label>
                    <input type="text" id="correo_login" name="correo_login" placeholder="Ej: juan@email.com o 3001234567" required>
                    <div class="form-hint">Ingresa el correo o teléfono con el que te registraste</div>
                </div>
                <button type="button" class="btn-primary next-step">Continuar</button>
            </div>

            <!-- Paso 2: Contraseña -->
            <div class="form-step" data-step="2">
                <div class="form-group password-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="loginPassword" name="password" placeholder="Ingresa tu contraseña" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-hint">La contraseña distingue entre mayúsculas y minúsculas</div>
                </div>
                
                <div class="login-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Recordar mi cuenta</label>
                    </div>
                    <a href="<?= BASE_URL ?>/auth/recuperar.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>
                
                <div class="step-buttons">
                    <button type="button" class="btn-outline prev-step">Atrás</button>
                    <button type="submit" class="btn-primary" name="submit2">Iniciar sesión</button>
                </div>
            </div>
        </form>

        <div class="social-login">
            <p>También puedes iniciar sesión con:</p>
            <div class="social-buttons">
                <a href="<?= BASE_URL ?>/auth/google_auth.php" class="social-btn google">
                    <i class="fab fa-google"></i> Google
                </a>
            </div>
        </div>

        <div class="register-redirect">
            ¿No tienes una cuenta? <a href="<?= BASE_URL ?>/users/formuser.php" class="text-link">Regístrate</a>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/form.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
        <script src="<?= BASE_URL ?>/js/validacionlogin.js"></script>

    <script>
 
    </script>
</body>
</html>