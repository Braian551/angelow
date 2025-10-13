# =====================================================
# Script para ejecutar la Migración 008
# Sistema de Entregas - Corrección de Flujo
# =====================================================

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "  MIGRACIÓN 008: Corrección de Flujo  " -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

# Configuración de la base de datos
$DB_HOST = "localhost"
$DB_USER = "root"
$DB_PASS = ""
$DB_NAME = "angelow"
$MIGRATION_FILE = "$PSScriptRoot\database\migrations\008_fix_delivery_workflow.sql"

# Verificar que el archivo de migración existe
if (-Not (Test-Path $MIGRATION_FILE)) {
    Write-Host "❌ Error: No se encuentra el archivo de migración:" -ForegroundColor Red
    Write-Host "   $MIGRATION_FILE" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Presiona cualquier tecla para salir..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

Write-Host "✓ Archivo de migración encontrado" -ForegroundColor Green
Write-Host ""

# Preguntar confirmación
Write-Host "Esta migración realizará los siguientes cambios:" -ForegroundColor Yellow
Write-Host "  1. Corregir tipo de dato de driver_id (VARCHAR -> INT)" -ForegroundColor White
Write-Host "  2. Agregar campos de coordenadas de destino" -ForegroundColor White
Write-Host "  3. Agregar campos de ubicación actual del transportista" -ForegroundColor White
Write-Host "  4. Eliminar restricción UNIQUE de order_id" -ForegroundColor White
Write-Host "  5. Reconfigurar foreign keys correctamente" -ForegroundColor White
Write-Host "  6. Actualizar procedimientos almacenados" -ForegroundColor White
Write-Host "  7. Actualizar coordenadas de destino para órdenes existentes" -ForegroundColor White
Write-Host ""
Write-Host "Base de datos: $DB_NAME" -ForegroundColor Cyan
Write-Host ""

$confirm = Read-Host "¿Deseas continuar? (S/N)"
if ($confirm -ne "S" -and $confirm -ne "s") {
    Write-Host "❌ Migración cancelada por el usuario" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Presiona cualquier tecla para salir..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 0
}

Write-Host ""
Write-Host "🔄 Ejecutando migración..." -ForegroundColor Yellow
Write-Host ""

# Construir comando mysql
$mysqlPath = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"

# Verificar si existe mysql
if (-Not (Test-Path $mysqlPath)) {
    Write-Host "⚠️  MySQL no encontrado en la ruta por defecto de Laragon" -ForegroundColor Yellow
    Write-Host "   Intentando encontrar MySQL en PATH..." -ForegroundColor Yellow
    $mysqlPath = "mysql"
}

# Ejecutar migración
try {
    $args = @(
        "-h$DB_HOST",
        "-u$DB_USER"
    )
    
    if ($DB_PASS -ne "") {
        $args += "-p$DB_PASS"
    }
    
    $args += @(
        $DB_NAME,
        "-e",
        "source $MIGRATION_FILE"
    )
    
    $process = Start-Process -FilePath $mysqlPath -ArgumentList $args -NoNewWindow -Wait -PassThru
    
    if ($process.ExitCode -eq 0) {
        Write-Host ""
        Write-Host "✅ ¡Migración ejecutada exitosamente!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Cambios aplicados:" -ForegroundColor Cyan
        Write-Host "  ✓ Estructura de order_deliveries corregida" -ForegroundColor Green
        Write-Host "  ✓ Campos de ubicación agregados" -ForegroundColor Green
        Write-Host "  ✓ Foreign keys actualizadas" -ForegroundColor Green
        Write-Host "  ✓ Procedimientos almacenados actualizados" -ForegroundColor Green
        Write-Host ""
        Write-Host "Próximos pasos:" -ForegroundColor Yellow
        Write-Host "  1. Actualizar coordenadas reales de destino (actualmente en Bogotá)" -ForegroundColor White
        Write-Host "  2. Probar el flujo completo de asignación -> aceptar -> iniciar recorrido" -ForegroundColor White
        Write-Host "  3. Verificar que la navegación funciona correctamente" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "❌ Error al ejecutar la migración (código: $($process.ExitCode))" -ForegroundColor Red
        Write-Host ""
        Write-Host "Revisa los errores anteriores para más información" -ForegroundColor Yellow
        Write-Host ""
    }
} catch {
    Write-Host ""
    Write-Host "❌ Error al ejecutar la migración:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Sugerencias:" -ForegroundColor Cyan
    Write-Host "  - Verifica que MySQL esté corriendo" -ForegroundColor White
    Write-Host "  - Verifica las credenciales de la base de datos" -ForegroundColor White
    Write-Host "  - Verifica que la base de datos '$DB_NAME' existe" -ForegroundColor White
    Write-Host ""
}

Write-Host "Presiona cualquier tecla para salir..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
