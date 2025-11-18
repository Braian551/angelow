# Mapa de Procesos - Sistema Angelow

## Descripción General
Este documento describe el mapa completo de procesos del sistema Angelow, una plataforma E-commerce especializada en ropa infantil con funcionalidades avanzadas de gestión administrativa y servicios de envío/entregas mediante aliados externos.

## Arquitectura de Procesos

### 1. Proceso de Exploración y Registro
**Propósito:** Permitir a los usuarios explorar la tienda y registrarse si es necesario.

#### Flujo Principal:
1. **Acceso a Tienda**: Usuario llega a la página principal
2. **Exploración**: Navega por sliders, categorías, productos destacados
3. **Búsqueda**: Utiliza filtros y búsqueda de productos
4. **Decisión de Registro**:
   - **Usuario Registrado**: Acceso completo a todas las funciones
   - **Usuario Anónimo**: Funcionalidades limitadas, se ofrece registro

#### Subprocesos:
- **Registro de Usuario**: Validación de datos, verificación de email único
- **Autenticación**: Login con credenciales, redirección por roles
- **Recuperación de Contraseña**: Envío de códigos de verificación

### 2. Proceso de Compra
**Propósito:** Gestionar el flujo completo desde selección hasta pago.

#### Flujo Principal:
1. **Selección de Producto**: Exploración de catálogo y detalles
2. **Gestión del Carrito**:
   - Agregar productos con variantes (talla, color, cantidad)
   - Modificar cantidades, eliminar items
   - Aplicar códigos de descuento
   - Calcular costos de envío automáticos

3. **Pago**: Proceso de finalización de compra
   - Verificación de autenticación
   - Selección de dirección de envío
   - Elección de método de pago

#### Validaciones Críticas:
- **Stock Disponible**: Verificación antes de agregar al carrito
- **Autenticación**: Requerida para proceder al pago
- **Dirección Válida**: Verificación de datos de envío

### 3. Proceso de Pago
**Propósito:** Gestionar transacciones financieras de forma segura.

#### Métodos de Pago:
**Transferencia Bancaria** (único método implementado):
- Muestra datos bancarios del sistema
- Usuario realiza transferencia externa
- Sube comprobante de pago (imagen/PDF)
- Sistema almacena comprobante para validación manual por administrador

#### Validaciones:
- **Comprobante Válido**: Verificación manual por administrador
- **Monto Correcto**: Comparación con total de orden
- **Fecha Reciente**: Validación de fecha de transferencia

### 4. Proceso de Gestión de Pedidos
**Propósito:** Administrar el ciclo de vida completo de las órdenes.

#### Estados de Orden:
1. **Pendiente**: Orden creada, esperando validación
2. **Pagado**: Pago validado por transferencia bancaria
3. **Preparando**: Productos siendo empacados
4. **Listo para Envío**: Preparación completada
5. **Enviando**: Asignado a repartidor
6. **En Camino**: Repartidor en ruta
7. **En Destino**: Repartidor llegó a dirección
8. **Entregado**: Proceso completado exitosamente

#### Subprocesos Paralelos:
- **Validación de Pago**: Proceso manual para transferencias
- **Preparación de Productos**: Verificación de stock y empaque
- **Asignación de Envíos**: Selección y notificación de método/aliado

#### Reglas de Negocio:
- **Cancelación**: Permitida solo en estados iniciales
- **Reembolso**: Según política por método de pago
- **Tiempo Límite**: 48 horas para validación de pagos

### 5. Proceso de Envíos / Delivery (externo)
**Propósito:** Gestionar entregas con seguimiento GPS en tiempo real.

#### Flujo de Entrega:
1. **Asignación**: Administrador asigna orden a repartidor disponible
2. **Aceptación**: Repartidor confirma aceptación de la orden
3. **Navegación GPS**:
   - Cálculo automático de ruta óptima
   - Mapa interactivo con instrucciones
   - Seguimiento en tiempo real (cada 30 segundos)
   - Instrucciones de voz (opcional)

4. **Ejecución**:
   - Actualización de estados en tiempo real
   - Comunicación con cliente (llamadas)
   - Confirmación de entrega

#### Funcionalidades GPS:
- **Rutas Óptimas**: Cálculo usando OpenStreetMap
- **Tracking en Tiempo Real**: Cliente puede ver ubicación del repartidor
- **Estimación de Llegada**: Actualización automática de ETA
- **Historial de Ruta**: Registro completo del trayecto

### 6. Procesos Administrativos
**Propósito:** Gestión completa del sistema E-commerce.

#### Gestión de Catálogo:
- **Productos**: CRUD completo con variantes e imágenes
- **Categorías**: Estructura jerárquica
- **Colecciones**: Agrupaciones temáticas por temporada
- **Tallas**: Sistema configurable por género/categoría

