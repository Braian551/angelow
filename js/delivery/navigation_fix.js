/**
 * Fix para navigation.js - Sistema de tráfico con modal
 */

(function() {
    'use strict';

    console.log('Aplicando fix de navegacion...');

    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTrafficFix);
    } else {
        initTrafficFix();
    }

    function initTrafficFix() {
        // Crear modal de tráfico
        createTrafficModal();

        // Sobrescribir la función toggleTraffic
        window.toggleTraffic = function() {
            console.log('Toggle Traffic ejecutado');
            
            // Verificar estado
            if (!window.trafficState) {
                window.trafficState = {
                    isVisible: false,
                    layer: null
                };
            }
            
            window.trafficState.isVisible = !window.trafficState.isVisible;
            const button = document.getElementById('btn-traffic');
            
            if (window.trafficState.isVisible) {
                // Activar tráfico
                console.log('Activando vista de trafico...');
                
                // Determinar nivel de tráfico
                const trafficLevel = getTrafficLevelByTime();
                
                // Mostrar modal con información
                showTrafficModal(trafficLevel);
                
                // Activar botón
                if (button) {
                    button.classList.add('active');
                }
                
                // Mostrar badge de tráfico
                showTrafficBadge(trafficLevel);
                
                console.log('Trafico ' + trafficLevel.label + ' activado');
                
            } else {
                // Desactivar tráfico
                hideTrafficBadge();
                
                if (button) {
                    button.classList.remove('active');
                }
                
                showNotification('Vista de trafico desactivada', 'info');
                console.log('Trafico desactivado');
            }
        };

        console.log('Fix de toggleTraffic aplicado correctamente');
    }

    // Obtener nivel de tráfico según la hora
    function getTrafficLevelByTime() {
        const now = new Date();
        const hour = now.getHours();
        const dayOfWeek = now.getDay();
        
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            // Fin de semana
            if (hour >= 10 && hour <= 14) {
                return { 
                    level: 'medium', 
                    label: 'Moderado', 
                    color: '#fbbf24',
                    icon: 'fa-car',
                    description: 'El tráfico está moderado. Puede haber algunos retrasos menores.',
                    multiplier: 1.2
                };
            }
            return { 
                level: 'low', 
                label: 'Fluido', 
                color: '#10b981',
                icon: 'fa-check-circle',
                description: 'El tráfico está fluido. No hay retrasos significativos.',
                multiplier: 1.0
            };
        }
        
        // Entre semana
        if ((hour >= 6 && hour < 9) || (hour >= 17 && hour < 20)) {
            return { 
                level: 'high', 
                label: 'Pesado', 
                color: '#ef4444',
                icon: 'fa-exclamation-triangle',
                description: 'Hora pico. El tráfico está pesado y pueden haber retrasos importantes.',
                multiplier: 1.5
            };
        } else if (hour >= 12 && hour < 14) {
            return { 
                level: 'medium', 
                label: 'Moderado', 
                color: '#fbbf24',
                icon: 'fa-car',
                description: 'El tráfico está moderado. Puede haber algunos retrasos menores.',
                multiplier: 1.2
            };
        }
        
        return { 
            level: 'low', 
            label: 'Fluido', 
            color: '#10b981',
            icon: 'fa-check-circle',
            description: 'El tráfico está fluido. No hay retrasos significativos.',
            multiplier: 1.0
        };
    }

    // Crear modal de tráfico
    function createTrafficModal() {
        const modalHTML = `
            <div id="traffic-modal-overlay" class="modal-overlay" style="display: none;">
                <div class="modal-container" style="max-width: 400px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3 style="margin: 0; color: white; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-traffic-light"></i>
                            Información de Tráfico
                        </h3>
                        <button class="modal-close" onclick="closeTrafficModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body" id="traffic-modal-content" style="padding: 24px;">
                        <!-- Contenido dinámico -->
                    </div>
                    <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <button onclick="closeTrafficModal()" class="btn-modal btn-modal-secondary" style="padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; background: rgba(255,255,255,0.1); color: white;">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Mostrar modal de tráfico
    function showTrafficModal(trafficLevel) {
        const content = `
            <div style="text-align: center;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: ${trafficLevel.color}; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px ${trafficLevel.color}50;">
                    <i class="fas ${trafficLevel.icon}" style="font-size: 36px; color: white;"></i>
                </div>
                <h2 style="margin: 0 0 12px; color: white; font-size: 24px;">
                    Tráfico ${trafficLevel.label}
                </h2>
                <p style="margin: 0 0 20px; color: rgba(255,255,255,0.8); font-size: 15px; line-height: 1.6;">
                    ${trafficLevel.description}
                </p>
                <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 16px; margin-top: 20px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 12px;">
                        <i class="fas fa-clock" style="color: rgba(255,255,255,0.7);"></i>
                        <span style="color: rgba(255,255,255,0.9); font-size: 14px;">
                            Impacto estimado en tiempo: <strong>+${Math.round((trafficLevel.multiplier - 1) * 100)}%</strong>
                        </span>
                    </div>
                    ${trafficLevel.multiplier > 1 ? `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <i class="fas fa-info-circle" style="color: rgba(255,255,255,0.7);"></i>
                            <span style="color: rgba(255,255,255,0.7); font-size: 13px;">
                                Considera rutas alternativas si es posible
                            </span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.getElementById('traffic-modal-content').innerHTML = content;
        document.getElementById('traffic-modal-overlay').style.display = 'flex';
        
        // Agregar animación
        setTimeout(() => {
            document.getElementById('traffic-modal-overlay').style.opacity = '1';
        }, 10);
    }

    // Cerrar modal
    window.closeTrafficModal = function() {
        const modal = document.getElementById('traffic-modal-overlay');
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    };

    // Mostrar badge de tráfico
    function showTrafficBadge(trafficLevel) {
        let badge = document.getElementById('traffic-badge');
        
        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'traffic-badge';
            badge.style.cssText = `
                position: fixed;
                top: 80px;
                right: 16px;
                z-index: 999;
                background: rgba(0, 0, 0, 0.9);
                backdrop-filter: blur(10px);
                padding: 12px 16px;
                border-radius: 12px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                gap: 12px;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateX(20px);
            `;
            document.body.appendChild(badge);
        }
        
        badge.innerHTML = `
            <div style="width: 8px; height: 8px; background: ${trafficLevel.color}; border-radius: 50%; box-shadow: 0 0 8px ${trafficLevel.color};"></div>
            <div style="color: white; font-size: 14px; font-weight: 500;">
                Tráfico ${trafficLevel.label}
            </div>
        `;
        
        badge.style.display = 'flex';
        setTimeout(() => {
            badge.style.opacity = '1';
            badge.style.transform = 'translateX(0)';
        }, 10);
    }

    // Ocultar badge de tráfico
    function hideTrafficBadge() {
        const badge = document.getElementById('traffic-badge');
        if (badge) {
            badge.style.opacity = '0';
            badge.style.transform = 'translateX(20px)';
            setTimeout(() => {
                badge.style.display = 'none';
            }, 300);
        }
    }

    // Función helper para notificaciones (si existe)
    function showNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            console.log('[' + type + '] ' + message);
        }
    }

})();
