# 🚀 Guía Rápida - Sistema de Anuncios

## ⚡ Pasos para Implementar

### 1️⃣ Ejecutar Migración
```bash
# Abrir HeidiSQL o phpMyAdmin
# Ejecutar el archivo:
database/migrations/006_create_announcements_table.sql
```

### 2️⃣ Verificar Instalación
```bash
# Ejecutar el script de verificación:
database/migrations/006_verify_announcements.sql
```

### 3️⃣ Acceder al Panel Admin
```
URL: http://localhost/angelow/admin/announcements/list.php
Usuario: admin
```

## 📱 Crear Anuncio de Barra Superior

1. Click en **"Agregar anuncio"**
2. Seleccionar tipo: **"Barra Superior"**
3. Llenar campos:
   - **Título:** Envío Gratis
   - **Mensaje:** ¡Envío gratis en compras superiores a $50.000!
   - **Icono:** fa-truck
   - **Color fondo:** #000000
   - **Color texto:** #ffffff
   - **Prioridad:** 10
4. Marcar **"Activo"**
5. Click **"Guardar"**

## 🎨 Crear Banner Promocional

1. Click en **"Agregar anuncio"**
2. Seleccionar tipo: **"Banner Promocional"**
3. Llenar campos:
   - **Título:** ¡Oferta 3x2!
   - **Mensaje:** Compra 2 prendas y llévate la 3ra con 50% de descuento
   - **Subtítulo:** Válido hasta el 30 de junio
   - **Texto botón:** Ver oferta
   - **URL botón:** /tienda/tienda.php?promo=3x2
   - **Icono:** fa-tags
   - **Color fondo:** #ff6b6b
   - **Color texto:** #ffffff
   - **Prioridad:** 5
4. (Opcional) Subir imagen
5. (Opcional) Configurar fechas de inicio/fin
6. Marcar **"Activo"**
7. Click **"Guardar"**

## 🎯 Consejos de Uso

### Prioridad
- **Mayor número = Mayor prioridad**
- Solo se muestra 1 anuncio de cada tipo
- Use prioridades diferentes para probar múltiples anuncios

### Fechas
- **Sin fechas:** Se muestra siempre (si está activo)
- **Con fecha inicio:** Se muestra desde esa fecha
- **Con fecha fin:** Se oculta después de esa fecha
- Útil para ofertas temporales

### Iconos Populares
```
fa-truck         - Envíos
fa-tags          - Ofertas
fa-gift          - Regalos
fa-percent       - Descuentos
fa-star          - Destacados
fa-heart         - Favoritos
fa-shopping-bag  - Compras
fa-fire          - Trending
```

## 🔄 Reemplazar Contenido Existente

### Migrar de `news` a `announcements`:

**Antes (sistema viejo):**
```php
// admin/news/news_list.php ❌ OBSOLETO
```

**Después (sistema nuevo):**
```php
// admin/announcements/list.php ✅ NUEVO
```

## 📋 Checklist Post-Migración

- [ ] Ejecutar migración SQL
- [ ] Verificar que tabla `announcements` existe
- [ ] Verificar que tabla `news` fue eliminada
- [ ] Crear anuncio de prueba
- [ ] Verificar visualización en página principal
- [ ] Probar edición de anuncio
- [ ] Probar eliminación de anuncio
- [ ] (Opcional) Eliminar carpeta `admin/news/`

## 🆘 Problemas Comunes

### No veo los anuncios en el sitio
```sql
-- Verificar que hay anuncios activos
SELECT * FROM announcements WHERE is_active = 1;
```

### Error 404 en panel admin
```
Verificar ruta: /admin/announcements/list.php
No: /admin/news/ (ruta antigua)
```

### Imagen no se sube
```
Verificar permisos en:
uploads/announcements/
```

## 📞 URLs Importantes

```
Panel Admin:        /admin/announcements/list.php
Agregar:           /admin/announcements/add.php
Ver Anuncios:      / (página principal)
```

## ✅ Validación Final

Después de implementar, verificar:

1. ✓ Aparece barra superior con anuncio
2. ✓ Aparece banner promocional
3. ✓ Colores personalizados se aplican
4. ✓ Iconos se visualizan
5. ✓ Botones funcionan (si configurados)
6. ✓ Admin puede crear/editar/eliminar

---

**¡Listo para usar!** 🎉

Si todo funciona correctamente, puede eliminar:
- `admin/news/` (carpeta completa)
- Referencias a `news` en menús o documentación antigua
