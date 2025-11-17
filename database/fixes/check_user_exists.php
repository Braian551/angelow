<?php
require_once __DIR__ . '/../../conexion.php';
$email = $argv[1] ?? 'testcurl+1@example.com';
$stmt = $conn->prepare('SELECT id,email,name FROM users WHERE email = ?');
$stmt->execute([$email]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$result) {
    echo "No user found for $email\n";
} else {
    echo "Found user(s):\n";
    print_r($result);
}
?>