<?php
/**
 * Script de prueba para verificar conexión y consultas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

echo "<h1>Test de Conexión - Delivery API</h1>";

// 1. Verificar sesión
echo "<h2>1. Sesión</h2>";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NO DEFINIDO') . "\n";
echo "User Role: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "\n";
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    die("<p style='color: red;'>ERROR: No hay sesión iniciada. Por favor inicia sesión primero.</p>");
}

$driverId = $_SESSION['user_id'];

// 2. Verificar usuario en BD
echo "<h2>2. Datos del Usuario en BD</h2>";
try {
    $stmt = $conn->prepare("SELECT id, name, apellido, email, role FROM users WHERE id = ?");
    $stmt->execute([$driverId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Usuario no encontrado en la base de datos</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al consultar usuario: " . $e->getMessage() . "</p>";
}

// 3. Verificar estructura de tablas
echo "<h2>3. Verificación de Tablas</h2>";

// Verificar tabla orders
try {
    $stmt = $conn->query("SHOW COLUMNS FROM orders");
    echo "<h3>Columnas de 'orders':</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al consultar tabla orders: " . $e->getMessage() . "</p>";
}

// Verificar tabla order_deliveries
try {
    $stmt = $conn->query("SHOW COLUMNS FROM order_deliveries");
    echo "<h3>Columnas de 'order_deliveries':</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al consultar tabla order_deliveries: " . $e->getMessage() . "</p>";
}

// 4. Probar consulta de órdenes disponibles
echo "<h2>4. Consulta de Órdenes Disponibles</h2>";
try {
    $query = "
        SELECT 
            o.id,
            o.order_number,
            o.total,
            o.shipping_address,
            o.shipping_city,
            o.delivery_notes,
            o.created_at,
            o.status as order_status,
            o.payment_status,
            CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as customer_name,
            u.phone as customer_phone,
            u.email as customer_email
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        WHERE o.status = 'shipped'
        AND o.payment_status = 'paid'
        AND NOT EXISTS (
            SELECT 1 FROM order_deliveries od2 
            WHERE od2.order_id = o.id 
            AND od2.delivery_status NOT IN ('rejected', 'cancelled')
        )
        LIMIT 5
    ";
    
    $stmt = $conn->query($query);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Órdenes disponibles encontradas: " . count($orders) . "</p>";
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al consultar órdenes disponibles: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 5. Probar consulta de órdenes asignadas
echo "<h2>5. Consulta de Órdenes Asignadas a Este Transportista</h2>";
try {
    $query = "
        SELECT 
            o.id,
            o.order_number,
            od.id as delivery_id,
            od.driver_id,
            od.delivery_status
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        WHERE od.driver_id = ?
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$driverId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Órdenes asignadas encontradas: " . count($orders) . "</p>";
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al consultar órdenes asignadas: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 6. Verificar tipo de dato de driver_id
echo "<h2>6. Información de driver_id</h2>";
echo "<p>driver_id de la sesión: <strong>" . $driverId . "</strong></p>";
echo "<p>Tipo de dato: <strong>" . gettype($driverId) . "</strong></p>";

echo "<hr>";
echo "<p>Test completado. Revisa los resultados arriba.</p>";
?>
