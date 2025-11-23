<?php
require 'config.php';
require 'conexion.php';

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM question_answers");
    $count = $stmt->fetchColumn();
    echo "Total answers in DB: " . $count . "\n";
    
    if ($count > 0) {
        $stmt = $conn->query("SELECT * FROM question_answers LIMIT 5");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}