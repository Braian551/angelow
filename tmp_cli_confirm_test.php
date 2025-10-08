<?php
// Temporal test runner
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_SCHEME']='http';
if (session_status()===PHP_SESSION_NONE) session_start();
$_SESSION['user_id']=1;
$_SESSION['last_order']='TEST123';

include __DIR__ . '/tienda/confirmacion.php';
