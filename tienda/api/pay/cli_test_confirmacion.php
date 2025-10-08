<?php
// Script de prueba CLI para include de confirmacion.php
chdir(__DIR__ . '/../../');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Simular sesión
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 1;
$_SESSION['last_order'] = 'TEST123';

// Crear orden y datos mínimos en DB si es necesario — pero no tocar DB: solo includear y capturar errores
try {
    ob_start();
    include __DIR__ . '/../../tienda/confirmacion.php';
    $output = ob_get_clean();
    echo "Inclusion completa. Longitud salida: " . strlen($output) . " bytes\n";
} catch (Throwable $t) {
    echo "ERROR: " . $t->getMessage() . "\n";
}
