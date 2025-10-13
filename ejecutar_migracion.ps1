# ============================================
# Script PowerShell para ejecutar la migración
# AngelOW - Corrección de Procedimientos
# ============================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CORRECCIÓN DE PROCEDIMIENTOS - ANGELOW  " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que estamos en el directorio correcto
$currentPath = Get-Location
Write-Host "📁 Directorio actual: $currentPath" -ForegroundColor Yellow

# Buscar PHP
$phpPath = "php"

# Intentar encontrar PHP en Laragon
if (Test-Path "C:\laragon\bin\php\php-8.2.12\php.exe") {
    $phpPath = "C:\laragon\bin\php\php-8.2.12\php.exe"
    Write-Host "✅ PHP encontrado en Laragon" -ForegroundColor Green
} elseif (Test-Path "C:\laragon\bin\php\php-8.1.10\php.exe") {
    $phpPath = "C:\laragon\bin\php\php-8.1.10\php.exe"
    Write-Host "✅ PHP encontrado en Laragon" -ForegroundColor Green
} else {
    # Verificar si php está en el PATH
    $phpTest = Get-Command php -ErrorAction SilentlyContinue
    if ($phpTest) {
        Write-Host "✅ PHP encontrado en PATH del sistema" -ForegroundColor Green
    } else {
        Write-Host "❌ No se encontró PHP. Por favor, instala PHP o configura el PATH" -ForegroundColor Red
        Write-Host ""
        Write-Host "Opciones:" -ForegroundColor Yellow
        Write-Host "  1. Instala Laragon (incluye PHP)" -ForegroundColor White
        Write-Host "  2. Descarga PHP desde: https://windows.php.net/download/" -ForegroundColor White
        Write-Host "  3. Agrega PHP al PATH del sistema" -ForegroundColor White
        Write-Host ""
        pause
        exit 1
    }
}

Write-Host ""
Write-Host "🚀 Ejecutando migración..." -ForegroundColor Cyan
Write-Host ""

# Cambiar al directorio database
$scriptPath = Join-Path $currentPath "database"
if (Test-Path $scriptPath) {
    Set-Location $scriptPath
    Write-Host "📂 Cambiando a: $scriptPath" -ForegroundColor Yellow
} else {
    Write-Host "❌ No se encontró el directorio 'database'" -ForegroundColor Red
    Write-Host "   Asegúrate de ejecutar este script desde: c:\laragon\www\angelow" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

Write-Host ""

# Ejecutar el script PHP
try {
    & $phpPath "run_fix_procedures.php"
    $exitCode = $LASTEXITCODE
    
    Write-Host ""
    
    if ($exitCode -eq 0) {
        Write-Host "============================================" -ForegroundColor Green
        Write-Host "  ✅ MIGRACIÓN COMPLETADA CON ÉXITO  " -ForegroundColor Green
        Write-Host "============================================" -ForegroundColor Green
    } else {
        Write-Host "============================================" -ForegroundColor Red
        Write-Host "  ❌ ERROR EN LA MIGRACIÓN  " -ForegroundColor Red
        Write-Host "============================================" -ForegroundColor Red
    }
    
} catch {
    Write-Host ""
    Write-Host "❌ Error al ejecutar el script: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "Presiona cualquier tecla para salir..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Volver al directorio original
Set-Location $currentPath
