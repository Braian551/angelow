# Angelow - Tienda Online de Ropa Infantil

## 📋 Descripción del Proyecto

**Angelow** es una plataforma de e-commerce especializada en ropa infantil de alta calidad. El proyecto está desarrollado en PHP con MySQL y ofrece una experiencia completa de compra online con gestión administrativa avanzada.

## 🎯 Características Principales

### Para Clientes
- **Catálogo de productos** con filtros avanzados por categoría, género, precio y tallas
- **Sistema de carrito** persistente con gestión de variantes (color/talla)
- **Proceso de compra** simplificado con múltiples métodos de pago
- **Panel de usuario** para gestión de pedidos y direcciones
- **Sistema de favoritos** y recomendaciones personalizadas
- **Búsqueda inteligente** con historial y sugerencias
- **Autenticación** tradicional y con Google OAuth

### Para Administradores
- **Dashboard administrativo** con estadísticas en tiempo real
- **Gestión completa de productos** con variantes y stock
- **Sistema de inventario** con alertas de bajo stock
- **Gestión de pedidos** con seguimiento de estados
- **Sistema de descuentos** y códigos promocionales
- **Gestión de categorías** y tallas
- **Reportes y exportación** de datos
- **Auditoría completa** de cambios en el sistema

## 🏗️ Arquitectura del Sistema

### Tecnologías Utilizadas
- **Backend**: PHP 8.0+
- **Base de datos**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Librerías**: 
  - PHPMailer para envío de correos
  - TCPDF para generación de facturas
  - Font Awesome para iconografía
- **Servidor**: Apache/Nginx compatible

### Estructura de Directorios

```
angelow/
├── admin/                    # Panel administrativo
│   ├── api/                 # APIs del admin
│   ├── categoria/           # Gestión de categorías
│   ├── descuento/           # Sistema de descuentos
│   ├── envio/               # Reglas de envío
│   ├── inventario/          # Gestión de inventario
│   ├── modals/              # Modales del admin
│   ├── order/               # Gestión de órdenes
│   └── tallas/              # Gestión de tallas
├── ajax/                    # Endpoints AJAX
├── auth/                    # Sistema de autenticación
├── css/                     # Estilos CSS
├── images/                  # Recursos multimedia
├── js/                      # Scripts JavaScript
├── layouts/                 # Plantillas reutilizables
├── pagos/                   # Sistema de pagos
├── producto/                # Páginas de productos
├── tienda/                  # Catálogo y carrito
├── users/                   # Panel de usuario
└── vendor/                  # Dependencias Composer
```

## 🗄️ Base de Datos

### Tablas Principales

#### Gestión de Usuarios
- `users` - Información de usuarios
- `access_tokens` - Tokens de acceso
- `google_auth` - Autenticación con Google
- `audit_users` - Auditoría de cambios de usuarios

#### Catálogo de Productos
- `products` - Productos principales
- `categories` - Categorías de productos
- `colors` - Colores disponibles
- `sizes` - Tallas disponibles
- `product_color_variants` - Variantes de color
- `product_size_variants` - Variantes de talla con stock
- `product_images` - Imágenes de productos
- `variant_images` - Imágenes específicas de variantes

#### Sistema de Compras
- `carts` - Carritos de compra
- `cart_items` - Items del carrito
- `orders` - Órdenes de compra
- `order_items` - Items de las órdenes
- `payment_transactions` - Transacciones de pago

#### Gestión Administrativa
- `bulk_discount_rules` - Reglas de descuento por volumen
- `discount_codes` - Códigos de descuento
- `delivery_cities` - Ciudades de entrega
- `shipping_rules` - Reglas de envío
- `audit_orders` - Auditoría de órdenes
- `audit_categories` - Auditoría de categorías

### Triggers y Procedimientos
- **Triggers de auditoría** para usuarios y órdenes
- **Procedimientos almacenados** para consultas complejas
- **Sistema de cache** para optimización

## 🔐 Sistema de Autenticación

### Características
- **Registro de usuarios** con validación de email
- **Login tradicional** con email/teléfono
- **Autenticación con Google** OAuth 2.0
- **Sistema de "Recordar mi cuenta"** con tokens seguros
- **Protección contra ataques** de fuerza bruta
- **Roles de usuario**: customer, admin, delivery

### Seguridad
- Contraseñas hasheadas con `password_hash()`
- Tokens de sesión seguros
- Validación de CSRF
- Sanitización de inputs
- Headers de seguridad

## 🛒 Sistema de E-commerce

### Catálogo de Productos
- **Filtros avanzados**: categoría, género, precio, talla
- **Búsqueda inteligente** con sugerencias
- **Sistema de variantes** (color/talla)
- **Gestión de stock** por variante
- **Imágenes múltiples** por producto
- **Sistema de valoraciones** y reseñas

### Carrito de Compras
- **Persistencia** entre sesiones
- **Gestión de variantes** (color/talla)
- **Cálculo automático** de totales
- **Validación de stock** en tiempo real
- **Sistema de favoritos**

### Proceso de Compra
- **Múltiples métodos de pago**:
  - Transferencia bancaria
  - Pago contra entrega (solo Medellín)
- **Cálculo de envíos** automático
- **Generación de facturas** PDF
- **Seguimiento de pedidos** en tiempo real

## 💳 Sistema de Pagos

### Métodos Disponibles
1. **Transferencia Bancaria**
   - Validación de comprobantes
   - Subida de archivos segura
   - Verificación manual

2. **Pago Contra Entrega**
   - Solo disponible para Medellín
   - Validación de dirección
   - Gestión de entregas

