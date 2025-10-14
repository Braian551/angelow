<?php
/**
 * Tests para el Sistema de Sesiones de Navegación
 * 
 * Ejecutar desde consola:
 * php tests/delivery/test_navigation_session.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Colores para consola
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

class NavigationSessionTest {
    private $conn;
    private $testDeliveryId = null;
    private $testDriverId = null;
    private $testOrderId = null;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Ejecutar todos los tests
     */
    public function runAll() {
        echo COLOR_BLUE . "\n";
        echo "╔═══════════════════════════════════════════════════╗\n";
        echo "║   TESTS: Sistema de Sesiones de Navegación      ║\n";
        echo "╚═══════════════════════════════════════════════════╝\n";
        echo COLOR_RESET . "\n";
        
        // Setup
        echo COLOR_YELLOW . "📋 Preparando datos de prueba...\n" . COLOR_RESET;
        $this->setupTestData();
        
        // Tests
        $this->testDatabaseTablesExist();
        $this->testStoredProceduresExist();
        $this->testStartNavigation();
        $this->testUpdateLocation();
        $this->testPauseNavigation();
        $this->testResumeNavigation();
        $this->testSaveRoute();
        $this->testCompleteNavigation();
        $this->testNavigationEvents();
        $this->testTriggers();
        $this->testViews();
        
        // Cleanup
        echo COLOR_YELLOW . "\n🧹 Limpiando datos de prueba...\n" . COLOR_RESET;
        $this->cleanupTestData();
        
        // Resumen
        $this->printSummary();
    }
    
    /**
     * Setup de datos de prueba
     */
    private function setupTestData() {
        try {
            // Crear usuario de prueba (driver)
            $this->testDriverId = 'test_driver_' . uniqid();
            
            $stmt = $this->conn->prepare("
                INSERT INTO users (id, name, email, phone, role, password, status)
                VALUES (?, 'Test Driver', 'testdriver@test.com', '0000000000', 'delivery', '', 'active')
            ");
            $stmt->execute([$this->testDriverId]);
            
            // Crear usuario cliente
            $testCustomerId = 'test_customer_' . uniqid();
            $stmt = $this->conn->prepare("
                INSERT INTO users (id, name, email, phone, role, password, status)
                VALUES (?, 'Test Customer', 'testcustomer@test.com', '1111111111', 'customer', '', 'active')
            ");
            $stmt->execute([$testCustomerId]);
            
            // Crear orden de prueba
            $stmt = $this->conn->prepare("
                INSERT INTO orders (
                    user_id, order_number, total, status,
                    shipping_address, shipping_city, shipping_state
                )
                VALUES (?, ?, 50000, 'processing', 'Calle Test 123', 'Ciudad Test', 'Estado Test')
            ");
            $orderNumber = 'TEST-' . time();
            $stmt->execute([$testCustomerId, $orderNumber]);
            $this->testOrderId = $this->conn->lastInsertId();
            
            // Crear entrega de prueba
            $stmt = $this->conn->prepare("
                INSERT INTO order_deliveries (
                    order_id, driver_id, delivery_status, 
                    assigned_at, accepted_at,
                    destination_lat, destination_lng
                )
                VALUES (?, ?, 'driver_accepted', NOW(), NOW(), -34.6037, -58.3816)
            ");
            $stmt->execute([$this->testOrderId, $this->testDriverId]);
            $this->testDeliveryId = $this->conn->lastInsertId();
            
            echo COLOR_GREEN . "✅ Datos de prueba creados correctamente\n" . COLOR_RESET;
            echo "   - Driver ID: {$this->testDriverId}\n";
            echo "   - Order ID: {$this->testOrderId}\n";
            echo "   - Delivery ID: {$this->testDeliveryId}\n\n";
            
        } catch (Exception $e) {
            echo COLOR_RED . "❌ Error al crear datos de prueba: " . $e->getMessage() . "\n" . COLOR_RESET;
            exit(1);
        }
    }
    
    /**
     * Test: Verificar que las tablas existen
     */
    private function testDatabaseTablesExist() {
        $testName = "Verificar existencia de tablas";
        
        try {
            $tables = [
                'delivery_navigation_sessions',
                'delivery_navigation_events'
            ];
            
            foreach ($tables as $table) {
                $stmt = $this->conn->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                
                if (!$exists) {
                    throw new Exception("La tabla $table no existe");
                }
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Verificar procedimientos almacenados
     */
    private function testStoredProceduresExist() {
        $testName = "Verificar procedimientos almacenados";
        
        try {
            $procedures = [
                'StartNavigation',
                'PauseNavigation',
                'UpdateNavigationLocation',
                'GetNavigationState',
                'CompleteNavigation',
                'SaveRouteData'
            ];
            
            foreach ($procedures as $procedure) {
                $stmt = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = '$procedure'");
                $exists = $stmt->rowCount() > 0;
                
                if (!$exists) {
                    throw new Exception("El procedimiento $procedure no existe");
                }
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Iniciar navegación
     */
    private function testStartNavigation() {
        $testName = "Iniciar navegación";
        
        try {
            $stmt = $this->conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?)");
            $deviceInfo = json_encode(['device' => 'test', 'os' => 'test']);
            $stmt->execute([
                $this->testDeliveryId,
                $this->testDriverId,
                -34.6037,
                -58.3816,
                $deviceInfo
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado: " . $result['message']);
            }
            
            // Verificar que se creó la sesión
            $stmt = $this->conn->prepare("
                SELECT * FROM delivery_navigation_sessions 
                WHERE delivery_id = ? AND driver_id = ?
            ");
            $stmt->execute([$this->testDeliveryId, $this->testDriverId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                throw new Exception("No se creó la sesión en la base de datos");
            }
            
            if ($session['session_status'] !== 'navigating') {
                throw new Exception("Estado de sesión incorrecto: " . $session['session_status']);
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Actualizar ubicación
     */
    private function testUpdateLocation() {
        $testName = "Actualizar ubicación";
        
        try {
            $stmt = $this->conn->prepare("
                CALL UpdateNavigationLocation(?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->testDeliveryId,
                $this->testDriverId,
                -34.6040,
                -58.3820,
                35.5, // speed
                5.2,  // distance_remaining
                900,  // eta_seconds
                85    // battery_level
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado");
            }
            
            // Verificar actualización
            $stmt = $this->conn->prepare("
                SELECT * FROM delivery_navigation_sessions 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session['current_speed_kmh'] != 35.5) {
                throw new Exception("Velocidad no actualizada correctamente");
            }
            
            if ($session['remaining_distance_km'] != 5.2) {
                throw new Exception("Distancia no actualizada correctamente");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Pausar navegación
     */
    private function testPauseNavigation() {
        $testName = "Pausar navegación";
        
        try {
            $stmt = $this->conn->prepare("CALL PauseNavigation(?, ?)");
            $stmt->execute([$this->testDeliveryId, $this->testDriverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado: " . $result['message']);
            }
            
            // Verificar estado
            $stmt = $this->conn->prepare("
                SELECT session_status FROM delivery_navigation_sessions 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session['session_status'] !== 'paused') {
                throw new Exception("Estado no cambió a 'paused'");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Reanudar navegación
     */
    private function testResumeNavigation() {
        $testName = "Reanudar navegación";
        
        try {
            // Reanudar usando StartNavigation
            $stmt = $this->conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?)");
            $deviceInfo = json_encode(['device' => 'test']);
            $stmt->execute([
                $this->testDeliveryId,
                $this->testDriverId,
                -34.6045,
                -58.3825,
                $deviceInfo
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado");
            }
            
            // Verificar estado
            $stmt = $this->conn->prepare("
                SELECT session_status FROM delivery_navigation_sessions 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session['session_status'] !== 'navigating') {
                throw new Exception("Estado no volvió a 'navigating'");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Guardar datos de ruta
     */
    private function testSaveRoute() {
        $testName = "Guardar datos de ruta";
        
        try {
            $routeData = json_encode([
                'waypoints' => [
                    ['lat' => -34.6037, 'lng' => -58.3816],
                    ['lat' => -34.6040, 'lng' => -58.3820]
                ],
                'instructions' => ['Gira a la derecha', 'Continúa recto']
            ]);
            
            $stmt = $this->conn->prepare("CALL SaveRouteData(?, ?, ?, ?)");
            $stmt->execute([
                $this->testDeliveryId,
                $this->testDriverId,
                $routeData,
                5.5
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado");
            }
            
            // Verificar guardado
            $stmt = $this->conn->prepare("
                SELECT route_data FROM delivery_navigation_sessions 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (empty($session['route_data'])) {
                throw new Exception("Datos de ruta no guardados");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Completar navegación
     */
    private function testCompleteNavigation() {
        $testName = "Completar navegación";
        
        try {
            $stmt = $this->conn->prepare("CALL CompleteNavigation(?, ?, ?)");
            $stmt->execute([
                $this->testDeliveryId,
                $this->testDriverId,
                5.8
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['status'] !== 'success') {
                throw new Exception("Resultado inesperado: " . $result['message']);
            }
            
            // Verificar estado
            $stmt = $this->conn->prepare("
                SELECT session_status FROM delivery_navigation_sessions 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session['session_status'] !== 'completed') {
                throw new Exception("Estado no cambió a 'completed'");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Verificar eventos de navegación
     */
    private function testNavigationEvents() {
        $testName = "Registrar eventos de navegación";
        
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM delivery_navigation_events 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$this->testDeliveryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] < 1) {
                throw new Exception("No se registraron eventos");
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Verificar triggers
     */
    private function testTriggers() {
        $testName = "Verificar triggers automáticos";
        
        try {
            // Los triggers ya se ejecutaron en los tests anteriores
            // Verificamos que existan
            $triggers = [
                'create_navigation_session_on_accept',
                'log_navigation_session_changes'
            ];
            
            foreach ($triggers as $trigger) {
                $stmt = $this->conn->query("SHOW TRIGGERS LIKE '%$trigger%'");
                $exists = $stmt->rowCount() > 0;
                
                if (!$exists) {
                    throw new Exception("El trigger $trigger no existe");
                }
            }
            
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Test: Verificar vistas
     */
    private function testViews() {
        $testName = "Verificar vistas";
        
        try {
            $stmt = $this->conn->query("SELECT * FROM v_active_navigation_sessions LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // La vista debe funcionar sin errores
            $this->pass($testName);
            
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }
    
    /**
     * Limpiar datos de prueba
     */
    private function cleanupTestData() {
        try {
            // Las foreign keys en CASCADE se encargarán de la limpieza
            if ($this->testOrderId) {
                $stmt = $this->conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$this->testOrderId]);
            }
            
            if ($this->testDriverId) {
                $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$this->testDriverId]);
            }
            
            // Limpiar usuario cliente
            $stmt = $this->conn->query("DELETE FROM users WHERE email = 'testcustomer@test.com'");
            
            echo COLOR_GREEN . "✅ Datos de prueba eliminados\n" . COLOR_RESET;
            
        } catch (Exception $e) {
            echo COLOR_RED . "❌ Error al limpiar: " . $e->getMessage() . "\n" . COLOR_RESET;
        }
    }
    
    /**
     * Registrar test exitoso
     */
    private function pass($testName) {
        $this->passedTests++;
        echo COLOR_GREEN . "✅ PASS: " . COLOR_RESET . $testName . "\n";
    }
    
    /**
     * Registrar test fallido
     */
    private function fail($testName, $message) {
        $this->failedTests++;
        echo COLOR_RED . "❌ FAIL: " . COLOR_RESET . $testName . "\n";
        echo COLOR_RED . "   Error: " . $message . "\n" . COLOR_RESET;
    }
    
    /**
     * Imprimir resumen
     */
    private function printSummary() {
        $total = $this->passedTests + $this->failedTests;
        
        echo "\n";
        echo COLOR_BLUE . "╔═══════════════════════════════════════════════════╗\n";
        echo "║                    RESUMEN                       ║\n";
        echo "╚═══════════════════════════════════════════════════╝\n" . COLOR_RESET;
        
        echo "\nTotal de tests ejecutados: " . $total . "\n";
        echo COLOR_GREEN . "Tests exitosos: " . $this->passedTests . COLOR_RESET . "\n";
        echo COLOR_RED . "Tests fallidos: " . $this->failedTests . COLOR_RESET . "\n";
        
        if ($this->failedTests === 0) {
            echo "\n" . COLOR_GREEN . "🎉 ¡Todos los tests pasaron correctamente!\n" . COLOR_RESET;
            exit(0);
        } else {
            echo "\n" . COLOR_RED . "⚠️  Algunos tests fallaron. Revisa los errores arriba.\n" . COLOR_RESET;
            exit(1);
        }
    }
}

// Ejecutar tests
try {
    $test = new NavigationSessionTest($conn);
    $test->runAll();
} catch (Exception $e) {
    echo COLOR_RED . "\n❌ Error fatal: " . $e->getMessage() . "\n" . COLOR_RESET;
    exit(1);
}
