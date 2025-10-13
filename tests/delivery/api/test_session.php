<?php
// Test simple de sesión
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_exists' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'session_data' => $_SESSION ?? []
]);
