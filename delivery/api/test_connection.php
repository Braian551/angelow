<?php
// Simple test to validate the procedures work
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'angelow';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>