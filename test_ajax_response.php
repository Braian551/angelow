<?php
session_start();
require_once 'config.php';
require_once 'conexion.php';

// Simular sesión de admin
$_SESSION['user_id'] = 1; // Asumiendo que hay un admin con ID 1

$url = BASE_URL . '/ajax/admin/productos/productsearchadmin.php?page=1';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: PHPSESSID=' . session_id()));
$response = curl_exec($ch);
curl_close($ch);

echo "URL: $url\n";
echo "Response:\n";
echo $response;
?>