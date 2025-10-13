<?php
/**
 * Script para ejecutar la migración 007 - Sistema de Tracking de Ubicación
 * Ejecutar este archivo en el navegador para aplicar la migración
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Configurar para mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Solo permitir ejecución en desarrollo o por administradores
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('❌ Esta migración solo puede ejecutarse en entorno de desarrollo');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración 007 - Sistema de Tracking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .log {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .step {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #667eea;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ Migración 007: Sistema de Tracking GPS</h1>
        <p class="subtitle">Esta migración agregará las tablas y funciones necesarias para el sistema de navegación en tiempo real.</p>
        
        <div class="log" id="log">
            <div class="info">⏳ Listo para ejecutar migración...</div>
        </div>
        
        <button class="btn" onclick="runMigration()" id="runBtn">▶️ Ejecutar Migración</button>
    </div>

    <script>
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const step = document.createElement('div');
            step.className = 'step ' + type;
            step.textContent = message;
            logDiv.appendChild(step);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        async function runMigration() {
            const btn = document.getElementById('runBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Ejecutando...';

            log('🚀 Iniciando migración...', 'info');

            try {
                const response = await fetch('?action=run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (result.success) {
                    log('✅ ' + result.message, 'success');
                    result.steps.forEach(step => {
                        log('  ✓ ' + step, 'success');
                    });
                    
                    btn.textContent = '✅ Migración Completada';
                    btn.style.background = '#10b981';
                    
                    setTimeout(() => {
                        if (confirm('Migración completada. ¿Deseas ir al panel de delivery?')) {
                            window.location.href = '<?= BASE_URL ?>/delivery/orders.php';
                        }
                    }, 2000);
                } else {
                    log('❌ Error: ' + result.error, 'error');
                    btn.disabled = false;
                    btn.textContent = '🔄 Reintentar';
                }

            } catch (error) {
                log('❌ Error de red: ' + error.message, 'error');
                btn.disabled = false;
                btn.textContent = '🔄 Reintentar';
            }
        }
    </script>
</body>
</html>

<?php
// Procesar la migración si se envía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'run') {
    header('Content-Type: application/json');
    
    try {
        // Leer el archivo SQL de migración
        $sqlFile = __DIR__ . '/migrations/007_add_location_tracking.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('Archivo de migración no encontrado: ' . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        
        if ($sql === false) {
            throw new Exception('Error al leer el archivo de migración');
        }
        
        $steps = [];
        
        // Dividir el SQL en statements individuales
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       !preg_match('/^\/\*/', $stmt);
            }
        );
        
        // Ejecutar cada statement
        $conn->beginTransaction();
        
        foreach ($statements as $statement) {
            // Limpiar comentarios y espacios
            $statement = trim($statement);
            
            if (empty($statement)) {
                continue;
            }
            
            // Detectar el tipo de statement para logging
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Tabla creada: {$matches[1]}";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Tabla modificada: {$matches[1]}";
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Vista creada: {$matches[1]}";
            } elseif (preg_match('/CREATE.*?PROCEDURE.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Procedimiento creado: {$matches[1]}";
            } elseif (preg_match('/CREATE.*?FUNCTION.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Función creada: {$matches[1]}";
            } elseif (preg_match('/CREATE EVENT.*?`?(\w+)`?/i', $statement, $matches)) {
                $steps[] = "Evento creado: {$matches[1]}";
            } elseif (preg_match('/SET GLOBAL/i', $statement)) {
                $steps[] = "Configuración global aplicada";
            }
            
            try {
                $conn->exec($statement);
            } catch (PDOException $e) {
                // Ignorar errores de "ya existe" para hacer la migración idempotente
                if (strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Migración 007 ejecutada exitosamente',
            'steps' => $steps
        ]);
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit();
}
?>
