<?php
/**
 * Middleware para redirección basada en roles
 * Este archivo maneja la lógica de redirección después del login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

/**
 * Obtiene el dashboard correspondiente según el rol del usuario
 */
function getDashboardByRole($role) {
    $dashboards = [
        'admin' => BASE_URL . '/admin/dashboardadmin.php',
        'user' => BASE_URL . '/users/dashboarduser.php',
        'customer' => BASE_URL . '/users/dashboarduser.php'
    ];

    return $dashboards[$role] ?? BASE_URL . '/users/dashboarduser.php';
}

/**
 * Obtiene las páginas permitidas por rol
 */
function getAllowedPagesByRole($role) {
    $allowedPages = [
        'admin' => ['admin/', 'auth/logout.php'],
        'user' => ['users/', 'tienda/', 'producto/', 'pagos/', 'donaciones/', 'auth/logout.php'],
        'customer' => ['users/', 'tienda/', 'producto/', 'pagos/', 'donaciones/', 'auth/logout.php']
    ];

    return $allowedPages[$role] ?? [];
}

/**
 * Verifica si el usuario tiene acceso a la página actual
 */
function checkRoleAccess($role, $currentPage) {
    $allowedPages = getAllowedPagesByRole($role);
    
    foreach ($allowedPages as $allowedPath) {
        if (strpos($currentPage, $allowedPath) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Redirige al usuario al dashboard correcto según su rol
 */
function redirectToDashboard($userId, $conn) {
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $dashboard = getDashboardByRole($user['role']);
            header("Location: $dashboard");
            exit();
        }
    } catch (PDOException $e) {
        error_log('Error al obtener rol de usuario: ' . $e->getMessage());
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}

/**
 * Verifica y redirige si el usuario está en una página no permitida para su rol
 */
function enforceRoleAccess() {
    global $conn;
    
    // No verificar en páginas públicas
    $publicPages = ['index.php', 'login.php', 'register.php', 'logout.php', 'productos.php', 'verproducto.php'];
    $currentScript = basename($_SERVER['PHP_SELF']);
    
    if (in_array($currentScript, $publicPages)) {
        return;
    }
    
    // Verificar si hay sesión activa
    if (!isset($_SESSION['user_id'])) {
        return; // Dejar que otros middlewares manejen la autenticación
    }
    
    $userId = $_SESSION['user_id'];
    $currentPage = $_SERVER['PHP_SELF'];
    
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            session_destroy();
            header("Location: " . BASE_URL . "/auth/login.php");
            exit();
        }
        
        $role = $user['role'];
        
        // Verificar si el usuario tiene acceso a la página actual
        if (!checkRoleAccess($role, $currentPage)) {
            // Redirigir al dashboard correspondiente
            $dashboard = getDashboardByRole($role);
            header("Location: $dashboard");
            exit();
        }
        
        // Guardar el rol en la sesión para uso futuro
        $_SESSION['user_role'] = $role;
        
    } catch (PDOException $e) {
        error_log('Error al verificar acceso por rol: ' . $e->getMessage());
    }
}

/**
 * Verifica que el usuario tenga el rol requerido
 * Uso: requireRole('admin') o requireRole(['admin', 'user'])
 */
function requireRole($requiredRoles) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
    
    // Convertir a array si es un string
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !in_array($user['role'], $requiredRoles)) {
            // Redirigir al dashboard correcto del usuario
            $dashboard = getDashboardByRole($user['role'] ?? 'user');
            header("Location: $dashboard");
            exit();
        }
        
        // Guardar el rol en la sesión para uso futuro (con ambas claves para compatibilidad)
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_role'] = $user['role'];
        
        return $user['role'];
        
    } catch (PDOException $e) {
        error_log('Error al verificar rol requerido: ' . $e->getMessage());
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}
