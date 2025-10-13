# 🧪 Tests del Sistema AngeloW

Esta carpeta contiene todos los tests del sistema, organizados por módulos siguiendo la misma estructura del código fuente.

## 📁 Estructura Modular

```
tests/
├── README.md                    (este archivo)
├── admin/                       (tests del módulo de administración)
│   └── orders/                  (tests de gestión de órdenes)
├── cart/                        (tests del carrito de compras)
├── delivery/                    (tests del sistema de entregas)
├── navigation/                  (tests del sistema de navegación)
├── voice/                       (tests del sistema de voz)
└── database/                    (tests de base de datos)
```

## 🎯 Organización por Módulos

### 🛒 Cart (`cart/`)
Tests relacionados con el carrito de compras:
- `add_test_cart_items.php` - Agregar items de prueba al carrito
- `add_to_cart_test.php` - Test de agregar al carrito
- `debug_cart_detailed.php` - Debug detallado del carrito
- `debug_cart_step_by_step.php` - Debug paso a paso del carrito
- `diagnose_cart.php` - Diagnóstico del carrito
- `diagnose_cart_full.php` - Diagnóstico completo del carrito
- `test_search_cart.html` - Test de búsqueda en el carrito

### 🚚 Delivery (`delivery/`)
Tests del sistema de entregas:
- `test_delivery_actions.html` - Test de acciones de delivery
- `test_complete.php` - Test completo de delivery

### 🗺️ Navigation (`navigation/`)
Tests del sistema de navegación:
- `debug_start_navigation.php` - Debug de inicio de navegación
- `test_navigation_api.html` - Test de API de navegación
- `test_navigation_query.php` - Test de consultas de navegación
- `test_pause_voice_navigation.html` - Test de pausar voz en navegación
- `test_start_navigation.php` - Test de iniciar navegación

### 🔊 Voice (`voice/`)
Tests del sistema de voz:
- `test_utf8_voice.html` - Test de voz UTF-8
- `test_voice_spanish.html` - Test de voz en español
- `test_voicerss_simple.html` - Test simple de VoiceRSS

### 💾 Database (`database/`)
Tests y verificaciones de base de datos:
- `check_addresses_full.php` - Verificar direcciones completas
- `check_db_structure.php` - Verificar estructura de BD
- `check_deliveries.php` - Verificar entregas
- `check_delivery_state.php` - Verificar estado de entregas
- `check_delivery_status.php` - Verificar status de entregas
- `check_direcciones_structure.php` - Verificar estructura de direcciones
- `check_driver_id_type.php` - Verificar tipo de ID de conductor
- `check_gps_used.php` - Verificar GPS usado
- `check_navigation_events.php` - Verificar eventos de navegación
- `check_order_address_relation.php` - Verificar relación orden-dirección
- `check_users_orders.php` - Verificar órdenes de usuarios
- `verify_data.php` - Verificar datos
- `verify_delivery_table.php` - Verificar tabla de entregas
- `verify_stored_procedure.php` - Verificar procedimientos almacenados
- `analyze_address_redundancy.php` - Analizar redundancia de direcciones

### 🔧 Admin (`admin/`)
Tests del panel de administración:
- `orders/` - Tests de órdenes, historial, actualización masiva

## 🚀 Ejecución de Tests

### Tests PHP
```bash
# Tests de carrito
php tests/cart/diagnose_cart.php

# Tests de navegación
php tests/navigation/test_start_navigation.php

# Tests de base de datos
php tests/database/verify_data.php

# Tests de admin
php tests/admin/orders/test_bulk_update.php
```

### Tests HTML
Abrir en el navegador:
```
http://localhost/angelow/tests/cart/test_search_cart.html
http://localhost/angelow/tests/voice/test_voice_spanish.html
http://localhost/angelow/tests/navigation/test_navigation_api.html
http://localhost/angelow/tests/delivery/test_delivery_actions.html
```

## 📊 Tipos de Tests

### 🔍 Tests de Verificación
Verifican la estructura de la base de datos, configuración, etc.
- Archivos con prefijo `check_` - Verifican estructura/estado
- Archivos con prefijo `verify_` - Verifican datos

### ✅ Tests Funcionales
Prueban la funcionalidad completa de características.
- Archivos con prefijo `test_` - Tests directos

### � Tests de Debug
Herramientas para debugging y diagnóstico.
- Archivos con prefijo `debug_` - Para debugging
- Archivos con prefijo `diagnose_` - Diagnostican problemas

## � Convenciones

- Archivos con prefijo `test_` son tests directos
- Archivos con prefijo `debug_` son para debugging
- Archivos con prefijo `check_` verifican estructura/estado
- Archivos con prefijo `verify_` verifican datos
- Archivos con prefijo `diagnose_` diagnostican problemas
- Archivos `.php` se ejecutan desde terminal o navegador
- Archivos `.html` se abren directamente en el navegador
- Cada módulo puede tener su propio `README.md` con detalles específicos

## ⚠️ Importante

- No ejecutar tests de modificación en producción
- Los tests de debug pueden mostrar información sensible
- Algunos tests requieren configuración específica en `config.php`
- Tests de base de datos requieren conexión activa a MySQL

## 🔗 Enlaces Relacionados

- **Documentación**: Ver `/docs/` (misma estructura modular)
- **Código**: Ver carpetas correspondientes en la raíz
- **Migraciones**: Ver `/database/migrations/`

## ✅ Mejores Prácticas

1. **Ejecutar tests antes de commits importantes**
2. **Documentar nuevos tests en el README del módulo**
3. **Mantener tests actualizados con cambios en el código**
4. **Usar nombres descriptivos para archivos de test**

---

*Última actualización: 13 de Octubre, 2025*
