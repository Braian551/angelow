# 🗂️ Índice General de Organización

**Proyecto:** AngeloW - Tienda Online
**Fecha de Organización:** 13 de Octubre, 2025

---

## 📋 Resumen de Organización

Este documento sirve como índice central para toda la organización del proyecto. Se han reorganizado **103 archivos** en una estructura modular clara y profesional.

## 📁 Estructura Principal

```
angelow/
├── 📚 docs/              → Documentación completa (22 archivos MD)
├── 🧪 tests/             → Tests organizados (32 archivos)
├── 💾 database/          → Migraciones, fixes y scripts (49 archivos)
└── 📖 README.md          → Documentación principal del proyecto
```

---

## 📚 Documentación (`docs/`)

### 📖 Índice Principal
- **[docs/README.md](docs/README.md)** - Índice completo de documentación

### Por Categoría

#### 🔧 Correcciones (8 archivos)
Documentación de correcciones aplicadas al sistema.
- Ver: [`docs/correcciones/`](docs/correcciones/)
- Archivos: CORRECCIONES_*, CORRECCION_*, RESUMEN_CORRECCION_*

#### 📚 Guías (4 archivos)
Guías de uso y configuración del sistema.
- Ver: [`docs/guias/`](docs/guias/)
- Incluye:
  - ESTRUCTURA_MODULAR.md
  - GUIA_COMPLETA_DELIVERY.md
  - GUIA_VOZ_ESPAÑOL.md
  - INSTRUCCIONES_FINALES.md

#### 🔄 Migraciones (4 archivos)
Documentación sobre migraciones de base de datos.
- Ver: [`docs/migraciones/`](docs/migraciones/)
- Archivos: MIGRACION_*, INSTRUCCIONES_MIGRACION_*, GUIA_RAPIDA_*

#### 💡 Soluciones (6 archivos)
Soluciones a problemas específicos encontrados.
- Ver: [`docs/soluciones/`](docs/soluciones/)
- Archivos: SOLUCION_*, ACTUALIZACION_*

#### 📂 Por Módulo
- **Admin**: [`docs/admin/`](docs/admin/)
- **Delivery**: [`docs/delivery/`](docs/delivery/)

---

## 🧪 Tests (`tests/`)

### 📖 Índice Principal
- **[tests/README.md](tests/README.md)** - Guía completa de tests

### Por Módulo

#### 🛒 Cart (7 archivos)
Tests del carrito de compras.
- Ver: [`tests/cart/`](tests/cart/)
- Incluye: add_to_cart_test, debug_cart, diagnose_cart, test_search_cart

#### 🚚 Delivery (2 archivos)
Tests del sistema de entregas.
- Ver: [`tests/delivery/`](tests/delivery/)
- Archivos: test_delivery_actions.html, test_complete.php

#### 🗺️ Navigation (5 archivos)
Tests del sistema de navegación GPS.
- Ver: [`tests/navigation/`](tests/navigation/)
- Incluye: test_navigation_api, test_start_navigation, debug_start_navigation

#### 🔊 Voice (3 archivos)
Tests del sistema de voz.
- Ver: [`tests/voice/`](tests/voice/)
- Archivos: test_voice_spanish, test_utf8_voice, test_voicerss_simple

#### 💾 Database (15 archivos)
Tests y verificaciones de base de datos.
- Ver: [`tests/database/`](tests/database/)
- Incluye: check_*, verify_*, analyze_*

#### 🔧 Admin
- Ver: [`tests/admin/`](tests/admin/)
- Incluye: tests de órdenes, badges, etc.

---

## 💾 Database (`database/`)

### 📖 Índice Principal
- **[database/README.md](database/README.md)** - Guía completa de base de datos

### Estructura

#### 🔄 Migraciones (19 archivos SQL)
Organizadas por versión y funcionalidad:

1. **007 - Location Tracking** (8 archivos)
   - Ver: [`database/migrations/007_location_tracking/`](database/migrations/007_location_tracking/)
   - Documentación: [`database/migrations/007_location_tracking/README.md`](database/migrations/007_location_tracking/README.md)

