@echo off
chcp 65001 > nul
title Migración 008 - Sistema de Entregas Angelow

echo =======================================
echo   MIGRACIÓN 008: Corrección de Flujo  
echo =======================================
echo.

REM Configuración
set DB_HOST=localhost
set DB_USER=root
set DB_PASS=
set DB_NAME=angelow
set MIGRATION_FILE=%~dp0database\migrations\008_fix_delivery_workflow.sql

REM Verificar que el archivo existe
if not exist "%MIGRATION_FILE%" (
    echo ❌ Error: No se encuentra el archivo de migración
    echo    %MIGRATION_FILE%
    echo.
    pause
    exit /b 1
)

echo ✓ Archivo de migración encontrado
echo.

echo Esta migración realizará los siguientes cambios:
echo   1. Corregir tipo de dato de driver_id (VARCHAR -^> INT^)
echo   2. Agregar campos de coordenadas de destino
echo   3. Agregar campos de ubicación actual del transportista
echo   4. Eliminar restricción UNIQUE de order_id
echo   5. Reconfigurar foreign keys correctamente
echo   6. Actualizar procedimientos almacenados
echo   7. Actualizar coordenadas de destino para órdenes existentes
echo.
echo Base de datos: %DB_NAME%
echo.

set /p CONFIRM="¿Deseas continuar? (S/N): "
if /i not "%CONFIRM%"=="S" (
    echo ❌ Migración cancelada por el usuario
    echo.
    pause
    exit /b 0
)

echo.
echo 🔄 Ejecutando migración...
echo.

REM Ruta de MySQL en Laragon
set MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe

REM Verificar si existe
if not exist "%MYSQL_PATH%" (
    echo ⚠️  MySQL no encontrado en la ruta por defecto de Laragon
    echo    Intentando usar mysql desde PATH...
    set MYSQL_PATH=mysql
)

REM Ejecutar migración
if "%DB_PASS%"=="" (
    "%MYSQL_PATH%" -h%DB_HOST% -u%DB_USER% %DB_NAME% < "%MIGRATION_FILE%"
) else (
    "%MYSQL_PATH%" -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < "%MIGRATION_FILE%"
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ ¡Migración ejecutada exitosamente!
    echo.
    echo Cambios aplicados:
    echo   ✓ Estructura de order_deliveries corregida
    echo   ✓ Campos de ubicación agregados
    echo   ✓ Foreign keys actualizadas
    echo   ✓ Procedimientos almacenados actualizados
    echo.
    echo Próximos pasos:
    echo   1. Actualizar coordenadas reales de destino (actualmente en Bogotá^)
    echo   2. Probar el flujo completo de asignación -^> aceptar -^> iniciar recorrido
    echo   3. Verificar que la navegación funciona correctamente
    echo.
) else (
    echo.
    echo ❌ Error al ejecutar la migración
    echo.
    echo Revisa los errores anteriores para más información
    echo.
)

pause
