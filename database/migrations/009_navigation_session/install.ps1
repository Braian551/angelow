# =========================================================
# Script de Instalación: Sistema de Persistencia de Navegación
# Angelow Delivery System
# =========================================================

# Colores para salida
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Write-Success { Write-ColorOutput Green $args }
function Write-Error { Write-ColorOutput Red $args }
function Write-Warning { Write-ColorOutput Yellow $args }
function Write-Info { Write-ColorOutput Cyan $args }

# =========================================================
# CONFIGURACIÓN
# =========================================================

$PROJECT_PATH = "C:\laragon\www\angelow"
$DB_NAME = "angelow"
$DB_USER = "root"
$MIGRATION_PATH = "$PROJECT_PATH\database\migrations\009_navigation_session"
$BACKUP_PATH = "$PROJECT_PATH\database\backups"

# =========================================================
# BANNER
# =========================================================

Clear-Host
Write-Info "╔═══════════════════════════════════════════════════════════════╗"
Write-Info "║                                                               ║"
Write-Info "║   Sistema de Persistencia de Navegación - Angelow            ║"
Write-Info "║   Instalación Automatizada                                    ║"
Write-Info "║                                                               ║"
Write-Info "╚═══════════════════════════════════════════════════════════════╝"
Write-Host ""

# =========================================================
# VERIFICAR PRE-REQUISITOS
# =========================================================

Write-Info "🔍 Verificando pre-requisitos..."
Write-Host ""

# Verificar que existe el proyecto
if (-not (Test-Path $PROJECT_PATH)) {
    Write-Error "❌ ERROR: No se encuentra el proyecto en $PROJECT_PATH"
    Write-Host ""
    Write-Warning "Edita la variable `$PROJECT_PATH en este script con la ruta correcta."
    exit 1
}

Write-Success "✅ Proyecto encontrado: $PROJECT_PATH"

# Verificar MySQL
try {
    $mysqlVersion = mysql --version
    Write-Success "✅ MySQL está disponible: $mysqlVersion"
} catch {
    Write-Error "❌ ERROR: MySQL no está disponible en el PATH"
    Write-Host ""
    Write-Warning "Asegúrate de que Laragon esté corriendo y MySQL esté en el PATH."
    exit 1
}

# Verificar PHP
try {
    $phpVersion = php --version | Select-Object -First 1
    Write-Success "✅ PHP está disponible: $phpVersion"
} catch {
    Write-Error "❌ ERROR: PHP no está disponible en el PATH"
    exit 1
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# SOLICITAR CONFIRMACIÓN
# =========================================================

Write-Warning "⚠️  Este script realizará las siguientes acciones:"
Write-Host ""
Write-Host "  1. Hacer backup de la base de datos"
Write-Host "  2. Ejecutar verificación pre-migración"
Write-Host "  3. Aplicar migración SQL"
Write-Host "  4. Ejecutar tests"
Write-Host "  5. Verificar instalación"
Write-Host ""

$confirm = Read-Host "¿Deseas continuar? (S/N)"

if ($confirm -ne "S" -and $confirm -ne "s") {
    Write-Warning "Instalación cancelada por el usuario."
    exit 0
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# SOLICITAR CONTRASEÑA DE MySQL
# =========================================================

Write-Info "🔐 Ingresa la contraseña de MySQL (usuario: $DB_USER)"
$DB_PASS = Read-Host "Contraseña" -AsSecureString
$DB_PASS_TEXT = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($DB_PASS)
)

Write-Host ""

# Probar conexión
Write-Info "🔌 Probando conexión a MySQL..."
$testQuery = "SELECT 1;"
$testResult = $testQuery | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Error "❌ ERROR: No se pudo conectar a MySQL"
    Write-Error $testResult
    exit 1
}

Write-Success "✅ Conexión exitosa a la base de datos '$DB_NAME'"
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# PASO 1: BACKUP
# =========================================================

Write-Info "💾 PASO 1: Creando backup de la base de datos..."
Write-Host ""

# Crear carpeta de backups si no existe
if (-not (Test-Path $BACKUP_PATH)) {
    New-Item -ItemType Directory -Force -Path $BACKUP_PATH | Out-Null
    Write-Success "✅ Carpeta de backups creada: $BACKUP_PATH"
}

# Crear backup con fecha
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "$BACKUP_PATH\backup_antes_navegacion_$timestamp.sql"

Write-Info "📦 Generando backup: backup_antes_navegacion_$timestamp.sql"

mysqldump -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME > $backupFile 2>&1

