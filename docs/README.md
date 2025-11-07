# 📚 Documentación del Sistema AngeloW

Esta carpeta contiene toda la documentación del proyecto organizada de forma modular.

## 📁 Estructura

### 📚 Guías (`guias/`)
Documentación general y guías de uso del sistema:
- `ESTRUCTURA_MODULAR.md` - Estructura modular del proyecto
- `GUIA_COMPLETA_DELIVERY.md` - Guía completa del sistema de entregas
- `GUIA_VOZ_ESPAÑOL.md` - Guía de configuración de voz en español
- `INSTRUCCIONES_FINALES.md` - Instrucciones finales de implementación

### 🏗️ Arquitectura del Sistema

- **DELIVERY_SEPARADO.md** - Documentación sobre la separación del módulo de delivery como aplicación independiente (Nov 2025)

### 🔄 Migraciones (`migraciones/`)
Documentación sobre migraciones de base de datos:
- `MIGRACION_007_COMPLETADA.md` - Migración 007 completada
- `MIGRACION_009_ORDERS_ADDRESSES_FINAL.md` - Migración 009 de órdenes y direcciones
- `INSTRUCCIONES_MIGRACION_CLI.md` - Instrucciones para ejecutar migraciones por CLI
- `GUIA_RAPIDA_008.md` - Guía rápida de la migración 008

### 🔧 Correcciones (`correcciones/`)
Documentación de correcciones y fixes aplicados:
- `CORRECCIONES_BUSQUEDA_CARRITO.md` - Correcciones en búsqueda del carrito
- `CORRECCIONES_NAVEGACION_GPS.md` - Correcciones en navegación GPS
- `CORRECCION_FINAL_NAVIGATION.md` - Corrección final de navegación
- `CORRECCION_GPS_USADO.md` - Corrección del GPS usado
- `CORRECCION_NAVIGATION_TRAFFIC.md` - Corrección de tráfico en navegación
- `CORRECCION_PAUSAR_VOZ_NAVEGACION.md` - Corrección para pausar voz en navegación
- `RESUMEN_CORRECCIONES_008.md` - Resumen de correcciones 008
- `RESUMEN_CORRECCION_DELIVERY.md` - Resumen de correcciones de delivery

### 💡 Soluciones (`soluciones/`)
Soluciones a problemas específicos:
- `SOLUCION_ENTREGAS_008.md` - Solución para entregas en versión 008
- `SOLUCION_ERRORES_DELIVERY.md` - Solución de errores en delivery
- `SOLUCION_ERROR_NAVEGACION_400.md` - Solución al error 400 de navegación
- `SOLUCION_INICIAR_RECORRIDO.md` - Solución para iniciar recorrido
- `SOLUCION_VOZ_ACENTO_NATIVO.md` - Solución para voz con acento nativo
- `ACTUALIZACION_EDIT_ORDER.md` - Actualización de edición de órdenes

### � Módulos Específicos

#### Admin (`admin/`)
Documentación del módulo de administración:
- `IMPLEMENTACION_ROLES.md` - Implementación del sistema de roles
- `SISTEMA_ROLES.md` - Sistema de roles y permisos
- `RESUMEN_IMPLEMENTACION.md` - Resumen de implementación
- `PRUEBA_RAPIDA.md` - Guía de pruebas rápidas

#### Delivery (`delivery/`)
Documentación del módulo de entregas

### 🔧 Fixes y Soluciones Adicionales

- **FIX_HISTORIAL_ORDENES.md** - Solución al error de foreign key constraint
- **SOLUCION_APLICADA.md** - Guía rápida de soluciones aplicadas

## 🔗 Enlaces Relacionados

- **Tests**: Ver carpeta `/tests/` para scripts de prueba organizados por módulo
- **Migraciones**: Ver carpeta `/database/migrations/` para scripts SQL

## 📝 Convenciones

- Los archivos con prefijo `FIX_` contienen soluciones técnicas detalladas
- Los archivos con prefijo `SOLUCION_` contienen guías de usuario
- Los archivos con prefijo `CORRECCION_` documentan correcciones aplicadas
- Los archivos con prefijo `GUIA_` son guías de uso y configuración
- Todos los documentos están en formato Markdown para fácil lectura

## 🔍 Búsqueda Rápida

Para encontrar documentación específica, puedes buscar por:
- **Palabra clave**: Usa el buscador de archivos en tu editor
- **Tipo de problema**: Revisa la carpeta `soluciones/`
- **Historial de cambios**: Revisa las carpetas `migraciones/` y `correcciones/`

---

*Última actualización: 7 de Noviembre, 2025*
