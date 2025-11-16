<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

// Protegido para entornos locales
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('Solo migraciones en entorno local');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $sql = file_get_contents(__DIR__ . '/013_add_shipping_method_to_orders.sql');
        if ($sql === false) {
            throw new Exception('No se encontró el archivo de migración');
        }

        $statements = array_filter(array_map('trim', explode(';', $sql)), function($s) {
            return !empty($s);
        });

        $conn->beginTransaction();
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $conn->exec($statement);
            }
        }
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Migración 013 aplicada correctamente']);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Migración 013 - shipping_method_id</title>
</head>
<body>
    <h1>Migración 013 - Agregar shipping_method_id a orders</h1>
    <p>Ejecutar desde entorno local con respaldo de BD.</p>
    <form method="POST">
        <button type="submit">Ejecutar migración</button>
    </form>
</body>
</html>