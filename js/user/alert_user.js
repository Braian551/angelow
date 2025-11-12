// ============================================
// SISTEMA DE ALERTAS PARA USUARIOS - WISHLIST
// ============================================

class UserAlertSystem {
    constructor() {
        this.overlay = document.getElementById('userAlertOverlay');
        this.iconElement = document.getElementById('userAlertIcon');
        this.titleElement = document.getElementById('userAlertTitle');
        this.messageElement = document.getElementById('userAlertMessage');
        this.actionsContainer = document.getElementById('userAlertActions');
        this.countdownElement = document.getElementById('userAlertCountdown');
        this.countdownNumber = document.getElementById('alertCountdown');
        this.currentTimeout = null;
        this.countdownInterval = null;
        
        this.init();
    }

    init() {
        // Cerrar al hacer click fuera del modal
        this.overlay?.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay?.style.display !== 'none') {
                this.close();
            }
        });
    }

    // Función auxiliar para convertir hex a rgb
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? 
            `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : 
            '0, 0, 0';
    }

    show(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            actions = [],
            autoClose = null,
            onClose = null
        } = options;

        // Limpiar timeouts anteriores
        this.clearTimeouts();

        // Configurar icono según tipo con mejores iconos y animaciones
        const iconConfig = {
            success: { 
                icon: 'fas fa-check-circle', 
                color: '#ffffff', 
                bgColor: '#4bb543',
                animation: 'bounce-in-success'
            },
            error: { 
                icon: 'fas fa-times-circle', 
                color: '#ffffff', 
                bgColor: '#ff3333',
                animation: 'shake-error'
            },
            warning: { 
                icon: 'fas fa-exclamation-triangle', 
                color: '#ffffff', 
                bgColor: '#ff9900',
                animation: 'bounce-in-warning'
            },
            info: { 
                icon: 'fas fa-info-circle', 
                color: '#ffffff', 
                bgColor: '#0077b6',
                animation: 'bounce-in-info'
            },
            question: { 
                icon: 'fas fa-question-circle', 
                color: '#ffffff', 
                bgColor: '#9333ea',
                animation: 'bounce-in-question'
            }
        };

        const config = iconConfig[type] || iconConfig.info;
        
        // Aplicar tipo al contenedor
        this.overlay.querySelector('.user-alert-container').className = `user-alert-container ${type}`;
        
        // Aplicar icono con mejor configuración
        this.iconElement.className = `user-alert-icon ${config.icon}`;
        this.iconElement.style.color = config.color;
        this.iconElement.style.animation = `${config.animation} 0.6s ease-out`;
        
        // Aplicar color de fondo al wrapper del icono
        const iconWrapper = this.overlay.querySelector('.user-alert-icon-wrapper');
        if (iconWrapper) {
            iconWrapper.style.background = config.bgColor;
            iconWrapper.style.boxShadow = `0 8px 25px rgba(${this.hexToRgb(config.bgColor)}, 0.4)`;
        }

        // Aplicar contenido
        this.titleElement.textContent = title;
        this.messageElement.textContent = message;

        // Limpiar y crear acciones
        this.actionsContainer.innerHTML = '';
        
        if (actions.length > 0) {
            actions.forEach(action => {
                const button = document.createElement('button');
                button.className = `user-alert-btn ${action.type || 'secondary'}`;
                button.textContent = action.text;
                
                if (action.icon) {
                    const icon = document.createElement('i');
                    icon.className = action.icon;
                    button.prepend(icon);
                    button.innerHTML = icon.outerHTML + ' ' + action.text;
                }
                
                button.addEventListener('click', () => {
                    if (action.callback) {
                        action.callback();
                    }
                    if (action.closeOnClick !== false) {
                        this.close();
                    }
                });
                
                this.actionsContainer.appendChild(button);
            });
        } else {
            // Botón por defecto
            const defaultBtn = document.createElement('button');
            defaultBtn.className = 'user-alert-btn primary';
            defaultBtn.innerHTML = '<i class="fas fa-check"></i> Entendido';
            defaultBtn.addEventListener('click', () => this.close());
            this.actionsContainer.appendChild(defaultBtn);
        }

        // Mostrar overlay
        this.overlay.style.display = 'flex';
        
        // Animación de entrada
        setTimeout(() => {
            this.overlay.classList.add('active');
        }, 10);

        // Auto-cerrar si se especifica
        if (autoClose) {
            this.startCountdown(autoClose, onClose);
        }

        // Callback al cerrar
        this.onCloseCallback = onClose;
    }

    startCountdown(seconds, onComplete) {
        this.countdownElement.style.display = 'block';
        let remaining = seconds;
        
        this.countdownNumber.textContent = `(${remaining}s)`;
        
        this.countdownInterval = setInterval(() => {
            remaining--;
            this.countdownNumber.textContent = `(${remaining}s)`;
            
            if (remaining <= 0) {
                this.clearTimeouts();
                if (onComplete) {
                    onComplete();
                }
                this.close(true); // true = autoclose
            }
        }, 1000);
    }

    clearTimeouts() {
        if (this.currentTimeout) {
            clearTimeout(this.currentTimeout);
            this.currentTimeout = null;
        }
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
        if (this.countdownElement) {
            this.countdownElement.style.display = 'none';
        }
    }

    close(isAutoClose = false) {
        this.clearTimeouts();
        this.overlay?.classList.remove('active');
        
        setTimeout(() => {
            if (this.overlay) {
                this.overlay.style.display = 'none';
            }
            
            // Ejecutar callback si existe y no es auto-cierre
            if (!isAutoClose && this.onCloseCallback) {
                this.onCloseCallback();
                this.onCloseCallback = null;
            }
        }, 300);
    }
}

// ============================================
// SISTEMA DE NOTIFICACIONES TOAST
// ============================================

class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.maxNotifications = 3;
    }

    show(message, type = 'info', options = {}) {
        const {
            duration = 4000,
            clickable = false,
            onClick = null,
            icon = null
        } = options;

        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type} ${clickable ? 'clickable' : ''}`;
        
        // Configurar iconos por tipo
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const iconClass = icon || icons[type] || icons.info;
        
        notification.innerHTML = `
            <i class="${iconClass}"></i>
            <span>${message}</span>
        `;

        // Click handler si es clickable
        if (clickable && onClick) {
            notification.addEventListener('click', () => {
                onClick();
                this.remove(notification);
            });
        }

        // Agregar al DOM
        document.body.appendChild(notification);
        this.notifications.push(notification);

        // Limitar cantidad de notificaciones
        if (this.notifications.length > this.maxNotifications) {
            const oldest = this.notifications.shift();
            this.remove(oldest);
        }

        // Actualizar posiciones
        this.updatePositions();

        // Auto-remover
        if (duration > 0) {
            setTimeout(() => {
                this.remove(notification);
            }, duration);
        }

        return notification;
    }

    remove(notification) {
        notification.classList.add('fade-out');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
            this.updatePositions();
        }, 500);
    }

    updatePositions() {
        this.notifications.forEach((notif, index) => {
            notif.style.top = `${20 + (index * 90)}px`;
        });
    }
}

