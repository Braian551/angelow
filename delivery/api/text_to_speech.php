<?php
/**
 * Proxy para VoiceRSS Text-to-Speech API
 * Evita problemas de CORS al hacer la solicitud desde el servidor
 * 
 * API Gratuita: https://www.voicerss.org/
 * Límite gratuito: 350 solicitudes/día
 */

header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=86400'); // Cache por 24 horas

// API Key gratuita de VoiceRSS
// Registra tu propia key en: https://www.voicerss.org/personel/
$apiKey = '2ca1dadea5ee4f08a4212b6806d44c09'; // Key de ejemplo

// Obtener parámetros
$text = isset($_GET['text']) ? $_GET['text'] : 'Texto de prueba';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es-mx';
$rate = isset($_GET['rate']) ? $_GET['rate'] : '0';

// VoiceRSS no usa parámetro 'voice', solo usa el idioma (lang)
// El idioma determina automáticamente la voz nativa

// Validar longitud del texto (VoiceRSS tiene límite de 100KB)
if (strlen($text) > 10000) {
    $text = substr($text, 0, 10000);
}

// Construir URL de VoiceRSS
$voiceRssUrl = 'https://api.voicerss.org/';
$params = http_build_query([
    'key' => $apiKey,
    'src' => $text,
    'hl' => $lang,
    'r' => $rate,
    'c' => 'MP3',
    'f' => '44khz_16bit_stereo'
]);

$url = $voiceRssUrl . '?' . $params;

// Hacer solicitud a VoiceRSS
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Verificar si hay errores
if ($httpCode !== 200 || $error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener audio de VoiceRSS',
        'details' => $error,
        'http_code' => $httpCode
    ]);
    exit;
}

// Verificar si la respuesta es un error de VoiceRSS (JSON)
if (substr($response, 0, 1) === '{' || substr($response, 0, 1) === '[') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'VoiceRSS devolvió un error',
        'voicerss_response' => $response,
        'url_requested' => $url
    ]);
    exit;
}

// Verificar si la respuesta comienza con "ERROR"
if (strpos($response, 'ERROR') === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'VoiceRSS Error',
        'voicerss_error' => $response,
        'url_requested' => $url
    ]);
    exit;
}

// Devolver el audio MP3
echo $response;
exit;
