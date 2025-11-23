# Instrucciones para Consolidar la Matriz de Requisitos Completa

## Archivos Generados

Se han creado 3 archivos con la matriz completa de requisitos, todos con numeración correcta de RF-001 a RF-169:

1. **PARTE_1_Usuarios_Productos.md** - RF-001 a RF-042 (42 requisitos)
2. **PARTE_2_Tienda_Checkout_Ordenes.md** - RF-043 a RF-110 (68 requisitos)
3. **PARTE_3_Marketing_Admin_Config_Diagnostico.md** - RF-111 a RF-169 (59 requisitos)

## Cómo Consolidar

### Opción 1: Unir los 3 archivos manualmente

1. Abre `PARTE_1_Usuarios_Productos.md`
2. Copia todo el contenido de `PARTE_2_Tienda_Checkout_Ordenes.md` y pégalo al final de la Parte 1 (después de la línea "**FIN DE PARTE 1**")
3. Copia todo el contenido de `PARTE_3_Marketing_Admin_Config_Diagnostico.md` y pégalo al final
4. Guarda el archivo resultante como `matriz_requisitos_completa.md`

### Opción 2: Usar PowerShell para unir automáticamente

Ejecuta este comando en PowerShell desde la carpeta `docs/requisitos/`:

```powershell
Get-Content "PARTE_1_Usuarios_Productos.md", "PARTE_2_Tienda_Checkout_Ordenes.md", "PARTE_3_Marketing_Admin_Config_Diagnostico.md" | Set-Content "matriz_requisitos_completa.md" -Encoding UTF8
```

## Verificación

La matriz completa debe tener:
- **169 requisitos funcionales** numerados consecutivamente de RF-001 a RF-169
- **28 nuevos requisitos** integrados en sus posiciones lógicas:
  - RF-017 a RF-021: Autenticación y Seguridad (5)
  - RF-059 a RF-066: Reseñas y Preguntas Usuario (8)
  - RF-102 a RF-106: Reseñas y Preguntas Admin (5)
  - RF-140 a RF-144: Configuración del Sitio (5)
  - RF-145 a RF-152: Diagnóstico del Sistema (8)

## Estructura de la Matriz

Cada requisito tiene el formato:

| Módulo | Subprocesos | Nro. | Descripción (RF) | Nro. | Descripción (RN) | Nro. | Descripción (RI) |

Donde:
- **Nro. (RF)**: Número del Requisito Funcional
- **Descripción (RF)**: Qué debe hacer el sistema
- **Nro. (RN)**: Número de la Regla de Negocio
- **Descripción (RN)**: Cómo funciona y restricciones
- **Nro. (RI)**: Número del Requisito de Información
- **Descripción (RI)**: Qué datos se manejan

## Notas Importantes

- Todos los números están correctamente secuenciados
- No hay duplicados
- Los 28 nuevos requisitos están integrados en sus secciones lógicas
- El backup original se mantiene como `matriz_requisitos_completa_BACKUP_20251122_200240.md`
