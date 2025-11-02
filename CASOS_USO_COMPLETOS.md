# Casos de Uso - Sistema Angelow

## Descripción General
Este documento describe los casos de uso completos del sistema Angelow, una plataforma E-commerce especializada en ropa infantil con funcionalidades avanzadas de gestión administrativa y sistema de delivery con GPS.

## Actores del Sistema

### Actores Principales
- **Cliente (Usuario Registrado)**: Usuario con cuenta que puede realizar compras completas
- **Usuario Anónimo (Navegador)**: Visitante no registrado que puede explorar la tienda
- **Administrador**: Usuario con permisos completos para gestionar el sistema
- **Repartidor/Delivery**: Usuario responsable de las entregas con funcionalidades GPS

### Actores Secundarios/Externos
- **Sistema de Pago**: Maneja transferencias bancarias con validación manual
- **OpenStreetMap**: Proporciona servicios de geolocalización y mapas
- **Sistema de Correo**: Gestiona envío de notificaciones y confirmaciones

## Módulos y Casos de Uso

### 1. Módulo de Autenticación y Usuarios

#### UC-01: Registrar Nueva Cuenta
**Actor**: Usuario Anónimo
**Descripción**: Permite a un visitante crear una cuenta nueva en el sistema
**Precondiciones**: Usuario no debe estar registrado
**Postcondiciones**: Cuenta creada, usuario autenticado automáticamente
**Flujo Principal**:
1. Usuario ingresa datos personales (nombre, email, contraseña)
2. Sistema valida formato de datos
3. Sistema verifica que email no esté registrado
4. Sistema crea cuenta y envía email de confirmación
5. Usuario es redirigido al dashboard

#### UC-02: Iniciar Sesión
**Actor**: Usuario Anónimo, Cliente, Administrador, Delivery
**Descripción**: Autenticación de usuarios existentes
**Precondiciones**: Usuario debe tener cuenta registrada
**Postcondiciones**: Usuario autenticado y redirigido según rol
**Flujo Principal**:
1. Usuario ingresa email y contraseña
2. Sistema valida credenciales
3. Sistema identifica rol del usuario
4. Usuario es redirigido a dashboard correspondiente

#### UC-03: Cerrar Sesión
**Actor**: Cliente, Administrador, Delivery
**Descripción**: Finalizar sesión activa
**Precondiciones**: Usuario debe estar autenticado
**Postcondiciones**: Sesión destruida, redirigido a página principal

#### UC-04: Gestionar Perfil Personal
**Actor**: Cliente
**Descripción**: Actualizar información personal del usuario
**Precondiciones**: Usuario autenticado
**Flujo Principal**:
1. Usuario accede a configuración de perfil
2. Usuario modifica datos (nombre, teléfono, etc.)
3. Sistema valida y guarda cambios

#### UC-05: Gestionar Direcciones de Envío
**Actor**: Cliente
**Descripción**: Administrar direcciones para entregas
**Precondiciones**: Usuario autenticado
**Flujo Principal**:
1. Usuario agrega/edita/elimina direcciones
2. Puede marcar dirección como predeterminada
3. Sistema valida formato de direcciones

#### UC-06: Cambiar Contraseña
**Actor**: Cliente, Administrador, Delivery
**Descripción**: Actualizar contraseña de acceso
**Precondiciones**: Usuario autenticado
**Flujo Principal**:
1. Usuario solicita cambio de contraseña
2. Sistema envía código de verificación por email
3. Usuario ingresa nueva contraseña
4. Sistema valida y actualiza

### 2. Módulo de Tienda y Catálogo

#### UC-07: Explorar Página Principal
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Visualizar contenido principal de la tienda
**Flujo Principal**:
1. Usuario accede a index.php
2. Sistema muestra sliders, categorías destacadas, productos destacados
3. Usuario puede navegar a diferentes secciones

#### UC-08: Navegar por Categorías
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Explorar productos por categorías
**Flujo Principal**:
1. Usuario selecciona categoría
2. Sistema filtra y muestra productos de la categoría
3. Usuario puede aplicar filtros adicionales

#### UC-09: Buscar Productos
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Búsqueda de productos por texto
**Flujo Principal**:
1. Usuario ingresa términos de búsqueda
2. Sistema busca en nombres, descripciones, categorías
3. Muestra resultados con paginación

