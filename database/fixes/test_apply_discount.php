<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

session_start();
// Use a real user id that exists in DB; adapt if needed
$_SESSION['user_id'] = '6861e06ddcf49';

// Simulate POST
$_POST['discount_code'] = 'E20FA9C5';
$_POST['subtotal'] = 210000;
$_POST['action'] = 'apply';

// Include the API file - it will echo JSON
require __DIR__ . '/../../tienda/pagos/apply_discount.php';
