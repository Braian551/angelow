<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';
requireRole('admin');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'admin');
    if (empty($email) || empty($name) || empty($password)) {
        $errors[] = 'Complete todos los campos obligatorios';
    } else {
        // Simple creation flow for admin
        try {
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (:n, :e, :p, :r)');
            $stmt->execute([':n' => $name, ':e' => $email, ':p' => password_hash($password, PASSWORD_DEFAULT), ':r' => $role]);
            header('Location: ' . BASE_URL . '/admin/admins/index.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = 'No se pudo crear el usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear administrador | Panel Angelow</title>
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
                <h1>Crear administrador</h1>
            </div>
            <section class="surface-card">
                <?php if ($errors): ?>
                    <div class="text-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-row">
                        <label>Nombre</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-row">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-row">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="actions">
                        <button class="btn-soft primary" type="submit">Crear</button>
                        <a class="btn-soft" href="<?= BASE_URL ?>/admin/admins/index.php">Cancelar</a>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
</body>
</html>
