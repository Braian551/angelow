/**
 * Sistema de notificaciones de órdenes nuevas
 * Maneja el badge de notificaciones en el sidebar
 */

(function() {
    'use strict';

    // Obtener la URL base desde el meta tag
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const ordersBadge = document.getElementById('orders-badge');

    /**
     * Marcar todas las órdenes como vistas
     */
    function markOrdersAsViewed() {
        fetch(`${baseUrl}/admin/api/mark_orders_viewed.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Órdenes marcadas como vistas:', data.marked_count);
                
                // Ocultar el badge con animación
                if (ordersBadge) {
                    ordersBadge.style.transition = 'all 0.3s ease';
                    ordersBadge.style.opacity = '0';
                    ordersBadge.style.transform = 'scale(0)';
                    
                    setTimeout(() => {
                        ordersBadge.remove();
                    }, 300);
                }
            }
        })
        .catch(error => {
            console.error('Error al marcar órdenes como vistas:', error);
        });
    }

    /**
     * Actualizar el contador del badge
     */
    function updateBadgeCount() {
        const endpoint = `${baseUrl}/admin/api/get_new_orders_count.php`.replace(/([^:]\/\/)/, '$1');

        // Try main configured path first; if not found (404), try a fallback
        // computed from location.pathname so the badge works when the app
        // runs from a subfolder (Laragon, XAMPP, etc.).
        fetch(endpoint, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.count;
                const badge = document.getElementById('orders-badge');
                
                if (count > 0) {
                    if (badge) {
                        badge.textContent = count;
                    } else {
                        // Crear el badge si no existe
                        const ordersLink = document.querySelector('a[href*="orders.php"]');
                        if (ordersLink) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'badge';
                            newBadge.id = 'orders-badge';
                            newBadge.textContent = count;
                            ordersLink.appendChild(newBadge);
                        }
                    }
                } else {
                    // Remover el badge si el conteo es 0
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(error => {
            // If we got a 404-like failure, try a fallback using pathname root
            try {
                const segments = window.location.pathname.split('/').filter(Boolean);
                if (segments.length > 0) {
                    const appRoot = window.location.origin + '/' + segments[0];
                    const fallback = `${appRoot}/admin/api/get_new_orders_count.php`;
                    return fetch(fallback, { method: 'GET', credentials: 'same-origin' })
                        .then(resp => resp.json())
                        .then(d => {
                            if (d.success && d.count > 0) {
                                const badge = document.getElementById('orders-badge');
                                if (badge) badge.textContent = d.count;
                            }
                        })
                        .catch(err => console.error('Error fallback al obtener el conteo de órdenes:', err));
                }
            } catch (e) {
                console.error('Error al obtener el conteo de órdenes:', error);
            }
        });
    }

    /**
     * Inicializar el sistema de notificaciones
     */
    function init() {
        // Verificar si estamos en la página de órdenes
        const currentPage = window.location.pathname;
        const isOrdersPage = currentPage.includes('orders.php');

        if (isOrdersPage) {
            // Si estamos en la página de órdenes, marcar como vistas
            markOrdersAsViewed();
        }

        // Actualizar el badge cada 30 segundos (solo si NO estamos en orders.php)
        if (!isOrdersPage) {
            setInterval(updateBadgeCount, 30000);
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
