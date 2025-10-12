<?php
require_once __DIR__ . '/../../../conexion.php';

echo "=== VERIFICANDO COLLATIONS ===\n\n";

echo "Tabla users, columna id:\n";
$stmt = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME, COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'angelow'
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'id'
");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Tipo: {$result['COLUMN_TYPE']}\n";
echo "   Collation: {$result['COLLATION_NAME']}\n\n";

echo "Tabla order_status_history, columna changed_by:\n";
$stmt = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME, COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'angelow'
    AND TABLE_NAME = 'order_status_history'
    AND COLUMN_NAME = 'changed_by'
");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Tipo: {$result['COLUMN_TYPE']}\n";
echo "   Collation: {$result['COLLATION_NAME']}\n\n";

echo "Tabla orders:\n";
$stmt = $conn->query("SHOW TABLE STATUS WHERE Name = 'orders'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Collation: {$result['Collation']}\n\n";

echo "Tabla users:\n";
$stmt = $conn->query("SHOW TABLE STATUS WHERE Name = 'users'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Collation: {$result['Collation']}\n\n";

echo "Tabla order_status_history:\n";
$stmt = $conn->query("SHOW TABLE STATUS WHERE Name = 'order_status_history'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Collation: {$result['Collation']}\n";
