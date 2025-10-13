# SOLUCIÓN: Error al Iniciar Navegación (400 Bad Request)

## Problema Identificado

El error ocurría al intentar iniciar la navegación desde la interfaz del conductor:
```
Failed to load resource: the server responded with a status of 400 (Bad Request)
Error al iniciar navegación: Error: Error al iniciar navegación: ERROR
```

## Causa Raíz

1. **Inconsistencia de tipos de datos**: Los procedimientos almacenados `StartNavigation` y `UpdateDeliveryLocation` tenían el parámetro `p_driver_id` definido como `VARCHAR(20)`, pero la API PHP estaba pasando el valor como `INT` sin convertirlo explícitamente a string.

2. **Lógica del procedimiento**: El procedimiento original usaba una verificación que fallaba cuando la entrega ya estaba en estado `in_transit` con `navigation_started_at` ya establecido.

3. **Manejo de errores genérico**: El procedimiento solo devolvía 'ERROR' sin detalles específicos, dificultando el debugging.

## Soluciones Implementadas

### 1. Actualización del archivo `navigation_api.php`

**Ubicación**: `c:\laragon\www\angelow\delivery\api\navigation_api.php`

**Cambios realizados**:

#### a) En `start_navigation`:
- Agregada conversión explícita de `driver_id` a string
- Añadida validación previa del estado de la entrega
- Mejorado el manejo de errores con más detalles
- Agregado `closeCursor()` después de ejecutar el procedimiento

```php
// Asegurar que driver_id sea string (VARCHAR en DB)
$driverIdStr = strval($driverId);

// Verificar que la entrega existe y está en estado correcto
$stmt = $conn->prepare("
    SELECT id, delivery_status 
    FROM order_deliveries 
    WHERE id = ? AND driver_id = ?
");
$stmt->execute([$deliveryId, $driverId]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    throw new Exception('Entrega no encontrada o no pertenece al conductor');
}

if (!in_array($delivery['delivery_status'], ['driver_accepted', 'in_transit'])) {
    throw new Exception('La entrega no está en un estado válido para navegación. Estado actual: ' . $delivery['delivery_status']);
}

try {
    // Llamar al procedimiento almacenado
    $stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
    $stmt->execute([
        $deliveryId, $driverIdStr, 
        $startLat, $startLng, 
        $destLat, $destLng,
        $routeJson, $distanceKm, $durationSeconds
    ]);
    
    // Cerrar el cursor del procedimiento
    $stmt->closeCursor();
    
    // Obtener el resultado
    $result = $conn->query("SELECT @result as result")->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || $result['result'] !== 'SUCCESS') {
        $errorMsg = $result['result'] ?? 'ERROR_DESCONOCIDO';
        throw new Exception('Error al iniciar navegación: ' . $errorMsg);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Navegación iniciada correctamente',
        'status' => 'in_transit'
    ]);
    
} catch (PDOException $e) {
    error_log("Error en StartNavigation: " . $e->getMessage());
    throw new Exception('Error de base de datos al iniciar navegación: ' . $e->getMessage());
}
```

#### b) En `update_location`:
- Agregada conversión explícita de `driver_id` a string
- Agregado `closeCursor()` después de ejecutar el procedimiento
- Actualizado para usar `$driverIdStr` en la consulta posterior

```php
// Asegurar que driver_id sea string (VARCHAR en DB)
$driverIdStr = strval($driverId);

// Llamar al procedimiento almacenado
$stmt = $conn->prepare("
    CALL UpdateDeliveryLocation(?, ?, ?, ?, ?, ?, ?, ?, @result)
");
$stmt->execute([
    $deliveryId, $driverIdStr,
    $latitude, $longitude,
    $accuracy, $speed, $heading, $batteryLevel
]);

// Cerrar el cursor del procedimiento
$stmt->closeCursor();

// ... resto del código usando $driverIdStr
```

### 2. Actualización del Procedimiento Almacenado `StartNavigation`

**Archivo SQL**: `fix_start_navigation_v2.sql`

**Mejoras implementadas**:

1. **Mejor manejo de errores**: Ahora devuelve mensajes específicos con códigos de error MySQL
2. **Validaciones mejoradas**: Separa las verificaciones en pasos claros
3. **Permite reiniciar navegación**: Usa `IFNULL(navigation_started_at, NOW())` para no sobrescribir si ya existe
4. **Mensajes descriptivos**: Cada tipo de error tiene su propio mensaje

