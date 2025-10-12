<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Extensiones PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #006699;
            border-bottom: 3px solid #006699;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #006699;
            padding: 15px;
            margin: 20px 0;
        }
        .extension-list {
            list-style: none;
            padding: 0;
        }
        .extension-list li {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            background: #f8f9fa;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Extensiones PHP para PDF</h1>
        
        <div class="info-box">
            <strong>ℹ️ Información del Sistema:</strong><br>
            <strong>Versión de PHP:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Archivo php.ini:</strong> <?php echo php_ini_loaded_file(); ?><br>
            <strong>Sistema Operativo:</strong> <?php echo PHP_OS; ?>
        </div>

        <h2>Extensiones Requeridas para Dompdf:</h2>
        
        <?php
        $requiredExtensions = [
            'gd' => 'Necesaria para procesar imágenes en PDFs',
            'mbstring' => 'Necesaria para manejar cadenas multibyte',
            'dom' => 'Necesaria para procesar HTML',
            'fileinfo' => 'Necesaria para detectar tipos de archivo',
            'xml' => 'Necesaria para procesar XML',
            'libxml' => 'Necesaria para funciones XML'
        ];
        
        $allLoaded = true;
        
        echo '<ul class="extension-list">';
        foreach ($requiredExtensions as $ext => $description) {
            $loaded = extension_loaded($ext);
            $allLoaded = $allLoaded && $loaded;
            $status = $loaded ? '<span class="success">✓ INSTALADA</span>' : '<span class="error">✗ NO INSTALADA</span>';
            echo "<li><strong>{$ext}</strong> - {$status}<br><small>{$description}</small></li>";
        }
        echo '</ul>';
        ?>

        <h2>Estado de GD Library:</h2>
        <?php if (extension_loaded('gd')): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px;">
                <strong>✓ GD está instalada y activa</strong><br>
                Información de GD:
                <pre><?php print_r(gd_info()); ?></pre>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px;">
                <strong>✗ GD NO está instalada o activa</strong><br><br>
                <strong>Solución para XAMPP en Windows:</strong>
                <ol>
                    <li>Abre el archivo <code>php.ini</code> ubicado en: <code><?php echo php_ini_loaded_file(); ?></code></li>
                    <li>Busca la línea: <code>;extension=gd</code> o <code>;extension=php_gd2.dll</code></li>
                    <li>Elimina el punto y coma (;) al inicio de la línea para descomentarla</li>
                    <li>Guarda el archivo</li>
                    <li>Reinicia Apache desde el panel de control de XAMPP</li>
                    <li>Recarga esta página para verificar</li>
                </ol>
            </div>
        <?php endif; ?>

        <h2>Extensiones Instaladas:</h2>
        <div style="max-height: 300px; overflow-y: auto;">
            <pre><?php print_r(get_loaded_extensions()); ?></pre>
        </div>

        <?php if ($allLoaded): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 4px; margin-top: 20px; text-align: center;">
                <h3 style="margin: 0;">✓ ¡Todo está listo!</h3>
                <p>Todas las extensiones necesarias están instaladas. Puedes generar PDFs sin problemas.</p>
            </div>
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 4px; margin-top: 20px;">
                <h3 style="margin: 0;">⚠️ Acción requerida</h3>
                <p>Algunas extensiones necesitan ser habilitadas. Sigue las instrucciones anteriores.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center; color: #666;">
            <small>Generado el <?php echo date('d/m/Y H:i:s'); ?></small>
        </div>
    </div>
</body>
</html>
