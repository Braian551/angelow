# Sistema de Anuncios y Ofertas - Angelow

## 📋 Descripción

Este documento describe el nuevo sistema de anuncios y ofertas que reemplaza el módulo de noticias (`news`) en la aplicación Angelow.

## 🔄 Migración Realizada

### Cambios en Base de Datos

- **Tabla eliminada:** `news`
- **Tabla nueva:** `announcements`

### Estructura de la Tabla `announcements`

```sql
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('top_bar','promo_banner') - Tipo de anuncio
  `title` varchar(255) - Título del anuncio
  `message` text - Mensaje principal
  `subtitle` varchar(255) - Subtítulo (opcional, para banners)
  `button_text` varchar(100) - Texto del botón (opcional)
  `button_link` varchar(500) - URL del botón (opcional)
  `image` varchar(500) - Imagen para banner promocional
  `background_color` varchar(20) - Color de fondo
  `text_color` varchar(20) - Color del texto
  `icon` varchar(50) - Clase de icono FontAwesome
  `priority` int - Prioridad de visualización
  `is_active` tinyint(1) - Estado activo/inactivo
  `start_date` datetime - Fecha de inicio (opcional)
  `end_date` datetime - Fecha de fin (opcional)
  `created_at` datetime
  `updated_at` datetime
)
```

## 📁 Archivos Creados

### Administración (`admin/announcements/`)
- `list.php` - Listado de anuncios
- `add.php` - Formulario para agregar anuncio
- `edit.php` - Formulario para editar anuncio
- `save.php` - Procesa creación/actualización
- `delete.php` - Elimina anuncio

### AJAX (`ajax/admin/`)
- `get_announcements.php` - API para obtener anuncios

### JavaScript (`js/admin/announcements/`)
- `announcementsadmin.php` - Lógica del frontend admin

### Migración (`database/migrations/`)
- `006_create_announcements_table.sql` - Script de migración

## 🚀 Cómo Aplicar la Migración

1. **Hacer backup de la base de datos:**
   ```bash
   mysqldump -u root -p angelow > backup_antes_migracion.sql
   ```

2. **Ejecutar la migración:**
   ```sql
   USE angelow;
   SOURCE c:/laragon/www/angelow/database/migrations/006_create_announcements_table.sql;
   ```

3. **Verificar que se creó la tabla:**
   ```sql
   SHOW TABLES LIKE 'announcements';
   SELECT * FROM announcements;
   ```

## 📝 Tipos de Anuncios

### 1. Barra Superior (`top_bar`)
- Aparece en la parte superior del sitio
- Texto corto con información importante
- Soporta iconos FontAwesome
- Colores personalizables

**Ejemplo:**
```
🚚 ¡Envío gratis en compras superiores a $50.000! | 3 cuotas sin interés
```

### 2. Banner Promocional (`promo_banner`)
- Aparece en el contenido del sitio
- Incluye título, subtítulo y botón opcional
- Soporta imagen de fondo
- Ideal para promociones destacadas

**Ejemplo:**
```
🏷️ ¡Compra 2 prendas y llévate la 3ra con 50% de descuento!
Válido hasta el 30 de junio o hasta agotar existencias
[Botón: Aprovechar oferta]
```

## 🎨 Características

### Personalización
- **Colores:** Background y texto personalizables con picker de color
- **Iconos:** Integración con FontAwesome
- **Fechas:** Programación automática de inicio/fin
- **Prioridad:** Control de qué anuncio mostrar cuando hay múltiples activos

### Gestión Admin
- Búsqueda y filtrado de anuncios
- Vista previa de imágenes
- Edición en línea
- Activación/desactivación rápida
- Eliminación con confirmación

### Visualización
- Respeta fechas de vigencia automáticamente
- Muestra solo el anuncio de mayor prioridad
- Se oculta si no hay anuncios activos
- Responsive design

## 🔧 Uso en el Frontend

Los anuncios se cargan automáticamente en `index.php`:

### Barra Superior
```php
<?php if ($top_bar_announcement): ?>
    <div class="announcement-bar" style="background-color: <?= $top_bar_announcement['background_color'] ?>">
        <p>
            <?php if ($top_bar_announcement['icon']): ?>
                <i class="fas <?= $top_bar_announcement['icon'] ?>"></i>
            <?php endif; ?>
            <?= $top_bar_announcement['message'] ?>
        </p>
    </div>
<?php endif; ?>
```

### Banner Promocional
```php
<?php if ($promo_banner): ?>
    <section class="promo-banner" style="background-color: <?= $promo_banner['background_color'] ?>">
        <div class="promo-content">
            <h2><?= $promo_banner['title'] ?></h2>
            <p><?= $promo_banner['subtitle'] ?></p>
            <a href="<?= $promo_banner['button_link'] ?>" class="btn">
                <?= $promo_banner['button_text'] ?>
            </a>
        </div>
    </section>
<?php endif; ?>
```

## 🎯 Acceso al Panel Admin

1. Iniciar sesión como administrador
2. Ir a: `http://localhost/angelow/admin/announcements/list.php`
3. Gestionar anuncios desde el panel

## 📌 Iconos FontAwesome Recomendados

- `fa-truck` - Envíos
- `fa-tags` - Ofertas
- `fa-gift` - Regalos
- `fa-percent` - Descuentos
- `fa-star` - Destacados
- `fa-bullhorn` - Anuncios
- `fa-heart` - Favoritos
- `fa-shopping-bag` - Compras

Ver más en: https://fontawesome.com/icons

## 🔍 Validaciones

- Título y mensaje son obligatorios
- Tipo debe ser 'top_bar' o 'promo_banner'
- Imágenes: JPG, PNG, WEBP (máx 3MB)
- Colores en formato hexadecimal (#000000)
- Fechas opcionales con validación automática

## ⚠️ Notas Importantes

1. **Eliminación de `news`:** La carpeta `admin/news/` ya no es necesaria y puede eliminarse
2. **Datos de ejemplo:** La migración incluye 2 anuncios de ejemplo
3. **Prioridad:** Si hay múltiples anuncios activos del mismo tipo, se muestra el de mayor prioridad
4. **Fechas:** Los anuncios solo se muestran dentro del rango de fechas configurado
5. **Imágenes:** Se guardan en `uploads/announcements/`

## 🐛 Solución de Problemas

### No se muestran los anuncios
1. Verificar que existan anuncios activos: `SELECT * FROM announcements WHERE is_active = 1`
2. Revisar que las fechas sean correctas
3. Verificar que el tipo sea correcto ('top_bar' o 'promo_banner')

### Error al subir imágenes
1. Verificar permisos de escritura en `uploads/announcements/`
2. Verificar tamaño de archivo (máx 3MB)
3. Verificar formato (JPG, PNG, WEBP)

### No aparece en el panel admin
1. Verificar rol de administrador
2. Verificar ruta: `/admin/announcements/list.php`
3. Revisar consola del navegador por errores JavaScript

## 📧 Soporte

Para consultas sobre esta migración, contactar al equipo de desarrollo.

---

**Versión:** 1.0  
**Fecha:** 2025-11-11  
**Autor:** Sistema de Migración Angelow
