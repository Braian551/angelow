# ✅ ACTUALIZACIÓN: admin/order/edit.php

## 📋 RESUMEN

Se ha actualizado completamente el sistema de edición de órdenes para usar la nueva estructura con Foreign Keys y coordenadas GPS.

---

## 🎯 CAMBIOS IMPLEMENTADOS

### 1. **Query Actualizado** 
```php
// ANTES:
SELECT o.*, u.name, u.email FROM orders o LEFT JOIN users u ...

// AHORA:
SELECT 
    o.*, 
    u.*,
    ua.id AS current_address_id,
    ua.address AS current_address,
    ua.gps_latitude AS current_gps_lat,
    ua.gps_longitude AS current_gps_lng,
    ...
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
```

### 2. **Obtener Direcciones del Usuario**
```php
// Nueva query para selector
SELECT id, alias, address, gps_latitude, gps_longitude, ...
FROM user_addresses 
WHERE user_id = ? AND is_active = 1
ORDER BY is_default DESC
```

### 3. **Lógica de Actualización Mejorada**

#### **Opción A: Seleccionar Dirección Guardada (Recomendado)**
```php
if ($shippingAddressId) {
    // 1. Obtener datos de la dirección seleccionada
    // 2. Crear snapshot automático
    // 3. Actualizar shipping_address_id + shipping_address
    // 4. Actualizar coordenadas GPS en order_deliveries
}
```

#### **Opción B: Editar Manual (Legacy)**
```php
// Solo actualizar campos de texto
// Para órdenes antiguas sin FK
UPDATE orders SET shipping_address = ?, shipping_city = ? ...
```

---

## 🎨 INTERFAZ MEJORADA

### **Selector de Dirección con Preview**

```
┌─────────────────────────────────────────────────┐
│ 📍 Dirección de Envío              🟢 Con GPS   │
├─────────────────────────────────────────────────┤
│ 📌 Seleccionar Dirección del Usuario [Recomendado] │
│                                                 │
│ [Select Dropdown]                               │
│ ⭐ Casa (Por defecto) 📍 GPS - Terminal...     │
│ 🏢 Trabajo 📍 GPS - Cra 16D #57...             │
│ 🏠 Apartamento ⚠️ Sin GPS - Calle 45...        │
│                                                 │
│ 💡 Actualmente vinculada: Casa                  │
├─────────────────────────────────────────────────┤
│ 👁️ Vista previa de dirección seleccionada:     │
│                                                 │
│ 🏠 Dirección: Terminal el faro                  │
│ ℹ️  Complemento: Bloque 3                       │
│ 🗺️  Barrio: Comuna 8 - Villa Hermosa           │
│ 🏢 Edificio: Residencias Termal                │
│ 📍 GPS: 6.25289087, -75.53848550               │
│    🔗 Ver en Maps                               │
│                                                 │
│ ✅ Esta dirección tiene coordenadas GPS         │
│    para navegación                              │
├─────────────────────────────────────────────────┤
│        O editar manualmente (legacy)            │
├─────────────────────────────────────────────────┤
│ 🏙️ Ciudad: [Medellín ▼]                        │
│ ⚠️ Solo editar si no seleccionaste dirección arriba │
│                                                 │
│ 🏠 Dirección Completa (Snapshot Histórico)     │
│ [Textarea]                                      │
│ 📜 Se actualizará automáticamente si cambias   │
│    la dirección arriba                          │
└─────────────────────────────────────────────────┘
```

---

## 💡 FUNCIONALIDADES

### **1. Selector Inteligente**
- ✅ Muestra todas las direcciones activas del usuario
- ✅ Indica cuál es la dirección por defecto (⭐)
- ✅ Muestra si tiene GPS (📍) o no (⚠️)
- ✅ Previsualiza dirección completa al seleccionar
- ✅ Link directo a Google Maps

### **2. Preview en Tiempo Real**
- ✅ JavaScript actualiza preview al cambiar selector
- ✅ Muestra todos los campos: dirección, barrio, edificio, apto
- ✅ Destaca coordenadas GPS
- ✅ Alerta si no tiene GPS

### **3. Actualización Automática**
- ✅ Al seleccionar dirección:
  - Actualiza `shipping_address_id` (FK)
  - Crea snapshot en `shipping_address` (histórico)
  - Actualiza `order_deliveries.destination_lat/lng`
- ✅ Validación: Requiere dirección (FK o manual)

### **4. Compatibilidad Legacy**
- ✅ Órdenes antiguas sin FK pueden seguir editándose manualmente
- ✅ Si no se selecciona dirección, usa campos de texto
- ✅ Transición gradual entre sistemas

---

## 🔧 CAMBIOS TÉCNICOS

### **Archivos Modificados:**
```
✅ admin/order/edit.php
   - Query con JOIN a user_addresses
   - Obtención de direcciones del usuario
   - Lógica de actualización dual (FK + manual)
   - Selector de direcciones
   - Preview con JavaScript
   - Estilos CSS integrados
```

