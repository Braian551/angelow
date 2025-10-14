/**
 * =========================================================
 * INTEGRACIÓN DE PERSISTENCIA DE SESIÓN DE NAVEGACIÓN
 * Restaura el estado desde la base de datos al cargar
 * =========================================================
 */

(function() {
    'use strict';

    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';
    const urlParams = new URLSearchParams(window.location.search);
    const DELIVERY_ID = parseInt(urlParams.get('delivery_id'));

    console.log('🔄 [Session] Verificando estado de sesión para delivery_id:', DELIVERY_ID);

    // =========================================================
    // RESTAURAR ESTADO AL CARGAR
    // =========================================================
    async function restoreNavigationState() {
        if (!DELIVERY_ID) {
            console.warn('⚠️ [Session] No hay delivery_id en URL');
            return;
        }

        try {
            // Llamar a la API de sesiones para obtener el estado
            const response = await fetch(`${BASE_URL}/delivery/api/navigation_session.php?action=get-state&delivery_id=${DELIVERY_ID}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                console.log('ℹ️ [Session] No hay sesión previa');
                return;
            }

            const data = await response.json();
            
            if (!data.success || !data.state) {
                console.log('ℹ️ [Session] No hay sesión activa');
                return;
            }

            const sessionState = data.state;
            console.log('✅ [Session] Sesión encontrada:', sessionState);

            // Restaurar según el estado
            switch(sessionState.session_status) {
                case 'navigating':
                    console.log('🚗 [Session] Restaurando navegación activa...');
                    await restoreNavigatingState(sessionState);
                    break;
                
                case 'paused':
                    console.log('⏸️ [Session] Restaurando navegación pausada...');
                    await restorePausedState(sessionState);
                    break;
                
                case 'idle':
                    console.log('⏹️ [Session] Sesión en estado idle, listo para iniciar');
                    break;
                
                case 'completed':
                    console.log('✅ [Session] Navegación completada previamente');
                    break;
                
                case 'cancelled':
                    console.log('❌ [Session] Navegación cancelada previamente');
                    break;
            }

        } catch (error) {
            console.error('❌ [Session] Error al restaurar estado:', error);
        }
    }

    // =========================================================
    // RESTAURAR ESTADO "NAVEGANDO"
    // =========================================================
    async function restoreNavigatingState(sessionState) {
        // Cambiar el botón principal a "Pausar" usando la función nativa
        if (typeof window.updateActionButton === 'function') {
            window.updateActionButton('pause', 'Pausar navegación');
        } else {
            // Fallback manual
            const btnMain = document.getElementById('btn-action-main');
            if (btnMain) {
                btnMain.textContent = 'Pausar';
                btnMain.dataset.action = 'pause';
                btnMain.classList.remove('btn-start');
                btnMain.classList.add('btn-pause');
            }
        }

        // Mostrar información restaurada en el panel
        updatePanelWithSessionData(sessionState);

        // Notificar al usuario
        showNotification('Navegación restaurada desde sesión anterior', 'success');

        console.log('✅ [Session] Estado de navegación restaurado');
    }

    // =========================================================
    // RESTAURAR ESTADO "PAUSADO"
    // =========================================================
    async function restorePausedState(sessionState) {
        // Cambiar el botón principal a "Reanudar" usando la función nativa
        if (typeof window.updateActionButton === 'function') {
            window.updateActionButton('resume', 'Reanudar navegación');
        } else {
            // Fallback manual
            const btnMain = document.getElementById('btn-action-main');
            if (btnMain) {
                btnMain.textContent = 'Reanudar';
                btnMain.dataset.action = 'resume';
                btnMain.classList.remove('btn-start');
                btnMain.classList.add('btn-resume');
            }
        }

        // Mostrar información restaurada en el panel
        updatePanelWithSessionData(sessionState);

        // Notificar al usuario
        showNotification('Navegación en pausa - Haz clic en Reanudar para continuar', 'info');

        console.log('⏸️ [Session] Estado pausado restaurado');
    }

    // =========================================================
    // ACTUALIZAR PANEL CON DATOS DE SESIÓN
    // =========================================================
    function updatePanelWithSessionData(sessionState) {
        // Actualizar distancia restante
        const distanceEl = document.getElementById('distance-remaining');
        if (distanceEl && sessionState.remaining_distance_km) {
            distanceEl.textContent = `${sessionState.remaining_distance_km} km`;
        }

        // Actualizar ETA
        const etaEl = document.getElementById('eta-time');
        if (etaEl && sessionState.eta_seconds) {
            const minutes = Math.round(sessionState.eta_seconds / 60);
            etaEl.textContent = `${minutes} min`;
        }

        // Actualizar velocidad
        const speedEl = document.getElementById('current-speed');
        if (speedEl && sessionState.current_speed_kmh) {
            speedEl.textContent = `${sessionState.current_speed_kmh} km/h`;
        }

        // Actualizar tiempo de navegación
        const timeEl = document.getElementById('navigation-time');
        if (timeEl && sessionState.total_navigation_time_seconds) {
            const minutes = Math.floor(sessionState.total_navigation_time_seconds / 60);
            timeEl.textContent = `${minutes} min`;
        }

        // Actualizar batería
        const batteryEl = document.getElementById('battery-level');
        if (batteryEl && sessionState.battery_level) {
            batteryEl.textContent = `${sessionState.battery_level}%`;
        }

        console.log('📊 [Session] Panel actualizado con datos de sesión');
    }

    // =========================================================
    // NO NECESITAMOS INTERCEPTAR - navigation.js ya guarda en BD
    // =========================================================
    function interceptNavigationFunctions() {
        // Las funciones originales de navigation.js ya llaman a navigation_api.php
        // que a su vez llama a los procedimientos almacenados que guardan en BD
        // Por lo tanto, NO necesitamos interceptar nada adicional
        
        console.log('🔗 [Session] Funciones de navegación ya están conectadas a BD');
    }

    // =========================================================
    // HELPER: Mostrar notificación
    // =========================================================
    function showNotification(message, type = 'info') {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    // =========================================================
    // INICIALIZACIÓN
    // =========================================================
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('🔄 [Session] Inicializando sistema de persistencia...');
        
        // Esperar un momento para que navigation.js se cargue completamente
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Interceptar funciones de navegación
        interceptNavigationFunctions();
        
        // Restaurar estado de sesión
        await restoreNavigationState();
        
        console.log('✅ [Session] Sistema de persistencia inicializado');
    });

})();