#### UC-10: Filtrar Productos
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Aplicar filtros avanzados a listados
**Flujo Principal**:
1. Usuario selecciona filtros (precio, talla, género, etc.)
2. Sistema aplica filtros a resultados
3. Muestra productos filtrados

#### UC-11: Ver Detalles de Producto
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Visualizar información completa de un producto
**Flujo Principal**:
1. Usuario selecciona producto
2. Sistema muestra imágenes, descripción, precio, variantes
3. Usuario puede agregar a carrito o favoritos

#### UC-12: Ver Variantes de Producto
**Actor**: Usuario Anónimo, Cliente
**Descripción**: Explorar diferentes opciones de un producto
**Flujo Principal**:
1. Usuario selecciona variante (talla, color)
2. Sistema actualiza precio e imágenes
3. Muestra disponibilidad por variante

### 3. Módulo de Carrito y Compra

#### UC-13: Agregar Producto al Carrito
**Actor**: Cliente
**Descripción**: Incorporar productos al carrito de compras
**Precondiciones**: Usuario autenticado
**Flujo Principal**:
1. Usuario selecciona variante y cantidad
2. Sistema verifica stock disponible
3. Producto se agrega al carrito en base de datos

#### UC-14: Gestionar Carrito de Compras
**Actor**: Cliente
**Descripción**: Administrar items en el carrito
**Flujo Principal**:
1. Usuario puede modificar cantidades
2. Usuario puede eliminar items
3. Sistema recalcula totales automáticamente

#### UC-15: Aplicar Código de Descuento
**Actor**: Cliente
**Descripción**: Usar códigos promocionales
**Flujo Principal**:
1. Usuario ingresa código de descuento
2. Sistema valida código y aplica descuento
3. Recalcula totales

#### UC-16: Calcular Costos de Envío
**Actor**: Cliente
**Descripción**: Determinar costo de envío según dirección
**Flujo Principal**:
1. Usuario selecciona dirección de envío
2. Sistema calcula costo basado en reglas configuradas
3. Muestra costo total con envío

#### UC-17: Proceder al Checkout
**Actor**: Cliente
**Descripción**: Iniciar proceso de compra
**Precondiciones**: Carrito con items
**Flujo Principal**:
1. Sistema recopila datos de envío y facturación
2. Usuario confirma dirección y método de pago
3. Sistema prepara datos para procesamiento

#### UC-18: Seleccionar Método de Pago
**Actor**: Cliente
**Descripción**: Elegir forma de pago (transferencia bancaria)
**Flujo Principal**:
1. Usuario selecciona transferencia bancaria como único método
2. Sistema muestra instrucciones de pago

#### UC-19: Confirmar Pedido
**Actor**: Cliente
**Descripción**: Finalizar compra
**Flujo Principal**:
1. Usuario revisa resumen final
2. Sistema crea orden en base de datos
3. Usuario recibe confirmación

### 4. Módulo de Pagos

#### UC-20: Procesar Transferencia Bancaria
**Actor**: Cliente, Sistema de Pago
**Descripción**: Gestionar pagos por transferencia
**Flujo Principal**:
1. Usuario selecciona transferencia bancaria
2. Sistema muestra datos bancarios
3. Usuario realiza transferencia externa

#### UC-21: Subir Comprobante de Pago
**Actor**: Cliente
**Descripción**: Adjuntar comprobante de transferencia
**Flujo Principal**:
1. Usuario sube imagen del comprobante
2. Sistema valida formato y tamaño
3. Almacena comprobante para validación

#### UC-22: Validar Pago
**Actor**: Administrador, Sistema de Pago
**Descripción**: Verificar pago recibido
**Flujo Principal**:
1. Administrador revisa comprobante
2. Confirma recepción del pago
3. Actualiza estado de la orden

#### UC-23: Generar Recibo/Factura
**Actor**: Sistema de Pago
**Descripción**: Crear documento fiscal
**Flujo Principal**:
1. Sistema genera PDF con datos de la transacción
2. Incluye detalles de productos, totales, datos fiscales

### 5. Módulo de Gestión de Pedidos

#### UC-25: Ver Historial de Pedidos
**Actor**: Cliente
**Descripción**: Revisar compras anteriores
**Flujo Principal**:
1. Sistema muestra lista de órdenes del usuario
2. Ordenadas por fecha descendente
3. Incluye estados y totales

#### UC-26: Ver Detalle de Pedido
**Actor**: Cliente
**Descripción**: Información completa de una orden
**Flujo Principal**:
1. Usuario selecciona orden específica
2. Sistema muestra productos, direcciones, estado de pago/envío

