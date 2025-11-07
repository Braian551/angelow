<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'dashboardadmin.php') {
                $redirect_url = defined('BASE_URL') 
                    ? BASE_URL . '/admin/dashboardadmin.php' 
                    : '/admin/dashboardadmin.php';
                
                header("Location: $redirect_url");
                exit();
            }
        }

    } catch (PDOException $e) {
        error_log('Error al verificar rol de usuario: ' . $e->getMessage());
    }
}
?>
    
    <header class="main-header">
        <div class="header-container">
            <!-- Logo -->
            <div class="content-logo2">
                <a href="<?= BASE_URL ?>/index.html">
                    <img src="<?= BASE_URL ?>/images/logo2.png" alt="Angelow - Ropa Infantil" width="100">
                </a>
            </div>

 
     
    </header>