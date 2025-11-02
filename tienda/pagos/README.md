# Carpeta de Pagos - Angelow

Esta carpeta contiene todos los archivos relacionados con el proceso de pago y checkout del sitio web.

## Estructura de Archivos

### Archivos Principales

- **cart.php** - Vista del carrito de compras (Paso 1)
- **envio.php** - Selección de dirección y método de envío (Paso 2)
- **pay.php** - Confirmación y subida de comprobante de pago (Paso 3)
- **confirmacion.php** - Página de confirmación del pedido (Paso 4)
- **apply_discount.php** - API para aplicar/remover códigos de descuento

## Flujo de Pago

```
1. cart.php (Carrito)
   ↓
2. envio.php (Envío y Dirección)
   ↓
3. pay.php (Confirmación y Pago)
   ↓
4. confirmacion.php (Pedido Confirmado)
```

## URLs de Acceso

- Carrito: `/tienda/pagos/cart.php`
- Envío: `/tienda/pagos/envio.php`
- Pago: `/tienda/pagos/pay.php`
- Confirmación: `/tienda/pagos/confirmacion.php`

## Dependencias

Estos archivos dependen de:
- `config.php` - Configuración general
- `conexion.php` - Conexión a base de datos
- Layouts en `/layouts/`
- Estilos CSS en `/css/`
- Scripts JavaScript en `/js/cart/`
- API de pagos en `/tienda/api/pay/`

## Método de Pago

El sistema actualmente solo acepta **transferencias bancarias**. Los usuarios deben:
1. Realizar la transferencia a la cuenta configurada
2. Subir el comprobante de pago
3. Esperar verificación del administrador

## Notas Importantes

- Todos los archivos usan rutas relativas con `__DIR__ . '/../../'`
- La sesión debe estar iniciada para acceder a estos archivos
- Los datos del checkout se almacenan en `$_SESSION['checkout_data']`
- La última orden se guarda en `$_SESSION['last_order']`

## Actualización de Rutas (Nov 2025)

Estos archivos fueron movidos desde `/tienda/` a `/tienda/pagos/` para mejor organización.
Todas las referencias internas y externas han sido actualizadas.
