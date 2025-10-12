# Layouts de Delivery

Este directorio contiene los componentes modulares para el panel de transportistas (delivery).

## Archivos

### `asidedelivery.php`
- **Descripción**: Sidebar de navegación para transportistas
- **Incluye**: 
  - Perfil del transportista (foto, nombre, teléfono)
  - Menú de navegación con enlaces a:
    - Dashboard/Resumen
    - Órdenes
    - Historial
    - Configuración
    - Cerrar sesión
  - Sistema de detección de página activa

### `headerdelivery.php`
- **Descripción**: Encabezado del panel de transportista
- **Incluye**:
  - Título del panel
  - Indicador de estado (Disponible/No disponible)
  - Botón de notificaciones con contador

## Uso

Para usar estos componentes en cualquier página del módulo delivery:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar rol
requireRole('delivery');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... meta tags y estilos ... -->
</head>
<body>
    <div class="delivery-container">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

        <!-- Main Content -->
        <main class="delivery-content">
            <!-- Header -->
            <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>
            
            <!-- Tu contenido aquí -->
            
        </main>
    </div>
</body>
</html>
```

## Variables requeridas

Los layouts esperan que existan las siguientes variables:
- `$conn`: Conexión a la base de datos (PDO)
- `$_SESSION['user_id']`: ID del usuario en sesión
- Constante `BASE_URL`: URL base de la aplicación

## Características

- **Menú activo automático**: Detecta automáticamente la página actual y marca el elemento del menú correspondiente
- **Manejo de errores**: Incluye try-catch para errores de base de datos
- **Responsive**: Compatible con diseño responsive
- **Reutilizable**: Los mismos componentes pueden ser usados en todas las páginas del módulo delivery

## Estructura similar

Esta estructura replica el patrón usado en:
- `layouts/admin/` - Para administradores
- `layouts/` (asideuser.php) - Para usuarios/clientes
