<?php
// Simular una petición al AJAX con sesión
session_start();
$_SESSION['user_id'] = '6860007924a6a6'; // ID del admin encontrado

// Hacer la petición usando curl
$ch = curl_init();
$url = 'http://localhost/angelow/ajax/admin/productos/productsearchadmin.php?page=1';
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: PHPSESSID=' . session_id()));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response;
?>