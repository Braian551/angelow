# -*- coding: utf-8 -*-
import codecs

# Contenido de la matriz en español sin tecnicismos
contenido = """# Matriz de Requisitos Completa del Sistema
## 169 Requisitos Funcionales

| Módulo | Proceso | Número | Qué debe hacer el sistema | Número | Cómo funciona | Número | Información que maneja |
|---|---|---|---|---|---|---|---|
| Gestión de Usuarios | Registro | RF-001 | Permitir que los visitantes creen una cuenta nueva con correo, nombre, teléfono y contraseña. | RN-001 | Para crear una cuenta, debes aceptar los términos y condiciones. Sin esta confirmación no se puede completar el registro. | RI-001 | Nombre completo, correo electrónico único, teléfono, contraseña cifrada, fecha de registro y aceptación de términos. |
| Gestión de Usuarios | Inicio de sesión | RF-002 | Permitir que los usuarios inicien sesión con su correo y contraseña, con opción de "Recordar mi cuenta". | RN-002 | Si activas "Recordar mi cuenta", tu sesión se mantendrá activa por 30 días. De lo contrario, se cerrará al cerrar el navegador. | RI-002 | Correo de acceso, contraseña, código de sesión único, fecha de vencimiento, dirección IP y estado de sesión recordada. |
"""

# Guardar con UTF-8
with codecs.open('c:/laragon/www/angelow/docs/requisitos/matriz_requisitos_completa.md', 'w', encoding='utf-8') as f:
    f.write(contenido)

print("Archivo creado con UTF-8")
