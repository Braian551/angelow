# 🧪 Tests - Sistema de Entregas

Esta carpeta contiene tests y ejemplos para el sistema de entregas tipo Didi.

## 📁 Contenido

### Tests Automatizados

- **`test_delivery_system.php`** - Test de verificación del sistema
  - Verifica tablas, triggers, procedimientos
  - Valida estructura de base de datos
  - Comprueba archivos del sistema

- **`test_integration_flow.php`** - Test del flujo completo
  - Simula el proceso completo de entrega
  - Crea datos de prueba
  - Verifica cada paso del flujo

### Documentación

- **`EJEMPLOS_API.md`** - Ejemplos de uso de la API
  - Peticiones HTTP completas
  - Respuestas esperadas
  - Código JavaScript de ejemplo
  - Integración con geolocalización

## 🚀 Ejecutar Tests

### Test de Sistema
```bash
cd c:\laragon\www\angelow
php tests\delivery\test_delivery_system.php
```

Este test verifica:
- ✅ Existencia de tablas
- ✅ Triggers configurados
- ✅ Procedimientos almacenados
- ✅ Vistas SQL
- ✅ Usuarios transportistas
- ✅ Archivos del sistema

### Test de Integración
```bash
cd c:\laragon\www\angelow
php tests\delivery\test_integration_flow.php
```

Este test ejecuta:
1. Crea/busca orden de prueba
2. Asigna transportista
3. Simula aceptación
4. Inicia recorrido
5. Marca llegada
6. Completa entrega
7. Verifica historial
8. Valida estadísticas

## 📊 Resultados Esperados

### Test Exitoso
```
=== TEST DEL SISTEMA DE ENTREGAS TIPO DIDI ===

[TEST 1] Verificando tablas...
  ✓ Tabla 'order_deliveries' existe
  ✓ Tabla 'delivery_status_history' existe
  ✓ Tabla 'driver_statistics' existe

...

=== RESUMEN DE PRUEBAS ===
Tests exitosos: 15
Tests fallidos: 0
✓ Todos los tests pasaron correctamente!
Porcentaje de éxito: 100%
```

### Test con Advertencias
Si hay advertencias pero el sistema funciona:
```
⚠ No hay usuarios con rol 'delivery'
  Ejecuta: INSERT INTO users (...)
```

## 🔧 Solución de Problemas

### Error: "Tabla no existe"
```bash
# Ejecutar migración
Get-Content database\migrations\add_delivery_system.sql | mysql -u root angelow
```

### Error: "Procedimiento no existe"
```bash
# Ejecutar fix de procedimientos
Get-Content database\migrations\fix_delivery_procedures.sql | mysql -u root angelow
```

### Error: "No hay transportistas"
```sql
-- Crear transportista de prueba
INSERT INTO users (id, name, email, password, phone, role) 
VALUES ('TEST001', 'Test Driver', 'driver@test.com', '$2y$10$test', '999999999', 'delivery');
```

## 📝 Crear Datos de Prueba

### Orden de Prueba
```sql
INSERT INTO orders (user_id, order_number, status, subtotal, total, shipping_address, shipping_city) 
VALUES (
    (SELECT id FROM users WHERE role = 'customer' LIMIT 1),
    'TEST-001',
    'processing',
    100.00,
    100.00,
    'Av. Test 123',
    'Lima'
);
```

### Transportista de Prueba
```sql
INSERT INTO users (name, email, password, phone, role) 
VALUES ('Juan Transportista', 'delivery1@test.com', '$2y$10$...', '987654321', 'delivery');
```

## 🎯 Tests Recomendados

### 1. Test Básico (Rápido)
```bash
php tests\delivery\test_delivery_system.php
```
Tiempo: ~2 segundos

### 2. Test Completo (Detallado)
```bash
php tests\delivery\test_integration_flow.php
```
Tiempo: ~5 segundos

### 3. Test Manual (Interfaz)
1. Login como transportista
2. Ir a `/delivery/dashboarddeli.php`
3. Ver órdenes asignadas
4. Probar botones de aceptar/rechazar
5. Completar flujo completo

## 📚 Recursos Adicionales

- Ver **EJEMPLOS_API.md** para integración con JavaScript
- Ver `/docs/delivery/` para documentación completa
- Ver `/docs/delivery/DIAGRAMA_FLUJO.md` para flujo visual

## ⚡ Tips de Testing

1. **Usar transacciones** para tests que no modifiquen datos reales
2. **Crear datos de prueba** separados de producción
3. **Limpiar después** de cada test
4. **Verificar logs** en caso de errores
5. **Probar casos edge** (rechazo, cancelación, etc.)

## 🔍 Debugging

### Ver logs de errores
```bash
# Ver últimas líneas del error log
Get-Content c:\laragon\www\error.log -Tail 50
```

### Ver queries ejecutadas
```php
// Habilitar en config.php
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Verificar datos en BD
```sql
-- Ver entregas activas
SELECT * FROM order_deliveries WHERE delivery_status != 'delivered';

-- Ver historial
SELECT * FROM delivery_status_history ORDER BY created_at DESC LIMIT 10;

-- Ver estadísticas
SELECT * FROM driver_statistics;
```

---

**💡 Ejecuta los tests después de cada cambio para asegurar que todo funciona correctamente**
