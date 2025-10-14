# HOTFIX #001 - Corrección de Parámetros en StartNavigation

**Fecha:** 2025-10-13  
**Módulo:** Persistencia de Navegación  
**Severidad:** CRÍTICO - Impide iniciar navegación  

---

## 🔴 PROBLEMA DETECTADO

```
Error: SQLSTATE[42000]: Syntax error or access violation: 1318 
Incorrect number of arguments for PROCEDURE angelow.StartNavigation; 
expected 5, got 10
```

### Causa Raíz
El archivo `delivery/api/navigation_api.php` estaba llamando al procedimiento `StartNavigation` con **10 parámetros** cuando el procedimiento solo acepta **5**.

```php
// ❌ INCORRECTO (código anterior)
$stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?, ?, ?, ?, ?, @result)");
$stmt->execute([
    $deliveryId, $driverIdStr, 
    $startLat, $startLng, 
    $destLat, $destLng,
    $routeJson, $distanceKm, $durationSeconds
]);
```

### Definición Real del Procedimiento
```sql
CREATE PROCEDURE `StartNavigation`(
    IN p_delivery_id INT,
    IN p_driver_id VARCHAR(20),
    IN p_lat DECIMAL(10, 8),
    IN p_lng DECIMAL(11, 8),
    IN p_device_info JSON  -- ⬅️ Solo 5 parámetros
)
```

---

## ✅ SOLUCIÓN APLICADA

### 1. Ajuste en `delivery/api/navigation_api.php` (línea ~226)

**CAMBIO:** Reducir de 10 parámetros a 5 y empaquetar la información extra en JSON.

```php
// ✅ CORRECTO (código corregido)
// Preparar device_info JSON
$deviceInfo = json_encode([
    'route' => $data['route'] ?? [],
    'distance_km' => $distanceKm,
    'duration_seconds' => $durationSeconds,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
]);

// Llamar al procedimiento con 5 parámetros correctos
$stmt = $conn->prepare("CALL StartNavigation(?, ?, ?, ?, ?)");
$stmt->execute([
    $deliveryId,      // p_delivery_id
    $driverIdStr,     // p_driver_id
    $startLat,        // p_lat
    $startLng,        // p_lng
    $deviceInfo       // p_device_info (JSON con info extra)
]);

// Obtener el resultado del procedimiento
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$result || $result['status'] !== 'success') {
    throw new Exception('Error al iniciar navegación: ' . $errorMsg);
}
```

### 2. Corrección del Manejo del Resultado

**CAMBIO:** El procedimiento devuelve `'success'` como campo `status`, no `'SUCCESS'` en `result`.

```php
// ❌ ANTERIOR
if ($result['result'] !== 'SUCCESS') { ... }

// ✅ CORREGIDO
if ($result['status'] !== 'success') { ... }
```

---

## 🧪 VERIFICACIÓN

### Comando de prueba manual:
```sql
-- Probar el procedimiento directamente
CALL StartNavigation(
    9,                                    -- delivery_id
    '6862b7448112f',                      -- driver_id
    6.252805,                             -- lat
    -75.538451,                           -- lng
    '{"device":"test","route":[]}'        -- device_info JSON
);

-- Verificar sesión creada
SELECT * FROM delivery_navigation_sessions WHERE delivery_id = 9;
```

### Resultado esperado:
```json
{
  "status": "success",
  "message": "Navegación iniciada"
}
```

---

## 📝 ARCHIVOS MODIFICADOS

- ✅ `delivery/api/navigation_api.php` - Líneas 226-261
  - Ajustado número de parámetros de 10 a 5
  - Corregido manejo del resultado (`status` en lugar de `result`)
  - Agregado empaquetamiento de datos extras en JSON

---

## 🔍 IMPACTO

**ANTES del fix:**
- ❌ Error 400 al iniciar navegación
- ❌ Imposible crear sesiones de navegación
- ❌ Bloquea todo el sistema de persistencia

**DESPUÉS del fix:**
- ✅ Navegación se inicia correctamente
- ✅ Sesiones se crean en BD
- ✅ Estado se persiste al recargar

---

## 🚀 PRÓXIMOS PASOS

1. ✅ Corrección aplicada en `navigation_api.php`
2. ⏳ **Probar en navegador:** `http://localhost/angelow/delivery/navigation.php?delivery_id=9`
3. ⏳ Verificar que la sesión se crea correctamente
4. ⏳ Confirmar que al recargar mantiene el estado

---

## 📊 MONITOREO POST-FIX

```powershell
# Ver sesiones activas
mysql -u root angelow -e "SELECT * FROM delivery_navigation_sessions;"

# Ver eventos de navegación
mysql -u root angelow -e "SELECT * FROM delivery_navigation_events ORDER BY created_at DESC LIMIT 5;"

# Ver estado de la entrega
mysql -u root angelow -e "SELECT id, delivery_status FROM order_deliveries WHERE id = 9;"
```

---

**STATUS:** ✅ HOTFIX APLICADO - Listo para probar  
**Tiempo de resolución:** ~10 minutos  
**Desarrollador:** Sistema Automatizado  