// ============================================
// GESTIÓN DE WISHLIST
// ============================================

class WishlistManager {
    constructor() {
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        this.alertSystem = new UserAlertSystem();
        this.notificationSystem = new NotificationSystem();
        
        console.log('🎯 WishlistManager: Inicializando...');
        console.log('📍 Base URL:', this.baseUrl);
        
        this.init();
    }

    init() {
        console.log('🔧 WishlistManager: Configurando event listeners...');
        
        // Event listeners para botones de wishlist
        const wishlistButtons = document.querySelectorAll('.wishlist-btn');
        console.log(`❤️ Botones de wishlist encontrados: ${wishlistButtons.length}`);
        
        wishlistButtons.forEach((btn, index) => {
            const productId = btn.dataset.productId;
            console.log(`  [${index + 1}] Botón para producto ID: ${productId}`);
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🖱️ Click en wishlist button, producto:', productId);
                
                const isActive = btn.classList.contains('active');
                console.log('  Estado actual:', isActive ? 'ACTIVO (en wishlist)' : 'INACTIVO (no en wishlist)');
                
                if (isActive) {
                    console.log('  ➡️ Acción: ELIMINAR de wishlist');
                    this.removeFromWishlist(productId, btn);
                } else {
                    console.log('  ➡️ Acción: AGREGAR a wishlist');
                    this.addToWishlist(productId, btn);
                }
            });
        });

        // Event listener para limpiar toda la lista
        const clearAllBtn = document.getElementById('clearAllWishlist');
        if (clearAllBtn) {
            console.log('🗑️ Botón "Limpiar todo" encontrado');
            clearAllBtn.addEventListener('click', () => {
                console.log('🖱️ Click en "Limpiar todo"');
                this.clearAllWishlist();
            });
        }

        // Cargar imágenes con lazy loading
        this.setupLazyLoading();
        
        console.log('✅ WishlistManager: Inicialización completa');
    }

    async addToWishlist(productId, button) {
        console.log('📤 addToWishlist: Iniciando...', { productId });
        
        try {
            // Optimistic UI update
            button.classList.add('active');
            console.log('  ✓ UI actualizada (optimistic)');
            
            const url = `${this.baseUrl}/ajax/wishlist/add.php`;
            console.log('  📡 Enviando petición a:', url);
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            });

            console.log('  📥 Respuesta recibida:', response.status);
            const data = await response.json();
            console.log('  📋 Datos:', data);

            if (data.success) {
                console.log('  ✅ Éxito! Mostrando notificación toast...');
                this.notificationSystem.show(
                    '¡Producto agregado a tu lista de deseos!',
                    'success',
                    {
                        duration: 3000,
                        clickable: true,
                        onClick: () => {
                            window.location.href = `${this.baseUrl}/users/wishlist.php`;
                        }
                    }
                );
            } else {
                console.log('  ❌ Error:', data.error);
                // Revertir si falla
                button.classList.remove('active');
                
                if (data.error === 'not_logged_in') {
                    console.log('  🔐 Usuario no logueado, mostrando alerta de login...');
                    this.showLoginAlert();
                } else {
                    this.notificationSystem.show(
                        data.message || 'Error al agregar a la lista de deseos',
                        'error'
                    );
                }
            }
        } catch (error) {
            console.error('  ❌ Error de conexión:', error);
            button.classList.remove('active');
            this.notificationSystem.show(
                'Error de conexión. Intenta nuevamente.',
                'error'
            );
        }
    }

    async removeFromWishlist(productId, button) {
        try {
            const response = await fetch(`${this.baseUrl}/ajax/wishlist/remove.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            });

            const data = await response.json();

            if (data.success) {
                // Si estamos en la página de wishlist, remover el card
                if (window.location.pathname.includes('wishlist.php')) {
                    const productCard = button.closest('.product-card');
                    if (productCard) {
                        productCard.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            productCard.remove();
                            this.updateWishlistCount();
                            this.checkIfEmpty();
                        }, 300);
                    }
                } else {
                    // Solo actualizar el botón en otras páginas
                    button.classList.remove('active');
                }

                this.notificationSystem.show(
                    'Producto eliminado de tu lista de deseos',
                    'info'
                );
            } else {
                this.notificationSystem.show(
                    data.message || 'Error al eliminar de la lista de deseos',
                    'error'
                );
            }
        } catch (error) {
            console.error('Error:', error);
            this.notificationSystem.show(
                'Error de conexión. Intenta nuevamente.',
                'error'
            );
        }
    }

    clearAllWishlist() {
        this.alertSystem.show({
            type: 'warning',
            title: '¿Estás seguro?',
            message: 'Se eliminarán todos los productos de tu lista de deseos. Esta acción no se puede deshacer.',
            actions: [
                {
                    text: 'Cancelar',
                    type: 'secondary',
                    icon: 'fas fa-times',
                    callback: () => {
                        console.log('Operación cancelada');
                    }
                },
                {
                    text: 'Sí, limpiar todo',
                    type: 'danger',
                    icon: 'fas fa-trash',
                    callback: async () => {
                        await this.performClearAll();
                    }
                }
            ]
        });
    }

    async performClearAll() {
        try {
            const response = await fetch(`${this.baseUrl}/ajax/wishlist/clear_all.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Mostrar notificación de éxito y recargar
                this.notificationSystem.show(
                    '¡Lista de deseos limpiada exitosamente!',
                    'success',
                    {
                        duration: 3000,
                        onClick: () => {
                            window.location.reload();
                        }
                    }
                );
                
                // Recargar automáticamente después de 3 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                this.notificationSystem.show(
                    data.message || 'Error al limpiar la lista de deseos',
                    'error'
                );
            }
        } catch (error) {
            console.error('Error:', error);
            this.notificationSystem.show(
                'Error de conexión. Intenta nuevamente.',
                'error'
            );
        }
    }

    showLoginAlert() {
        console.log('🔐 Mostrando alerta de login...');
        this.alertSystem.show({
            type: 'info',
            title: 'Inicia sesión',
            message: 'Debes iniciar sesión para agregar productos a tu lista de deseos.',
            actions: [
                {
                    text: 'Cancelar',
                    type: 'secondary',
                    icon: 'fas fa-times'
                },
                {
                    text: 'Iniciar sesión',
                    type: 'primary',
                    icon: 'fas fa-sign-in-alt',
                    callback: () => {
                        const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
                        window.location.href = `${this.baseUrl}/auth/login.php?redirect=${currentUrl}`;
                    }
                }
            ]
        });
    }

    updateWishlistCount() {
        const remainingProducts = document.querySelectorAll('.product-card').length;
        const totalElement = document.querySelector('.total-products p');
        const statValue = document.querySelector('.stat-value');
        const statLabel = document.querySelector('.stat-label');

        if (totalElement) {
            const plural = remainingProducts !== 1;
            totalElement.textContent = `${remainingProducts} producto${plural ? 's' : ''} en tu lista`;
        }

        if (statValue) {
            statValue.textContent = remainingProducts;
        }

        if (statLabel) {
            const plural = remainingProducts !== 1;
            statLabel.textContent = `Producto${plural ? 's' : ''} guardado${plural ? 's' : ''}`;
        }
    }

    checkIfEmpty() {
        const remainingProducts = document.querySelectorAll('.product-card').length;
        
        if (remainingProducts === 0) {
            // Recargar la página para mostrar el estado vacío
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    setupLazyLoading() {
        const images = document.querySelectorAll('.product-image img');
        
        images.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
                img.parentElement.classList.remove('loading');
            } else {
                img.addEventListener('load', () => {
                    img.classList.add('loaded');
                    img.parentElement.classList.remove('loading');
                });
            }
        });
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar el sistema de wishlist
    window.wishlistManager = new WishlistManager();
    
    console.log('✅ Sistema de Wishlist inicializado');
});

// Añadir animación de fadeOut para productos eliminados
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);
