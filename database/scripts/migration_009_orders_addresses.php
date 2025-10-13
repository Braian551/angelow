<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * MIGRACIÓN 009: RELACIÓN ORDERS ↔ USER_ADDRESSES
 * ═══════════════════════════════════════════════════════════════
 * 
 * OBJETIVO: Eliminar redundancia entre orders y user_addresses
 * 
 * CAMBIOS:
 * 1. Agregar orders.shipping_address_id (FK → user_addresses.id)
 * 2. Agregar orders.billing_address_id (FK → user_addresses.id)
 * 3. Mantener shipping_address y shipping_city como snapshot histórico
 * 4. Relacionar órdenes existentes con sus direcciones
 * 
 * FILOSOFÍA:
 * - shipping_address_id: Para obtener datos actuales + GPS
 * - shipping_address: Snapshot del momento de la orden (histórico)
 * - Mejor de ambos mundos: datos actuales + historial preservado
 * ═══════════════════════════════════════════════════════════════
 */

require_once dirname(__DIR__) . '/conexion.php';

class Migration009 {
    private $conn;
    private $log = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function up() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  MIGRACIÓN 009: RELACIÓN ORDERS ↔ USER_ADDRESSES             ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        try {
            // Nota: ALTER TABLE hace commit automático en MySQL, no usamos transacciones
            
            // PASO 1: Agregar columnas FK
            $this->log("PASO 1: Agregando columnas FK a orders...");
            $this->addForeignKeyColumns();
            
            // PASO 2: Relacionar órdenes existentes
            $this->log("PASO 2: Relacionando órdenes existentes...");
            $this->linkExistingOrders();
            
            // PASO 3: Agregar constraints FK
            $this->log("PASO 3: Agregando constraints FK...");
            $this->addForeignKeyConstraints();
            
            // PASO 4: Actualizar order_deliveries para usar el FK
            $this->log("PASO 4: Verificando order_deliveries...");
            $this->updateOrderDeliveries();
            
            echo "\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n\n";
            $this->showSummary();
            
            return true;
            
        } catch (Exception $e) {
            echo "\n❌ ERROR EN MIGRACIÓN: " . $e->getMessage() . "\n";
            echo "   Archivo: " . $e->getFile() . "\n";
            echo "   Línea: " . $e->getLine() . "\n\n";
            return false;
        }
    }
    
    private function addForeignKeyColumns() {
        // Verificar si ya existen
        $result = $this->conn->query("SHOW COLUMNS FROM orders LIKE 'shipping_address_id'");
        if ($result->rowCount() > 0) {
            $this->log("   ⚠️  Columna shipping_address_id ya existe, saltando...");
            return;
        }
        
        // Agregar shipping_address_id después de shipping_city
        $this->conn->exec("
            ALTER TABLE orders 
            ADD COLUMN shipping_address_id INT NULL 
            COMMENT 'FK a user_addresses - Dirección de envío actual'
            AFTER shipping_city
        ");
        $this->log("   ✅ Agregada columna shipping_address_id");
        
        // Agregar billing_address_id después de billing_address
        $this->conn->exec("
            ALTER TABLE orders 
            ADD COLUMN billing_address_id INT NULL 
            COMMENT 'FK a user_addresses - Dirección de facturación'
            AFTER billing_address
        ");
        $this->log("   ✅ Agregada columna billing_address_id");
    }
    
    private function linkExistingOrders() {
        // Obtener todas las órdenes
        $result = $this->conn->query("
            SELECT id, user_id, shipping_address, shipping_city 
            FROM orders 
            WHERE shipping_address IS NOT NULL
        ");
        
        $ordersLinked = 0;
        $ordersUnlinked = 0;
        
        while ($order = $result->fetch(PDO::FETCH_ASSOC)) {
            // Buscar dirección por defecto del usuario
            $stmt = $this->conn->prepare("
                SELECT id, address, neighborhood 
                FROM user_addresses 
                WHERE user_id = ? 
                AND is_default = 1 
                AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$order['user_id']]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($address) {
                // Vincular orden con dirección
                $updateStmt = $this->conn->prepare("
                    UPDATE orders 
                    SET shipping_address_id = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$address['id'], $order['id']]);
                
                $ordersLinked++;
                $this->log("   ✅ Orden #{$order['id']} vinculada con address #{$address['id']}");
            } else {
                $ordersUnlinked++;
                $this->log("   ⚠️  Orden #{$order['id']} sin dirección por defecto (user: {$order['user_id']})");
            }
        }
        
        $this->log("\n   📊 Resultado:");
        $this->log("      - Órdenes vinculadas: $ordersLinked");
        $this->log("      - Órdenes sin vincular: $ordersUnlinked");
    }
    
    private function addForeignKeyConstraints() {
        try {
            // Verificar si ya existe el constraint
            $result = $this->conn->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'orders' 
                AND CONSTRAINT_NAME = 'fk_orders_shipping_address'
            ");
            
            if ($result->rowCount() > 0) {
                $this->log("   ⚠️  FK shipping_address ya existe, saltando...");
            } else {
                $this->conn->exec("
                    ALTER TABLE orders 
                    ADD CONSTRAINT fk_orders_shipping_address 
                    FOREIGN KEY (shipping_address_id) 
                    REFERENCES user_addresses(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                $this->log("   ✅ Constraint FK shipping_address_id creado");
            }
            
            // FK para billing_address_id
            $result = $this->conn->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'orders' 
                AND CONSTRAINT_NAME = 'fk_orders_billing_address'
            ");
            
            if ($result->rowCount() > 0) {
                $this->log("   ⚠️  FK billing_address ya existe, saltando...");
            } else {
                $this->conn->exec("
                    ALTER TABLE orders 
                    ADD CONSTRAINT fk_orders_billing_address 
                    FOREIGN KEY (billing_address_id) 
                    REFERENCES user_addresses(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                $this->log("   ✅ Constraint FK billing_address_id creado");
            }
            
        } catch (PDOException $e) {
            $this->log("   ⚠️  Error al crear constraints: " . $e->getMessage());
        }
    }
    
    private function updateOrderDeliveries() {
        // Verificar que order_deliveries use correctamente las coordenadas
        $result = $this->conn->query("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN destination_lat IS NULL OR destination_lat = 0 THEN 1 ELSE 0 END) as sin_coords
            FROM order_deliveries
        ");
        $stats = $result->fetch(PDO::FETCH_ASSOC);
        
        $this->log("   📊 Estadísticas order_deliveries:");
        $this->log("      - Total entregas: {$stats['total']}");
        $this->log("      - Sin coordenadas: {$stats['sin_coords']}");
        
        if ($stats['sin_coords'] > 0) {
            $this->log("\n   🔧 Reparando entregas sin coordenadas...");
            
            // Actualizar entregas sin coordenadas
            $this->conn->exec("
                UPDATE order_deliveries od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN user_addresses ua ON o.shipping_address_id = ua.id
                SET od.destination_lat = ua.gps_latitude,
                    od.destination_lng = ua.gps_longitude
                WHERE (od.destination_lat IS NULL OR od.destination_lat = 0)
                AND ua.gps_latitude IS NOT NULL 
                AND ua.gps_longitude IS NOT NULL
            ");
            
            $updated = $this->conn->query("SELECT ROW_COUNT()")->fetchColumn();
            $this->log("   ✅ Actualizadas $updated entregas con coordenadas GPS");
        }
    }
    
    private function showSummary() {
        echo "════════════════════════════════════════════════════════════════\n";
        echo "📋 RESUMEN DE CAMBIOS:\n";
        echo "════════════════════════════════════════════════════════════════\n\n";
        
        // Mostrar estructura final
        $result = $this->conn->query("
            SELECT 
                COLUMN_NAME, 
                COLUMN_TYPE, 
                IS_NULLABLE,
                COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'orders'
            AND COLUMN_NAME LIKE '%address%'
            ORDER BY ORDINAL_POSITION
        ");
        
        echo "🗃️  ESTRUCTURA FINAL DE ORDERS:\n";
        echo str_repeat("─", 80) . "\n";
        while ($col = $result->fetch(PDO::FETCH_ASSOC)) {
            printf("   %-30s | %-25s | NULL: %s\n", 
                $col['COLUMN_NAME'], 
                $col['COLUMN_TYPE'], 
                $col['IS_NULLABLE']
            );
            if ($col['COLUMN_COMMENT']) {
                echo "   └─ Comentario: {$col['COLUMN_COMMENT']}\n";
            }
        }
        
        // Estadísticas finales
        echo "\n📊 ESTADÍSTICAS:\n";
        echo str_repeat("─", 80) . "\n";
        
        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN shipping_address_id IS NOT NULL THEN 1 ELSE 0 END) as con_fk,
                SUM(CASE WHEN shipping_address IS NOT NULL THEN 1 ELSE 0 END) as con_texto
            FROM orders
        ");
        $stats = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "   Total órdenes: {$stats['total']}\n";
        echo "   Con shipping_address_id (FK): {$stats['con_fk']}\n";
        echo "   Con shipping_address (texto): {$stats['con_texto']}\n";
        
        echo "\n💡 CÓMO USAR AHORA:\n";
        echo str_repeat("─", 80) . "\n";
        echo "   1. Al crear orden: Guarda shipping_address_id\n";
        echo "   2. Al mostrar orden: JOIN con user_addresses para datos actuales + GPS\n";
        echo "   3. shipping_address: Mantiene snapshot histórico\n";
        echo "   4. En admin/orders.php: Mostrar ambos (FK para editar, texto para historial)\n";
        echo "\n";
    }
    
    private function log($message) {
        echo "$message\n";
        $this->log[] = $message;
    }
    
    public function down() {
        echo "\n🔄 REVERTIR MIGRACIÓN 009...\n\n";
        
        try {
            // Eliminar constraints
            $this->conn->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS fk_orders_shipping_address");
            $this->conn->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS fk_orders_billing_address");
            
            // Eliminar columnas
            $this->conn->exec("ALTER TABLE orders DROP COLUMN IF EXISTS shipping_address_id");
            $this->conn->exec("ALTER TABLE orders DROP COLUMN IF EXISTS billing_address_id");
            
            echo "✅ MIGRACIÓN REVERTIDA\n\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ ERROR AL REVERTIR: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// EJECUCIÓN
// ═══════════════════════════════════════════════════════════════

if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'up';
    
    $migration = new Migration009($conn);
    
    if ($action === 'up') {
        $success = $migration->up();
        exit($success ? 0 : 1);
    } elseif ($action === 'down') {
        $success = $migration->down();
        exit($success ? 0 : 1);
    } else {
        echo "Uso: php migration_009_orders_addresses.php [up|down]\n";
        exit(1);
    }
}
