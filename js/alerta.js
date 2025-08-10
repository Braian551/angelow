// alerta.js - Versión profesional completa y corregida
document.addEventListener('DOMContentLoaded', function() {
    // Verificación segura del overlay
    const alertOverlay = document.querySelector('.alert-overlay');
    const container = document.getElementById('container');
    
    if (alertOverlay && container) {
        container.classList.remove('right-panel-active');
    }

    // Función para mostrar alertas mejoradas
    window.showAlert = function(message, type = 'default', options = {}) {
        // Cerrar cualquier alerta previa
        closeAlert();
        
        // Crear elementos base
        const newAlertOverlay = document.createElement('div');
        newAlertOverlay.className = 'alert-overlay';
        
        const alertBox = document.createElement('div');
        alertBox.className = `alert-box ${type}`;
        
        // Mapeo de iconos según tipo
        const iconMap = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'i',
            default: 'i'
        };
        
        // Contenedor circular para el icono
        const iconContainer = document.createElement('div');
        iconContainer.className = 'alert-icon-container';
        
        const alertIcon = document.createElement('div');
        alertIcon.className = 'alert-icon';
        alertIcon.textContent = iconMap[type] || iconMap.default;
        
        iconContainer.appendChild(alertIcon);
        
        // Contenido del mensaje
        const messageP = document.createElement('div');
        messageP.className = 'alert-message';
        messageP.innerHTML = message;
        
        // Botón de cierre
        const closeButton = document.createElement('button');
        closeButton.className = 'alert-close';
        closeButton.innerHTML = '&times;';
        closeButton.setAttribute('aria-label', 'Cerrar alerta');
        
        // Contenedor de botones
        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'alert-buttons';
        
        // Botón principal
        const mainButton = document.createElement('button');
        mainButton.className = 'alert-button';
        mainButton.textContent = options.buttonText || 'OK';
        
        // Configurar acciones de botones
        if (options.onConfirm) {
            mainButton.addEventListener('click', function() {
                options.onConfirm();
                closeSpecificAlert(newAlertOverlay);
            });
        } else {
            mainButton.addEventListener('click', function() {
                closeSpecificAlert(newAlertOverlay);
            });
        }
        
        // Botón secundario si se proporciona
        if (options.secondaryButton) {
            const secondaryBtn = document.createElement('button');
            secondaryBtn.className = 'alert-button outline';
            secondaryBtn.textContent = options.secondaryButton.text;
            
            secondaryBtn.addEventListener('click', function() {
                if (options.secondaryButton.action) {
                    options.secondaryButton.action();
                }
                closeSpecificAlert(newAlertOverlay);
            });
            
            buttonsDiv.appendChild(secondaryBtn);
        }
        
        buttonsDiv.appendChild(mainButton);
        
        // Ensamblar la alerta
        alertBox.appendChild(iconContainer);
        alertBox.appendChild(messageP);
        alertBox.appendChild(buttonsDiv);
        alertBox.appendChild(closeButton);
        
        newAlertOverlay.appendChild(alertBox);
        document.body.appendChild(newAlertOverlay);
        
        // Activar animación
        setTimeout(() => {
            newAlertOverlay.classList.add('active');
        }, 50);
        
        // Función para cerrar esta alerta específica
        function closeSpecificAlert(overlay) {
            overlay.classList.remove('active');
            setTimeout(() => {
                if (document.body.contains(overlay)) {
                    document.body.removeChild(overlay);
                }
            }, 300);
        }
        
        // Event listeners
        closeButton.addEventListener('click', function() {
            closeSpecificAlert(newAlertOverlay);
        });
        
        // Cerrar al hacer clic fuera del contenido
        newAlertOverlay.addEventListener('click', function(e) {
            if (e.target === newAlertOverlay && options.closeOnOverlayClick !== false) {
                closeSpecificAlert(newAlertOverlay);
            }
        });
        
        // Retornar función para cerrar manualmente
        return function() {
            closeSpecificAlert(newAlertOverlay);
        };
    };

    // Función para cerrar alertas desde otros scripts
    window.closeAlert = function() {
        const alerts = document.querySelectorAll('.alert-overlay');
        alerts.forEach(alert => {
            alert.classList.remove('active');
            setTimeout(() => {
                if (document.body.contains(alert)) {
                    document.body.removeChild(alert);
                }
            }, 300);
        });
    };

    // Función para errores de registro (compatible con tu código actual)
    function setupRegisterErrors() {
        if (typeof registerError !== 'undefined' && registerError) {
            showAlert(registerError, 'error');
        }
    }

    // Inicialización
    setupRegisterErrors();
});