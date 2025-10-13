# Script para corregir encoding UTF-8 en archivos JavaScript
# Fecha: 13 de Octubre, 2025

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  CORRECTOR DE ENCODING UTF-8" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$archivos = @(
    "c:\laragon\www\angelow\js\delivery\navigation.js",
    "c:\laragon\www\angelow\js\delivery\voice-helper.js",
    "c:\laragon\www\angelow\delivery\api\text_to_speech.php"
)

foreach ($archivo in $archivos) {
    Write-Host "Procesando: $archivo" -ForegroundColor Yellow
    
    if (Test-Path $archivo) {
        # Leer contenido
        $contenido = Get-Content $archivo -Raw -Encoding UTF8
        
        # Verificar si tiene caracteres mal codificados
        $problemas = @(
            "Navegacin",
            "ubicacin",
            "instruccin",
            "pausar navegacin",
            "direccin",
            "informacin",
            "verificacin"
        )
        
        $tieneProblemas = $false
        foreach ($problema in $problemas) {
            if ($contenido -match $problema) {
                $tieneProblemas = $true
                Write-Host "  - Encontrado: '$problema'" -ForegroundColor Red
            }
        }
        
        if ($tieneProblemas) {
            # Hacer backup
            $backup = $archivo + ".backup"
            Copy-Item $archivo $backup -Force
            Write-Host "  - Backup creado: $backup" -ForegroundColor Green
            
            # Corregir caracteres
            $contenido = $contenido -replace "Navegacin", "Navegación"
            $contenido = $contenido -replace "navegacin", "navegación"
            $contenido = $contenido -replace "ubicacin", "ubicación"
            $contenido = $contenido -replace "instruccin", "instrucción"
            $contenido = $contenido -replace "direccin", "dirección"
            $contenido = $contenido -replace "informacin", "información"
            $contenido = $contenido -replace "verificacin", "verificación"
            $contenido = $contenido -replace "actualizacin", "actualización"
            $contenido = $contenido -replace "funcin", "función"
            $contenido = $contenido -replace "reasignacin", "reasignación"
            $contenido = $contenido -replace "opcin", "opción"
            $contenido = $contenido -replace "seccin", "sección"
            $contenido = $contenido -replace "excepcin", "excepción"
            $contenido = $contenido -replace "realizacin", "realización"
            
            # Guardar con UTF-8 sin BOM
            $utf8NoBom = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText($archivo, $contenido, $utf8NoBom)
            
            Write-Host "  - Archivo corregido y guardado en UTF-8" -ForegroundColor Green
        } else {
            Write-Host "  - El archivo esta OK" -ForegroundColor Green
        }
    } else {
        Write-Host "  - Archivo no encontrado" -ForegroundColor Yellow
    }
    
    Write-Host ""
}

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  PROCESO COMPLETADO" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Proximos pasos:" -ForegroundColor Yellow
Write-Host "1. Recarga la pagina de navegacion (Ctrl + F5)" -ForegroundColor White
Write-Host "2. Abre la consola del navegador (F12)" -ForegroundColor White
Write-Host "3. Verifica que ahora diga: 'Navegacion iniciada'" -ForegroundColor White
Write-Host ""
Write-Host "Si quieres revertir cambios, usa los archivos .backup" -ForegroundColor Gray
Write-Host ""