if ($LASTEXITCODE -eq 0) {
    $backupSize = (Get-Item $backupFile).Length / 1MB
    Write-Success "✅ Backup creado correctamente ($([math]::Round($backupSize, 2)) MB)"
    Write-Success "   Ubicación: $backupFile"
} else {
    Write-Error "❌ ERROR: No se pudo crear el backup"
    exit 1
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# PASO 2: VERIFICACIÓN PRE-MIGRACIÓN
# =========================================================

Write-Info "🔍 PASO 2: Ejecutando verificación pre-migración..."
Write-Host ""

$verifyScript = "$MIGRATION_PATH\002_verify_migration.sql"

if (-not (Test-Path $verifyScript)) {
    Write-Error "❌ ERROR: No se encuentra el script de verificación"
    Write-Error "   Ruta esperada: $verifyScript"
    exit 1
}

Write-Info "📋 Ejecutando verificaciones..."

Get-Content $verifyScript | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME 2>&1 | Out-Null

if ($LASTEXITCODE -eq 0) {
    Write-Success "✅ Verificación completada"
    Write-Info "   Revisa la salida para detectar posibles problemas"
} else {
    Write-Warning "⚠️  Hubo advertencias en la verificación"
    Write-Host ""
    $continue = Read-Host "¿Deseas continuar de todos modos? (S/N)"
    
    if ($continue -ne "S" -and $continue -ne "s") {
        Write-Warning "Instalación cancelada."
        exit 0
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# PASO 3: APLICAR MIGRACIÓN
# =========================================================

Write-Info "🎯 PASO 3: Aplicando migración..."
Write-Host ""

$migrationScript = "$MIGRATION_PATH\001_create_navigation_session.sql"

if (-not (Test-Path $migrationScript)) {
    Write-Error "❌ ERROR: No se encuentra el script de migración"
    Write-Error "   Ruta esperada: $migrationScript"
    exit 1
}

Write-Info "📄 Ejecutando migración: 001_create_navigation_session.sql"

Get-Content $migrationScript | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME 2>&1 | Out-Null

if ($LASTEXITCODE -eq 0) {
    Write-Success "✅ Migración aplicada correctamente"
} else {
    Write-Error "❌ ERROR: Fallo al aplicar la migración"
    Write-Host ""
    Write-Warning "Puedes restaurar el backup con:"
    Write-Warning "mysql -u $DB_USER -p $DB_NAME < $backupFile"
    exit 1
}

# Verificar que las tablas se crearon
Write-Info "🔍 Verificando tablas creadas..."

$checkTables = "SHOW TABLES LIKE 'delivery_navigation%';"
$tables = $checkTables | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME 2>&1

if ($tables -match "delivery_navigation_sessions" -and $tables -match "delivery_navigation_events") {
    Write-Success "✅ Tablas creadas correctamente:"
    Write-Success "   - delivery_navigation_sessions"
    Write-Success "   - delivery_navigation_events"
} else {
    Write-Error "❌ ERROR: Las tablas no se crearon correctamente"
    exit 1
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# PASO 4: EJECUTAR TESTS
# =========================================================

Write-Info "✔️  PASO 4: Ejecutando tests..."
Write-Host ""

$testScript = "$PROJECT_PATH\tests\delivery\test_navigation_session.php"

if (-not (Test-Path $testScript)) {
    Write-Warning "⚠️  No se encuentra el script de tests"
    Write-Warning "   Ruta esperada: $testScript"
    Write-Warning "   Saltando tests..."
} else {
    Write-Info "🧪 Ejecutando: test_navigation_session.php"
    Write-Host ""
    
    php $testScript
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Success "✅ Todos los tests pasaron correctamente"
    } else {
        Write-Host ""
        Write-Error "❌ Algunos tests fallaron"
        Write-Warning "Revisa los errores arriba"
        Write-Host ""
        $continue = Read-Host "¿Deseas continuar de todos modos? (S/N)"
        
        if ($continue -ne "S" -and $continue -ne "s") {
            Write-Warning "Instalación cancelada."
            exit 0
        }
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# PASO 5: VERIFICACIÓN FINAL
# =========================================================

Write-Info "🔍 PASO 5: Verificación final..."
Write-Host ""

# Contar registros
$countQuery = "SELECT COUNT(*) as total FROM delivery_navigation_sessions;"
$sessionCount = $countQuery | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME -s 2>&1

Write-Success "✅ Sistema instalado correctamente"
Write-Info "   - Sesiones en BD: $sessionCount"

# Verificar procedimientos
$procQuery = "SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '$DB_NAME' AND ROUTINE_NAME LIKE '%Navigation%';"
$procCount = $procQuery | mysql -u $DB_USER -p"$DB_PASS_TEXT" $DB_NAME -s 2>&1

Write-Info "   - Procedimientos: $procCount"

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════"
Write-Host ""

# =========================================================
# RESUMEN
# =========================================================

Write-Success "╔═══════════════════════════════════════════════════════════════╗"
Write-Success "║                                                               ║"
Write-Success "║   ✅ INSTALACIÓN COMPLETADA EXITOSAMENTE                      ║"
Write-Success "║                                                               ║"
Write-Success "╚═══════════════════════════════════════════════════════════════╝"

Write-Host ""
Write-Info "📦 Backup guardado en:"
Write-Host "   $backupFile"
Write-Host ""

Write-Info "📊 Para consultar el estado del sistema:"
Write-Host "   mysql -u $DB_USER -p $DB_NAME -e 'SELECT * FROM v_active_navigation_sessions;'"
Write-Host ""

Write-Info "📚 Documentación:"
Write-Host "   $PROJECT_PATH\docs\delivery\NAVEGACION_SESSION_PERSISTENCIA.md"
Write-Host ""

Write-Info "🚀 El sistema ya está listo para usar!"
Write-Host ""
Write-Warning "⚠️  No olvides probar el sistema con un delivery real."
Write-Host ""

# =========================================================
# FIN
# =========================================================

Write-Host "Presiona Enter para salir..."
$null = Read-Host