2. **008 - Delivery Workflow** (5 archivos)
   - Ver: [`database/migrations/008_delivery_workflow/`](database/migrations/008_delivery_workflow/)
   - Documentación: [`database/migrations/008_delivery_workflow/README.md`](database/migrations/008_delivery_workflow/README.md)

3. **009 - Orders & Addresses** (2 archivos)
   - Ver: [`database/migrations/009_orders_addresses/`](database/migrations/009_orders_addresses/)
   - Documentación: [`database/migrations/009_orders_addresses/README.md`](database/migrations/009_orders_addresses/README.md)

4. **Adicionales**
   - Order History: [`database/migrations/order_history/`](database/migrations/order_history/)
   - Orders Badge: [`database/migrations/orders_badge/`](database/migrations/orders_badge/)
   - Roles System: [`database/migrations/roles_system/`](database/migrations/roles_system/)

#### 🔧 Fixes (13 archivos)
Correcciones organizadas por módulo:
- **Procedures**: [`database/fixes/procedures/`](database/fixes/procedures/) (6 archivos)
- **Navigation**: [`database/fixes/navigation/`](database/fixes/navigation/) (2 archivos)
- **Delivery**: [`database/fixes/delivery/`](database/fixes/delivery/) (2 archivos)
- **Generales**: [`database/fixes/`](database/fixes/) (3 archivos PHP)

#### 🚀 Scripts (13 archivos)
Scripts de ejecución centralizados:
- Ver: [`database/scripts/`](database/scripts/)
- Incluye: ejecutar_migracion.*, run_migration.php, migrate_*, fix_utf8.ps1

---

## 📊 Estadísticas

### Por Categoría
| Categoría | Carpeta | Archivos |
|-----------|---------|----------|
| 📚 Documentación | `docs/` | 22 |
| 🧪 Tests | `tests/` | 32 |
| 💾 Migraciones | `database/migrations/` | 19 |
| 🔧 Fixes | `database/fixes/` | 13 |
| 🚀 Scripts | `database/scripts/` | 13 |
| 📖 READMEs | Varios | 7 |
| **TOTAL** | - | **103** |

### Por Tipo de Archivo
- **Markdown (.md)**: 22 archivos de documentación + 7 READMEs
- **SQL (.sql)**: 27 archivos
- **PHP (.php)**: 34 archivos
- **HTML (.html)**: 8 archivos
- **Shell (.ps1, .bat)**: 5 archivos

---

## 🔍 Búsqueda Rápida

### Por Necesidad

| Necesidad | Ubicación | Archivo |
|-----------|-----------|---------|
| Ejecutar migración 007 | `database/scripts/` | `run_migration_007.php` |
| Ejecutar migración 008 | `database/scripts/` | `ejecutar_migracion_008.ps1` |
| Ejecutar migración 009 | `database/scripts/` | `migration_009_orders_addresses.php` |
| Fix de navegación | `database/fixes/navigation/` | `fix_start_navigation_v2.sql` |
| Test de carrito | `tests/cart/` | `diagnose_cart.php` |
| Test de delivery | `tests/delivery/` | `test_complete.php` |
| Verificar base de datos | `tests/database/` | `check_db_structure.php` |
| Guía de delivery | `docs/guias/` | `GUIA_COMPLETA_DELIVERY.md` |
| Solución de errores | `docs/soluciones/` | `SOLUCION_ERRORES_DELIVERY.md` |

### Por Palabra Clave

| Palabra Clave | Buscar en |
|--------------|-----------|
| **Migración** | `docs/migraciones/`, `database/migrations/` |
| **Fix** | `database/fixes/`, `docs/correcciones/` |
| **Test** | `tests/` (todas las subcarpetas) |
| **Delivery** | `docs/delivery/`, `tests/delivery/`, `database/fixes/delivery/` |
| **Cart** | `tests/cart/`, `database/fixes/` (fix_cart*) |
| **Navigation** | `tests/navigation/`, `database/fixes/navigation/` |
| **Voice** | `tests/voice/`, `docs/guias/` (GUIA_VOZ_*) |
| **Database** | `database/` (todas las subcarpetas), `tests/database/` |