### Características
- **Generación automática** de facturas PDF
- **Códigos QR** para validación DIAN
- **Seguimiento de transacciones**
- **Notificaciones por email**

## 👥 Panel Administrativo

### Dashboard Principal
- **Estadísticas en tiempo real**
- **Gráficos de ventas** mensuales
- **Productos más vendidos**
- **Alertas de bajo stock**
- **Actividad reciente**

### Gestión de Productos
- **CRUD completo** de productos
- **Gestión de variantes** (color/talla)
- **Subida múltiple** de imágenes
- **Control de stock** por variante
- **Sistema de categorías** jerárquico

### Gestión de Inventario
- **Control de stock** en tiempo real
- **Alertas automáticas** de bajo stock
- **Transferencias** entre variantes
- **Historial de movimientos**
- **Reportes de inventario**

### Gestión de Órdenes
- **Listado completo** de pedidos
- **Filtros avanzados** por estado, fecha, cliente
- **Cambio de estados** de pedidos
- **Gestión de pagos**
- **Exportación** de datos

### Sistema de Descuentos
- **Códigos promocionales** personalizables
- **Descuentos por volumen** automáticos
- **Reglas de descuento** flexibles
- **Seguimiento de uso**

## 🎨 Interfaz de Usuario

### Diseño Responsivo
- **Mobile-first** approach
- **Breakpoints** optimizados
- **Navegación intuitiva**
- **Accesibilidad** mejorada

### Componentes UI
- **Sistema de colores** consistente
- **Tipografía** legible y moderna
- **Iconografía** con Font Awesome
- **Animaciones** suaves y profesionales
- **Modales** y notificaciones

### Páginas Principales
- **Homepage** con hero banner y productos destacados
- **Catálogo** con filtros y paginación
- **Detalle de producto** con galería e información completa
- **Carrito** con gestión de cantidades
- **Checkout** con formularios optimizados
- **Panel de usuario** con gestión de pedidos

## 📧 Sistema de Notificaciones

### Email Marketing
- **Bienvenida** automática para nuevos usuarios
- **Confirmación** de pedidos
- **Notificaciones** de cambio de estado
- **Newsletter** con ofertas especiales

### Notificaciones en Tiempo Real
- **Alertas** de stock bajo
- **Confirmaciones** de acciones
- **Mensajes** de error y éxito

## 🔧 Configuración e Instalación

### Requisitos del Sistema
- PHP 8.0 o superior
- MySQL 8.0 o superior
- Apache/Nginx
- Extensiones PHP: PDO, GD, cURL, OpenSSL
- Composer (para dependencias)

### Instalación
1. **Clonar el repositorio**
2. **Configurar base de datos** con `basededatos.sql`
3. **Instalar dependencias** con `composer install`
4. **Configurar variables** en `config.php`
5. **Configurar permisos** de directorios
6. **Configurar servidor web**

### Variables de Configuración
```php
// config.php
define('BASE_URL', 'http://localhost/angelow');
define('DEBUG_MODE', true);
// Configuración de base de datos
// Configuración de email
// Configuración de Google OAuth
```

## 🚀 Características Avanzadas

### Optimización de Rendimiento
- **Cache** de consultas frecuentes
- **Lazy loading** de imágenes
- **Compresión** de assets
- **Minificación** de CSS/JS

### SEO y Marketing
- **URLs amigables** con slugs
- **Meta tags** dinámicos
- **Sitemap** automático
- **Google Analytics** integrado

### Seguridad
- **Validación** de todos los inputs
- **Sanitización** de datos
- **Protección** contra SQL injection
- **Headers** de seguridad
- **Logs** de auditoría

## 📊 Reportes y Analytics

### Métricas Disponibles
- **Ventas** por período
- **Productos** más vendidos
- **Clientes** activos
- **Conversión** de carrito
- **Rendimiento** de descuentos

### Exportación de Datos
- **Excel/CSV** de productos
- **PDF** de reportes
- **Facturas** automáticas
- **Backup** de base de datos

## 🔄 Mantenimiento y Actualizaciones

### Tareas Automáticas
- **Limpieza** de carritos abandonados
- **Backup** de base de datos
- **Logs** de errores
- **Optimización** de tablas

### Monitoreo
- **Logs** de aplicación
- **Métricas** de rendimiento
- **Alertas** de errores
- **Uptime** del sistema

## 📱 Responsive Design

### Breakpoints
- **Mobile**: 320px - 768px
- **Tablet**: 768px - 1024px
- **Desktop**: 1024px+

### Características Mobile
- **Navegación** hamburguesa
- **Touch** optimizado
- **Carga** rápida
- **UX** simplificada

## 🌐 Internacionalización

### Preparado para Múltiples Idiomas
- **Estructura** modular
- **Archivos** de traducción
- **Formato** de fechas localizado
- **Moneda** configurable

## 🔮 Roadmap Futuro

### Próximas Características
- **App móvil** nativa
- **Integración** con más pasarelas de pago
- **Sistema de afiliados**
- **Chat** en vivo
- **AR** para probar ropa
- **IA** para recomendaciones

## 📞 Soporte y Contacto

### Información de Contacto
- **Email**: anelhiguita@hotmail.com
- **Teléfono**: +57 313 595 1664
- **Dirección**: Calle 120 # 49 B 24, Medellín

### Documentación Adicional
- **API Documentation** (en desarrollo)
- **Guía de usuario** (en desarrollo)
- **Manual técnico** (en desarrollo)

---

## 📄 Licencia

Este proyecto es propiedad de Angelow Ropa Infantil. Todos los derechos reservados.

---

*Última actualización: Enero 2025*