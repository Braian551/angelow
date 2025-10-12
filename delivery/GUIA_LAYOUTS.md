# Guía de Implementación - Layouts Modulares Delivery

## Resumen

Se ha creado una estructura modular para el panel de transportistas (delivery) siguiendo el mismo patrón que admin y user.

## Estructura Creada

```
layouts/
└── delivery/
    ├── asidedelivery.php   - Sidebar con menú de navegación
    ├── headerdelivery.php  - Encabezado del panel
    └── README.md          - Documentación del módulo
```

## Cambios Realizados en `dashboarddeli.php`

### Antes:
```php
// El código del aside y header estaba directamente en el archivo
<aside class="delivery-sidebar">
    <!-- Todo el código del sidebar aquí -->
</aside>

<header class="delivery-header">
    <!-- Todo el código del header aquí -->
</header>
```

### Después:
```php
<!-- Sidebar -->
<?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

<!-- Main Content -->
<main class="delivery-content">
    <!-- Header -->
    <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>
```

## Cómo Aplicar en Otros Archivos del Módulo Delivery

Si tienes otros archivos en el módulo delivery (orders.php, history.php, settings.php), aplica esta estructura:

### Plantilla Base:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar rol
requireRole('delivery');

// Tu lógica PHP aquí (consultas, procesamiento, etc.)
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Título - Angelow</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarddelivery.css">
    <!-- Tus estilos adicionales -->
</head>
<body>
    <div class="delivery-container">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

        <!-- Main Content -->
        <main class="delivery-content">
            <!-- Header -->
            <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>
            
            <!-- Tu contenido específico de la página aquí -->
            <section>
                <h2>Tu contenido</h2>
                <!-- ... -->
            </section>
            
        </main>
    </div>
    
    <!-- Tus scripts -->
    <script src="<?= BASE_URL ?>/js/tu-script.js"></script>
</body>
</html>
```

## Ventajas de Esta Estructura

1. **Mantenibilidad**: Un cambio en el aside o header se refleja en todas las páginas
2. **Consistencia**: Todas las páginas del módulo delivery comparten la misma navegación
3. **DRY (Don't Repeat Yourself)**: No repetir código en cada archivo
4. **Fácil actualización**: Agregar un nuevo elemento al menú solo requiere editar `asidedelivery.php`
5. **Detección automática de página activa**: El menú marca automáticamente la página actual

## Funcionalidades del Aside

- **Detección automática de página activa**: La clase `active` se aplica automáticamente
- **Carga de datos de usuario**: Si `$userData` no existe, el aside lo carga automátamente
- **Manejo de errores**: Redirecciona apropiadamente en caso de error

## Variables de Menú

En `asidedelivery.php` se definen las rutas del menú:

```php
$menu_items = [
    'dashboard' => '/delivery/dashboarddeli.php',
    'orders' => '/delivery/orders.php',
    'history' => '/delivery/history.php',
    'settings' => '/delivery/settings.php'
];
```

Para agregar nuevas páginas, simplemente añade una nueva entrada en este array y un nuevo `<li>` en el menú.

## Personalización del Header

Si necesitas un header diferente en alguna página específica, puedes:

1. Crear un nuevo archivo de header (ej: `headerdelivery-orders.php`)
2. Incluirlo condicionalmente o directamente en esa página específica

## Ejemplo de Uso en Nueva Página

Si creas `delivery/orders.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

requireRole('delivery');

// Tus consultas de órdenes aquí
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Órdenes - Angelow</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarddelivery.css">
</head>
<body>
    <div class="delivery-container">
        <?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

        <main class="delivery-content">
            <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>
            
            <!-- Tu contenido de órdenes -->
            <section class="orders-section">
                <h2>Órdenes Asignadas</h2>
                <!-- Tu lista de órdenes -->
            </section>
        </main>
    </div>
</body>
</html>
```

## Notas Importantes

- **Rutas relativas**: Los layouts usan `__DIR__ . '/..'` para asegurar rutas correctas
- **BASE_URL**: Siempre usar `BASE_URL` para enlaces y recursos
- **Consistencia CSS**: Usar las clases CSS existentes del módulo delivery
- **Íconos FontAwesome**: Ya incluidos en la plantilla base

## Siguiente Paso

Si tienes otros archivos en `delivery/` que necesiten esta estructura, aplica la plantilla base mostrada arriba.
