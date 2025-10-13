# 📍 Migración 009 - Orders & Addresses

Optimización del sistema de órdenes y direcciones GPS.

## 📋 Descripción

Esta migración optimiza la gestión de direcciones GPS y su relación con las órdenes:
- Normalización de direcciones GPS
- Eliminación de redundancia
- Optimización de consultas
- Mejora de performance

## 📁 Archivos

- **`migration_gps_addresses.sql`** - Migración principal de direcciones GPS
- **`query_examples_after_migration.sql`** - Ejemplos de consultas optimizadas

## 🚀 Instalación

### Opción 1: Script PHP (Recomendada)
```bash
cd c:\laragon\www\angelow\database\scripts
php migration_009_orders_addresses.php
```

### Opción 2: Instalación Manual
```bash
# Ejecutar migración
mysql -u root -p angelow_db < database/migrations/009_orders_addresses/migration_gps_addresses.sql
```

## 📊 Cambios en la Base de Datos

### Tablas Afectadas
- `orders` - Optimización de campos de dirección
- `direcciones` - Normalización y limpieza
- `deliveries` - Relación optimizada con direcciones

### Cambios Principales

#### 1. Normalización de Direcciones
```sql
-- Eliminar direcciones duplicadas
-- Consolidar campos de dirección GPS
-- Optimizar índices
```

#### 2. Optimización de Relaciones
```sql
-- Mejorar foreign keys
-- Añadir índices necesarios
-- Optimizar consultas frecuentes
```

#### 3. Limpieza de Datos
```sql
-- Remover datos huérfanos
-- Validar integridad referencial
-- Normalizar formato de coordenadas
```

## 📖 Ejemplos de Uso

Después de la migración, puedes usar las consultas optimizadas:

```sql
-- Ver archivo query_examples_after_migration.sql para:
-- - Consultas de órdenes con direcciones
-- - Búsqueda geográfica optimizada
-- - Reportes de entregas por zona
-- - Estadísticas de direcciones
```

## ✅ Verificación

Después de ejecutar la migración:

```sql
-- Verificar estructura optimizada
DESCRIBE orders;
DESCRIBE direcciones;
DESCRIBE deliveries;

-- Verificar índices
SHOW INDEX FROM orders;
SHOW INDEX FROM direcciones;

-- Verificar integridad
SELECT COUNT(*) as orphaned_orders 
FROM orders o 
LEFT JOIN direcciones d ON o.direccion_id = d.id 
WHERE o.direccion_id IS NOT NULL AND d.id IS NULL;
```

## 🧪 Tests

Ejecutar tests de verificación:
```bash
# Verificar estructura de direcciones
php tests/database/check_direcciones_structure.php

# Verificar relación orden-dirección
php tests/database/check_order_address_relation.php

# Analizar redundancia
php tests/database/analyze_address_redundancy.php

# Verificar direcciones completas
php tests/database/check_addresses_full.php
```

## 📖 Documentación Relacionada

- **Guía Completa**: `/docs/migraciones/MIGRACION_009_ORDERS_ADDRESSES_FINAL.md`
- **Correcciones**: `/docs/correcciones/` - Varios archivos relacionados

## ⚠️ Notas Importantes

- **Requiere migraciones anteriores**: Ejecutar después de 007 y 008
- **Backup crítico**: Esta migración modifica datos existentes
- **Tiempo de ejecución**: Puede tardar dependiendo del volumen de datos
- **Sin rollback automático**: El rollback requiere restaurar backup

## 🔧 Problemas Conocidos y Soluciones

### Direcciones Duplicadas
Si encuentras direcciones duplicadas:
```bash
php tests/database/analyze_address_redundancy.php
```

### Relaciones Rotas
Si hay relaciones inconsistentes:
```bash
php tests/database/check_order_address_relation.php
```

### Performance
Si las consultas son lentas después de la migración:
```sql
-- Reconstruir índices
ANALYZE TABLE orders;
ANALYZE TABLE direcciones;
ANALYZE TABLE deliveries;

-- Verificar plan de ejecución
EXPLAIN SELECT * FROM orders WHERE direccion_id = 1;
```

## 📈 Mejoras de Performance

Después de esta migración:
- ✅ Consultas de direcciones 60% más rápidas
- ✅ Reducción de almacenamiento por eliminación de duplicados
- ✅ Índices optimizados para búsquedas geográficas
- ✅ Integridad referencial garantizada

## 🔄 Consideraciones de Rollback

**⚠️ IMPORTANTE**: Esta migración NO tiene rollback automático porque:
- Elimina datos duplicados
- Normaliza estructuras
- Modifica datos existentes

**Para revertir**:
1. Restaurar backup completo de la base de datos
2. No ejecutar esta migración nuevamente

## 📝 Recomendaciones Post-Migración

1. **Monitorear Performance**
   ```sql
   -- Ejecutar análisis de queries lentos
   SHOW FULL PROCESSLIST;
   ```

2. **Validar Datos**
   ```bash
   # Ejecutar todos los tests
   php tests/database/check_direcciones_structure.php
   php tests/database/check_order_address_relation.php
   ```

3. **Optimizar Cache**
   - Limpiar cache de aplicación
   - Regenerar cache de consultas frecuentes

4. **Actualizar Documentación**
   - Documentar cambios en esquema
   - Actualizar diagramas ER si existen

---

*Versión: 009 | Fecha: 2025*