#### UC-27: Rastrear Pedido en Tiempo Real
**Actor**: Cliente
**Descripción**: Seguimiento GPS del delivery
**Flujo Principal**:
1. Usuario accede a tracking de orden
2. Sistema muestra ubicación actual del repartidor
3. Actualización en tiempo real

#### UC-28: Cancelar Pedido
**Actor**: Cliente
**Descripción**: Anular orden pendiente
**Precondiciones**: Orden en estado pendiente
**Flujo Principal**:
1. Usuario solicita cancelación
2. Sistema verifica condiciones de cancelación
3. Actualiza estado y notifica

#### UC-29: Reordenar Productos
**Actor**: Cliente
**Descripción**: Repetir compra anterior
**Flujo Principal**:
1. Usuario selecciona "reordenar" en pedido anterior
2. Sistema agrega productos al carrito actual
3. Usuario puede modificar y proceder al checkout

### 6. Módulo Administrativo

#### UC-30: Gestionar Productos (CRUD)
**Actor**: Administrador
**Descripción**: Crear, leer, actualizar, eliminar productos
**Flujo Principal**:
1. Administrador accede al panel de productos
2. Puede crear nuevos productos con variantes
3. Editar información existente
4. Activar/desactivar productos

#### UC-31: Gestionar Categorías
**Actor**: Administrador
**Descripción**: Administrar estructura de categorías
**Flujo Principal**:
1. Crear categorías padre/hijo
2. Asignar productos a categorías
3. Gestionar imágenes de categorías

#### UC-32: Gestionar Colecciones
**Actor**: Administrador
**Descripción**: Crear agrupaciones temáticas de productos
**Flujo Principal**:
1. Definir colección con nombre, descripción, fechas
2. Asignar productos a colecciones
3. Gestionar banners de colecciones

#### UC-33: Gestionar Tallas y Variantes
**Actor**: Administrador
**Descripción**: Administrar opciones de productos
**Flujo Principal**:
1. Crear tallas por género/categoría
2. Gestionar colores disponibles
3. Controlar stock por variante

#### UC-34: Controlar Inventario
**Actor**: Administrador
**Descripción**: Gestionar niveles de stock
**Flujo Principal**:
1. Ver niveles actuales de inventario
2. Recibir alertas de stock bajo
3. Actualizar cantidades manualmente

#### UC-35: Gestionar Órdenes de Compra
**Actor**: Administrador
**Descripción**: Administrar pedidos de clientes
**Flujo Principal**:
1. Ver todas las órdenes del sistema
2. Cambiar estados de órdenes
3. Gestionar problemas de pedidos

#### UC-36: Asignar Órdenes a Delivery
**Actor**: Administrador
**Descripción**: Asignar entregas a repartidores
**Flujo Principal**:
1. Seleccionar orden pendiente de entrega
2. Asignar a repartidor disponible
3. Notificar al repartidor

#### UC-37: Gestionar Usuarios/Clientes
**Actor**: Administrador
**Descripción**: Administrar cuentas de usuario
**Flujo Principal**:
1. Ver lista de usuarios registrados
2. Gestionar roles y permisos
3. Activar/desactivar cuentas

#### UC-38: Gestionar Sliders/Banners
**Actor**: Administrador
**Descripción**: Controlar contenido promocional
**Flujo Principal**:
1. Crear/editar sliders principales
2. Gestionar imágenes y enlaces
3. Controlar orden y visibilidad

#### UC-39: Gestionar Descuentos y Promociones
**Actor**: Administrador
**Descripción**: Crear códigos de descuento
**Flujo Principal**:
1. Generar códigos promocionales
2. Configurar porcentajes o montos fijos
3. Establecer fechas de validez

#### UC-40: Gestionar Envíos y Costos
**Actor**: Administrador
**Descripción**: Configurar reglas de envío
**Flujo Principal**:
1. Definir costos por rangos de precio
2. Configurar zonas de entrega
3. Establecer costos especiales

#### UC-41: Generar Reportes de Ventas
**Actor**: Administrador
**Descripción**: Análisis de ventas por períodos
**Flujo Principal**:
1. Seleccionar rango de fechas
2. Generar reportes de ingresos
3. Exportar a PDF/Excel

