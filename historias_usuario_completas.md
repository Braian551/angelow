# Historias de Usuario - Sistema Angelow

## Módulo de Gestión de Usuarios

**HU-01**
Como cliente nuevo
Quiero registrarme en el sistema con email, contraseña y datos básicos
Para poder acceder a todas las funcionalidades de compra

**HU-02**
Como usuario registrado
Quiero iniciar sesión con mis credenciales
Para acceder a mi cuenta personal

**HU-03**
Como usuario autenticado
Quiero editar mi perfil (datos personales, dirección, preferencias)
Para mantener mi información actualizada

**HU-04**
Como usuario
Quiero recuperar mi contraseña mediante email
Para restablecer el acceso si lo olvido

## Módulo de Catálogo y Búsqueda

**HU-05**
Como cliente
Quiero buscar productos por categorías, colecciones o términos
Para encontrar rápidamente lo que necesito

**HU-06**
Como cliente
Quiero ver detalles completos de productos (imágenes, variantes, descripción)
Para tomar decisiones informadas de compra

**HU-07**
Como cliente
Quiero filtrar productos por talla, color, precio y otras características
Para refinar mis resultados de búsqueda

## Módulo de Carrito y Compras

**HU-08**
Como cliente
Quiero agregar productos al carrito con sus variantes
Para preparar mi compra

**HU-09**
Como cliente
Quiero modificar cantidades o eliminar items del carrito
Para ajustar mi pedido antes de pagar

**HU-10**
Como cliente
Quiero aplicar códigos de descuento a mi compra
Para obtener beneficios promocionales

**HU-11**
Como cliente
Quiero proceder al pago mediante transferencia bancaria
Para completar mi transacción

## Módulo de Pedidos y Logística

**HU-12**
Como cliente
Quiero ver el cálculo automático de costos de envío
Para conocer el total de mi pedido

**HU-13**
Como cliente
Quiero seleccionar dirección de envío entre mis guardadas
Para recibir mis productos donde prefiera

**HU-14**
Como sistema
Quiero generar factura electrónica con datos DIAN
Para documentar legalmente la transacción

**HU-15**
Como cliente
Quiero ver el estado de mis pedidos (procesando, enviado, entregado)
Para conocer el avance de mi compra

## Módulo de Delivery

**HU-16**
Como administrador
Quiero asignar pedidos aprobados a repartidores disponibles
Para iniciar el proceso de entrega

**HU-17**
Como repartidor
Quiero ver mi lista de entregas asignadas con detalles
Para planificar mi ruta

**HU-18**
Como repartidor
Quiero actualizar el estado de las entregas (en camino, entregado)
Para mantener informados a clientes y administración

**HU-19**
Como cliente
Quiero recibir notificaciones cuando mi pedido esté en camino
Para preparar la recepción

## Módulo Administrativo

**HU-20**
Como administrador
Quiero gestionar productos (crear, editar, desactivar)
Para mantener actualizado el catálogo

**HU-21**
Como administrador
Quiero administrar inventario y variantes (entrada, salida, devoluciones)
Para controlar el stock disponible

**HU-22**
Como administrador
Quiero procesar órdenes (confirmar pagos, actualizar estados)
Para gestionar el flujo de ventas

**HU-23**
Como administrador
Quiero generar reportes de ventas, productos más vendidos y clientes
Para analizar el desempeño del negocio

**HU-24**
Como administrador
Quiero gestionar códigos de descuento y promociones
Para implementar estrategias comerciales

---

# Criterios de Aceptación

## Módulo de Gestión de Usuarios

**HU-01: Registro de nuevo cliente**

1. Dado que soy un cliente nuevo,
   Cuando completo el formulario con email válido, contraseña segura y datos básicos,
   Entonces recibo un email de confirmación y mi cuenta queda creada.

2. Dado que intento registrarme con un email ya existente,
   Cuando envío el formulario,
   Entonces el sistema muestra un mensaje indicando que el email ya está registrado.

**HU-02: Inicio de sesión**

1. Dado que soy un usuario registrado,
   Cuando ingreso mis credenciales correctas,
   Entonces accedo a mi cuenta personal.

2. Dado que ingreso credenciales incorrectas,
   Cuando intento iniciar sesión,
   Entonces el sistema muestra un mensaje de error y no me permite acceder.

**HU-03: Edición de perfil**

1. Dado que estoy autenticado,
   Cuando actualizo mis datos personales y guardo los cambios,
   Entonces el sistema almacena la nueva información y muestra un mensaje de confirmación.

**HU-04: Recuperación de contraseña**