#### Gestión Operativa:
- **Inventario**: Control de stock con alertas automáticas
- **Pedidos**: Supervisión de todas las órdenes
- **Usuarios**: Gestión de cuentas y roles
- **Envíos**: Gestión y seguimiento por servicios externos

#### Gestión de Contenido:
- **Sliders**: Banners promocionales de la página principal
- **Descuentos**: Códigos promocionales y reglas
- **Envíos**: Configuración de costos por zona/distancia
- **Noticias**: Contenido del blog y comunicaciones

#### Reportes y Análisis:
- **Ventas**: Ingresos por período, productos más vendidos
- **Clientes**: Comportamiento, frecuencia de compra, valor promedio
- **Productos**: Rendimiento por categoría, rotación de inventario
- **Envíos**: Eficiencia del partner y tiempos promedio

### 7. Procesos Post-Entrega
**Propósito:** Gestionar interacciones posteriores a la entrega.

#### Funcionalidades:
- **Calificaciones**: Sistema de estrellas para productos y servicio
- **Reseñas**: Comentarios detallados de clientes
- **Reordenar**: Funcionalidad para repetir compras anteriores
- **Historial Completo**: Registro detallado de toda la transacción

#### Beneficios:
- **Mejora Continua**: Feedback para optimizar procesos
- **Lealtad**: Sistema de recompensas basado en reseñas
- **Análisis**: Datos para mejorar productos y servicios

## Tiempos y Métricas de Procesos

### Tiempos Estimados:
- **Exploración**: Inmediato (páginas web)
- **Registro**: 2-3 minutos
- **Compra**: 5-15 minutos
- **Validación de Pago**: 1-2 días hábiles
- **Preparación**: 2-4 horas
- **Entrega**: 1-3 horas (dependiendo zona)

### Métricas de Rendimiento:
- **Tasa de Conversión**: Porcentaje de visitantes que compran
- **Tiempo Promedio de Compra**: Desde entrada hasta pago
- **Tasa de Abandono de Carrito**: Carritos no completados
- **Tiempo de Entrega**: Desde pedido hasta entrega
- **Satisfacción del Cliente**: Basado en calificaciones

## Puntos de Decisión Críticos

### Decisiones del Sistema:
1. **¿Usuario Registrado?**: Determina nivel de acceso
2. **¿Stock Disponible?**: Bloquea compras si no hay inventario
3. **¿Pago Válido?**: Determina si orden puede procesarse
4. **¿Envío Disponible?**: Afecta tiempos de entrega

### Decisiones del Usuario:
1. **¿Registrarse o Continuar como Invitado?**: Afecta funcionalidades disponibles
2. **¿Subir Comprobante?**: Adjuntar comprobante de transferencia bancaria
3. **¿Aplicar Descuento?**: Código promocional opcional

### Decisiones Administrativas:
1. **¿Aprobar Pago?**: Validación manual de comprobantes
2. **¿Asignar Envío?**: Selección de método/aliado disponible
3. **¿Cancelar Orden?**: En casos de problemas

## Integración de Procesos

### Procesos Secuenciales:
```
Exploración → Compra → Pago → Gestión de Pedidos → Envíos → Post-Entrega
```

### Procesos Paralelos:
- **Validación de Pago** y **Preparación** ocurren simultáneamente
- **Gestión Administrativa** es continua y no bloqueante
- **Tracking GPS** y **Actualización de Estados** son paralelos

### Procesos Independientes:
- **Gestión de Catálogo**: Puede ocurrir en cualquier momento
- **Reportes**: Generación bajo demanda
- **Mantenimiento**: Tareas de sistema

## Manejo de Excepciones

### Errores Comunes:
- **Pago Rechazado**: Reintentos o cambio de método
- **Stock Insuficiente**: Cancelación automática o backorder
- **Dirección Inválida**: Corrección por cliente
- **Envío No Disponible**: Reasignación automática o replanificación

### Recuperación:
- **Punto de Control**: Estados guardados permiten reanudar procesos
- **Notificaciones**: Sistema informa de problemas y soluciones
- **Soporte**: Canal de comunicación cliente-administrador

## Escalabilidad y Optimización

### Optimizaciones Implementadas:
- **Caché de Productos**: Reduce consultas a base de datos
- **Procesamiento Asíncrono**: Validación de pagos en background
- **Compresión de Imágenes**: Optimización de carga de páginas
- **Lazy Loading**: Carga progresiva de contenido

### Preparación para Crecimiento:
- **Arquitectura Modular**: Fácil adición de nuevos procesos
- **APIs RESTful**: Integración con sistemas externos
- **Base de Datos Optimizada**: Índices y consultas eficientes
- **Sistema de Roles**: Escalabilidad de permisos

---

*Este mapa de procesos refleja la implementación completa del sistema Angelow, con énfasis en los flujos reales de negocio y las integraciones entre módulos.*