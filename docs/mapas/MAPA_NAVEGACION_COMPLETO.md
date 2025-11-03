# Mapa de Navegación Completo - Sistema Angelow

## Descripción General
Este documento describe la arquitectura completa de navegación del sistema Angelow, una plataforma E-commerce especializada en ropa infantil con funcionalidades avanzadas de gestión administrativa y sistema de delivery con GPS.

## Arquitectura del Sistema

### 1. Frontend Público (Tienda Online)
**Propósito:** Interfaz de usuario para clientes no registrados y registrados.

#### Páginas Principales:
- **Inicio** (`index.php`)
  - Sliders promocionales
  - Categorías destacadas
  - Productos destacados
  - Colecciones activas
  - Newsletter y testimonios

- **Catálogo/Productos** (`tienda/productos.php`)
  - Listado de productos con filtros
  - Búsqueda avanzada
  - Paginación

- **Detalle de Producto** (`producto/verproducto.php`)
  - Información completa del producto
  - Variantes (tallas, colores)
  - Imágenes múltiples
  - Reseñas y calificaciones

- **Carrito de Compras** (`tienda/carrito.php`)
  - Gestión de items
  - Cálculo de totales
  - Aplicación de descuentos
  - Persistencia de sesión

- **Checkout/Pago** (`tienda/pay.php`)
  - Selección de dirección de envío
  - Método de pago (transferencia bancaria)
  - Validación de comprobantes
  - Confirmación de pedido

- **Confirmación** (`tienda/pay.php`)
  - Resumen del pedido
  - Número de orden
  - Instrucciones de seguimiento

### 2. Sistema de Autenticación
**Propósito:** Gestión de usuarios y control de acceso basado en roles.

#### Componentes:
- **Login** (`auth/login.php`)
  - Autenticación por email/contraseña
  - Recordar sesión

- **Registro** (`auth/register.php`)
  - Creación de cuenta nueva
  - Validación de datos

- **Middleware de Roles** (`auth/auth_middleware.php`)
  - Verificación de permisos
  - Redirección automática

- **Redirección por Roles** (`auth/role_redirect.php`)
  - Enrutamiento según tipo de usuario
  - Roles: user, admin, delivery

### 3. Panel de Usuario
**Propósito:** Área personal para gestión de cuenta y pedidos.

#### Funcionalidades:
- **Dashboard** (`users/dashboarduser.php`)
  - Resumen de actividad
  - Accesos rápidos

- **Perfil** (`users/settings.php`)
  - Actualización de datos personales
  - Cambio de contraseña
  - Preferencias

- **Mis Órdenes** (`users/orders.php`)
  - Historial de compras
  - Estados de pedidos

- **Direcciones** (`users/addresses.php`)
  - Gestión de direcciones de envío
  - Dirección predeterminada

- **Seguimiento de Pedidos** (`users/track_order.php`)
  - Estado en tiempo real
  - Ubicación del delivery

- **Detalle de Orden** (`users/order_detail.php`)
  - Información completa del pedido
  - Reordenar productos

### 4. Panel de Administración
**Propósito:** Gestión completa del sistema E-commerce.

#### Módulos Principales:

##### Gestión de Productos:
- **Productos** (`admin/products.php`)
  - CRUD completo de productos
  - Gestión de variantes

- **Agregar Producto** (`admin/subproducto.php`)
  - Formulario de creación
  - Subida de imágenes

- **Categorías** (`admin/categoria/`)
  - Árbol jerárquico
  - Gestión de subcategorías

- **Colecciones** (`admin/colecciones/`)
  - Agrupaciones temáticas
  - Fechas de lanzamiento

- **Tallas** (`admin/tallas/`)
  - Sistema de tallas
  - Compatibilidad productos

- **Inventario** (`admin/inventario/`)
  - Control de stock
  - Alertas de reposición

##### Gestión de Ventas:
- **Órdenes** (`admin/orders.php`)
  - Gestión de pedidos
  - Asignación a delivery
  - Cambios de estado

- **Pagos** (`admin/pagos/`)
  - Validación de comprobantes
  - Gestión de métodos

- **Envíos** (`admin/envio/`)
  - Reglas de envío
  - Costos por zona

- **Descuentos** (`admin/descuento/`)
  - Códigos promocionales
  - Descuentos por cantidad

##### Contenido y Marketing:
- **Sliders** (`admin/sliders/`)
  - Banners principales
  - Gestión de campañas

- **Noticias** (`admin/news/`)
  - Artículos del blog
  - SEO y posicionamiento

- **Reseñas** (`admin/reseñas`)
  - Moderación de comentarios
  - Gestión de calificaciones

##### Informes y Análisis:
- **Ventas** (`admin/informes/ventas.php`)
  - Reportes por período
  - Análisis de tendencias

- **Productos** (`admin/informes/productos.php`)
  - Productos más vendidos
  - Rendimiento por categoría

