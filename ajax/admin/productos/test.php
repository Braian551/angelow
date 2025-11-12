<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'base_url' => BASE_URL,
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set'
]);
?>