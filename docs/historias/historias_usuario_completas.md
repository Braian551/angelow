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

# Criterios de Aceptación Detallados

## Módulo de Gestión de Usuarios

**HU-01: Registro de nuevo cliente**

1. **Escenario: Registro exitoso con email válido**
   - Dado que soy un cliente nuevo en la página de registro
   - Cuando completo el formulario con nombre, email válido, teléfono opcional, contraseña (mínimo 6 caracteres) y acepto términos
   - Entonces el sistema valida que el email no existe, crea la cuenta, envía email de bienvenida con PHPMailer, y redirige al dashboard del usuario

2. **Escenario: Email ya registrado**
   - Dado que intento registrarme con un email existente
   - Cuando envío el formulario
   - Entonces el sistema muestra mensaje "Este correo ya está registrado" y mantiene el formulario abierto

3. **Escenario: Datos incompletos**
   - Dado que dejo campos obligatorios vacíos
   - Cuando intento enviar el formulario
   - Entonces el sistema muestra "Todos los campos obligatorios deben ser completados"

4. **Escenario: Contraseña muy corta**
   - Dado que ingreso una contraseña de menos de 6 caracteres
   - Cuando envío el formulario
   - Entonces el sistema muestra "La contraseña debe tener al menos 6 caracteres"

**HU-02: Inicio de sesión**

1. **Escenario: Login exitoso**
   - Dado que tengo una cuenta registrada
   - Cuando ingreso email y contraseña correctos
   - Entonces el sistema valida credenciales con password_verify(), crea sesión con user_id, IP y user_agent, y redirige según rol (cliente/admin/delivery)

2. **Escenario: Credenciales incorrectas**
   - Dado que ingreso datos inválidos
   - Cuando intento iniciar sesión
   - Entonces el sistema muestra "Credenciales incorrectas" y registra intento fallido en logs

**HU-03: Edición de perfil**

1. **Escenario: Actualización exitosa**
   - Dado que estoy autenticado en mi perfil
   - Cuando modifico datos personales, direcciones o preferencias y guardo
   - Entonces el sistema actualiza la base de datos y muestra mensaje de confirmación

**HU-04: Recuperación de contraseña**

1. **Escenario: Solicitud de recuperación**
   - Dado que olvidé mi contraseña
   - Cuando ingreso mi email registrado en el formulario de recuperación
   - Entonces el sistema genera token único, guarda en base de datos con expiración, y envía email con enlace seguro

## Módulo de Catálogo y Búsqueda

**HU-05: Búsqueda de productos**

1. **Escenario: Búsqueda por texto**
   - Dado que estoy en la página de catálogo
   - Cuando ingreso términos de búsqueda
   - Entonces el sistema busca en nombres, descripciones y categorías de productos usando LIKE queries

2. **Escenario: Sin resultados**
   - Dado que mi búsqueda no coincide con ningún producto
   - Cuando realizo la búsqueda
   - Entonces el sistema muestra mensaje "No se encontraron productos" y sugiere términos alternativos

**HU-06: Detalles de producto**

1. **Escenario: Visualización completa**
   - Dado que selecciono un producto del catálogo
   - Cuando accedo a su página de detalles
   - Entonces veo galería de imágenes, descripción completa, precio base, variantes disponibles (tallas, colores), y stock por variante

**HU-07: Filtrado de productos**

1. **Escenario: Filtros aplicados**
   - Dado que tengo resultados de búsqueda
   - Cuando aplico filtros por categoría, género, precio mínimo/máximo, talla o color
   - Entonces el sistema construye query WHERE dinámica y muestra solo productos que cumplen todos los criterios

## Módulo de Carrito y Compras

**HU-08: Agregar al carrito**

1. **Escenario: Agregado exitoso**
   - Dado que selecciono producto con variantes específicas
   - Cuando hago clic en "Agregar al carrito" con cantidad válida
   - Entonces el sistema verifica stock disponible, calcula precio con variantes, inserta en cart_items, y muestra notificación de éxito

2. **Escenario: Stock insuficiente**
   - Dado que la cantidad solicitada excede el stock
   - Cuando intento agregar al carrito
   - Entonces el sistema muestra "Stock insuficiente" y sugiere cantidad máxima disponible

**HU-09: Modificar carrito**

1. **Escenario: Cambio de cantidad**
   - Dado que tengo items en el carrito
   - Cuando modifico la cantidad de un producto
   - Entonces el sistema recalcula subtotales automáticamente y actualiza la base de datos

**HU-10: Códigos de descuento**

1. **Escenario: Descuento válido aplicado**
   - Dado que tengo un código de descuento activo y no usado
   - Cuando lo aplico en el checkout
   - Entonces el sistema valida código, calcula descuento (porcentaje o monto fijo), actualiza totales, y marca código como usado

## Módulo de Pedidos y Logística

**HU-11: Proceso de pago con transferencia**

