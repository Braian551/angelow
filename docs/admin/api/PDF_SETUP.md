# Configuración de Exportación de PDF para Órdenes

## Funcionalidad Implementada

Se ha implementado la funcionalidad de exportación de PDFs para órdenes siguiendo el patrón usado en los PDFs de descuentos del proyecto.

## Archivos Modificados/Creados

1. **admin/api/export_orders_pdf.php** - Endpoint para generar PDFs de órdenes
2. **js/orderadmin.php** - JavaScript con la lógica de exportación
3. **admin/orders.php** - Página principal con el botón de exportar

## Requisitos

### 1. Instalar Dependencias de Composer

Primero, asegúrate de tener Composer instalado. Luego ejecuta:

```bash
cd c:\xampp\htdocs\angelow
composer install
```

Esto instalará:
- TCPDF (librería de generación de PDF)
- PHPMailer
- MongoDB

### 2. Verificar la Instalación

Accede a la siguiente URL en tu navegador:
```
http://localhost/angelow/tests/admin/api/test_pdf.php
```

Deberías ver un mensaje confirmando que TCPDF está instalado correctamente.

## Uso

### Exportar Órdenes a PDF

1. Ve a la sección de **Gestión de Órdenes** en el panel de administración
2. Selecciona una o más órdenes usando los checkboxes
3. Haz clic en el botón **"Exportar"** en la parte superior derecha
4. El sistema generará un PDF con todas las órdenes seleccionadas y lo descargará automáticamente

### Características del PDF Generado

- **Logo de la empresa** (si existe en `/images/logo2.png`)
- **Información completa del cliente**: nombre, documento, dirección, teléfono, email
- **Detalle de productos**: código SKU, descripción, cantidad, precio unitario y subtotal
- **Información de pago**: método de pago, banco, cuenta, referencia
- **Estados**: estado de la orden y estado del pago
- **Totales**: subtotal, costo de envío y total
- **Notas adicionales** de la orden
- **Una página por orden** con diseño profesional

## Estructura del Código

### Backend (export_orders_pdf.php)

```php
// 1. Validación de sesión y permisos de administrador
// 2. Recepción de IDs de órdenes desde POST
// 3. Consulta de datos de órdenes e items
// 4. Generación de PDF usando TCPDF
// 5. Descarga del archivo PDF
```

### Frontend (orderadmin.php)

```javascript
// 1. Escucha el evento click en el botón de exportar
// 2. Valida que haya órdenes seleccionadas
// 3. Envía petición POST con los IDs
// 4. Maneja la respuesta como blob (PDF)
// 5. Descarga automáticamente el archivo
```

## Personalización

### Modificar el Diseño del PDF

Edita el archivo `admin/api/export_orders_pdf.php` y busca la sección de estilos CSS:

```php
<style>
    .header-title { color: #006699; } // Color del título
    .section-title { background-color: #E6F2FF; } // Fondo de secciones
    // ... más estilos
</style>
```

### Cambiar la Información de la Empresa

Busca la sección del footer en el mismo archivo:

```php
<div class="footer">
    <strong>Angelow Ropa Infantil</strong><br>
    NIT: 901234567-8 | Tel: +57 604 1234567 | Email: contacto@angelow.com<br>
    // ... actualiza con tu información
</div>
```

## Solución de Problemas

### Error: "TCPDF no está instalado"
- Ejecuta `composer install` en la raíz del proyecto

### Error: "Usuario no autenticado"
- Asegúrate de estar logueado como administrador

### El PDF no se descarga
- Verifica la consola del navegador (F12) para ver errores
- Revisa los logs de PHP en `php_errors.log`

### El logo no aparece en el PDF
- Verifica que exista el archivo en `/images/logo2.png`
- Asegúrate de que el archivo tenga permisos de lectura

## Compatibilidad

- **PHP**: 7.4 o superior
- **Navegadores**: Chrome, Firefox, Edge, Safari (modernos)
- **Sistema Operativo**: Windows, Linux, macOS

## Seguridad

- ✓ Validación de sesión de usuario
- ✓ Verificación de rol de administrador
- ✓ Sanitización de datos de entrada
- ✓ Prevención de inyección SQL con prepared statements
- ✓ Escape de HTML en el contenido del PDF

## Mantenimiento

Para actualizar la librería TCPDF:

```bash
composer update tecnickcom/tcpdf
```

## Soporte

Si encuentras problemas:
1. Revisa este archivo de documentación
2. Verifica los logs de errores
3. Ejecuta el archivo de prueba `/tests/admin/api/test_pdf.php`
4. Contacta al desarrollador del sistema
