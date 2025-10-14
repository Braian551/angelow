/**
 * =========================================================
 * MÓDULO DE GESTIÓN DE SESIONES DE NAVEGACIÓN
 * Maneja la persistencia del estado de navegación
 * =========================================================
 */

class NavigationSessionManager {
    constructor(baseUrl, deliveryId, driverId) {
        this.baseUrl = baseUrl;
        this.deliveryId = deliveryId;
        this.driverId = driverId;
        this.apiUrl = `${baseUrl}/delivery/api/navigation_session.php`;
        this.sessionState = null;
        this.autoSaveInterval = null;
        this.isInitialized = false;
        
        console.log('📦 NavigationSessionManager inicializado', {
            deliveryId: this.deliveryId,
            driverId: this.driverId
        });
    }
    
    /**
     * Inicializar - Obtener estado de la base de datos
     */
    async initialize() {
        try {
            console.log('🔄 Cargando estado de sesión desde la base de datos...');
            
            const response = await fetch(
                `${this.apiUrl}?action=get-state&delivery_id=${this.deliveryId}`,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.sessionState = data.state;
                this.isInitialized = true;
                
                console.log('✅ Estado de sesión cargado:', {
                    hasSession: data.has_active_session,
                    status: data.state?.session_status,
                    state: data.state
                });
                
                return {
                    success: true,
                    hasActiveSession: data.has_active_session,
                    state: data.state
                };
            } else {
                console.warn('⚠️ No se pudo cargar el estado de sesión');
                return {
                    success: false,
                    hasActiveSession: false,
                    state: null
                };
            }
            
        } catch (error) {
            console.error('❌ Error al inicializar sesión:', error);
            return {
                success: false,
                error: error.message,
                hasActiveSession: false,
                state: null
            };
        }
    }
    
