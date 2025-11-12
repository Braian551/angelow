<?php
require_once 'config.php';
require_once 'conexion.php';

// Simulate session
$_SESSION['user_id'] = '6860007924a6a'; // Admin user ID from the database

// Simulate GET parameters
$_GET = [
    'page' => 1
];

// Include the AJAX file
ob_start();
include 'ajax/admin/productos/productsearchadmin.php';
$content = ob_get_clean();

echo "Response: " . $content . PHP_EOL;
?>