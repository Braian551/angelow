<?php
// Test simple para verificar acceso
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

echo json_encode([
    'test' => 'ok',
    'session_active' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'base_url' => BASE_URL,
    'file_path' => __FILE__
]);
?>
