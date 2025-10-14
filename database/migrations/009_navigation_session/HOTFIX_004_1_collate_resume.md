# HOTFIX #004.1 - Corrección COLLATE en Resume

**Fecha:** 2025-10-13 20:45  
**Módulo:** navigation_api.php - resume_navigation  
**Severidad:** CRÍTICO - Impedía reanudar navegación  

---

## 🔴 ERROR

```
SQLSTATE[42000]: Syntax error or access violation: 1253 
COLLATION 'utf8mb4_general_ci' is not valid for CHARACTER SET 'utf8mb3'
```

### Causa:
En el UPDATE de `resume_navigation`, usé:
```sql
AND driver_id = ? COLLATE utf8mb4_general_ci
```

Pero `COLLATE` NO se puede usar directamente con placeholders `?` en PDO preparado.

---

## ✅ SOLUCIÓN

**Archivo:** `delivery/api/navigation_api.php` (línea ~488)

**ANTES (❌ Error):**
```sql
WHERE delivery_id = ? 
AND driver_id = ? COLLATE utf8mb4_general_ci
AND session_status = 'paused'
```

**DESPUÉS (✅ Correcto):**
```sql
WHERE delivery_id = ? 
AND driver_id = CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci
AND session_status = 'paused'
```

**Explicación:**
- `CONVERT(? USING utf8mb4)` convierte el parámetro a utf8mb4
- Luego aplica `COLLATE utf8mb4_general_ci`
- Esto evita el error de charset incompatible

---

## 🧪 PRUEBA

```powershell
# 1. Pausar navegación
# 2. Hacer clic en "Reanudar"
# 3. NO debe mostrar error 400
# 4. Debe reanudar correctamente

# Verificar en BD:
mysql -u root angelow -e "SELECT session_status, navigation_resumed_at FROM delivery_navigation_sessions WHERE delivery_id = 9;"
# Debe mostrar: navigating, (timestamp)
```

---

**STATUS:** ✅ APLICADO  
**Tiempo:** 5 minutos  
**Hotfixes totales:** 4 + 1 micro-fix
