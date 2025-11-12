<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplos - Sistema de Alertas de Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 3rem;
            font-size: 1.3rem;
        }
        
        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .example-btn {
            padding: 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1.4rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .example-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .example-btn:active {
            transform: translateY(0);
        }
        
        .btn-success {
            background: #4bb543;
        }
        
        .btn-error {
            background: #ff3333;
        }
        
        .btn-warning {
            background: #ffcc00;
        }
        
        .btn-info {
            background: #0077b6;
        }
        
        .btn-confirm {
            background: #48cae4;
        }
        
        .code-section {
            background: #f5f5f5;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .code-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 1.3rem;
            line-height: 1.6;
        }
        
        .icon {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php 
    // Simular BASE_URL para el ejemplo
    if (!defined('BASE_URL')) {
        define('BASE_URL', '..');
    }
    require_once __DIR__ . '/alert_user.php'; 
    ?>
    
    <div class="container">
        <h1>🎨 Sistema de Alertas de Usuario</h1>
        <p class="subtitle">Haz clic en los botones para ver los diferentes tipos de alertas</p>
        
        <div class="examples-grid">
            <button class="example-btn btn-success" onclick="showSuccess()">
                <i class="fas fa-check-circle icon"></i>Éxito
            </button>
            
            <button class="example-btn btn-error" onclick="showError()">
                <i class="fas fa-times-circle icon"></i>Error
            </button>
            
            <button class="example-btn btn-warning" onclick="showWarning()">
                <i class="fas fa-exclamation-triangle icon"></i>Advertencia
            </button>
            
            <button class="example-btn btn-info" onclick="showInfo()">
                <i class="fas fa-info-circle icon"></i>Información
            </button>
            
            <button class="example-btn btn-confirm" onclick="showConfirm()">
                <i class="fas fa-question-circle icon"></i>Confirmar
            </button>
        </div>
        
        <div class="code-section">
            <h3>📝 Código de Ejemplo</h3>
            <pre><code>// Alerta de éxito
showUserSuccess('¡Operación completada con éxito!');

// Alerta de error
showUserError('Hubo un problema al procesar tu solicitud');

// Alerta de advertencia
showUserWarning('Tu sesión está por expirar');

// Alerta de información
showUserInfo('Tienes 5 notificaciones nuevas');

// Alerta de confirmación
showUserConfirm(
    '¿Estás seguro de eliminar este elemento?',
    function() {
        // Acción al confirmar
        console.log('Elemento eliminado');
    },
    {
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar'
    }
);</code></pre>
        </div>
    </div>
    
    <script>
        function showSuccess() {
            showUserSuccess('¡Operación completada con éxito!', {
                title: '¡Genial!',
                confirmText: 'Entendido'
            });
        }
        
        function showError() {
            showUserError('Hubo un problema al procesar tu solicitud. Por favor, inténtalo nuevamente.', {
                title: '¡Ups!',
                confirmText: 'OK'
            });
        }
        
        function showWarning() {
            showUserWarning('Tu sesión está por expirar en 5 minutos. Guarda tus cambios.', {
                title: '¡Atención!',
                confirmText: 'Guardar ahora'
            });
        }
        
        function showInfo() {
            showUserInfo('Tienes 5 notificaciones nuevas en tu bandeja de entrada.', {
                title: 'Notificaciones',
                confirmText: 'Ver notificaciones'
            });
        }
        
        function showConfirm() {
            showUserConfirm(
                '¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.',
                function() {
                    // Acción al confirmar
                    showUserSuccess('Elemento eliminado correctamente');
                },
                {
                    title: 'Confirmar eliminación',
                    confirmText: 'Sí, eliminar',
                    cancelText: 'No, cancelar'
                }
            );
        }
    </script>
</body>
</html>