1. Dado que olvidé mi contraseña,
   Cuando ingreso mi email en el formulario de recuperación,
   Entonces recibo un email con un enlace para restablecer mi contraseña.

## Módulo de Catálogo y Búsqueda

**HU-05: Búsqueda de productos**

1. Dado que estoy en la página de búsqueda,
   Cuando ingreso un término válido y presiono buscar,
   Entonces el sistema muestra productos relevantes.

2. Dado que no hay productos coincidentes,
   Cuando realizo una búsqueda,
   Entonces el sistema muestra un mensaje indicando que no hay resultados.

**HU-06: Detalles de producto**

1. Dado que selecciono un producto,
   Cuando accedo a su página de detalles,
   Entonces veo imágenes, descripción, variantes y precio.

**HU-07: Filtrado de productos**

1. Dado que tengo resultados de búsqueda,
   Cuando aplico filtros por talla, color o precio,
   Entonces el sistema muestra solo los productos que cumplen los criterios.

## Módulo de Carrito y Compras

**HU-08: Agregar al carrito**

1. Dado que selecciono un producto con sus variantes,
   Cuando lo agrego al carrito,
   Entonces aparece en mi carrito con la cantidad y variantes correctas.

**HU-09: Modificar carrito**

1. Dado que tengo items en el carrito,
   Cuando cambio cantidades o elimino productos,
   Entonces el carrito se actualiza reflejando estos cambios.

**HU-10: Códigos de descuento**

1. Dado que tengo un código de descuento válido,
   Cuando lo aplico a mi compra,
   Entonces el total se actualiza reflejando el descuento.

**HU-11: Método de pago**

1. Dado que procedo al pago,
   Cuando selecciono transferencia bancaria como método de pago,
   Entonces el sistema muestra los datos bancarios y permite subir comprobante.

## Módulo de Pedidos y Logística

**HU-12: Cálculo de envío**

1. Dado que tengo productos en el carrito,
   Cuando ingreso mi dirección,
   Entonces el sistema muestra los costos de envío calculados.

**HU-13: Dirección de envío**

1. Dado que tengo direcciones guardadas,
   Cuando selecciono una para el envío,
   Entonces el sistema la utiliza para calcular costos y programar la entrega.

**HU-14: Factura electrónica**

1. Dado que completo una compra,
   Cuando el pago es aprobado,
   Entonces el sistema genera una factura electrónica válida según normativa DIAN.

**HU-15: Estado de pedidos**

1. Dado que realicé una compra,
   Cuando consulto el estado de mi pedido,
   Entonces veo su estado actual (procesando, enviado, entregado).

## Módulo de Delivery

**HU-16: Asignación a repartidores**

1. Dado que un pedido está aprobado,
   Cuando el administrador lo asigna a un repartidor disponible,
   Entonces el sistema registra la asignación y notifica al repartidor.

**HU-17: Lista de entregas**

1. Dado que soy un repartidor autenticado,
   Cuando accedo a mi panel,
   Entonces veo la lista de entregas asignadas con sus detalles.

**HU-18: Actualización de estado**

1. Dado que soy un repartidor,
   Cuando actualizo el estado de una entrega,
   Entonces el sistema registra el cambio y notifica al cliente si corresponde.

**HU-19: Notificaciones de entrega**

1. Dado que mi pedido está en camino,
   Cuando el repartidor actualiza el estado,
   Entonces recibo una notificación con los detalles de la entrega.

## Módulo Administrativo

**HU-20: Gestión de productos**

1. Dado que soy administrador,
   Cuando creo o edito un producto,
   Entonces los cambios se reflejan inmediatamente en el catálogo.

**HU-21: Gestión de inventario**

1. Dado que actualizo el stock de un producto,
   Cuando la cantidad llega a cero,
   Entonces el sistema lo marca como agotado automáticamente.

**HU-22: Procesamiento de órdenes**

1. Dado que recibo una nueva orden,
   Cuando confirmo el pago mediante validación del comprobante,
   Entonces el sistema actualiza el estado y notifica al cliente.

**HU-23: Generación de reportes**

1. Dado que solicito un reporte,
   Cuando selecciono el período y parámetros,
   Entonces el sistema genera el documento con los datos solicitados.

**HU-24: Gestión de descuentos**

1. Dado que creo un código de descuento,
   Cuando defino sus condiciones y vigencia,
   Entonces el sistema lo hace disponible para su uso según las reglas establecidas.

---

*Historias de Usuario actualizadas para el Sistema Angelow - Solo Transferencia Bancaria*</content>
<parameter name="filePath">c:\laragon\www\angelow\historias_usuario_completas.md