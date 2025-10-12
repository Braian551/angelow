# 🧪 Tests del Sistema Angelow

Esta carpeta contiene todos los tests del sistema, organizados por módulos siguiendo la misma estructura del código fuente.

## 📁 Estructura Modular

```
tests/
├── README.md                    (este archivo)
├── admin/                       (tests del módulo de administración)
│   ├── README.md
│   └── orders/                  (tests de gestión de órdenes)
│       ├── README.md
│       ├── test_bulk_update.php
│       ├── verify_triggers.php
│       └── ...
└── [otros módulos]/
```

## 🎯 Organización por Módulos

Los tests están organizados siguiendo la misma estructura que el código fuente:

- **`admin/`** - Tests del panel de administración
  - `orders/` - Tests de órdenes, historial, actualización masiva
  - `products/` - Tests de productos (futuro)
  - `users/` - Tests de usuarios (futuro)
  
- **`users/`** - Tests del módulo de clientes (futuro)
- **`tienda/`** - Tests del módulo de tienda (futuro)

## 🚀 Ejecución de Tests

### Test Completo de un Módulo

```bash
# Tests de admin/orders
php tests/admin/orders/test_bulk_update.php
```

### Tests Individuales

```bash
# Verificar triggers
php tests/admin/orders/verify_triggers.php

# Verificar estructura BD
php tests/admin/orders/check_tables.php
```

## 📊 Tipos de Tests

### 🔍 Tests de Verificación
Verifican la estructura de la base de datos, configuración, etc.
- `check_tables.php` - Estructura de tablas
- `check_collations.php` - Collations de BD
- `verify_triggers.php` - Triggers activos

### ✅ Tests Funcionales
Prueban la funcionalidad completa de características.
- `test_bulk_update.php` - Actualización masiva de órdenes
- Tests end-to-end de flujos completos

### 🔧 Tests de Integración
Prueban la integración entre módulos (futuro).

## 📖 Navegación Rápida

### Módulo Admin - Órdenes
- **Tests**: [admin/orders/](admin/orders/)
- **Documentación**: [../docs/admin/orders/](../docs/admin/orders/)

## 🔗 Enlaces Relacionados

- **Documentación**: Ver `/docs/` (misma estructura modular)
- **Código**: Ver carpetas correspondientes en la raíz
- **Migraciones**: Ver `/database/migrations/`

## 📝 Convenciones

- Cada módulo tiene su propio `README.md`
- Archivos de test prefijados con `test_` para tests funcionales
- Archivos prefijados con `check_` o `verify_` para tests de verificación
- Todos los tests son ejecutables desde línea de comandos

## ✅ Mejores Prácticas

1. **Ejecutar tests antes de commits importantes**
2. **Documentar nuevos tests en el README del módulo**
3. **Mantener tests actualizados con cambios en el código**
4. **Usar nombres descriptivos para archivos de test**

---

*Última actualización: 12 de Octubre, 2025*
