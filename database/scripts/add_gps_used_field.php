<?php
/**
 * Migración: Agregar campo gps_used a user_addresses
 * 
 * Este campo indica si el usuario utilizó la funcionalidad de GPS
 * (ya sea mediante geolocalización automática, búsqueda de dirección, o moviendo el pin manualmente)
 */

require_once __DIR__ . '/../../conexion.php';

try {
    echo "Iniciando migración: Agregar campo gps_used...\n";
    
    // Verificar si el campo ya existe
    $stmt = $conn->query("SHOW COLUMNS FROM user_addresses LIKE 'gps_used'");
    $fieldExists = $stmt->fetch();
    
    if ($fieldExists) {
        echo "✓ El campo 'gps_used' ya existe en la tabla user_addresses\n";
    } else {
        // Agregar el campo
        $conn->exec("
            ALTER TABLE user_addresses 
            ADD COLUMN gps_used TINYINT(1) DEFAULT 0 
            COMMENT 'Indica si se usó GPS (1) o no (0)' 
            AFTER gps_timestamp
        ");
        echo "✓ Campo 'gps_used' agregado exitosamente\n";
    }
    
    // Actualizar registros existentes: si tienen coordenadas GPS, marcar como GPS usado
    $result = $conn->exec("
        UPDATE user_addresses 
        SET gps_used = 1 
        WHERE gps_latitude IS NOT NULL 
        AND gps_longitude IS NOT NULL
        AND gps_used = 0
    ");
    
    echo "✓ Se actualizaron $result registros existentes con coordenadas GPS\n";
    
    echo "\n✅ Migración completada exitosamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