- **Clientes** (`admin/informes/clientes.php`)
  - Comportamiento de usuarios
  - Segmentación

##### Gestión de Usuarios:
- **Clientes** (`admin/clientes`)
  - Base de datos de usuarios
  - Historial de compras

- **Administradores** (`admin/administradores`)
  - Gestión de permisos
  - Roles y accesos

### 5. Panel de Delivery
**Propósito:** Sistema completo para gestión de entregas con GPS.

#### Funcionalidades:
- **Dashboard** (`delivery/dashboarddeli.php`)
  - Órdenes asignadas
  - Estado de entregas activas

- **Órdenes Asignadas** (`delivery/orders.php`)
  - Lista de entregas pendientes
  - Información de clientes

- **Navegación GPS** (`delivery/navigation.php`)
  - Mapa interactivo con rutas
  - Seguimiento en tiempo real
  - Instrucciones de voz
  - Actualización automática de ubicación

- **Acciones de Entrega** (`delivery/delivery_actions.php`)
  - Cambios de estado
  - Confirmación de entrega
  - Reporte de problemas

#### APIs de Delivery:
- **Actualizar Estado** (`delivery/api/update_status.php`)
  - Cambios en tiempo real
  - Notificaciones automáticas

- **GPS Tracking** (`delivery/api/gps_tracking.php`)
  - Coordenadas del conductor
  - Historial de rutas

### 6. APIs y Servicios Externos
**Propósito:** Integración con servicios de terceros.

#### Servicios Implementados:
- **OpenStreetMap**
  - Geolocalización
  - Cálculo de rutas
  - Mapas interactivos

- **reCAPTCHA**
  - Prevención de spam
  - Validación de formularios

- **Sistema de Correo** (PHPMailer)
  - Envío de confirmaciones
  - Notificaciones automáticas

### 7. Base de Datos
**Propósito:** Almacenamiento y gestión de toda la información del sistema.

#### Estructura Principal:
- **Usuarios** (`users`)
  - Datos personales y de acceso
  - Roles y permisos

- **Productos** (`products`)
  - Información de catálogo
  - Variantes y precios

- **Órdenes** (`orders`)
  - Pedidos y transacciones
  - Estados de procesamiento

- **Carrito** (`cart`)
  - Items temporales
  - Sesiones de compra

- **Inventario** (`inventory`)
  - Control de stock
  - Movimientos

- **Deliveries** (`order_deliveries`)
  - Asignaciones de entregas
  - Seguimiento GPS

- **Auditoría** (`audit_logs`)
  - Registro de acciones
  - Trazabilidad

## Flujos de Navegación Principales

### Flujo de Compra (Usuario Público)
1. **Inicio** → Explorar catálogo
2. **Catálogo** → Ver detalles de producto
3. **Producto** → Agregar al carrito (requiere login)
4. **Carrito** → Proceder al checkout
5. **Checkout** → Procesar pago
6. **Confirmación** → Ver en historial

### Flujo Administrativo
1. **Login Admin** → Dashboard principal
2. **Dashboard** → Gestión de módulos específicos
3. **Módulos** → CRUD operations
4. **Reportes** → Análisis y exportación

### Flujo de Delivery
1. **Login Delivery** → Dashboard de entregas
2. **Dashboard** → Ver órdenes asignadas
3. **Órdenes** → Iniciar navegación GPS
4. **Navegación** → Actualización en tiempo real
5. **Entrega** → Confirmación y finalización

## Tecnologías Utilizadas

### Backend:
- **PHP 8.0+** con PDO para base de datos
- **MySQL 8.0** para persistencia
- **Arquitectura MVC** modular

### Frontend:
- **HTML5/CSS3** para estructura y estilos
- **JavaScript Vanilla** para interactividad
- **Font Awesome** para iconografía

### APIs y Librerías:
- **OpenStreetMap** para mapas y geolocalización
- **Leaflet.js** para mapas interactivos
- **reCAPTCHA** para seguridad
- **PHPMailer** para correos (referenciado)

### Seguridad:
- **Sesiones PHP** para autenticación
- **Validación de entrada** en todos los formularios
- **Encriptación de contraseñas**
- **Control de acceso basado en roles**

## Consideraciones de Rendimiento

- **Optimización de consultas** SQL
- **Caché de sesiones** para carritos
- **Compresión de imágenes** automáticas
- **Lazy loading** en listados
- **CDN** para recursos estáticos (planeado)

## Escalabilidad

- **Arquitectura modular** para fácil expansión
- **APIs RESTful** para integraciones futuras
- **Base de datos normalizada** para crecimiento
- **Sistema de roles** extensible

---

*Este mapa de navegación refleja la implementación actual del sistema Angelow con 85% de completitud, incluyendo todos los módulos core operativos y preparados para futuras expansiones.*