---

## 📖 Documentos de Referencia

### Resúmenes de Organización
1. **[ORGANIZACION_ARCHIVOS.md](ORGANIZACION_ARCHIVOS.md)** - Resumen de organización de docs y tests
2. **[ORGANIZACION_DATABASE.md](ORGANIZACION_DATABASE.md)** - Resumen de organización de database
3. **Este archivo** - Índice general completo

### READMEs Principales
1. **[README.md](README.md)** - Documentación principal del proyecto
2. **[docs/README.md](docs/README.md)** - Índice de documentación
3. **[tests/README.md](tests/README.md)** - Guía de tests
4. **[database/README.md](database/README.md)** - Guía de base de datos

### READMEs de Migraciones
1. **[database/migrations/007_location_tracking/README.md](database/migrations/007_location_tracking/README.md)**
2. **[database/migrations/008_delivery_workflow/README.md](database/migrations/008_delivery_workflow/README.md)**
3. **[database/migrations/009_orders_addresses/README.md](database/migrations/009_orders_addresses/README.md)**

---

## 🎯 Convenciones del Proyecto

### Nombres de Archivos

#### Documentación (`.md`)
- `GUIA_*` - Guías de uso
- `SOLUCION_*` - Soluciones a problemas
- `CORRECCION_*` - Correcciones aplicadas
- `MIGRACION_*` - Documentación de migraciones
- `RESUMEN_*` - Resúmenes de cambios
- `INSTRUCCIONES_*` - Instrucciones paso a paso

#### Tests
- `test_*` - Tests funcionales
- `debug_*` - Herramientas de debug
- `diagnose_*` - Diagnósticos
- `check_*` - Verificaciones de estructura
- `verify_*` - Verificaciones de datos
- `analyze_*` - Análisis de datos

#### Base de Datos
- `00X_*` - Migraciones versionadas
- `fix_*` - Correcciones
- `run_*` - Scripts de ejecución
- `ejecutar_*` - Scripts de shell

### Estructura de Carpetas
- Por funcionalidad (cart, delivery, navigation)
- Por tipo (fixes, scripts, migrations)
- Por módulo (admin, users, tienda)

---

## ✅ Checklist de Uso

### Al Trabajar con Migraciones
- [ ] Leer `database/README.md`
- [ ] Revisar README específico de la migración
- [ ] Hacer backup de la base de datos
- [ ] Ejecutar en ambiente de desarrollo primero
- [ ] Ejecutar tests de verificación después

### Al Buscar Documentación
- [ ] Verificar `docs/README.md` primero
- [ ] Buscar en la carpeta correspondiente (guias, soluciones, etc.)
- [ ] Revisar archivos RESUMEN_* para contexto general

### Al Ejecutar Tests
- [ ] Leer `tests/README.md`
- [ ] Identificar el módulo correcto
- [ ] Seguir las instrucciones de ejecución
- [ ] Documentar resultados si encuentras problemas

---

## 🔗 Enlaces Útiles

### Documentación Externa
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Git Documentation](https://git-scm.com/doc)

### Herramientas del Proyecto
- Repositorio: `Braian551/angelow`
- Branch actual: `main`
- Servidor local: `http://localhost/angelow/`

---

## 📝 Notas Finales

Esta organización fue realizada el **13 de Octubre de 2025** con los siguientes objetivos:

1. ✅ Separar documentación del código
2. ✅ Organizar tests por funcionalidad
3. ✅ Estructurar migraciones por versión
4. ✅ Centralizar scripts de ejecución
5. ✅ Facilitar el mantenimiento futuro
6. ✅ Mejorar la navegabilidad del proyecto
7. ✅ Documentar todos los cambios

**Resultado:** Un proyecto más organizado, mantenible y profesional.

---

*Para más información sobre cada sección, consultar los READMEs específicos.*
