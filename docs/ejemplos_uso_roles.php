<?php
/**
 * EJEMPLOS DE USO DEL SISTEMA DE ROLES
 * Este archivo muestra diferentes formas de implementar el control de acceso
 * NO ejecutar este archivo directamente - es solo para referencia
 */

// ============================================
// EJEMPLO 1: Proteger una página de Admin
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Solo administradores pueden acceder
requireRole('admin');

// Tu código aquí - solo se ejecuta si es admin
echo "Bienvenido administrador!";
?>
*/

// ============================================
// EJEMPLO 2: Proteger una página de Delivery
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Solo repartidores pueden acceder
requireRole('delivery');

// Tu código aquí - solo se ejecuta si es delivery
echo "Panel de entregas";
?>
*/

// ============================================
// EJEMPLO 3: Permitir múltiples roles
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Admin o delivery pueden acceder
requireRole(['admin', 'delivery']);

// Tu código aquí - ejecuta si es admin O delivery
echo "Área de gestión";
?>
*/

// ============================================
// EJEMPLO 4: Página de usuario/cliente
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Solo clientes pueden acceder
requireRole(['user', 'customer']);

// Tu código aquí - solo clientes
echo "Mi perfil de usuario";
?>
*/

// ============================================
// EJEMPLO 5: Usar en un header personalizado
// ============================================

/*
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Aplicar control de acceso automático
enforceRoleAccess();

// Resto del header...
?>
*/

// ============================================
// EJEMPLO 6: Obtener el dashboard del usuario actual
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $dashboard = getDashboardByRole($user['role']);
        echo "Tu dashboard está en: $dashboard";
    }
}
?>
*/

// ============================================
// EJEMPLO 7: Verificar acceso programáticamente
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $hasAccess = checkRoleAccess($user['role'], '/admin/products.php');
        
        if ($hasAccess) {
            echo "Tienes acceso a productos de admin";
        } else {
            echo "No tienes acceso a esta página";
        }
    }
}
?>
*/

// ============================================
// EJEMPLO 8: Menú dinámico según rol
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo '<nav>';
        
        // Opciones comunes
        echo '<a href="' . BASE_URL . '">Inicio</a>';
        
        // Opciones según rol
        switch($user['role']) {
            case 'admin':
                echo '<a href="' . BASE_URL . '/admin/dashboardadmin.php">Dashboard Admin</a>';
                echo '<a href="' . BASE_URL . '/admin/products.php">Productos</a>';
                echo '<a href="' . BASE_URL . '/admin/orders.php">Órdenes</a>';
                break;
                
            case 'delivery':
                echo '<a href="' . BASE_URL . '/delivery/dashboarddeli.php">Mis Entregas</a>';
                break;
                
            case 'user':
            case 'customer':
                echo '<a href="' . BASE_URL . '/users/dashboarduser.php">Mi Cuenta</a>';
                echo '<a href="' . BASE_URL . '/tienda/productos.php">Tienda</a>';
                echo '<a href="' . BASE_URL . '/tienda/cart.php">Carrito</a>';
                break;
        }
        
        echo '<a href="' . BASE_URL . '/auth/logout.php">Cerrar Sesión</a>';
        echo '</nav>';
    }
}
?>
*/

// ============================================
// EJEMPLO 9: Página con contenido condicional
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Permitir a todos los usuarios autenticados
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Obtener rol
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userRole = $user['role'] ?? 'user';

// Mostrar contenido diferente según rol
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Unificado</title>
</head>
<body>
    <h1>Bienvenido</h1>
    
    <?php if ($userRole === 'admin'): ?>
        <div class="admin-section">
            <h2>Panel de Administración</h2>
            <p>Opciones de administrador...</p>
        </div>
    <?php elseif ($userRole === 'delivery'): ?>
        <div class="delivery-section">
            <h2>Panel de Entregas</h2>
            <p>Tus entregas pendientes...</p>
        </div>
    <?php else: ?>
        <div class="user-section">
            <h2>Mi Cuenta</h2>
            <p>Tus pedidos y configuración...</p>
        </div>
    <?php endif; ?>
