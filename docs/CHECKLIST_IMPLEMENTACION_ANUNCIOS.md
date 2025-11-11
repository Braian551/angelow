# ✅ Checklist de Implementación - Sistema de Anuncios

## Pre-implementación

- [ ] Backup de la base de datos realizado
- [ ] Servidor local ejecutándose (Apache + MySQL)
- [ ] Acceso admin funcionando
- [ ] Todos los archivos nuevos en su lugar

---

## Implementación Base de Datos

- [ ] Migración SQL ejecutada sin errores
- [ ] Tabla `announcements` creada correctamente
- [ ] Tabla `news` eliminada
- [ ] Índices creados correctamente
- [ ] 2 registros de ejemplo insertados

**Comando:**
```sql
USE angelow;
SOURCE database/migrations/EJECUTAR_MIGRACION.sql;
```

---

## Verificación Base de Datos

- [ ] Script de verificación ejecutado
- [ ] Consulta de anuncios activos funciona
- [ ] Estructura de tabla correcta (16 columnas)

**Comando:**
```sql
SELECT * FROM announcements;
```

---

## Configuración de Archivos

- [ ] Carpeta `uploads/announcements/` creada
- [ ] Permisos de escritura configurados
- [ ] CSS de anuncios cargado en index.php
- [ ] Archivos PHP sin errores de sintaxis

**Ubicación:**
```
c:\laragon\www\angelow\uploads\announcements\
```

---

## Pruebas Frontend

- [ ] Página principal carga sin errores
- [ ] Barra superior aparece con anuncio de ejemplo
- [ ] Banner promocional aparece con anuncio de ejemplo
- [ ] Colores personalizados se aplican correctamente
- [ ] Iconos FontAwesome se visualizan
- [ ] Diseño responsive funciona en móvil

**URL de prueba:**
```
http://localhost/angelow/
```

---

## Pruebas Panel Admin

### Acceso
- [ ] Panel de anuncios accesible
- [ ] Listado muestra 2 anuncios de ejemplo
- [ ] Interfaz se carga correctamente

**URL:**
```
http://localhost/angelow/admin/announcements/list.php
```

### Funcionalidad: Crear
- [ ] Formulario de creación accesible
- [ ] Tipo de anuncio seleccionable
- [ ] Campos de texto funcionan
- [ ] Selector de color funciona
- [ ] Selector de fecha funciona
- [ ] Checkbox "Activo" funciona
- [ ] Preview de imagen funciona
- [ ] Formulario se guarda correctamente
- [ ] Mensaje de éxito aparece
- [ ] Nuevo anuncio aparece en listado
- [ ] Nuevo anuncio aparece en frontend

### Funcionalidad: Editar
- [ ] Click en "Editar" abre formulario
- [ ] Datos existentes se cargan
- [ ] Imagen actual se muestra
- [ ] Cambios se guardan correctamente
- [ ] Mensaje de éxito aparece
- [ ] Cambios se reflejan en listado
- [ ] Cambios se reflejan en frontend

### Funcionalidad: Eliminar
- [ ] Click en "Eliminar" muestra confirmación
- [ ] Modal de confirmación aparece
- [ ] Click en "Cancelar" cierra modal
- [ ] Click en "Eliminar" ejecuta acción
- [ ] Mensaje de éxito aparece
- [ ] Anuncio desaparece del listado
- [ ] Anuncio desaparece del frontend

### Funcionalidad: Búsqueda y Filtros
- [ ] Búsqueda por texto funciona
- [ ] Filtro por tipo funciona
- [ ] Filtro por estado funciona
- [ ] Ordenamiento funciona
- [ ] Paginación funciona (si hay más de 12)
- [ ] Contador de resultados actualiza

---

## Pruebas de Integración

### Barra Superior
- [ ] Solo se muestra 1 anuncio a la vez
- [ ] Anuncio con mayor prioridad se muestra
- [ ] Fecha de inicio se respeta
- [ ] Fecha de fin se respeta
- [ ] Anuncio inactivo no aparece
- [ ] Color de fondo se aplica
- [ ] Color de texto se aplica
- [ ] Icono se visualiza correctamente

### Banner Promocional
- [ ] Solo se muestra 1 banner a la vez
- [ ] Banner con mayor prioridad se muestra
- [ ] Título se visualiza correctamente
- [ ] Subtítulo se visualiza (si existe)
- [ ] Botón aparece (si configurado)
- [ ] Link del botón funciona
- [ ] Imagen de fondo se muestra (si existe)
- [ ] Colores se aplican correctamente

---

## Pruebas de Seguridad

- [ ] Solo admins pueden acceder al panel
- [ ] Inputs están sanitizados (XSS)
- [ ] SQL injection protegido (prepared statements)
- [ ] Validación de tipos de archivo
- [ ] Límite de tamaño de archivo (3MB)
- [ ] Validación de formato de color

---

## Pruebas de Performance

- [ ] Página carga en menos de 2 segundos
- [ ] Consultas SQL optimizadas
- [ ] Imágenes no ralentizan carga
- [ ] AJAX responde rápidamente
- [ ] Sin errores en consola del navegador
- [ ] Sin warnings PHP

---

## Documentación

- [ ] README_ANNOUNCEMENTS.md revisado
- [ ] GUIA_RAPIDA_ANUNCIOS.md revisada
- [ ] INSTRUCCIONES_FINALES_ANUNCIOS.md revisadas
- [ ] Equipo capacitado en uso del sistema

---

## Limpieza (Opcional)

- [ ] Backup de carpeta `admin/news/` realizado
- [ ] Carpeta `admin/news/` eliminada
- [ ] Referencias a `news` eliminadas de menús
- [ ] Documentación antigua actualizada

---

## Post-Implementación

- [ ] Sistema en producción funcionando
- [ ] Anuncios personalizados creados
- [ ] Usuarios finales verifican funcionalidad
- [ ] Sin reportes de errores

---

## Firma de Aprobación

**Implementado por:** ___________________  
**Fecha:** ___________________  
**Verificado por:** ___________________  
**Fecha:** ___________________  

**Estado:** [ ] Aprobado [ ] Con observaciones [ ] Rechazado

**Observaciones:**
_______________________________________________
_______________________________________________
_______________________________________________

---

## Resumen Final

| Métrica | Objetivo | Estado |
|---------|----------|--------|
| Archivos creados | 14 | [ ] |
| Archivos modificados | 1 | [ ] |
| Tabla BD creada | 1 | [ ] |
| Tabla BD eliminada | 1 | [ ] |
| Errores encontrados | 0 | [ ] |
| Funcionalidades implementadas | 100% | [ ] |

---

**Total de items:** 100+  
**Completados:** _____  
**Porcentaje:** _____%

---

## Próxima Revisión

**Fecha programada:** ___________________  
**Responsable:** ___________________

---

*Versión del checklist: 1.0*  
*Última actualización: 11/11/2025*
