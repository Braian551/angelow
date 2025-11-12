/**
 * Sistema de Alertas para Usuarios
 * Maneja alertas modernas y elegantes para la sección de usuarios
 */

(function() {
    'use strict';

    // Objeto principal del sistema de alertas
    window.UserAlert = {
        
        /**
         * Configuración de iconos por tipo de alerta
         */
        icons: {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
            confirm: 'fas fa-question-circle'
        },

        /**
         * Títulos por defecto
         */
        defaultTitles: {
            success: '¡Éxito!',
            error: '¡Error!',
            warning: '¡Advertencia!',
            info: 'Información',
            confirm: '¿Estás seguro?'
        },

        /**
         * Muestra una alerta
         * @param {Object} options - Configuración de la alerta
         */
        show: function(options) {
            console.log('🟢 ALERTA: Mostrando alerta', options);
            const config = {
                type: options.type || 'info',
                title: options.title || this.defaultTitles[options.type || 'info'],
                message: options.message || '',
                confirmText: options.confirmText || 'Aceptar',
                cancelText: options.cancelText || 'Cancelar',
                showCancel: options.showCancel || false,
                onConfirm: options.onConfirm || null,
                onCancel: options.onCancel || null,
                closeOnOverlayClick: options.closeOnOverlayClick !== false,
             
                
            };

            this.render(config);
        },

        /**
         * Renderiza la alerta en el DOM
         */
        render: function(config) {
            console.log('🎨 ALERTA: Renderizando alerta', config);
            const overlay = document.getElementById('userAlertOverlay');
            if (!overlay) {
                console.error('❌ ALERTA: No se encontró el overlay');
                return;
            }

            const container = overlay.querySelector('.user-alert-container');
            const iconWrapper = overlay.querySelector('.user-alert-icon-wrapper');
            const icon = document.getElementById('userAlertIcon');
            const title = document.getElementById('userAlertTitle');
            const message = document.getElementById('userAlertMessage');
            const actions = document.getElementById('userAlertActions');

            if (!container || !icon || !title || !message || !actions) {
                console.error('❌ ALERTA: Elementos del DOM no encontrados');
                return;
            }

            // Aplicar tipo de alerta
            container.className = `user-alert-container ${config.type}`;

            // Configurar icono
            icon.className = `user-alert-icon ${this.icons[config.type]}`;

            // Configurar contenido
            title.textContent = config.title;
            message.textContent = config.message;

            // Limpiar acciones previas
            actions.innerHTML = '';

            // Botón de cancelar (si se requiere)
            if (config.showCancel) {
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'user-alert-btn user-alert-btn-cancel';
                cancelBtn.textContent = config.cancelText;
                cancelBtn.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🟣 ALERTA: Clic en botón cancelar');
                    if (config.onCancel) config.onCancel();
                    this.close();
                };
                actions.appendChild(cancelBtn);
            }

            // Botón principal
            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'user-alert-btn user-alert-btn-primary';
            confirmBtn.textContent = config.confirmText;
            confirmBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('🟡 ALERTA: Clic en botón confirmar');
                if (config.onConfirm) config.onConfirm();
                this.close();
            };
            actions.appendChild(confirmBtn);

            // Evento: cerrar al hacer clic en el overlay
            if (config.closeOnOverlayClick) {
                overlay.onclick = (e) => {
                    if (e.target === overlay) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('🟠 ALERTA: Clic en overlay');
                        this.close();
                    }
                };
            } else {
                overlay.onclick = null;
            }

            // Mostrar alerta
            overlay.style.display = 'flex';
            setTimeout(() => {
                overlay.classList.add('active');
                console.log('✅ ALERTA: Alerta mostrada completamente');
            }, 10);

            // Focus en el botón principal
            setTimeout(() => {
                confirmBtn.focus();
            }, 400);
        },

        /**
         * Cierra la alerta
         */
        close: function() {
            console.log('🔴 ALERTA: Cerrando alerta');
            const overlay = document.getElementById('userAlertOverlay');
            if (!overlay) {
                console.error('❌ ALERTA: No se encontró el overlay para cerrar');
                return;
            }

            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
                console.log('✅ ALERTA: Alerta cerrada completamente');
            }, 300);
        },

        /**
         * Alerta de éxito
         */
        success: function(message, options = {}) {
            this.show({
                type: 'success',
                message: message,
                ...options
            });
        },

        /**
         * Alerta de error
         */
        error: function(message, options = {}) {
            this.show({
                type: 'error',
                message: message,
                ...options
            });
        },

        /**
         * Alerta de advertencia
         */
        warning: function(message, options = {}) {
            this.show({
                type: 'warning',
                message: message,
                ...options
            });
        },

        /**
         * Alerta de información
         */
        info: function(message, options = {}) {
            this.show({
                type: 'info',
                message: message,
                ...options
            });
        },

        /**
         * Alerta de confirmación
         */
        confirm: function(message, onConfirm, options = {}) {
            this.show({
                type: 'confirm',
                message: message,
                showCancel: true,
                onConfirm: onConfirm,
                ...options
            });
        }
    };

    // Atajos globales para facilitar el uso
    window.showUserSuccess = function(message, options) {
        UserAlert.success(message, options);
    };

    window.showUserError = function(message, options) {
        UserAlert.error(message, options);
    };

    window.showUserWarning = function(message, options) {
        UserAlert.warning(message, options);
    };

    window.showUserInfo = function(message, options) {
        UserAlert.info(message, options);
    };

    window.showUserConfirm = function(message, onConfirm, options) {
        UserAlert.confirm(message, onConfirm, options);
    };

    // Soporte para ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('userAlertOverlay');
            if (overlay && overlay.classList.contains('active') && overlay.style.display !== 'none') {
                console.log('🔵 ALERTA: Cerrando por tecla ESC');
                UserAlert.close();
            }
        }
    });

    console.log('✅ Sistema de Alertas de Usuario cargado correctamente');
})();
