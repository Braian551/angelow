# 📢 Guía Simplificada de Anuncios

## ¿Qué son los Anuncios?

Los anuncios son mensajes destacados que aparecen en tu tienda para informar a los clientes sobre:
- ✅ Envíos gratis
- 🏷️ Ofertas y descuentos
- 🎁 Promociones especiales
- ⏰ Eventos importantes

---

## 🎨 Diseño Profesional Automático

**Los anuncios tienen un diseño azul elegante predefinido** que combina perfectamente con tu marca. No necesitas preocuparte por los colores - el sistema los gestiona automáticamente para garantizar:

- ✨ Apariencia profesional
- 👀 Máxima visibilidad
- 📱 Perfecta legibilidad en todos los dispositivos

**Colores por defecto:**
- **Barra Superior**: Azul vibrante (#2563eb) con texto blanco
- **Banner Promocional**: Azul profundo (#1e40af) con texto blanco

**Nota técnica:** Los colores ya no se almacenan en la base de datos. Se definen únicamente en el archivo CSS para mantener consistencia y simplificar el sistema.

---

## 📊 Tipos de Anuncios

### 1. **Barra Superior** (Top Bar)
- Aparece en la parte superior de toda la tienda
- Ideal para: Envíos gratis, descuentos generales
- Diseño: Compacto y siempre visible

### 2. **Banner Promocional**
- Aparece en el contenido de la página principal
- Ideal para: Promociones específicas, eventos
- Diseño: Más grande, puede incluir imágenes y botones

---

## 🚀 Cómo Crear un Anuncio

### Paso 1: Información Básica
1. **Tipo**: Elige entre Barra Superior o Banner Promocional
2. **Título**: Un título corto y atractivo (ej: "¡Envío Gratis!")
3. **Mensaje**: El texto principal del anuncio

### Paso 2: Selecciona un Icono
Elige el icono que mejor represente tu mensaje. **Al seleccionar un icono, verás un preview visual inmediatamente debajo del selector** para confirmar tu elección.

**Ofertas y Descuentos:**
- <i class="fas fa-tags"></i> Etiquetas → Para ofertas generales
- <i class="fas fa-percent"></i> Porcentaje → Para descuentos específicos
- <i class="fas fa-gift"></i> Regalo → Para promociones con regalo
- <i class="fas fa-fire"></i> Fuego → Para ofertas limitadas

**Envíos:**
- <i class="fas fa-truck"></i> Camión → Envío estándar
- <i class="fas fa-shipping-fast"></i> Envío rápido → Entregas express
- <i class="fas fa-plane"></i> Avión → Envíos internacionales

**Tiempo:**
- <i class="fas fa-clock"></i> Reloj → Ofertas por tiempo limitado
- <i class="fas fa-calendar"></i> Calendario → Eventos programados
- <i class="fas fa-bell"></i> Campana → Alertas importantes

**Preview Visual:**
Cuando selecciones un icono, aparecerá inmediatamente un preview visual debajo del selector mostrando cómo se verá el icono en tu anuncio. Esto te ayuda a confirmar que elegiste la opción correcta antes de guardar.

**Colores Automáticos:**
Los anuncios usan colores profesionales predefinidos automáticamente. No necesitas preocuparte por elegir colores - el sistema aplica el azul elegante perfecto para cada tipo de anuncio.

**Nota:** El formulario de administración ya no incluye campos para seleccionar colores. Todo se maneja automáticamente para mantener consistencia visual.

**Y más opciones organizadas por categoría...**

### Paso 3: Opciones Adicionales (Solo Banner)
Si elegiste **Banner Promocional**, puedes agregar:
- **Subtítulo**: Texto adicional explicativo
- **Botón**: Con texto y enlace (ej: "Ver Ofertas" → /tienda)
- **Imagen**: Foto promocional (opcional)

### Paso 4: Configuración
- **Prioridad**: Si tienes 2 anuncios, el de mayor número se muestra primero
- **Fechas**: Define inicio y fin (opcional) para anuncios temporales
- **Estado**: Activa/desactiva el anuncio cuando quieras

---

## 📝 Ejemplos Prácticos

### Ejemplo 1: Envío Gratis
```
Tipo: Barra Superior
Título: ¡Envío Gratis!
Mensaje: En compras superiores a $50
Icono: <i class="fas fa-truck"></i> Camión
```

### Ejemplo 2: Oferta de Verano
```
Tipo: Banner Promocional
Título: Colección Verano 2024
Mensaje: Hasta 40% de descuento en toda la colección
Subtítulo: Aprovecha mientras dure el stock
Icono: <i class="fas fa-fire"></i> Fuego
Botón: "Ver Colección" → /tienda/tienda.php?collection=verano
```

### Ejemplo 3: Evento Especial
```
Tipo: Barra Superior
Título: Black Friday
Mensaje: 3 días de descuentos increíbles - Del 24 al 26 de Nov
Icono: <i class="fas fa-clock"></i> Reloj
Fecha inicio: 24/11/2024 00:00
Fecha fin: 26/11/2024 23:59
```

---

## ⚠️ Límites y Reglas

### Máximo de Anuncios
- **Solo puedes tener 2 anuncios activos simultáneamente**
- Cuando alcances el límite, el botón "Agregar" desaparecerá
- Para agregar uno nuevo, primero elimina uno existente

### Buenas Prácticas
1. **Mantén los mensajes cortos y claros**
2. **Usa iconos relevantes** que ayuden a comunicar el mensaje
3. **Actualiza los anuncios regularmente** para mantener el interés
4. **Programa fechas** para ofertas temporales (se desactivan automáticamente)
5. **Usa prioridades** si tienes 2 anuncios (el número mayor aparece primero)

---

## 🎯 Consejos de Uso

### Para Barra Superior
- ✅ Mensajes urgentes o importantes
- ✅ Ofertas de envío gratis
- ✅ Anuncios que deben verse en todas las páginas
- ❌ Evita textos muy largos

### Para Banner Promocional
- ✅ Promociones con imagen visual
- ✅ Ofertas específicas de productos
- ✅ Eventos o lanzamientos
- ✅ Call-to-action con botones

---

## 🔧 Gestión de Anuncios

### Editar un Anuncio
1. Ve a la lista de anuncios
2. Clic en "Editar" (botón azul)
3. Modifica los campos necesarios
4. Guarda los cambios

### Eliminar un Anuncio
1. Ve a la lista de anuncios
2. Clic en "Eliminar" (botón rojo)
3. Confirma la eliminación

### Activar/Desactivar
- Usa el checkbox "Activo" en el formulario
- Anuncios inactivos no se muestran, pero conservan su configuración

---

## 📱 Visualización

Los anuncios se adaptan automáticamente a:
- 💻 Computadoras de escritorio
- 📱 Tablets
- 📱 Teléfonos móviles

**El diseño es responsive y siempre se ve profesional.**

---

## ❓ Preguntas Frecuentes

**P: ¿Por qué solo 2 anuncios?**
R: Para no saturar a los clientes con demasiada información. Dos anuncios permiten destacar lo más importante sin abrumar.

**P: ¿Puedo cambiar los colores?**
R: No, los colores están predefinidos en el CSS para mantener consistencia profesional. El formulario ya no incluye campos para seleccionar colores, y estos no se almacenan en la base de datos.

**P: ¿Qué pasa si olvido poner fecha de fin?**
R: El anuncio permanecerá activo hasta que lo desactives manualmente.

**P: ¿Los iconos son obligatorios?**
R: Sí, los iconos son obligatorios y usan Font Awesome para mantener consistencia visual.

**P: ¿Puedo usar emojis en el título?**
R: Aunque puedes, te recomendamos usar los iconos de Font Awesome que ya están optimizados.

---

## 🎉 ¡Listo para Empezar!

Con esta guía tienes todo lo necesario para crear anuncios efectivos y atractivos. El sistema es intuitivo y te guía en cada paso.

**Recuerda:** Menos es más. Dos buenos anuncios son mejor que muchos que se ignoran.
