# Matriz de Requisitos Actualizada - Sistema Angelow

Esta matriz desglosa los requisitos funcionales del sistema Angelow con un nivel de detalle granular, basado en la implementación actual del código.

## 1. Configuración General del Sitio
**Fuente:** `settings/site_settings.php`, `admin/settings/general.php`

| ID | Requisito Funcional | Descripción Detallada |
|---|---|---|
| **RF-CONF-001** | Configurar Nombre de la Tienda | El sistema debe permitir al administrador definir el nombre visible de la tienda (ej. "Angelow"). |
| **RF-CONF-002** | Configurar Lema de la Tienda | El sistema debe permitir al administrador definir una frase corta o slogan (ej. "Moda con propósito"). |
| **RF-CONF-003** | Configurar Logo de la Marca | El sistema debe permitir subir una imagen (PNG, SVG) para usar como logo principal. |
| **RF-CONF-004** | Configurar Color Primario | El sistema debe permitir seleccionar un color hexadecimal para la identidad principal de la marca. |
| **RF-CONF-005** | Configurar Color Secundario | El sistema debe permitir seleccionar un color hexadecimal secundario. |
| **RF-CONF-006** | Configurar Correo de Soporte | El sistema debe permitir definir el correo electrónico visible para contacto de clientes. |
| **RF-CONF-007** | Configurar Teléfono de Soporte | El sistema debe permitir definir el número de teléfono de contacto principal. |
| **RF-CONF-008** | Configurar WhatsApp de Soporte | El sistema debe permitir definir el número para el botón de chat de WhatsApp. |
| **RF-CONF-009** | Configurar Horario de Atención | El sistema debe permitir definir el texto del horario visible en el pie de página. |
| **RF-CONF-010** | Configurar Dirección Física | El sistema debe permitir definir la dirección física de la tienda o bodega. |
| **RF-CONF-011** | Configurar Auto-cancelación de Órdenes | El sistema debe permitir definir el tiempo en horas (1-720) para cancelar automáticamente órdenes pendientes. |
| **RF-CONF-012** | Configurar Ventana de Reseñas | El sistema debe permitir definir cuántos días después de la compra se permite dejar una reseña. |
| **RF-CONF-013** | Configurar Umbral de Stock Bajo | El sistema debe permitir definir la cantidad mínima de producto para activar la alerta de stock bajo. |
| **RF-CONF-014** | Configurar Auto-aprobación de Reseñas | El sistema debe permitir activar/desactivar la publicación automática de reseñas sin moderación previa. |
| **RF-CONF-015** | Configurar Moneda | El sistema debe permitir definir el código ISO de la moneda (ej. COP, USD). |
| **RF-CONF-016** | Configurar Zona Horaria | El sistema debe permitir definir la zona horaria para los reportes y registros. |
| **RF-CONF-017** | Configurar Mensaje de Bienvenida | El sistema debe permitir definir el título de bienvenida en el dashboard administrativo. |
| **RF-CONF-018** | Configurar Redes Sociales | El sistema debe permitir definir las URLs de Instagram, Facebook y TikTok. |

## 2. Gestión de Productos (Admin)
**Fuente:** `admin/products.php`, `admin/editproducto.php`

