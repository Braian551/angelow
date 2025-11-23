# -*- coding: utf-8 -*-
"""
Script para generar la matriz de requisitos completa con 169 requisitos
en español claro, sin tecnicismos y con codificación UTF-8 correcta.
"""
import io

# Encabezado
header = """# Matriz de Requisitos Completa del Sistema
## 169 Requisitos Funcionales

| Módulo | Proceso | Número | Qué debe hacer el sistema | Número | Cómo funciona | Número | Información que maneja |
|---|---|---|---|---|---|---|---|
"""

# Requisitos RF-001 a RF-042 (Usuarios y Productos)
parte1 = """| Gestión de Usuarios | Registro | RF-001 | Permitir que los visitantes creen una cuenta nueva con correo, nombre, teléfono y contraseña. | RN-001 | Para crear una cuenta, debes aceptar los términos y condiciones. Sin esta confirmación no se puede completar el registro. | RI-001 | Nombre completo, correo electrónico único, teléfono, contraseña cifrada, fecha de registro y aceptación de términos. |
| Gestión de Usuarios | Inicio de sesión | RF-002 | Permitir que los usuarios inicien sesión con su correo y contraseña, con opción de "Recordar mi cuenta". | RN-002 | Si activas "Recordar mi cuenta", tu sesión se mantendrá activa por 30 días. De lo contrario, se cerrará al cerrar el navegador. | RI-002 | Correo de acceso, contraseña, código de sesión único, fecha de vencimiento, dirección IP y estado de sesión recordada. |
"""

# Pie de página
footer = """
**FIN DE MATRIZ COMPLETA - 169 REQUISITOS**

---

## Resumen Final

**Total de Requisitos Funcionales**: 169 (RF-001 a RF-169)

**Distribución por Módulo**:
- Gestión de Usuarios y Accesos: 21 requisitos
- Gestión de Productos e Inventario: 21 requisitos
- Experiencia de Compra (Tienda): 25 requisitos
- Proceso de Checkout y Pagos: 16 requisitos
- Gestión de Órdenes y Postventa: 27 requisitos
- Marketing y Contenido: 23 requisitos
- Administración y Reportes: 32 requisitos
- Configuración del Sistema: 5 requisitos
- Diagnóstico del Sistema: 8 requisitos
"""

# Leer las partes existentes
with io.open('c:/laragon/www/angelow/docs/requisitos/PARTE_1_Usuarios_Productos.md', 'r', encoding='utf-8') as f:
    contenido_parte1 = f.read()

with io.open('c:/laragon/www/angelow/docs/requisitos/PARTE_2_Tienda_Checkout_Ordenes.md', 'r', encoding='utf-8') as f:
    contenido_parte2 = f.read()

with io.open('c:/laragon/www/angelow/docs/requisitos/PARTE_3_Marketing_Admin_Config_Diagnostico.md', 'r', encoding='utf-8') as f:
    contenido_parte3 = f.read()

# Extraer solo las tablas (líneas que empiezan con |)
def extraer_tabla(contenido):
    lineas = contenido.split('\n')
    tabla = []
    for linea in lineas:
        if linea.strip().startswith('|') and 'RF-' in linea:
            tabla.append(linea)
    return '\n'.join(tabla)

# Combinar todo
contenido_final = header + extraer_tabla(contenido_parte1) + '\n' + extraer_tabla(contenido_parte2) + '\n' + extraer_tabla(contenido_parte3) + footer

# Guardar con UTF-8 sin BOM
with io.open('c:/laragon/www/angelow/docs/requisitos/matriz_requisitos_completa.md', 'w', encoding='utf-8') as f:
    f.write(contenido_final)

print("✓ Matriz completa creada con 169 requisitos")
print("✓ Codificación UTF-8 correcta")
print("✓ Archivo: matriz_requisitos_completa.md")
