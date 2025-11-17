<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
require_once __DIR__ . '/../../admin/services/admin_profiles.php';

requireRole('admin');
ensure_admin_profiles_table($conn);

$userId = $_GET['id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    header('Location: ' . BASE_URL . '/admin/admins/index.php');
    exit;
}

$errors = [];
$success = false;

try {
    // Load current user + profile
    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.phone, u.image, u.created_at, u.last_access, u.is_blocked,
            ap.job_title, ap.department, ap.responsibilities, ap.emergency_contact
        FROM users u
        LEFT JOIN admin_profiles ap ON ap.user_id COLLATE utf8mb4_general_ci = u.id
        WHERE u.id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ' . BASE_URL . '/admin/admins/index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate inputs
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $responsibilities = trim($_POST['responsibilities'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $isBlocked = isset($_POST['is_blocked']) && $_POST['is_blocked'] ? 1 : 0;

        if ($name === '' || !$email) {
            $errors[] = 'Nombre y correo son obligatorios';
        }

        // Email uniqueness check
        if ($email) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetchColumn()) {
                $errors[] = 'El correo ya está en uso por otro usuario';
            }
        }

        if (empty($errors)) {
            $conn->beginTransaction();
            $update = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, is_blocked = ? WHERE id = ?');
            $update->execute([$name, $email, $phone ?: null, $isBlocked, $userId]);

            save_admin_profile($conn, $userId, [
                'job_title' => $jobTitle,
                'department' => $department,
                'responsibilities' => $responsibilities,
                'emergency_contact' => $emergencyContact
            ]);

            $conn->commit();

            $success = true;
            // reload
            header('Location: ' . BASE_URL . '/admin/admins/index.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('admin_edit error: ' . $e->getMessage());
    $errors[] = 'No se pudo guardar la información';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Editar administrador - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/management-hub.css">
</head>
<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>
            <div class="management-hub">
                <div class="page-header">
                    <h1>Editar Administrador</h1>
                </div>

                <section class="surface-card">
                    <?php if (!empty($errors)): ?>
                        <div class="text-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                        <div class="form-row">
                            <label>Nombre</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-row">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-row">
                            <label>Teléfono</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        <div class="form-row">
                            <label>Título / Cargo</label>
                            <input type="text" name="job_title" value="<?= htmlspecialchars($user['job_title'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Departamento</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Responsabilidades</label>
                            <textarea name="responsibilities"><?= htmlspecialchars($user['responsibilities'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <label>Contacto de emergencia</label>
                            <input type="text" name="emergency_contact" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <label>Bloqueado</label>
                            <input type="checkbox" name="is_blocked" value="1" <?= $user['is_blocked'] ? 'checked' : '' ?>>
                        </div>

                        <div class="actions">
                            <button class="btn-soft primary" type="submit">Guardar</button>
                            <a class="btn-soft" href="<?= BASE_URL ?>/admin/admins/index.php">Cancelar</a>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
