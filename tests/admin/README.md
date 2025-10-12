# 🧪 Tests - Módulo Admin

Tests del módulo de administración del sistema Angelow.

## 📁 Submódulos

### 📦 Orders (Órdenes)
Tests de gestión de órdenes, actualización masiva, historial.

- **Ubicación**: `admin/orders/`
- **Tests**: 6 archivos
- **Estado**: ✅ Funcionando

**Tests principales:**
- [test_bulk_update.php](orders/test_bulk_update.php) - Test completo de actualización masiva
- [verify_triggers.php](orders/verify_triggers.php) - Verificación de triggers

**Documentación:** [Ver docs](../../docs/admin/orders/)

---

### 📦 Products (Productos) - Futuro
Tests de gestión de productos, categorías, inventario.

- **Estado**: 📝 Pendiente

---

### 👥 Users (Usuarios) - Futuro
Tests de gestión de usuarios administrativos, permisos.

- **Estado**: 📝 Pendiente

---

## 🚀 Ejecución Rápida

```bash
# Todos los tests de orders
cd c:\laragon\www\angelow
php tests/admin/orders/test_bulk_update.php

# Verificar configuración
php tests/admin/orders/verify_triggers.php
```

## 🔗 Navegación

- ⬆️ [Volver a Tests Principal](../README.md)
- 📚 [Ver Documentación del Módulo Admin](../../docs/admin/README.md)

---

*Última actualización: 12 de Octubre, 2025*