</body>
</html>
*/

// ============================================
// EJEMPLO 10: API con verificación de rol
// ============================================

/*
<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Obtener rol
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que sea admin
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit();
}

// Procesar solicitud
echo json_encode(['success' => true, 'data' => 'Datos para admin']);
?>
*/

// ============================================
// EJEMPLO 11: Formulario que cambia según rol
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Solo usuarios autenticados
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userRole = $user['role'] ?? 'user';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Crear Orden</title>
</head>
<body>
    <form action="process_order.php" method="POST">
        <h2>Nueva Orden</h2>
        
        <!-- Campos comunes -->
        <input type="text" name="product" placeholder="Producto" required>
        <input type="number" name="quantity" placeholder="Cantidad" required>
        
        <!-- Campo solo para admin -->
        <?php if ($userRole === 'admin'): ?>
            <select name="discount_type">
                <option value="none">Sin descuento</option>
                <option value="percentage">Porcentaje</option>
                <option value="fixed">Cantidad fija</option>
            </select>
        <?php endif; ?>
        
        <!-- Campo solo para delivery -->
        <?php if ($userRole === 'delivery'): ?>
            <input type="text" name="delivery_notes" placeholder="Notas de entrega">
        <?php endif; ?>
        
        <button type="submit">Crear Orden</button>
    </form>
</body>
</html>
*/

// ============================================
// EJEMPLO 12: Middleware personalizado
// ============================================

/*
<?php
// En un archivo custom_middleware.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

function requireAdminOrDelivery() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
    
    $stmt = $GLOBALS['conn']->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !in_array($user['role'], ['admin', 'delivery'])) {
        header("Location: " . BASE_URL . "/users/dashboarduser.php");
        exit();
    }
    
    return $user['role'];
}

// Usar en una página
session_start();
require_once 'custom_middleware.php';

$userRole = requireAdminOrDelivery();
echo "Acceso permitido para: $userRole";
?>
*/

// ============================================
// EJEMPLO 13: Logging de accesos
// ============================================

/*
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Función para registrar acceso
function logAccess($userId, $page, $role) {
    global $conn;
    
    $stmt = $conn->prepare(
        "INSERT INTO access_logs (user_id, page, role, ip_address, access_date) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $userId,
        $page,
        $role,
        $_SERVER['REMOTE_ADDR']
    ]);
}

// Proteger página
requireRole('admin');

// Registrar acceso exitoso
if (isset($_SESSION['user_id'])) {
    logAccess($_SESSION['user_id'], $_SERVER['PHP_SELF'], 'admin');
}

// Tu código aquí...
?>
*/

// ============================================
// NOTAS FINALES
// ============================================

/*
MEJORES PRÁCTICAS:

1. Siempre usar requireRole() al inicio de páginas protegidas
2. Usar enforceRoleAccess() en headers para verificación global
3. No confiar solo en JavaScript - siempre validar en servidor
4. Registrar accesos importantes para auditoría
5. Mantener la lista de roles en un solo lugar (role_redirect.php)
6. Usar constantes para roles en lugar de strings cuando sea posible
7. Manejar errores apropiadamente
8. Documentar qué roles pueden acceder a cada página

SEGURIDAD:

- Nunca mostrar información de roles en el frontend innecesariamente
- Siempre usar HTTPS en producción
- Mantener logs de acceso para auditoría
- Revisar regularmente permisos de usuarios
- Usar prepared statements para todas las queries
- Validar entrada del usuario incluso con roles

PERFORMANCE:

- Cachear rol del usuario en sesión cuando sea posible
- Minimizar queries a la base de datos
- Usar índices en la columna 'role'
- No verificar el rol en cada línea de código

MANTENIMIENTO:

- Mantener documentación actualizada
- Usar versionado para cambios importantes
- Probar después de cada cambio de permisos
- Tener un plan de rollback si algo falla
*/