1. **Escenario: Pago completado correctamente**
   - Dado que tengo productos en carrito y dirección seleccionada
   - Cuando completo el formulario con número de referencia válido y subo comprobante (JPG/PNG/PDF < 5MB)
   - Entonces el sistema genera número de orden único (ORD + fecha + ID), crea registro en orders y order_items, guarda comprobante en uploads/payment_proofs/, registra transacción en payment_transactions con estado 'pending', limpia carrito, y redirige a confirmación

2. **Escenario: Comprobante faltante**
   - Dado que no subo el archivo de comprobante
   - Cuando intento confirmar el pago
   - Entonces el sistema muestra "Debes subir el comprobante de pago"

3. **Escenario: Archivo inválido**
   - Dado que subo archivo con formato no permitido o tamaño excesivo
   - Cuando valido el formulario
   - Entonces el sistema muestra error específico ("El archivo debe ser JPG, PNG o PDF" o "El archivo no puede ser mayor a 5MB")

**HU-12: Cálculo de envío**

1. **Escenario: Costo calculado automáticamente**
   - Dado que tengo dirección de envío seleccionada
   - Cuando el sistema procesa el envío
   - Entonces calcula costo basado en reglas configuradas (rango de precio, zona) y muestra total actualizado

**HU-13: Selección de dirección**

1. **Escenario: Dirección guardada seleccionada**
   - Dado que tengo direcciones guardadas en mi perfil
   - Cuando selecciono una para el envío
   - Entonces el sistema la utiliza para cálculo de costos y la guarda en la orden

**HU-14: Generación de factura**

1. **Escenario: Factura generada automáticamente**
   - Dado que el pago es aprobado por administrador
   - Cuando se confirma la orden
   - Entonces el sistema genera PDF con datos DIAN, código QR, detalles de productos, totales, y lo guarda en el servidor

**HU-15: Estado de pedidos**

1. **Escenario: Seguimiento en tiempo real**
   - Dado que tengo pedidos realizados
   - Cuando accedo a "Mis pedidos"
   - Entonces veo lista con estados: pending, paid, preparing, shipping, delivered, con fechas y detalles

## Módulo de Delivery

**HU-16: Asignación de pedidos**

1. **Escenario: Asignación exitosa**
   - Dado que un pedido está pagado y listo para envío
   - Cuando el administrador lo asigna a un repartidor disponible
   - Entonces el sistema actualiza order.delivery_assigned_id, cambia estado a 'shipping', y notifica al repartidor

**HU-17: Lista de entregas**

1. **Escenario: Visualización de asignaciones**
   - Dado que soy repartidor autenticado
   - Cuando accedo al panel de delivery
   - Entonces veo lista de órdenes asignadas con dirección completa, productos, total, e instrucciones especiales

**HU-18: Actualización de estados**

1. **Escenario: Cambio de estado**
   - Dado que tengo una entrega asignada
   - Cuando actualizo el estado (en_camino, en_destino, entregado)
   - Entonces el sistema registra timestamp, actualiza order.status, envía notificación al cliente, y guarda en order_status_history

**HU-19: Notificaciones al cliente**

1. **Escenario: Notificación automática**
   - Dado que mi pedido cambió de estado
   - Cuando el repartidor actualiza el progreso
   - Entonces recibo email automático con detalles del cambio y posible hora de llegada

## Módulo Administrativo

**HU-20: Gestión de productos**

1. **Escenario: Creación de producto**
   - Dado que soy administrador autenticado
   - Cuando creo un producto con nombre, descripción, precio, imágenes y variantes
   - Entonces el sistema inserta en products, product_images, y product_variants, y lo marca como activo

2. **Escenario: Edición de producto**
   - Dado que tengo un producto existente
   - Cuando modifico sus datos y guardo
   - Entonces el sistema actualiza todos los campos relacionados y mantiene consistencia referencial

**HU-21: Gestión de inventario**

1. **Escenario: Actualización de stock**
   - Dado que modifico el stock de una variante
   - Cuando guardo los cambios
   - Entonces el sistema actualiza product_variants.quantity y registra movimiento en inventory_log

**HU-22: Procesamiento de órdenes**

1. **Escenario: Validación de pago**
   - Dado que recibo una nueva orden con comprobante
   - Cuando reviso y apruebo el pago manualmente
   - Entonces el sistema cambia order.payment_status a 'paid', actualiza order.status a 'preparing', y envía confirmación al cliente

**HU-23: Generación de reportes**

1. **Escenario: Reporte de ventas**
   - Dado que solicito reporte por fechas
   - Cuando genero el informe
   - Entonces el sistema consulta orders, order_items, calcula totales, y exporta a PDF/Excel con gráficos

**HU-24: Gestión de descuentos**

1. **Escenario: Creación de código promocional**
   - Dado que configuro un descuento con código único, tipo (porcentaje/monto), valor, fechas de vigencia y condiciones
   - Cuando lo activo
   - Entonces el sistema lo guarda en discount_codes y permite su uso en checkout según reglas definidas

---

*Estos criterios de aceptación reflejan exactamente la implementación técnica del Sistema Angelow, incluyendo validaciones específicas, manejo de archivos, procesos de base de datos y flujos de negocio reales.*</content>
<parameter name="filePath">c:\laragon\www\angelow\historias_usuario_completas.md