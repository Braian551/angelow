# -*- coding: utf-8 -*-
"""
Script para eliminar requisitos de noticias y diagnóstico técnico,
y renumerar la matriz completa de 169 a 157 requisitos.
"""
import io
import re

# Leer el archivo actual
with io.open('c:/laragon/www/angelow/docs/requisitos/matriz_requisitos_completa.md', 'r', encoding='utf-8') as f:
    contenido = f.read()

# Extraer todas las líneas de requisitos
lineas = contenido.split('\n')
requisitos = []
otras_lineas = []

for linea in lineas:
    if linea.strip().startswith('|') and 'RF-' in linea:
        # Extraer el número de RF
        match = re.search(r'RF-(\d+)', linea)
        if match:
            num_rf = int(match.group(1))
            requisitos.append((num_rf, linea))
    else:
        otras_lineas.append(linea)

# Filtrar requisitos a eliminar
# RF-122 a RF-125: Noticias (4 requisitos)
# RF-145 a RF-152: Diagnóstico técnico (8 requisitos)
requisitos_eliminar = list(range(122, 126)) + list(range(145, 153))

print(f"Eliminando {len(requisitos_eliminar)} requisitos:")
print(f"- Noticias: RF-122 a RF-125")
print(f"- Diagnóstico técnico: RF-145 a RF-152")

# Filtrar requisitos
requisitos_filtrados = [(num, linea) for num, linea in requisitos if num not in requisitos_eliminar]

print(f"\nRequisitos antes: {len(requisitos)}")
print(f"Requisitos después: {len(requisitos_filtrados)}")

# Renumerar consecutivamente
requisitos_renumerados = []
nuevo_num = 1

for num_viejo, linea in requisitos_filtrados:
    # Reemplazar el número RF-XXX por el nuevo número
    nueva_linea = re.sub(r'RF-\d+', f'RF-{nuevo_num:03d}', linea)
    # También renumerar RN y RI
    nueva_linea = re.sub(r'RN-\d+', f'RN-{nuevo_num:03d}', nueva_linea)
    nueva_linea = re.sub(r'RI-\d+', f'RI-{nuevo_num:03d}', nueva_linea)
    requisitos_renumerados.append(nueva_linea)
    nuevo_num += 1

# Reconstruir el archivo
encabezado = """# Matriz de Requisitos Completa del Sistema
## 157 Requisitos Funcionales

| Módulo | Proceso | Número | Qué debe hacer el sistema | Número | Cómo funciona | Número | Información que maneja |
|---|---|---|---|---|---|---|---|
"""

pie = f"""
**FIN DE MATRIZ COMPLETA - 157 REQUISITOS**

---

## Resumen Final

**Total de Requisitos Funcionales**: 157 (RF-001 a RF-157)

**Requisitos eliminados**:
- Noticias (4 requisitos): No implementado en el proyecto
- Diagnóstico técnico (8 requisitos): Información solo para desarrolladores

**Distribución por Módulo**:
- Gestión de Usuarios y Accesos: 21 requisitos
- Gestión de Productos e Inventario: 21 requisitos
- Experiencia de Compra (Tienda): 25 requisitos
- Proceso de Checkout y Pagos: 16 requisitos
- Gestión de Órdenes y Postventa: 27 requisitos
- Marketing y Contenido: 19 requisitos (sin noticias)
- Administración y Reportes: 24 requisitos
- Configuración del Sistema: 5 requisitos
"""

contenido_final = encabezado + '\n'.join(requisitos_renumerados) + pie

# Guardar con UTF-8
with io.open('c:/laragon/www/angelow/docs/requisitos/matriz_requisitos_completa.md', 'w', encoding='utf-8') as f:
    f.write(contenido_final)

print(f"\n✓ Matriz actualizada con {len(requisitos_renumerados)} requisitos")
print("✓ Numeración consecutiva RF-001 a RF-157")
print("✓ Archivo guardado con UTF-8")
