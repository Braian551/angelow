<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

try {
    $stmt = $conn->query('SHOW CREATE TABLE `audit_users`');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "=== CREATE TABLE for audit_users ===\n";
    echo $row['Create Table'] . "\n";

    $stmt2 = $conn->prepare("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_users'");
    $stmt2->execute();
    $ai = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "AUTO_INCREMENT: " . ($ai['AUTO_INCREMENT'] ?? 'NULL') . "\n";

    $stmt3 = $conn->query("SELECT COUNT(*), MAX(id) FROM audit_users");
    $row3 = $stmt3->fetch(PDO::FETCH_NUM);
    echo "Count: " . $row3[0] . " | MAX(id): " . $row3[1] . "\n";

} catch (PDOException $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}
?>