@echo off
chcp 65001 >nul
title Corrección de Procedimientos - AngelOW
color 0B

echo.
echo ============================================
echo   CORRECCIÓN DE PROCEDIMIENTOS - ANGELOW  
echo ============================================
echo.

REM Obtener el directorio actual
set "CURRENT_DIR=%cd%"
echo 📁 Directorio actual: %CURRENT_DIR%
echo.

REM Buscar PHP en Laragon
set "PHP_PATH=php"

if exist "C:\laragon\bin\php\php-8.2.12\php.exe" (
    set "PHP_PATH=C:\laragon\bin\php\php-8.2.12\php.exe"
    echo ✅ PHP encontrado en Laragon 8.2.12
    goto :found_php
)

if exist "C:\laragon\bin\php\php-8.1.10\php.exe" (
    set "PHP_PATH=C:\laragon\bin\php\php-8.1.10\php.exe"
    echo ✅ PHP encontrado en Laragon 8.1.10
    goto :found_php
)

if exist "C:\laragon\bin\php\php-8.3.0\php.exe" (
    set "PHP_PATH=C:\laragon\bin\php\php-8.3.0\php.exe"
    echo ✅ PHP encontrado en Laragon 8.3.0
    goto :found_php
)

REM Verificar si php está en el PATH
where php >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP encontrado en PATH del sistema
    goto :found_php
)

REM No se encontró PHP
color 0C
echo.
echo ❌ No se encontró PHP
echo.
echo Por favor, instala PHP o verifica la instalación de Laragon
echo.
pause
exit /b 1

:found_php
echo.

REM Verificar que existe el directorio database
if not exist "database" (
    color 0C
    echo ❌ No se encontró el directorio 'database'
    echo.
    echo Asegúrate de ejecutar este script desde: c:\laragon\www\angelow
    echo.
    pause
    exit /b 1
)

echo 🚀 Ejecutando migración...
echo.
echo ============================================
echo.

REM Cambiar al directorio database y ejecutar
cd database
"%PHP_PATH%" run_fix_procedures.php

set "EXIT_CODE=%errorlevel%"

cd ..

echo.
if %EXIT_CODE% equ 0 (
    color 0A
    echo ============================================
    echo   ✅ MIGRACIÓN COMPLETADA CON ÉXITO  
    echo ============================================
) else (
    color 0C
    echo ============================================
    echo   ❌ ERROR EN LA MIGRACIÓN  
    echo ============================================
)

echo.
echo Presiona cualquier tecla para salir...
pause >nul