| ID | Requisito Funcional | Descripción Detallada |
|---|---|---|
| **RF-PROD-001** | Listar Productos | El sistema debe mostrar una grilla con los productos existentes. |
| **RF-PROD-002** | Filtrar por Texto | El sistema debe permitir buscar productos por nombre o SKU. |
| **RF-PROD-003** | Filtrar por Categoría | El sistema debe permitir filtrar la lista de productos por una categoría específica. |
| **RF-PROD-004** | Filtrar por Estado | El sistema debe permitir filtrar productos por estado "Activo" o "Inactivo". |
| **RF-PROD-005** | Filtrar por Género | El sistema debe permitir filtrar productos por género (Niño, Niña, Bebé, Unisex). |
| **RF-PROD-006** | Ordenar Productos | El sistema debe permitir ordenar la lista por: Más recientes, Nombre (A-Z, Z-A), Precio (asc/desc), Stock (asc/desc). |
| **RF-PROD-007** | Vista Rápida de Producto | El sistema debe permitir ver un resumen del producto en un modal sin salir de la lista. |
| **RF-PROD-008** | Zoom de Imagen | El sistema debe permitir ampliar la imagen del producto desde la lista o vista rápida. |
| **RF-PROD-009** | Crear Nuevo Producto | El sistema debe permitir acceder al formulario de creación de un nuevo producto. |
| **RF-PROD-010** | Editar Producto | El sistema debe permitir acceder al formulario de edición de un producto existente. |
| **RF-PROD-011** | Exportar Productos | El sistema debe permitir exportar el listado de productos (ej. a CSV o Excel). |
| **RF-PROD-012** | Paginación de Productos | El sistema debe dividir la lista de productos en páginas para facilitar la navegación. |
| **RF-PROD-013** | Visualizar Stock en Lista | El sistema debe mostrar visualmente el nivel de stock en la tarjeta del producto. |
| **RF-PROD-014** | Visualizar Etiquetas en Lista | El sistema debe mostrar las etiquetas (tags) asignadas al producto en la vista de lista. |

## 3. Gestión de Usuarios (Cliente)
**Fuente:** `users/formlogin.php`, `users/addresses.php`, `users/wishlist.php`

| ID | Requisito Funcional | Descripción Detallada |
|---|---|---|
| **RF-USER-001** | Registro de Usuario | El sistema debe permitir registrarse con Nombre, Email, Teléfono y Contraseña. |
| **RF-USER-002** | Aceptación de Términos | El sistema debe obligar a aceptar los términos y condiciones para registrarse. |
| **RF-USER-003** | Inicio de Sesión | El sistema debe permitir ingresar con Email y Contraseña. |
| **RF-USER-004** | Listar Direcciones | El sistema debe mostrar todas las direcciones guardadas por el usuario. |
| **RF-USER-005** | Agregar Dirección | El sistema debe permitir guardar una nueva dirección con coordenadas GPS. |
| **RF-USER-006** | Establecer Dirección Predeterminada | El sistema debe permitir marcar una dirección como la principal para envíos. |
| **RF-USER-007** | Eliminar Dirección | El sistema debe permitir borrar una dirección guardada. |
| **RF-USER-008** | Ver Lista de Deseos | El sistema debe mostrar los productos agregados a favoritos. |
| **RF-USER-009** | Agregar a Lista de Deseos | El sistema debe permitir marcar un producto como favorito desde la tienda. |
| **RF-USER-010** | Eliminar de Lista de Deseos | El sistema debe permitir quitar un producto de la lista de favoritos. |

## 4. Gestión de Órdenes (Admin)
**Fuente:** `admin/orders.php`, `admin/order/`

| ID | Requisito Funcional | Descripción Detallada |
|---|---|---|
| **RF-ORD-001** | Listar Órdenes | El sistema debe mostrar un listado de todas las órdenes de compra. |
| **RF-ORD-002** | Filtrar Órdenes por Estado | El sistema debe permitir filtrar órdenes (Pendiente, Pagado, Enviado, etc.). |
| **RF-ORD-003** | Ver Detalle de Orden | El sistema debe mostrar el detalle completo: productos, cliente, dirección, pagos. |
| **RF-ORD-004** | Cambiar Estado de Orden | El sistema debe permitir al administrador actualizar el estado del pedido. |
| **RF-ORD-005** | Verificar Pago | El sistema debe permitir validar y aprobar comprobantes de pago manuales. |

## 5. Descuentos y Promociones
**Fuente:** `admin/descuento/`

| ID | Requisito Funcional | Descripción Detallada |
|---|---|---|
| **RF-DESC-001** | Crear Código de Descuento | El sistema debe permitir generar códigos promocionales manuales. |
| **RF-DESC-002** | Generar Códigos Masivos | El sistema debe permitir generar múltiples códigos aleatorios automáticamente. |
| **RF-DESC-003** | Configurar Descuentos por Volumen | El sistema debe permitir definir reglas de descuento automático por cantidad (ej. lleva 3 paga 2). |

