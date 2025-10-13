# 🚀 Instrucciones de Migración por Consola

## 📋 Opciones para Ejecutar la Migración

Tienes **3 opciones** para ejecutar la migración:

---

## ✅ Opción 1: Usando el archivo .BAT (Más Fácil)

### Pasos:
1. Abre el **Explorador de Windows**
2. Navega a: `c:\laragon\www\angelow`
3. **Doble clic** en el archivo: `ejecutar_migracion.bat`
4. Se abrirá una ventana de consola y ejecutará automáticamente la migración
5. Espera a ver el mensaje: **"✅ MIGRACIÓN COMPLETADA CON ÉXITO"**

**Ventajas:**
- ✅ No necesitas escribir comandos
- ✅ Busca automáticamente PHP en Laragon
- ✅ Interfaz con colores y emojis

---

## ✅ Opción 2: Usando PowerShell (Recomendado)

### Pasos:
1. Abre **PowerShell** en el directorio del proyecto:
   - Click derecho en la carpeta `c:\laragon\www\angelow`
   - Selecciona **"Abrir en Terminal"** o **"Open PowerShell window here"**

2. Ejecuta el script:
   ```powershell
   .\ejecutar_migracion.ps1
   ```

3. **Si aparece un error de permisos**, ejecuta primero:
   ```powershell
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   ```
   Luego vuelve a ejecutar:
   ```powershell
   .\ejecutar_migracion.ps1
   ```

**Ventajas:**
- ✅ Interfaz con colores
- ✅ Detección automática de PHP
- ✅ Mensajes claros de éxito/error

---

## ✅ Opción 3: Comando PHP Directo

### Pasos:
1. Abre **PowerShell** o **CMD** en: `c:\laragon\www\angelow`

2. Ejecuta:
   ```bash
   cd database
   php run_fix_procedures.php
   ```

3. O en una sola línea:
   ```bash
   php database/run_fix_procedures.php
   ```

**Ventajas:**
- ✅ Más rápido si ya tienes la terminal abierta
- ✅ Control directo

---

## 📊 ¿Qué Hace la Migración?

El script realiza las siguientes acciones:

### 1. ✅ Corrige los Procedimientos Almacenados
```
- AssignOrderToDriver       ✅
- DriverAcceptOrder         ✅
- DriverRejectOrder         ✅
- DriverStartTrip           ✅
- DriverMarkArrived         ✅
- CompleteDelivery          ✅
```

### 2. ✅ Elimina el Error de Parámetros
```
ANTES: CALL AssignOrderToDriver(?, ?, @result)  ❌ 3 parámetros
AHORA: CALL AssignOrderToDriver(?, ?)            ✅ 2 parámetros
```

### 3. ✅ Actualiza el Código PHP
- `delivery/delivery_actions.php` - Llamadas corregidas
- `delivery/dashboarddeli.php` - Redirección a navigation.php

---

## 🎯 Verificación de Éxito

Después de ejecutar la migración, deberías ver:

```
============================================
📊 VERIFICACIÓN DE PROCEDIMIENTOS
============================================

ℹ️  Procedimientos encontrados en la base de datos:

Procedimiento                       Tipo            Fecha Creación
---------------------------------------------------------------------------
AssignOrderToDriver                 PROCEDURE       2025-10-12 10:30:00
CompleteDelivery                    PROCEDURE       2025-10-12 10:30:00
DriverAcceptOrder                   PROCEDURE       2025-10-12 10:30:00
DriverMarkArrived                   PROCEDURE       2025-10-12 10:30:00
DriverRejectOrder                   PROCEDURE       2025-10-12 10:30:00
DriverStartTrip                     PROCEDURE       2025-10-12 10:30:00

============================================
✅ MIGRACIÓN COMPLETADA
============================================

✅ Consultas ejecutadas: 18
✅ Procedimientos activos: 6
✅ Procedimientos creados/actualizados: 6
```

---

## 🧪 Probar la Funcionalidad

Una vez completada la migración:

### 1. Inicia sesión como Delivery
```
URL: http://localhost/angelow/auth/login.php
Usuario: delivery@test.com (o tu usuario de delivery)
```

### 2. Ve al Dashboard
```
URL: http://localhost/angelow/delivery/dashboarddeli.php
```

### 3. Acepta una Orden
- Busca una orden disponible
- Haz clic en **"Aceptar"**

### 4. Inicia el Recorrido
- Haz clic en **"▶️ Iniciar Recorrido"**
- Verás una notificación de éxito
- **Serás redirigido automáticamente a la página de navegación GPS**

### 5. Verifica la Navegación
- Deberías ver el mapa
- Panel con información del pedido
- Botones de acción (He Llegado, etc.)

---

## 🐛 Solución de Problemas

### ❌ Problema: "No se encontró PHP"

**Solución:**
1. Verifica que Laragon esté instalado
2. O descarga PHP: https://windows.php.net/download/
3. Agrega PHP al PATH del sistema

### ❌ Problema: "No se puede ejecutar scripts"

**Solución para PowerShell:**
```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
```

### ❌ Problema: "Error de conexión a la base de datos"

**Solución:**
1. Verifica que MySQL esté ejecutándose en Laragon
2. Abre `config.php` y verifica las credenciales:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'angelow');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### ❌ Problema: "No se encontró el archivo SQL"

**Solución:**
Asegúrate de estar ejecutando desde el directorio correcto:
```bash
cd c:\laragon\www\angelow
```

---

## 📁 Archivos Creados

```
c:\laragon\www\angelow\
├── ejecutar_migracion.bat              ← Script Batch (doble clic)
├── ejecutar_migracion.ps1              ← Script PowerShell
├── INSTRUCCIONES_MIGRACION_CLI.md      ← Este archivo
├── SOLUCION_INICIAR_RECORRIDO.md       ← Documentación completa
└── database\
    ├── run_fix_procedures.php          ← Script PHP principal
    └── migrations\
        └── fix_procedures_parameters.sql  ← SQL de corrección
```

---

## 📝 Comandos Rápidos

### Windows (PowerShell):
```powershell
# Opción 1: Ejecutar el .bat
.\ejecutar_migracion.bat

# Opción 2: PowerShell script
.\ejecutar_migracion.ps1

# Opción 3: PHP directo
php database/run_fix_procedures.php
```

### CMD (Símbolo del sistema):
```batch
ejecutar_migracion.bat
```

---

## ✅ Checklist Final

Después de la migración, verifica:

- [ ] Script ejecutado sin errores
- [ ] 6 procedimientos creados/actualizados
- [ ] Dashboard de delivery carga correctamente
- [ ] Botón "Iniciar Recorrido" funciona
- [ ] Redirección a navigation.php exitosa
- [ ] Mapa de navegación se muestra correctamente
- [ ] No hay errores en la consola del navegador (F12)

---

## 🆘 ¿Necesitas Ayuda?

Si después de seguir todos estos pasos aún tienes problemas:

1. **Revisa los logs del script** - La consola muestra mensajes detallados
2. **Verifica MySQL** - Asegúrate de que esté ejecutándose
3. **Comprueba config.php** - Credenciales de base de datos correctas
4. **Revisa la consola del navegador** - F12 para ver errores JavaScript

---

**Fecha:** 2025-10-12  
**Versión:** 1.0  
**Sistema:** AngelOW - Delivery System  
**Tipo:** Migración CLI