#### UC-42: Generar Reportes de Productos
**Actor**: Administrador
**Descripción**: Análisis de rendimiento de productos
**Flujo Principal**:
1. Ver productos más vendidos
2. Análisis de inventario
3. Tendencias de ventas

#### UC-43: Generar Reportes de Clientes
**Actor**: Administrador
**Descripción**: Análisis de comportamiento de usuarios
**Flujo Principal**:
1. Clientes más activos
2. Valor promedio de pedidos
3. Segmentación por ubicación

#### UC-44: Gestionar Noticias/Contenido
**Actor**: Administrador
**Descripción**: Administrar contenido del blog/noticias
**Flujo Principal**:
1. Crear/editar artículos
2. Gestionar categorías de contenido
3. Publicar/ocultar contenido

### 7. Módulo de Delivery

#### UC-45: Ver Órdenes Asignadas
**Actor**: Delivery
**Descripción**: Revisar entregas pendientes
**Flujo Principal**:
1. Sistema muestra órdenes asignadas al repartidor
2. Información de cliente, dirección, productos
3. Estados de cada orden

#### UC-46: Aceptar Orden de Entrega
**Actor**: Delivery
**Descripción**: Confirmar aceptación de entrega
**Flujo Principal**:
1. Repartidor acepta orden asignada
2. Sistema actualiza estado
3. Prepara navegación GPS

#### UC-47: Iniciar Navegación GPS
**Actor**: Delivery
**Descripción**: Comenzar ruta de entrega
**Flujo Principal**:
1. Sistema calcula ruta óptima
2. Abre mapa interactivo
3. Inicia seguimiento GPS

#### UC-48: Actualizar Ubicación en Tiempo Real
**Actor**: Delivery
**Descripción**: Enviar coordenadas GPS
**Flujo Principal**:
1. App móvil envía ubicación cada cierto tiempo
2. Sistema actualiza posición en base de datos
3. Cliente puede ver ubicación en tiempo real

#### UC-49: Cambiar Estado de Entrega
**Actor**: Delivery
**Descripción**: Actualizar progreso de entrega
**Flujo Principal**:
1. Repartidor cambia estado (en camino, llegado, entregado)
2. Sistema notifica al cliente
3. Actualiza historial de estados

#### UC-50: Confirmar Entrega Exitosa
**Actor**: Delivery
**Descripción**: Finalizar proceso de entrega
**Flujo Principal**:
1. Repartidor confirma entrega física
2. Sistema registra firma digital si aplica
3. Actualiza estado final

#### UC-51: Reportar Problema de Entrega
**Actor**: Delivery
**Descripción**: Notificar incidencias
**Flujo Principal**:
1. Repartidor describe el problema
2. Sistema registra incidencia
3. Notifica a administrador

#### UC-52: Contactar al Cliente
**Actor**: Delivery
**Descripción**: Comunicación con destinatario
**Flujo Principal**:
1. Repartidor inicia llamada telefónica
2. Sistema registra comunicación
3. Actualiza notas de entrega

## Relaciones entre Casos de Uso

### Dependencias Principales
- **Registro → Inicio de Sesión**: Usuario debe tener cuenta para autenticarse
- **Explorar Productos → Agregar al Carrito**: Requiere autenticación
- **Carrito → Checkout**: Proceso secuencial de compra
- **Checkout → Procesar Pago**: Diferentes métodos según selección
- **Pago → Confirmación**: Validación antes de finalizar
- **Confirmación → Historial**: Pedido registrado para seguimiento

### Relaciones Administrativas
- **Gestión de Productos → Inventario**: Productos afectan control de stock
- **Órdenes → Asignación Delivery**: Pedidos requieren repartidores
- **Reportes → Toma de Decisiones**: Análisis para mejoras

### Relaciones de Delivery
- **Órdenes Asignadas → Navegación GPS**: Ruta calculada para entrega
- **Navegación → Actualización Estado**: Seguimiento en tiempo real
- **Estado → Notificaciones**: Cliente informado del progreso

## Métricas de Calidad

- **Total de Casos de Uso**: 51 casos de uso identificados
- **Actores Principales**: 4 (Cliente, Admin, Delivery, Anónimo)
- **Actores Secundarios**: 3 (Sistemas externos)
- **Módulos**: 7 módulos principales
- **Cobertura Funcional**: 100% de funcionalidades implementadas documentadas

---

*Este documento de casos de uso refleja la implementación completa del sistema Angelow, con énfasis en las funcionalidades reales desarrolladas y su interacción entre actores y módulos.*