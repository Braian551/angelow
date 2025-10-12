<?php
/**
 * Script de prueba para verificar el sistema de roles
 * Uso: Ejecutar desde el navegador /tests/test_role_system.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Función para mostrar resultados
function showResult($test, $passed, $message = '') {
    $status = $passed ? '✓ PASS' : '✗ FAIL';
    $color = $passed ? 'green' : 'red';
    echo "<div style='color: $color; margin: 10px 0;'>";
    echo "<strong>$status:</strong> $test";
    if ($message) echo " - <em>$message</em>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba del Sistema de Roles</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Prueba del Sistema de Roles y Autenticación</h1>
        <p>Este script verifica que el sistema de roles esté configurado correctamente.</p>

        <?php
        // Test 1: Verificar que role_redirect.php existe
        echo "<div class='test-section'>";
        echo "<h2>1. Verificación de Archivos</h2>";
        
        $roleRedirectExists = file_exists(__DIR__ . '/../auth/role_redirect.php');
        showResult(
            'Archivo role_redirect.php existe',
            $roleRedirectExists,
            $roleRedirectExists ? 'Encontrado en /auth/role_redirect.php' : 'No encontrado'
        );

        // Test 2: Verificar funciones del sistema de roles
        if ($roleRedirectExists) {
            require_once __DIR__ . '/../auth/role_redirect.php';
            
            $functions = [
                'getDashboardByRole',
                'getAllowedPagesByRole',
                'checkRoleAccess',
                'redirectToDashboard',
                'enforceRoleAccess',
                'requireRole'
            ];
            
            foreach ($functions as $func) {
                $exists = function_exists($func);
                showResult(
                    "Función $func() definida",
                    $exists,
                    $exists ? 'Disponible' : 'No encontrada'
                );
            }
        }
        echo "</div>";

        // Test 3: Verificar estructura de roles en la base de datos
        echo "<div class='test-section'>";
        echo "<h2>2. Verificación de Base de Datos</h2>";
        
        try {
            // Verificar que la tabla users existe
            $stmt = $conn->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
            showResult(
                'Tabla users existe',
                $tableExists,
                $tableExists ? 'Tabla encontrada' : 'Tabla no encontrada'
            );

            if ($tableExists) {
                // Verificar que la columna role existe
                $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
                $columnExists = $stmt->rowCount() > 0;
                showResult(
                    'Columna role existe en users',
                    $columnExists,
                    $columnExists ? 'Columna encontrada' : 'Columna no encontrada'
                );

                // Obtener estadísticas de roles
                if ($columnExists) {
                    echo "<h3>Estadísticas de Usuarios por Rol:</h3>";
                    $stmt = $conn->query("
                        SELECT role, COUNT(*) as count 
                        FROM users 
                        GROUP BY role 
                        ORDER BY count DESC
                    ");
                    
                    echo "<table>";
                    echo "<tr><th>Rol</th><th>Cantidad de Usuarios</th></tr>";
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $role = htmlspecialchars($row['role'] ?? 'sin rol');
                        $count = $row['count'];
                        echo "<tr><td>$role</td><td>$count</td></tr>";
                    }
                    echo "</table>";
                }
            }
        } catch (PDOException $e) {
            showResult(
                'Conexión a base de datos',
                false,
                'Error: ' . $e->getMessage()
            );
        }
        echo "</div>";

        // Test 4: Verificar dashboards
        echo "<div class='test-section'>";
        echo "<h2>3. Verificación de Dashboards</h2>";
        
        $dashboards = [
            'admin' => __DIR__ . '/../admin/dashboardadmin.php',
            'delivery' => __DIR__ . '/../delivery/dashboarddeli.php',
            'user' => __DIR__ . '/../users/dashboarduser.php'
        ];

        foreach ($dashboards as $role => $path) {
            $exists = file_exists($path);
            showResult(
                "Dashboard de $role",
                $exists,
                $exists ? basename($path) : 'No encontrado'
            );
        }
        echo "</div>";

        // Test 5: Verificar configuración de URLs
        echo "<div class='test-section'>";
        echo "<h2>4. Configuración del Sistema</h2>";
        
        $baseUrlDefined = defined('BASE_URL');
        showResult(
            'BASE_URL definida',
            $baseUrlDefined,
            $baseUrlDefined ? BASE_URL : 'No definida'
        );

        if ($baseUrlDefined && $roleRedirectExists) {
            echo "<h3>URLs de Dashboards:</h3>";
            echo "<ul>";
            foreach (['admin', 'delivery', 'user', 'customer'] as $role) {
                $url = getDashboardByRole($role);
                echo "<li><strong>$role:</strong> <code>$url</code></li>";
            }
            echo "</ul>";
        }
        echo "</div>";

        // Test 6: Verificar páginas permitidas por rol
        if ($roleRedirectExists) {
            echo "<div class='test-section'>";
            echo "<h2>5. Páginas Permitidas por Rol</h2>";
            
            foreach (['admin', 'delivery', 'user'] as $role) {
                echo "<h3>Rol: $role</h3>";
                $pages = getAllowedPagesByRole($role);
                echo "<ul>";
                foreach ($pages as $page) {
                    echo "<li><code>$page</code></li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }

        // Test 7: Verificar headers
        echo "<div class='test-section'>";
        echo "<h2>6. Verificación de Headers</h2>";
        
        $headers = [
            'headerproducts.php' => __DIR__ . '/../layouts/headerproducts.php',
            'headerclientconfig.php' => __DIR__ . '/../layouts/client/headerclientconfig.php'
        ];

        foreach ($headers as $name => $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $hasRoleRedirect = strpos($content, 'role_redirect.php') !== false;
                $hasEnforceRole = strpos($content, 'enforceRoleAccess') !== false;
                
                showResult(
                    "$name incluye role_redirect.php",
                    $hasRoleRedirect,
                    $hasRoleRedirect ? 'Incluido correctamente' : 'No incluido'
                );
                
                showResult(
                    "$name ejecuta enforceRoleAccess()",
                    $hasEnforceRole,
                    $hasEnforceRole ? 'Ejecuta correctamente' : 'No ejecuta'
                );
            } else {
                showResult(
                    "$name existe",
                    false,
                    'Archivo no encontrado'
                );
            }
        }
        echo "</div>";

        // Test 8: Verificar login.php
        echo "<div class='test-section'>";
        echo "<h2>7. Verificación de Login</h2>";
        
        $loginPath = __DIR__ . '/../auth/login.php';
        if (file_exists($loginPath)) {
            $content = file_get_contents($loginPath);
            $hasRoleRedirect = strpos($content, 'role_redirect.php') !== false;
            $hasRedirectToDashboard = strpos($content, 'redirectToDashboard') !== false;
            
            showResult(
                'login.php incluye role_redirect.php',
                $hasRoleRedirect,
                $hasRoleRedirect ? 'Incluido correctamente' : 'No incluido'
            );
            
            showResult(
                'login.php usa redirectToDashboard()',
                $hasRedirectToDashboard,
                $hasRedirectToDashboard ? 'Implementado correctamente' : 'No implementado'
            );
        } else {
            showResult(
                'login.php existe',
                false,
                'Archivo no encontrado'
            );
        }
        echo "</div>";

        // Resumen final
        echo "<div class='summary'>";
        echo "<h2>📊 Resumen</h2>";
        echo "<p><strong>Estado del Sistema:</strong> ";
        
        if ($roleRedirectExists && $tableExists && $columnExists) {
            echo "<span style='color: green; font-size: 1.2em;'>✓ Sistema de roles configurado correctamente</span>";
            echo "<p>El sistema está listo para:</p>";
            echo "<ul>";
            echo "<li>Redirigir usuarios según su rol después del login</li>";
            echo "<li>Controlar acceso a páginas según permisos</li>";
            echo "<li>Proteger dashboards de admin, delivery y usuarios</li>";
            echo "</ul>";
        } else {
            echo "<span style='color: red; font-size: 1.2em;'>✗ Hay problemas de configuración</span>";
            echo "<p>Revisa los errores anteriores para corregir el sistema.</p>";
        }
        echo "</div>";
        ?>

        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3>⚠️ Instrucciones de Uso:</h3>
            <ol>
                <li>Verifica que todos los tests pasen (✓ PASS)</li>
                <li>Corrige cualquier error (✗ FAIL) antes de continuar</li>
                <li>Prueba el login con usuarios de diferentes roles</li>
                <li>Verifica que cada usuario sea redirigido a su dashboard correcto</li>
                <li>Intenta acceder manualmente a dashboards de otros roles (deben redirigir)</li>
            </ol>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="<?= BASE_URL ?>/auth/login.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">
                Ir al Login para Probar
            </a>
        </div>
    </div>
</body>
</html>
