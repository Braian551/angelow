<?php
// conexion_mongodb.php

if (!extension_loaded('mongodb')) {
    die('❌ Error: El driver de MongoDB no está instalado');
}

// CONFIGURACIÓN
$usuario = "braianoquen";
$password = "braianroot";
$cluster = "angelow.4e330nx";
$baseDatos = "angelow";

try {
    $connectionString = "mongodb+srv://{$usuario}:{$password}@{$cluster}.mongodb.net/{$baseDatos}?retryWrites=true&w=majority";
    $client = new MongoDB\Client($connectionString);
    $client->listDatabases();

    echo "✅ Conexión exitosa a MongoDB Atlas";
} catch (Exception $e) {
    die('❌ Error: ' . $e->getMessage());
}
