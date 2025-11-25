<?php
require_once __DIR__ . '/conexion.php';
header('Content-Type: text/plain');
$stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE collection_id = 1");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Products in Collection 1 (Verano Mágico): " . $res['count'];
?>
