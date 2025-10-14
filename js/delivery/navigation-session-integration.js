/**
 * =========================================================
 * INTEGRACIÓN: Sistema de Persistencia de Navegación
 * Añade gestión de sesiones al sistema de navegación existente
 * =========================================================
 * 
 * INSTRUCCIONES:
 * Este código debe integrarse en navigation.js después de la inicialización
 * 
 * Buscar la línea: document.addEventListener('DOMContentLoaded', function() {
 * Y añadir el código de integración después de cargar deliveryData
 */

// =========================================================
// CÓDIGO PARA AÑADIR EN navigation.js
// =========================================================

/*
// Después de cargar deliveryData, añadir:

// =========================================================
// INICIALIZAR SESSION MANAGER
// =========================================================
let sessionManager = null;

async function initializeSessionManager() {
    console.log('🔄 Inicializando Session Manager...');
    
    try {
        // Crear instancia del gestor de sesiones
        sessionManager = new NavigationSessionManager(
            CONFIG.BASE_URL,
            state.deliveryData.delivery_id,
            state.deliveryData.driver_id
        );
        
        // Cargar estado desde la base de datos
        const result = await sessionManager.initialize();
        
        if (result.success && result.hasActiveSession) {
            console.log('✅ Sesión activa encontrada:', result.state.session_status);
            
            // Restaurar estado según lo que había en la BD
            const savedState = result.state;
            
            if (savedState.session_status === 'navigating') {
                // Estaba navegando, restaurar navegación
                console.log('🚗 Restaurando navegación activa...');
                
                state.isNavigating = true;
                
                // Restaurar ubicación si existe
                if (savedState.current_lat && savedState.current_lng) {
                    state.currentLocation = {
                        lat: parseFloat(savedState.current_lat),
                        lng: parseFloat(savedState.current_lng)
                    };
                }
                
                // Restaurar métricas
                if (savedState.remaining_distance_km) {
                    state.distanceRemaining = savedState.remaining_distance_km * 1000; // convertir a metros
                }
                
                if (savedState.eta_seconds) {
                    state.etaSeconds = savedState.eta_seconds;
                }
                
                if (savedState.current_speed_kmh) {
                    state.currentSpeed = savedState.current_speed_kmh;
                }
                
                // Restaurar configuración
                if (savedState.voice_enabled !== undefined) {
                    state.isVoiceEnabled = Boolean(savedState.voice_enabled);
                }
                
                if (savedState.traffic_visible !== undefined) {
                    state.isTrafficVisible = Boolean(savedState.traffic_visible);
                }
                
                // Actualizar UI
                updateUIForNavigating();
                
                showNotification('Continuando navegación...', 'info');
                
            } else if (savedState.session_status === 'paused') {
                // Estaba pausado
                console.log('⏸️ Sesión pausada detectada');
                
                state.isNavigating = false;
                
                // Mostrar mensaje
                showNotification('Navegación pausada. Pulsa para continuar.', 'warning');
                
                // Cambiar botón a "Reanudar"
                const btnText = document.getElementById('btn-action-text');
                if (btnText) {
                    btnText.textContent = 'Reanudar Navegación';
                }
                
            } else if (savedState.session_status === 'idle') {
                // En espera, normal
                console.log('⏸️ Sesión en espera');
            }
            
        } else {
            console.log('ℹ️ No hay sesión activa previa');
        }
        
    } catch (error) {
        console.error('❌ Error al inicializar Session Manager:', error);
        // Continuar sin persistencia
        showNotification('Sistema de persistencia no disponible', 'warning');
    }
}

// =========================================================
// MODIFICAR handleMainAction
// =========================================================
// Reemplazar la función handleMainAction existente con esta versión mejorada:

window.handleMainAction = async function() {
    console.log('🎯 handleMainAction - isNavigating:', state.isNavigating);
    
    if (!state.isNavigating) {
        // INICIAR NAVEGACIÓN
        if (!state.currentLocation) {
            showNotification('Esperando ubicación actual...', 'warning');
            return;
        }
        
        try {
            // Iniciar en el Session Manager
            if (sessionManager) {
                const result = await sessionManager.startNavigation(
                    state.currentLocation.lat,
                    state.currentLocation.lng,
                    {
                        device: navigator.platform,
                        userAgent: navigator.userAgent
                    }
                );
                
                if (!result.success) {
                    throw new Error('No se pudo iniciar la sesión');
                }
                
                console.log('✅ Sesión iniciada en BD');
            }
            
            // Iniciar navegación local
            state.isNavigating = true;
            startLocationTracking();
            
            // Actualizar UI
            updateUIForNavigating();
            
            showNotification('Navegación iniciada', 'success');
            
        } catch (error) {
            console.error('❌ Error al iniciar navegación:', error);
            showNotification('Error al iniciar navegación', 'error');
        }
        
    } else {
        // PAUSAR NAVEGACIÓN
        try {
            // Pausar en el Session Manager
            if (sessionManager) {
                const result = await sessionManager.pauseNavigation();
                
                if (!result.success) {
                    throw new Error('No se pudo pausar la sesión');
                }
                
                console.log('✅ Sesión pausada en BD');
            }
            
            // Pausar navegación local
            state.isNavigating = false;
            stopLocationTracking();
            
            // Actualizar UI
            updateUIForPaused();
            
            showNotification('Navegación pausada', 'warning');
            
        } catch (error) {
            console.error('❌ Error al pausar navegación:', error);
            showNotification('Error al pausar navegación', 'error');
        }
    }
};

// =========================================================
// MODIFICAR updateLocation (para auto-guardado)
// =========================================================
// Añadir después de actualizar la posición del marker:

async function saveLocationToDatabase(location) {
    if (!sessionManager || !state.isNavigating) {
        return;
    }
    
    try {
        // Obtener nivel de batería
        let batteryLevel = 100;
        if (navigator.getBattery) {
            const battery = await navigator.getBattery();
            batteryLevel = Math.round(battery.level * 100);
        }
        
        // Guardar en BD
        await sessionManager.updateLocation({
            lat: location.lat,
            lng: location.lng,
            speed: state.currentSpeed || 0,
            distanceRemaining: (state.distanceRemaining || 0) / 1000, // convertir a km
            etaSeconds: state.etaSeconds || 0,
            batteryLevel: batteryLevel
        });
        
    } catch (error) {
        console.error('Error al guardar ubicación:', error);
    }
}

// Llamar desde la función de actualización de ubicación existente:
// Buscar donde se actualiza la ubicación y añadir:
// saveLocationToDatabase(newLocation);

// =========================================================
// GUARDAR RUTA CUANDO SE CALCULA
// =========================================================
// Añadir en la función donde se calcula la ruta:

async function saveCalculatedRoute(route) {
    if (!sessionManager) {
        return;
    }
    
    try {
        const routeData = {
            waypoints: route.waypoints || [],
            coordinates: route.coordinates || [],
            instructions: route.instructions || [],
            summary: route.summary || {}
        };
        
        const totalDistanceKm = (route.summary?.totalDistance || 0) / 1000;
        
        await sessionManager.saveRoute(routeData, totalDistanceKm);
        
        console.log('✅ Ruta guardada en BD');
        
    } catch (error) {
        console.error('Error al guardar ruta:', error);
    }
}

// =========================================================
// COMPLETAR NAVEGACIÓN AL LLEGAR
// =========================================================

async function completeNavigationSession() {
    if (!sessionManager) {
        return;
    }
    
    try {
        const totalDistanceKm = (state.route?.summary?.totalDistance || 0) / 1000;
        
        await sessionManager.completeNavigation(totalDistanceKm);
        
        console.log('✅ Navegación completada en BD');
        
    } catch (error) {
        console.error('Error al completar navegación:', error);
    }
}

// =========================================================
// FUNCIONES AUXILIARES UI
// =========================================================

function updateUIForNavigating() {
    const btnActionText = document.getElementById('btn-action-text');
    const btnActionIcon = document.querySelector('#btn-action-main i');
    
    if (btnActionText) {
        btnActionText.textContent = 'Pausar Navegación';
    }
    
    if (btnActionIcon) {
        btnActionIcon.className = 'fas fa-pause-circle';
    }
    
    // Añadir clase de navegación activa
    document.body.classList.add('navigating-active');
}

function updateUIForPaused() {
    const btnActionText = document.getElementById('btn-action-text');
    const btnActionIcon = document.querySelector('#btn-action-main i');
    
    if (btnActionText) {
        btnActionText.textContent = 'Reanudar Navegación';
    }
    
    if (btnActionIcon) {
        btnActionIcon.className = 'fas fa-play-circle';
    }
    
    // Remover clase de navegación activa
    document.body.classList.remove('navigating-active');
}

// =========================================================
// ACTUALIZAR CONFIGURACIÓN
// =========================================================

async function saveNavigationSettings(settings) {
    if (!sessionManager) {
        return;
    }
    
    try {
        await sessionManager.updateSettings(settings);
    } catch (error) {
        console.error('Error al guardar configuración:', error);
    }
}

// Ejemplo de uso:
// Al cambiar voz:
// saveNavigationSettings({ voice_enabled: state.isVoiceEnabled ? 1 : 0 });

// Al cambiar tráfico:
// saveNavigationSettings({ traffic_visible: state.isTrafficVisible ? 1 : 0 });

// =========================================================
// INICIALIZAR TODO
// =========================================================

// Llamar al final del DOMContentLoaded:
initializeSessionManager().then(() => {
    console.log('✅ Sistema de navegación con persistencia listo');
});

// =========================================================
// LIMPIAR AL SALIR
// =========================================================

window.addEventListener('beforeunload', function(e) {
    // Opcional: Guardar estado final antes de cerrar
    if (sessionManager && state.isNavigating) {
        // El navegador puede no esperar async, pero intentamos
        navigator.sendBeacon(
            `${CONFIG.BASE_URL}/delivery/api/navigation_session.php?action=update-location`,
            JSON.stringify({
                delivery_id: state.deliveryData.delivery_id,
                lat: state.currentLocation?.lat || 0,
                lng: state.currentLocation?.lng || 0,
                speed: state.currentSpeed || 0,
                distance_remaining: (state.distanceRemaining || 0) / 1000,
                eta_seconds: state.etaSeconds || 0,
                battery_level: state.batteryLevel || 100
            })
        );
    }
});

*/

// =========================================================
// FIN DEL CÓDIGO DE INTEGRACIÓN
// =========================================================

console.log('📝 Instrucciones de integración cargadas');
console.log('Lee los comentarios de este archivo para integrar con navigation.js');