    /**
     * Iniciar navegación
     */
    async startNavigation(lat, lng, deviceInfo = {}) {
        try {
            console.log('🚀 Iniciando navegación...', { lat, lng });
            
            const response = await fetch(`${this.apiUrl}?action=start`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    lat: lat,
                    lng: lng,
                    device_info: {
                        ...deviceInfo,
                        userAgent: navigator.userAgent,
                        platform: navigator.platform,
                        screenResolution: `${window.screen.width}x${window.screen.height}`,
                        timestamp: new Date().toISOString()
                    }
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Navegación iniciada correctamente');
                
                // Actualizar estado local
                if (!this.sessionState) {
                    this.sessionState = {};
                }
                this.sessionState.session_status = 'navigating';
                this.sessionState.navigation_started_at = data.timestamp;
                this.sessionState.current_lat = lat;
                this.sessionState.current_lng = lng;
                
                // Iniciar auto-guardado
                this.startAutoSave();
                
                return { success: true, message: data.message };
            } else {
                throw new Error(data.error || 'Error al iniciar navegación');
            }
            
        } catch (error) {
            console.error('❌ Error al iniciar navegación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Pausar navegación
     */
    async pauseNavigation() {
        try {
            console.log('⏸️ Pausando navegación...');
            
            const response = await fetch(`${this.apiUrl}?action=pause`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Navegación pausada');
                
                if (this.sessionState) {
                    this.sessionState.session_status = 'paused';
                }
                
                // Detener auto-guardado
                this.stopAutoSave();
                
                return { success: true, message: data.message };
            } else {
                throw new Error(data.error || 'Error al pausar navegación');
            }
            
        } catch (error) {
            console.error('❌ Error al pausar navegación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Reanudar navegación
     */
    async resumeNavigation(lat, lng) {
        try {
            console.log('▶️ Reanudando navegación...');
            
            const response = await fetch(`${this.apiUrl}?action=resume`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    lat: lat,
                    lng: lng
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Navegación reanudada');
                
                if (this.sessionState) {
                    this.sessionState.session_status = 'navigating';
                }
                
                // Reiniciar auto-guardado
                this.startAutoSave();
                
                return { success: true, message: data.message };
            } else {
                throw new Error(data.error || 'Error al reanudar navegación');
            }
            
        } catch (error) {
            console.error('❌ Error al reanudar navegación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Actualizar ubicación (auto-save durante navegación)
     */
    async updateLocation(locationData) {
        try {
            const response = await fetch(`${this.apiUrl}?action=update-location`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    lat: locationData.lat,
                    lng: locationData.lng,
                    speed: locationData.speed || 0,
                    distance_remaining: locationData.distanceRemaining || 0,
                    eta_seconds: locationData.etaSeconds || 0,
                    battery_level: locationData.batteryLevel || 100
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar estado local
                if (this.sessionState) {
                    this.sessionState.current_lat = locationData.lat;
                    this.sessionState.current_lng = locationData.lng;
                    this.sessionState.current_speed_kmh = locationData.speed;
                    this.sessionState.remaining_distance_km = locationData.distanceRemaining;
                    this.sessionState.eta_seconds = locationData.etaSeconds;
                    this.sessionState.battery_level = locationData.batteryLevel;
                    this.sessionState.last_update_at = data.timestamp;
                }
                
                return { success: true };
            }
            
            return { success: false };
            
        } catch (error) {
            console.error('❌ Error al actualizar ubicación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Guardar datos de ruta
     */
    async saveRoute(routeData, totalDistance) {
        try {
            console.log('💾 Guardando datos de ruta...', { 
                totalDistance, 
                waypoints: routeData?.waypoints?.length 
            });
            
            const response = await fetch(`${this.apiUrl}?action=save-route`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    route_data: routeData,
                    total_distance: totalDistance
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Ruta guardada correctamente');
                
                if (this.sessionState) {
                    this.sessionState.route_data = routeData;
                    this.sessionState.total_distance_km = totalDistance;
                }
                
                return { success: true };
            }
            
            return { success: false };
            
        } catch (error) {
            console.error('❌ Error al guardar ruta:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Completar navegación
     */
    async completeNavigation(totalDistance) {
        try {
            console.log('✅ Completando navegación...', { totalDistance });
            
            const response = await fetch(`${this.apiUrl}?action=complete`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    total_distance: totalDistance
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Navegación completada');
                
                if (this.sessionState) {
                    this.sessionState.session_status = 'completed';
                }
                
                // Detener auto-guardado
                this.stopAutoSave();
                
                return { success: true, message: data.message };
            } else {
                throw new Error(data.error || 'Error al completar navegación');
            }
            
        } catch (error) {
            console.error('❌ Error al completar navegación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Cancelar navegación
     */
    async cancelNavigation() {
        try {
            console.log('❌ Cancelando navegación...');
            
            const response = await fetch(`${this.apiUrl}?action=cancel`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Navegación cancelada');
                
                if (this.sessionState) {
                    this.sessionState.session_status = 'cancelled';
                }
                
                // Detener auto-guardado
                this.stopAutoSave();
                
                return { success: true, message: data.message };
            } else {
                throw new Error(data.error || 'Error al cancelar navegación');
            }
            
        } catch (error) {
            console.error('❌ Error al cancelar navegación:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Actualizar configuración (voz, tráfico, etc.)
     */
    async updateSettings(settings) {
        try {
            const response = await fetch(`${this.apiUrl}?action=update-settings`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    delivery_id: this.deliveryId,
                    ...settings
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && this.sessionState) {
                Object.assign(this.sessionState, settings);
            }
            
            return data;
            
        } catch (error) {
            console.error('❌ Error al actualizar configuración:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Iniciar auto-guardado periódico
     */
    startAutoSave() {
        if (this.autoSaveInterval) {
            return; // Ya está activo
        }
        
        console.log('🔄 Iniciando auto-guardado cada 5 segundos');
        
        this.autoSaveInterval = setInterval(() => {
            if (this.sessionState && this.sessionState.session_status === 'navigating') {
                // Este método se llamará desde el código principal con los datos actuales
                console.log('💾 Auto-guardado activo');
            }
        }, 5000);
    }
    
    /**
     * Detener auto-guardado
     */
    stopAutoSave() {
        if (this.autoSaveInterval) {
            console.log('⏹️ Deteniendo auto-guardado');
            clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = null;
        }
    }
    
    /**
     * Obtener estado actual
     */
    getState() {
        return this.sessionState;
    }
    
    /**
     * Verificar si hay navegación activa
     */
    isNavigating() {
        return this.sessionState && this.sessionState.session_status === 'navigating';
    }
    
    /**
     * Verificar si está pausado
     */
    isPaused() {
        return this.sessionState && this.sessionState.session_status === 'paused';
    }
    
    /**
     * Limpiar y destruir
     */
    destroy() {
        this.stopAutoSave();
        this.sessionState = null;
        this.isInitialized = false;
        console.log('🗑️ NavigationSessionManager destruido');
    }
}

// Exportar para uso global
window.NavigationSessionManager = NavigationSessionManager;
