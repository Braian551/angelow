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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/administrators.css">
</head>
<body>
<div class="admin-container">
    <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>
    <main class="admin-content">
        <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

        <div class="dashboard-content">
            <div class="page-header">
                <h1><i class="fas fa-user-plus"></i> Crear administrador</h1>
                <div class="breadcrumb">
                    <a href="<?= BASE_URL ?>/admin">Dashboard</a> / 
                    <a href="<?= BASE_URL ?>/admin/admins/index.php">Administradores</a> / 
                    <span>Crear</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Información del Administrador</h2>
                </div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombre Completo</label>
                            <input type="text" name="name" class="form-control" required placeholder="Ingrese el nombre completo">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required placeholder="ejemplo@correo.com">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" name="password" class="form-control" required placeholder="Ingrese una contraseña segura">
                        </div>
                        
                        <div class="form-actions mt-4" style="display: flex; gap: 1rem; justify-content: flex-end;">
                            <a href="<?= BASE_URL ?>/admin/admins/index.php" class="btn btn-secondary" style="background-color: var(--text-light); color: white;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-save"></i> Guardar Administrador
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