### **Nuevos Campos Procesados:**
```php
$_POST['shipping_address_id']  // FK a user_addresses (nuevo)
$_POST['shipping_address']     // Snapshot (existente, auto-actualizado)
$_POST['shipping_city']        // Ciudad (existente, auto-actualizado)
$_POST['delivery_notes']       // Instrucciones (existente)
$_POST['notes']                // Notas admin (existente)
```

### **Actualización en Cascada:**
```php
// Si se cambia dirección FK, también se actualiza:
1. orders.shipping_address_id     → Nuevo FK
2. orders.shipping_address         → Snapshot automático
3. orders.shipping_city            → De la dirección seleccionada
4. order_deliveries.destination_lat → GPS de dirección
5. order_deliveries.destination_lng → GPS de dirección
```

---

## 🧪 PARA PROBAR

### **1. Editar orden existente:**
```
http://localhost/angelow/admin/order/edit.php?id=27
```

Deberías ver:
- ✅ Selector con direcciones del usuario
- ✅ Dirección actual pre-seleccionada
- ✅ Preview mostrando datos completos
- ✅ Badge "Con GPS" si tiene coordenadas
- ✅ Campos manuales como fallback

### **2. Cambiar dirección:**
1. Selecciona otra dirección del dropdown
2. Preview se actualiza automáticamente
3. Guarda cambios
4. Verifica en detail.php que se actualizó

### **3. Verificar en base de datos:**
```sql
SELECT 
    id,
    order_number,
    shipping_address_id,
    shipping_address,
    shipping_city
FROM orders 
WHERE id = 27;

-- Verificar coordenadas actualizadas
SELECT 
    od.id,
    od.destination_lat,
    od.destination_lng
FROM order_deliveries od
WHERE order_id = 27;
```

---

## 🎯 CASOS DE USO

### **Caso 1: Orden con dirección vinculada (Nueva)**
```
Usuario: Tiene 3 direcciones guardadas
Acción: Admin selecciona "Trabajo" (con GPS)
Resultado: 
- shipping_address_id = 2
- shipping_address = "Cra 16D #57 B 163, Belén" (snapshot)
- GPS actualizado en order_deliveries
```

### **Caso 2: Orden legacy (Sin FK)**
```
Usuario: Tiene direcciones, pero orden antigua sin FK
Acción: Admin selecciona dirección del dropdown
Resultado:
- shipping_address_id = 5 (ahora vinculada)
- shipping_address actualizado con snapshot
- GPS ahora disponible
```

### **Caso 3: Edición manual (Fallback)**
```
Usuario: Sin direcciones guardadas o quiere personalizar
Acción: Admin deja selector vacío y edita textarea
Resultado:
- shipping_address_id = NULL
- shipping_address = texto manual
- Sin GPS (alerta mostrada)
```

---

## 📊 BENEFICIOS

| Antes | Ahora |
|-------|-------|
| ❌ Dirección solo texto | ✅ FK + Snapshot |
| ❌ Sin GPS | ✅ GPS automático |
| ❌ Editar texto largo | ✅ Selector visual |
| ❌ Sin preview | ✅ Preview en vivo |
| ❌ Datos desactualizados | ✅ Acceso a datos actuales |
| ❌ Navegación no funciona | ✅ GPS para deliveries |

---

## 🚀 FLUJO COMPLETO

```
1. Admin abre edit.php
   ↓
2. Sistema carga:
   - Orden actual
   - Dirección vinculada (si existe)
   - Todas las direcciones del usuario
   ↓
3. Admin ve:
   - Selector de direcciones
   - Preview de dirección actual
   - Badges (GPS, Por defecto)
   ↓
4. Admin selecciona nueva dirección
   ↓
5. JavaScript actualiza preview en tiempo real
   ↓
6. Admin guarda cambios
   ↓
7. Backend procesa:
   - Actualiza shipping_address_id (FK)
   - Crea snapshot automático
   - Actualiza GPS en order_deliveries
   ↓
8. Redirige a detail.php
   ↓
9. Detail muestra dirección actualizada con GPS
```

---

## ✅ CHECKLIST DE VALIDACIÓN

- [x] Query actualizado con JOIN
- [x] Obtención de direcciones del usuario
- [x] Selector de direcciones funcional
- [x] Preview en tiempo real (JavaScript)
- [x] Actualización de FK en orders
- [x] Actualización de GPS en order_deliveries
- [x] Snapshot histórico preservado
- [x] Validación de formulario
- [x] Estilos CSS responsive
- [x] Compatible con órdenes legacy
- [x] Sin errores PHP
- [x] Alertas informativas

---

## 📝 NOTAS IMPORTANTES

1. **Prioridad al FK**: El sistema prioriza usar `shipping_address_id` cuando está disponible
2. **Snapshot Preservado**: `shipping_address` siempre se mantiene para historial
3. **GPS Automático**: Al seleccionar dirección, GPS se copia automáticamente
4. **Retrocompatibilidad**: Órdenes antiguas siguen funcionando
5. **Validación Suave**: No es obligatorio seleccionar FK, permite edición manual

---

**Fecha**: 13 de Octubre, 2025  
**Archivo**: admin/order/edit.php  
**Estado**: ✅ COMPLETADO Y PROBADO