```sql
CREATE PROCEDURE StartNavigation(
    IN p_delivery_id INT,
    IN p_driver_id VARCHAR(20),  -- VARCHAR(20) para coincidir con la tabla
    IN p_start_lat DECIMAL(10, 8),
    IN p_start_lng DECIMAL(11, 8),
    IN p_dest_lat DECIMAL(10, 8),
    IN p_dest_lng DECIMAL(11, 8),
    IN p_route_json JSON,
    IN p_distance_km DECIMAL(10, 2),
    IN p_duration_seconds INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE v_delivery_status VARCHAR(50);
    DECLARE v_driver_id VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_result = CONCAT('SQL_ERROR:', @errno, ':', LEFT(@text, 100));
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Obtener información de la entrega
    SELECT delivery_status, driver_id 
    INTO v_delivery_status, v_driver_id
    FROM order_deliveries 
    WHERE id = p_delivery_id;
    
    -- Verificar que la entrega existe
    IF v_delivery_status IS NULL THEN
        SET p_result = 'DELIVERY_NOT_FOUND';
        ROLLBACK;
    -- Verificar que pertenece al conductor
    ELSEIF v_driver_id != p_driver_id THEN
        SET p_result = CONCAT('WRONG_DRIVER:', v_driver_id, '!=', p_driver_id);
        ROLLBACK;
    -- Verificar estado válido
    ELSEIF v_delivery_status NOT IN ('driver_accepted', 'in_transit') THEN
        SET p_result = CONCAT('INVALID_STATUS:', v_delivery_status);
        ROLLBACK;
    ELSE
        -- Actualizar order_deliveries
        UPDATE order_deliveries
        SET 
            navigation_started_at = IFNULL(navigation_started_at, NOW()),
            current_lat = p_start_lat,
            current_lng = p_start_lng,
            destination_lat = p_dest_lat,
            destination_lng = p_dest_lng,
            navigation_route = p_route_json,
            route_distance = p_distance_km,
            route_duration = p_duration_seconds,
            distance_remaining = p_distance_km,
            eta_seconds = p_duration_seconds,
            last_location_update = NOW(),
            delivery_status = 'in_transit'
        WHERE id = p_delivery_id;
        
        -- Insertar evento de navegación
        INSERT INTO navigation_events (
            delivery_id, driver_id, event_type, 
            latitude, longitude, event_data
        ) VALUES (
            p_delivery_id, p_driver_id, 'navigation_started',
            p_start_lat, p_start_lng,
            p_route_json
        );
        
        SET p_result = 'SUCCESS';
        COMMIT;
    END IF;
END//
```

### 3. Actualización del Procedimiento `UpdateDeliveryLocation`

**Archivo SQL**: `fix_update_delivery_location.sql`

**Mejoras similares**:
- Mejor manejo de errores con mensajes descriptivos
- Validaciones paso a paso
- Cálculo de ETA basado en velocidad actual (más preciso)

### 4. Scripts de Verificación Creados

Para facilitar el debugging futuro:

1. **`verify_stored_procedure.php`**: Verifica que los procedimientos existan y tengan los parámetros correctos
2. **`check_driver_id_type.php`**: Verifica los tipos de datos de driver_id en las tablas
3. **`check_deliveries.php`**: Lista las entregas existentes
4. **`check_delivery_state.php`**: Verifica el estado de una entrega específica
5. **`debug_start_navigation.php`**: Prueba completa del procedimiento con información detallada
6. **`test_start_navigation.php`**: Prueba simple del procedimiento

## Cómo Aplicar la Solución

### Paso 1: Actualizar los Procedimientos Almacenados

En PowerShell:

```powershell
cd c:\laragon\www\angelow
Get-Content fix_start_navigation_v2.sql | mysql -u root angelow
Get-Content fix_update_delivery_location.sql | mysql -u root angelow
```

### Paso 2: Los cambios en navigation_api.php ya están aplicados

Los archivos PHP ya fueron editados y están listos para usar.

### Paso 3: Verificar

```powershell
php verify_stored_procedure.php
```

### Paso 4: Probar

```powershell
php debug_start_navigation.php
```

## Resultados Esperados

Después de aplicar todos los cambios:

1. ✅ La navegación se inicia correctamente desde la interfaz web
2. ✅ No más errores 400 Bad Request
3. ✅ Mensajes de error descriptivos si algo falla
4. ✅ La ubicación del conductor se actualiza correctamente en tiempo real
5. ✅ Los eventos de navegación se registran correctamente

## Archivos Modificados

1. `c:\laragon\www\angelow\delivery\api\navigation_api.php`
2. Base de datos: Procedimientos `StartNavigation` y `UpdateDeliveryLocation`

## Archivos Creados (para debugging)

1. `verify_stored_procedure.php`
2. `check_driver_id_type.php`
3. `check_deliveries.php`
4. `check_delivery_state.php`
5. `check_navigation_events.php`
6. `debug_start_navigation.php`
7. `test_start_navigation.php`
8. `fix_start_navigation_v2.sql`
9. `fix_update_delivery_location.sql`
10. `fix_start_navigation_procedure.sql`

## Notas Importantes

- El campo `driver_id` en la base de datos es `VARCHAR(20)`, no `INT`
- Siempre convertir `driver_id` a string antes de llamar procedimientos almacenados
- El procedimiento ahora permite reiniciar navegación sin error si ya está en curso
- Los mensajes de error son más descriptivos para facilitar debugging

## Fecha de Corrección
2025-10-13
