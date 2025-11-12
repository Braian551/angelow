# Sistema de Alertas para Usuarios

Sistema de alertas moderno y reutilizable para la sección de usuarios de Angelow. Diseño elegante con gradientes y animaciones suaves.

## 📁 Estructura de Archivos

```
users/
  └── alertas/
      └── alert_user.php       # Componente HTML de alerta

css/
  └── user/
      └── alert_user.css       # Estilos del sistema de alertas

js/
  └── user/
      └── alert_user.js        # Lógica y funciones del sistema
```

## 🚀 Instalación

### 1. Incluir el sistema en tu página

Agrega esta línea después de la apertura del tag `<body>`:

```php
<?php require_once __DIR__ . '/alertas/alert_user.php'; ?>
```

**Nota:** El archivo PHP ya incluye automáticamente los CSS y JS necesarios.

## 💡 Uso

### Tipos de Alertas

#### 1. **Alerta de Éxito** (Success)
```javascript
showUserSuccess('Producto agregado a la lista de deseos');
```

#### 2. **Alerta de Error** (Error)
```javascript
showUserError('No se pudo procesar tu solicitud');
```

#### 3. **Alerta de Advertencia** (Warning)
```javascript
showUserWarning('Tu sesión está por expirar');
```

#### 4. **Alerta de Información** (Info)
```javascript
showUserInfo('Tienes 3 notificaciones pendientes');
```

#### 5. **Alerta de Confirmación** (Confirm)
```javascript
showUserConfirm(
    '¿Estás seguro de eliminar este producto?',
    function() {
        // Código a ejecutar si el usuario confirma
        console.log('Producto eliminado');
    },
    {
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar'
    }
);
```

### Opciones Avanzadas

Todas las funciones aceptan opciones adicionales:

```javascript
showUserSuccess('Operación exitosa', {
    title: 'Título personalizado',
    confirmText: 'Entendido',
    onConfirm: function() {
        // Código al cerrar
        location.reload();
    },
    closeOnOverlayClick: false  // Deshabilitar cierre al hacer clic fuera
});
```

### Uso del Objeto Principal

También puedes usar el objeto `UserAlert` directamente:

```javascript
// Alerta personalizada
UserAlert.show({
    type: 'success',
    title: '¡Genial!',
    message: 'Tu operación fue exitosa',
    confirmText: 'OK',
    showCancel: false,
    onConfirm: function() {
        console.log('Usuario confirmó');
    }
});

// Cerrar alerta manualmente
UserAlert.close();
```

## 🎨 Tipos de Alertas y Colores

| Tipo | Color | Icono | Uso |
|------|-------|-------|-----|
| `success` | #4bb543 (Verde proyecto) | ✓ | Operaciones exitosas |
| `error` | #ff3333 (Rojo proyecto) | ✕ | Errores y fallos |
| `warning` | #ffcc00 (Amarillo proyecto) | ⚠ | Advertencias |
| `info` | #0077b6 (Azul principal) | i | Información general |
| `confirm` | #48cae4 (Azul secundario) | ? | Confirmaciones |

## 📋 Ejemplos Prácticos

### Ejemplo 1: Eliminar Producto de Wishlist
```javascript
document.querySelector('.delete-btn').addEventListener('click', function() {
    showUserConfirm(
        '¿Deseas eliminar este producto de tu lista?',
        function() {
            // Llamada API para eliminar
            fetch('/api/wishlist/remove', {
                method: 'POST',
                body: JSON.stringify({ productId: 123 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUserSuccess('Producto eliminado correctamente', {
                        onConfirm: function() {
                            location.reload(); // Recarga inmediata al hacer clic en Aceptar
                        }
                    });
                } else {
                    showUserError('Error al eliminar el producto');
                }
            });
        },
        {
            confirmText: 'Sí, eliminar',
            cancelText: 'No, mantener'
        }
    );
});
```

### Ejemplo 2: Validación de Formulario
```javascript
function validateForm() {
    const email = document.getElementById('email').value;
    
    if (!email) {
        showUserWarning('Por favor ingresa tu correo electrónico');
        return false;
    }
    
    if (!isValidEmail(email)) {
        showUserError('El correo electrónico no es válido');
        return false;
    }
    
    showUserSuccess('Formulario válido, enviando...');
    return true;
}
```

### Ejemplo 3: Notificación con Redirección
```javascript
showUserSuccess('Tu cuenta ha sido creada exitosamente', {
    confirmText: 'Ir al inicio',
    onConfirm: function() {
        window.location.href = '/dashboard';
    }
});
```

## ⌨️ Atajos de Teclado

- **ESC**: Cierra la alerta actual

## 🎯 Características

- ✅ Diseño moderno con **colores sólidos del proyecto**
- ✅ Animaciones suaves
- ✅ Responsive (móvil y escritorio)
- ✅ Iconos FontAwesome
- ✅ Confirmaciones con botón de cancelar
- ✅ Callback personalizado
- ✅ Cierre con ESC
- ✅ Overlay con blur
- ✅ 5 tipos de alertas diferentes
- ✅ Completamente personalizable

## 🔧 Personalización

### Colores del Proyecto

El sistema usa automáticamente los colores definidos en `css/style.css`:

- **Success**: `var(--success-color)` = #4bb543 (Verde)
- **Error**: `var(--error-color)` = #ff3333 (Rojo)
- **Warning**: `var(--warning-color)` = #ffcc00 (Amarillo)
- **Info**: `var(--primary-color)` = #0077b6 (Azul principal)
- **Confirm**: `var(--secondary-color)` = #48cae4 (Azul secundario)
- **Botón principal**: `var(--primary-color)` = #0077b6

### Modificar Colores (Opcional)

Si necesitas cambiar los colores, edita las variables CSS en `css/style.css`:

```css
:root {
  --success-color: #tu-verde-personalizado;
  --error-color: #tu-rojo-personalizado;
  --warning-color: #tu-amarillo-personalizado;
  --primary-color: #tu-azul-principal;
  --secondary-color: #tu-azul-secundario;
}
```

### Modificar Iconos

Edita el archivo `js/user/alert_user.js`:

```javascript
icons: {
    success: 'fas fa-check-circle',  // Cambia a tu icono
    error: 'fas fa-times-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle',
    confirm: 'fas fa-question-circle'
}
```

## ⚠️ Notas Importantes

1. **FontAwesome requerido**: El sistema usa iconos de FontAwesome. Asegúrate de tener la librería incluida.

2. **No usar las alertas de admin**: Este sistema es específico para usuarios. No mezcles con `alertas/alerta1.php`.

3. **Múltiples alertas**: Si necesitas mostrar varias alertas, la nueva reemplazará a la anterior.

4. **Auto-cierre**: Las alertas se cierran automáticamente después de 3 segundos, excepto las alertas de éxito con callback `onConfirm` que requieren interacción del usuario.

5. **Compatible con**: Chrome, Firefox, Safari, Edge (últimas versiones)

## 🐛 Solución de Problemas

### La alerta no aparece
- Verifica que `alert_user.php` esté incluido en el body
- Revisa la consola del navegador por errores
- Asegúrate de que los paths de CSS y JS sean correctos

### Los estilos no se aplican
- Limpia el caché del navegador
- Verifica que `alert_user.css` esté cargando correctamente
- Revisa conflictos con otros estilos

### Los iconos no aparecen
- Verifica que FontAwesome esté cargado
- Comprueba la versión de FontAwesome (debe ser 5.x o 6.x)

## 📝 Licencia

Este sistema es parte del proyecto Angelow y está diseñado exclusivamente para uso interno.

---

**Desarrollado para Angelow** - Sistema de E-commerce
