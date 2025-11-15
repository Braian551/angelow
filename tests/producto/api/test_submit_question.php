<?php
/**
 * Test manual para submit_question
 * - Ejecutar vía navegador o curl con POST
 * - Asegurarte de estar autenticado, o sesión simulada
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

session_start();
// Simular usuario de prueba: Comentar si quieres usar sesión real
// $_SESSION['user_id'] = '6861e06ddcf49';

// Endpoint
$endpoint = BASE_URL . '/api/submit_question.php';

// Data de ejemplo
$postData = [
    'product_id' => 71,
    'question' => '¿Este producto viene en versión para bebé de menos de 1 año?'
];

// Hacer llamado CURL
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');
if ($err) {
    echo json_encode(['success' => false, 'error' => $err]);
} else {
    echo $response;
}
