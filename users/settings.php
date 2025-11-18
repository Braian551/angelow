<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';
require_once __DIR__ . '/../layouts/functions.php';

// Helper para preferencias de notificaciones
function upsertNotificationPreference(PDO $conn, string $userId, int $typeId, int $emailEnabled, int $pushEnabled): void {
    $stmt = $conn->prepare("SELECT id FROM notification_preferences WHERE user_id = ? AND type_id = ? LIMIT 1");
    $stmt->execute([$userId, $typeId]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        $update = $conn->prepare("UPDATE notification_preferences SET email_enabled = ?, push_enabled = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$emailEnabled, $pushEnabled, $existingId]);
    } else {
        $insert = $conn->prepare("INSERT INTO notification_preferences (user_id, type_id, email_enabled, sms_enabled, push_enabled) VALUES (?, ?, ?, 0, ?)");
        $insert->execute([$userId, $typeId, $emailEnabled, $pushEnabled]);
    }
}

// Función para mostrar datos de forma segura
function safeDisplay($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
$userId = $_SESSION['user_id'] ?? null;
// Verificar acceso del usuario antes de procesar peticiones POST y posibles redirect
requireRole(['user', 'customer']);
$userData = getUserData($conn, $userId);

// `getUserData()` ya devuelve la ruta `uploads/users/` en `image`.
// Evitamos volver a hacer `SELECT *` para no sobreescribir la ruta del avatar.

// Asegurar que todos los campos existan
$userData = array_merge([
    'name' => '',
    'email' => '',
    'phone' => '',
    'image' => null,
    'last_access' => null,
    'password' => ''
], $userData);

// Preferencias de notificaciones
$notificationTypeMap = [];
$notificationPreferences = [];
$emailNotificationsEnabled = 1;
$productNotificationsEnabled = 1;
$promotionNotificationsEnabled = 1;
$cartReminderNotificationsEnabled = 1;

try {
    $typesStmt = $conn->query("SELECT id, name FROM notification_types WHERE is_active = 1");
    while ($row = $typesStmt->fetch(PDO::FETCH_ASSOC)) {
        $notificationTypeMap[$row['name']] = (int) $row['id'];
    }
} catch (PDOException $e) {
    error_log('No se pudieron obtener los tipos de notificación: ' . $e->getMessage());
}

if ($notificationTypeMap) {
    try {
        $prefStmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        while ($pref = $prefStmt->fetch(PDO::FETCH_ASSOC)) {
            $typeId = (int) $pref['type_id'];
            $notificationPreferences[$typeId] = $pref;
        }
    } catch (PDOException $e) {
        error_log('No se pudieron obtener las preferencias de notificación: ' . $e->getMessage());
    }
}

if ($notificationPreferences) {
    $emailValues = array_column($notificationPreferences, 'email_enabled');
    if ($emailValues) {
        $emailNotificationsEnabled = (int) min($emailValues);
    }
}

$productTypeId = $notificationTypeMap['product'] ?? null;
$promotionTypeId = $notificationTypeMap['promotion'] ?? null;
$orderTypeId = $notificationTypeMap['order'] ?? null; // Usado para recordatorios de carrito

if ($productTypeId && isset($notificationPreferences[$productTypeId])) {
    $productNotificationsEnabled = (int) $notificationPreferences[$productTypeId]['push_enabled'];
}

if ($promotionTypeId && isset($notificationPreferences[$promotionTypeId])) {
    $promotionNotificationsEnabled = (int) $notificationPreferences[$promotionTypeId]['push_enabled'];
}

if ($orderTypeId && isset($notificationPreferences[$orderTypeId])) {
    $cartReminderNotificationsEnabled = (int) $notificationPreferences[$orderTypeId]['push_enabled'];
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Procesar cada sección según el formulario enviado
            if (isset($_POST['update_profile'])) {
            // Actualizar información básica
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'], 
                $_POST['phone'], 
                    $userId
            ]);
            // Subida de imagen de perfil si se ha seleccionado
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowedMimes = ['image/jpeg','image/png','image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // Validaciones básicas
                if ($file['size'] > $maxSize) {
                    $_SESSION['error_message'] = 'La imagen es demasiado grande. Máximo 2MB.';
                } else {
                    $info = @getimagesize($file['tmp_name']);
                    if (!$info || !in_array($info['mime'], $allowedMimes)) {
                        $_SESSION['error_message'] = 'Formato no válido. Usa JPG, PNG o WEBP.';
                    } else {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $safeName = $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                        $destDir = __DIR__ . '/../uploads/users/';
                        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                        $destPath = $destDir . $safeName;

                        if (move_uploaded_file($file['tmp_name'], $destPath)) {
                            // Borrar foto previa si existia
                            $oldImage = $userData['image'] ?? null;
                            if ($oldImage && strpos($oldImage, 'uploads/users/') === 0) {
                                $oldPath = __DIR__ . '/../' . $oldImage;
                                if (file_exists($oldPath)) @unlink($oldPath);
                            }

                            // Guardar solo el nombre del archivo en users.image
                            $stmtImg = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
                            $stmtImg->execute([$safeName, $userId]);
                        } else {
                            $_SESSION['error_message'] = 'No se pudo subir la imagen.';
                        }
                    }
                }
            }

            $_SESSION['success_message'] = "Información actualizada correctamente";
            header("Location: ".BASE_URL."/users/settings.php");
            exit();
        } 
        elseif (isset($_POST['update_email'])) {
            // Actualizar email con verificación
            if (password_verify($_POST['password_email'], $userData['password'])) {
                // Aquí deberías implementar un sistema de verificación por email
                $_SESSION['success_message'] = "Se ha enviado un enlace de verificación a tu nuevo correo";
                header("Location: ".BASE_URL."/users/settings.php");
                exit();
            } else {
                $_SESSION['error_message'] = "La contraseña actual es incorrecta";
            }
        }
        elseif (isset($_POST['update_password'])) {
            // Actualizar contraseña
            if (password_verify($_POST['current_password'], $userData['password'])) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $userId]);
                
                $_SESSION['success_message'] = "Contraseña actualizada correctamente";
                header("Location: ".BASE_URL."/users/settings.php");
                exit();
            } else {
                $_SESSION['error_message'] = "La contraseña actual es incorrecta";
            }
        }
        elseif (isset($_POST['update_notifications'])) {
            $emailPref = isset($_POST['email_notifications']) ? 1 : 0;
            $productPref = isset($_POST['product_notifications']) ? 1 : 0;
            $promotionPref = isset($_POST['promotion_notifications']) ? 1 : 0;
            $cartPref = isset($_POST['cart_reminders']) ? 1 : 0;

            $conn->beginTransaction();

            if ($productTypeId) {
                upsertNotificationPreference($conn, $userId, $productTypeId, $emailPref, $productPref);
            }
            if ($promotionTypeId) {
                upsertNotificationPreference($conn, $userId, $promotionTypeId, $emailPref, $promotionPref);
            }
            if ($orderTypeId) {
                upsertNotificationPreference($conn, $userId, $orderTypeId, $emailPref, $cartPref);
            }

            $updateAll = $conn->prepare("UPDATE notification_preferences SET email_enabled = ?, updated_at = NOW() WHERE user_id = ?");
            $updateAll->execute([$emailPref, $userId]);

            $conn->commit();

            $_SESSION['success_message'] = "Preferencias de notificación actualizadas";
            header("Location: ".BASE_URL."/users/settings.php#notifications");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Error en actualización de ajustes: " . $e->getMessage());
        $_SESSION['error_message'] = "Ocurrió un error al actualizar la información";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes de Cuenta - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/ajustesuser.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
</head>

<body>
    <?php require_once __DIR__ . '/../layouts/headerproducts.php'; ?>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>
        
        <main class="user-main-content">
            <div class="dashboard-header">
                <h1>Ajustes de tu cuenta</h1>
                <p>Administra tu información personal, seguridad y preferencias.</p>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success_message'] ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error_message'] ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Menú lateral de ajustes -->
                <div class="settings-sidebar">
                    <ul>
                        <li class="active"><a href="#profile"><i class="fas fa-user"></i> Perfil</a></li>
                        <li><a href="#security"><i class="fas fa-lock"></i> Seguridad</a></li>
                        <li><a href="#notifications"><i class="fas fa-bell"></i> Notificaciones</a></li>
                    </ul>
                </div>

                <!-- Contenido de ajustes -->
                <div class="settings-content">
                    <!-- Sección de Perfil -->
                    <section id="profile" class="settings-section active">
                        <h2><i class="fas fa-user"></i> Información del Perfil</h2>
                        <form method="POST" enctype="multipart/form-data" class="settings-form">
                            <div class="form-group">
                                <label for="name">Nombre completo</label>
                                <input type="text" id="name" name="name" value="<?= safeDisplay($userData['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input type="email" id="email" value="<?= safeDisplay($userData['email']) ?>" disabled>
                                <small>Para cambiar tu correo, haz clic <a href="#change-email" class="change-email-link">aquí</a></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="tel" id="phone" name="phone" value="<?= safeDisplay($userData['phone']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Foto de perfil</label>
                                <div class="profile-picture-upload">
                                    <?php 
                                    $imagePath = !empty($userData['image']) ? (function_exists('normalizeUserImagePath') ? normalizeUserImagePath($userData['image']) : $userData['image']) : 'images/default-avatar.png';
                                    $imageUrl = BASE_URL . '/' . $imagePath;
                                    ?>
                                    <img src="<?= $imageUrl ?>" alt="Foto de perfil">
                                    <button type="button" class="btn-change-photo">Cambiar foto</button>
                                    <input type="file" id="profile-picture" name="profile_picture" accept="image/*" style="display: none;">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn-save">Guardar cambios</button>
                        </form>
                    </section>

                    <!-- Sección para cambiar email (oculta inicialmente) -->
                    <section id="change-email" class="settings-section" style="display: none;">
                        <h2><i class="fas fa-envelope"></i> Cambiar correo electrónico</h2>
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label for="current_email">Correo actual</label>
                                <input type="email" id="current_email" value="<?= safeDisplay($userData['email']) ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_email">Nuevo correo electrónico</label>
                                <input type="email" id="new_email" name="new_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_email">Confirmar nuevo correo</label>
                                <input type="email" id="confirm_email" name="confirm_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_email">Contraseña actual</label>
                                <input type="password" id="password_email" name="password_email" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn-cancel">Cancelar</button>
                                <button type="submit" name="update_email" class="btn-save">Guardar cambios</button>
                            </div>
                        </form>
                    </section>

                    <!-- Sección de Seguridad -->
                    <section id="security" class="settings-section">
                        <h2><i class="fas fa-lock"></i> Seguridad y acceso</h2>
                        <div class="security-item-father">
                        <div class="security-item">
                            <div class="security-info">
                                <h3>Cambiar contraseña</h3>
                                <p>Actualiza tu contraseña regularmente para mantener tu cuenta segura.</p>
                            </div>
                            <button type="button" class="btn-edit" onclick="showPasswordForm()">Cambiar</button>
                        </div>
                        
                        <form id="password-form" method="POST" class="settings-form" style="display: none;">
                            <div class="form-group">
                                <label for="current_password">Contraseña actual</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Nueva contraseña</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small>Mínimo 8 caracteres, incluyendo números y letras</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmar nueva contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn-cancel" onclick="hidePasswordForm()">Cancelar</button>
                                <button type="submit" name="update_password" class="btn-save">Guardar cambios</button>
                            </div>
                        </form>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h3>Sesión actual</h3>
                                <p>Iniciada el <?= safeDisplay($userData['last_access']) ? date('d/m/Y H:i', strtotime($userData['last_access'])) : 'Desconocido' ?> </p>
                            </div>
                            <button type="button" class="btn-logout" onclick="location.href='<?= BASE_URL ?>/auth/logout.php'">Cerrar sesión</button>
                        </div>
                        </div>
                    </section>

                    <!-- Sección de Notificaciones -->
                    <section id="notifications" class="settings-section">
                        <h2><i class="fas fa-bell"></i> Preferencias de notificaciones</h2>
                        
                        <form method="POST" class="settings-form">
                            <div class="form-group toggle-group">
                                <label>Notificaciones por correo electrónico</label>
                                <label class="switch">
                                    <input type="checkbox" name="email_notifications" value="1" <?= $emailNotificationsEnabled ? 'checked' : '' ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            
                            <div class="form-group toggle-group">
                                <label>Notificaciones de nuevos productos</label>
                                <label class="switch">
                                    <input type="checkbox" name="product_notifications" value="1" <?= $productNotificationsEnabled ? 'checked' : '' ?> <?= $productTypeId ? '' : 'disabled' ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            
                            <div class="form-group toggle-group">
                                <label>Notificaciones de ofertas especiales</label>
                                <label class="switch">
                                    <input type="checkbox" name="promotion_notifications" value="1" <?= $promotionNotificationsEnabled ? 'checked' : '' ?> <?= $promotionTypeId ? '' : 'disabled' ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            
                            <div class="form-group toggle-group">
                                <label>Recordatorios de carrito abandonado</label>
                                <label class="switch">
                                    <input type="checkbox" name="cart_reminders" value="1" <?= $cartReminderNotificationsEnabled ? 'checked' : '' ?> <?= $orderTypeId ? '' : 'disabled' ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            
                            <input type="hidden" name="update_notifications" value="1">
                            <button type="submit" class="btn-save">Guardar preferencias</button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>

    <script>
        // Mostrar/ocultar secciones
        document.querySelectorAll('.settings-sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Ocultar todas las secciones
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.classList.remove('active');
                    section.style.display = 'none';
                });
                
                // Mostrar la sección seleccionada
                const target = this.getAttribute('href').substring(1);
                document.getElementById(target).classList.add('active');
                document.getElementById(target).style.display = 'block';
                
                // Actualizar el menú activo
                document.querySelectorAll('.settings-sidebar li').forEach(item => {
                    item.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });
        
        // Mostrar formulario para cambiar email
        document.querySelector('.change-email-link').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('profile').style.display = 'none';
            document.getElementById('change-email').style.display = 'block';
        });
        
        // Cancelar cambio de email
        document.querySelector('#change-email .btn-cancel').addEventListener('click', function() {
            document.getElementById('profile').style.display = 'block';
            document.getElementById('change-email').style.display = 'none';
        });
        
        // Cambiar foto de perfil
        document.querySelector('.btn-change-photo').addEventListener('click', function() {
            document.getElementById('profile-picture').click();
        });
        
        document.getElementById('profile-picture').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    document.querySelector('.profile-picture-upload img').src = event.target.result;
                    // Aquí deberías implementar la subida del archivo al servidor
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Mostrar/ocultar formulario de contraseña
        function showPasswordForm() {
            document.getElementById('password-form').style.display = 'block';
        }
        
        function hidePasswordForm() {
            document.getElementById('password-form').style.display = 'none';
        }
    </script>
</body>
</html>