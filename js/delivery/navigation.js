/**
 * =========================================================
 * SISTEMA DE NAVEGACIÓN EN TIEMPO REAL - ANGELOW DELIVERY
 * Funcionalidad: Navegación GPS estilo Uber/Waze con tracking en tiempo real
 * =========================================================
 */

(function() {
    'use strict';

    // =========================================================
    // CONFIGURACIÓN GLOBAL
    // =========================================================
    const CONFIG = {
        BASE_URL: document.querySelector('meta[name="base-url"]')?.content || '',
        UPDATE_INTERVAL: 5000, // Actualizar ubicación cada 5 segundos
        ROUTE_CHECK_INTERVAL: 30000, // Verificar ruta cada 30 segundos
        NEAR_DESTINATION_THRESHOLD: 0.1, // 100 metros
        OFF_ROUTE_THRESHOLD: 0.05, // 50 metros fuera de ruta
        SPEECH_ENABLED: true,
        MAP_ZOOM: 17,
        MAP_MIN_ZOOM: 10,
        MAP_MAX_ZOOM: 19
    };

    // =========================================================
    // ESTADO DE LA APLICACIÓN
    // =========================================================
    const state = {
        map: null,
        driverMarker: null,
        destinationMarker: null,
        routePolyline: null,
        currentLocation: null,
        destination: null,
        route: null,
        isNavigating: false,
        isPanelExpanded: false,
        isVoiceEnabled: true,
        watchId: null,
        updateInterval: null,
        routeCheckInterval: null,
        currentSpeed: 0,
        currentHeading: 0,
        distanceRemaining: 0,
        etaSeconds: 0,
        currentStepIndex: 0,
        deliveryData: null,
        batteryLevel: 100
    };

    // =========================================================
    // INICIALIZACIÓN
    // =========================================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Iniciando sistema de navegación...');
        
        // Cargar datos del delivery
        loadDeliveryData();
        
        // Solicitar permisos de ubicación
        requestLocationPermission();
        
        // Inicializar mapa
        initializeMap();
        
        // Inicializar eventos
        initializeEvents();
        
        // Obtener información de batería si está disponible
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                state.batteryLevel = Math.round(battery.level * 100);
                battery.addEventListener('levelchange', () => {
                    state.batteryLevel = Math.round(battery.level * 100);
                });
            });
        }
        
        console.log('✅ Sistema de navegación inicializado');
    });

    // =========================================================
    // CARGA DE DATOS DEL DELIVERY
    // =========================================================
    function loadDeliveryData() {
        const dataElement = document.getElementById('delivery-data');
        if (!dataElement) {
            showNotification('Error al cargar datos del pedido', 'error');
            return;
        }
        
        try {
            state.deliveryData = JSON.parse(dataElement.textContent);
            state.destination = {
                lat: state.deliveryData.destination.lat,
                lng: state.deliveryData.destination.lng
            };
            
            console.log('📦 Datos del delivery cargados:', state.deliveryData);
        } catch (e) {
            console.error('Error al parsear datos del delivery:', e);
            showNotification('Error al cargar información del pedido', 'error');
        }
    }

    // =========================================================
    // SOLICITAR PERMISOS DE UBICACIÓN
    // =========================================================
    function requestLocationPermission() {
        if (!('geolocation' in navigator)) {
            showNotification('Tu dispositivo no soporta geolocalización', 'error');
            return;
        }

        updateStatus('Solicitando permisos de ubicación...');
        
        navigator.permissions.query({ name: 'geolocation' }).then(result => {
            if (result.state === 'granted') {
                console.log('✅ Permisos de ubicación concedidos');
                startLocationTracking();
            } else if (result.state === 'prompt') {
                // Solicitar permisos
                navigator.geolocation.getCurrentPosition(
                    position => {
                        console.log('✅ Permisos de ubicación concedidos');
                        startLocationTracking();
                    },
                    error => {
                        console.error('❌ Permisos de ubicación denegados:', error);
                        showNotification('Se requieren permisos de ubicación para navegar', 'error');
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                showNotification('Se requieren permisos de ubicación para navegar', 'error');
            }
        });
    }

    // =========================================================
    // INICIALIZAR MAPA
    // =========================================================
    function initializeMap() {
        // Crear mapa centrado en Colombia por defecto
        state.map = L.map('map', {
            center: [4.6097, -74.0817],
            zoom: 13,
            zoomControl: true,
            minZoom: CONFIG.MAP_MIN_ZOOM,
            maxZoom: CONFIG.MAP_MAX_ZOOM
        });

        // Capa de mapa (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: CONFIG.MAP_MAX_ZOOM
        }).addTo(state.map);

        // Agregar marcador de destino
        if (state.destination && state.destination.lat && state.destination.lng) {
            addDestinationMarker(state.destination);
        }

        console.log('🗺️ Mapa inicializado');
    }

    // =========================================================
    // TRACKING DE UBICACIÓN EN TIEMPO REAL
    // =========================================================
    function startLocationTracking() {
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        state.watchId = navigator.geolocation.watchPosition(
            handleLocationUpdate,
            handleLocationError,
            options
        );

        updateStatus('Obteniendo ubicación...');
        console.log('📍 Tracking de ubicación iniciado');
    }

    // =========================================================
    // MANEJAR ACTUALIZACIÓN DE UBICACIÓN
    // =========================================================
    function handleLocationUpdate(position) {
        const { latitude, longitude, accuracy, speed, heading } = position.coords;
        
        state.currentLocation = { lat: latitude, lng: longitude };
        state.currentSpeed = speed ? (speed * 3.6) : 0; // Convertir m/s a km/h
        state.currentHeading = heading || 0;

        // Actualizar marcador del conductor
        updateDriverMarker(state.currentLocation, state.currentHeading);

        // Si es la primera ubicación, centrar mapa y calcular ruta
        if (!state.route) {
            state.map.setView(state.currentLocation, CONFIG.MAP_ZOOM);
            calculateRoute(state.currentLocation, state.destination);
        }

        // Actualizar UI
        updateSpeedDisplay(state.currentSpeed);

        // Si estamos navegando, enviar actualización al servidor
        if (state.isNavigating) {
            sendLocationUpdate(position);
        }

        console.log(`📍 Ubicación actualizada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`);
    }

    // =========================================================
    // MANEJAR ERROR DE UBICACIÓN
    // =========================================================
    function handleLocationError(error) {
        console.error('❌ Error de ubicación:', error);
        
        let message = 'Error al obtener ubicación';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Permisos de ubicación denegados';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Ubicación no disponible';
                break;
            case error.TIMEOUT:
                message = 'Timeout al obtener ubicación';
                break;
        }
        
        showNotification(message, 'error');
        updateStatus('Error de ubicación');
    }

    // =========================================================
    // CALCULAR RUTA
    // =========================================================
    async function calculateRoute(start, end) {
        try {
            updateStatus('Calculando ruta...');
            showNotification('Calculando mejor ruta...', 'info');

            const url = `${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=get_route` +
                        `&start_lat=${start.lat}&start_lng=${start.lng}` +
                        `&end_lat=${end.lat}&end_lng=${end.lng}`;

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al calcular ruta');
            }

            state.route = data.route;
            state.distanceRemaining = state.route.distance_km;
            state.etaSeconds = state.route.duration_seconds;

            // Dibujar ruta en el mapa
            drawRoute(state.route.geometry);

            // Actualizar UI
            updateRouteInfo(state.route);
            updateETADisplay(state.etaSeconds);
            updateDistanceDisplay(state.distanceRemaining);

            // Centrar mapa en la ruta
            fitMapToRoute();

            updateStatus('Ruta calculada');
            showNotification(`Ruta de ${state.route.distance_km} km calculada`, 'success');

            console.log('🗺️ Ruta calculada:', state.route);

        } catch (error) {
            console.error('❌ Error al calcular ruta:', error);
            showNotification(error.message, 'error');
            updateStatus('Error al calcular ruta');
        }
    }

    // =========================================================
    // DIBUJAR RUTA EN EL MAPA
    // =========================================================
    function drawRoute(geometry) {
        // Remover ruta anterior si existe
        if (state.routePolyline) {
            state.map.removeLayer(state.routePolyline);
        }

        // Convertir coordenadas de GeoJSON a formato Leaflet [lat, lng]
        const coordinates = geometry.coordinates.map(coord => [coord[1], coord[0]]);

        // Crear polyline
        state.routePolyline = L.polyline(coordinates, {
            color: '#667eea',
            weight: 6,
            opacity: 0.8,
            lineJoin: 'round'
        }).addTo(state.map);

        // Agregar borde al polyline para mejor visibilidad
        L.polyline(coordinates, {
            color: '#fff',
            weight: 8,
            opacity: 0.4,
            lineJoin: 'round'
        }).addTo(state.map);
    }

    // =========================================================
    // ACTUALIZAR MARCADOR DEL CONDUCTOR
    // =========================================================
    function updateDriverMarker(location, heading) {
        if (!state.driverMarker) {
            // Crear marcador personalizado
            const icon = L.divIcon({
                html: `<div class="driver-marker" style="transform: rotate(${heading}deg)">
                        <i class="fas fa-location-arrow"></i>
                       </div>`,
                className: '',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            state.driverMarker = L.marker([location.lat, location.lng], { icon })
                .addTo(state.map)
                .bindPopup('Tu ubicación');
        } else {
            // Actualizar posición y rotación
            state.driverMarker.setLatLng([location.lat, location.lng]);
            
            const iconElement = state.driverMarker.getElement();
            if (iconElement) {
                const markerDiv = iconElement.querySelector('.driver-marker');
                if (markerDiv) {
                    markerDiv.style.transform = `rotate(${heading}deg)`;
                }
            }
        }
    }

    // =========================================================
    // AGREGAR MARCADOR DE DESTINO
    // =========================================================
    function addDestinationMarker(location) {
        const icon = L.divIcon({
            html: `<div class="destination-marker">
                    <i class="fas fa-map-marker-alt"></i>
                   </div>`,
            className: '',
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });

        state.destinationMarker = L.marker([location.lat, location.lng], { icon })
            .addTo(state.map)
            .bindPopup('Destino: ' + (state.deliveryData?.destination.address || 'Dirección de entrega'));
    }

    // =========================================================
    // AJUSTAR MAPA A LA RUTA
    // =========================================================
    function fitMapToRoute() {
        if (state.routePolyline) {
            const bounds = state.routePolyline.getBounds();
            state.map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    // =========================================================
    // INICIAR NAVEGACIÓN
    // =========================================================
    async function startNavigation() {
        if (!state.currentLocation || !state.destination || !state.route) {
            showNotification('Esperando ubicación y ruta...', 'warning');
            return;
        }

        try {
            updateStatus('Iniciando navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=start_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    start_lat: state.currentLocation.lat,
                    start_lng: state.currentLocation.lng,
                    dest_lat: state.destination.lat,
                    dest_lng: state.destination.lng,
                    route: state.route,
                    distance_km: state.route.distance_km,
                    duration_seconds: state.route.duration_seconds
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al iniciar navegación');
            }

            state.isNavigating = true;
            
            // Cambiar botón de acción
            updateActionButton('pause', 'Pausar navegación');
            
            // Iniciar actualizaciones periódicas
            startPeriodicUpdates();
            
            // Centrar mapa en conductor
            centerOnDriver();
            
            updateStatus('Navegando');
            showNotification('Navegación iniciada', 'success');
            
            // Instrucción de voz
            speak('Navegación iniciada. Sigue la ruta marcada.');

            console.log('🧭 Navegación iniciada');

        } catch (error) {
            console.error('❌ Error al iniciar navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // ACTUALIZACIONES PERIÓDICAS
    // =========================================================
    function startPeriodicUpdates() {
        // Actualizar ubicación al servidor cada 5 segundos
        state.updateInterval = setInterval(() => {
            if (state.isNavigating && state.currentLocation) {
                sendLocationUpdate({
                    coords: {
                        latitude: state.currentLocation.lat,
                        longitude: state.currentLocation.lng,
                        accuracy: 10,
                        speed: state.currentSpeed / 3.6, // Convertir a m/s
                        heading: state.currentHeading
                    }
                });
            }
        }, CONFIG.UPDATE_INTERVAL);

        // Verificar si estamos fuera de ruta cada 30 segundos
        state.routeCheckInterval = setInterval(() => {
            if (state.isNavigating) {
                checkIfOnRoute();
            }
        }, CONFIG.ROUTE_CHECK_INTERVAL);
    }

    // =========================================================
    // ENVIAR ACTUALIZACIÓN DE UBICACIÓN AL SERVIDOR
    // =========================================================
    async function sendLocationUpdate(position) {
        try {
            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=update_location`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0,
                    battery_level: state.batteryLevel
                })
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar información local con datos del servidor
                if (data.distance_remaining !== null) {
                    state.distanceRemaining = data.distance_remaining;
                    updateDistanceDisplay(state.distanceRemaining);
                }
                
                if (data.eta_seconds !== null) {
                    state.etaSeconds = data.eta_seconds;
                    updateETADisplay(state.etaSeconds);
                }

                // Verificar si estamos cerca del destino
                if (state.distanceRemaining < CONFIG.NEAR_DESTINATION_THRESHOLD) {
                    handleNearDestination();
                }
            }

        } catch (error) {
            console.error('Error al enviar actualización de ubicación:', error);
        }
    }

    // =========================================================
    // VERIFICAR SI ESTAMOS EN LA RUTA
    // =========================================================
    function checkIfOnRoute() {
        // TODO: Implementar verificación de distancia a la ruta
        // Por ahora, siempre asumimos que estamos en ruta
    }

    // =========================================================
    // MANEJAR PROXIMIDAD AL DESTINO
    // =========================================================
    function handleNearDestination() {
        if (!state.nearDestinationNotified) {
            state.nearDestinationNotified = true;
            showNotification('¡Estás cerca del destino!', 'success');
            speak('Estás cerca del destino');
            
            // Registrar evento
            logNavigationEvent('destination_near', {
                distance_remaining: state.distanceRemaining
            });
        }
    }

    // =========================================================
    // REGISTRAR EVENTO DE NAVEGACIÓN
    // =========================================================
    async function logNavigationEvent(eventType, eventData) {
        try {
            await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=log_event`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    event_type: eventType,
                    event_data: eventData,
                    latitude: state.currentLocation?.lat || null,
                    longitude: state.currentLocation?.lng || null
                })
            });
        } catch (error) {
            console.error('Error al registrar evento:', error);
        }
    }

    // =========================================================
    // ACTUALIZAR DISPLAYS DE UI
    // =========================================================
    function updateStatus(text) {
        const statusElement = document.getElementById('nav-status');
        if (statusElement) {
            statusElement.textContent = text;
        }
    }

    function updateRouteInfo(route) {
        const instructionMain = document.getElementById('instruction-main');
        if (instructionMain && route.steps && route.steps.length > 0) {
            instructionMain.textContent = route.steps[0].name || 'Sigue por esta vía';
        }
    }

    function updateETADisplay(seconds) {
        const minutes = Math.round(seconds / 60);
        const etaTimeElement = document.getElementById('eta-time');
        
        if (etaTimeElement) {
            etaTimeElement.textContent = minutes;
        }

        // Calcular hora de llegada
        const arrivalTime = new Date(Date.now() + seconds * 1000);
        const arrivalTimeElement = document.getElementById('arrival-time');
        
        if (arrivalTimeElement) {
            arrivalTimeElement.textContent = arrivalTime.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    function updateDistanceDisplay(km) {
        const distanceElement = document.getElementById('distance-remaining');
        const instructionDistance = document.getElementById('instruction-distance');
        
        if (distanceElement) {
            distanceElement.textContent = `${km.toFixed(1)} km`;
        }
        
        if (instructionDistance) {
            instructionDistance.textContent = `En ${km.toFixed(1)} km`;
        }
    }

    function updateSpeedDisplay(kmh) {
        const speedElement = document.getElementById('current-speed');
        if (speedElement) {
            speedElement.textContent = `${Math.round(kmh)} km/h`;
        }
    }

    function updateActionButton(action, text) {
        const button = document.getElementById('btn-action-main');
        const textElement = document.getElementById('btn-action-text');
        
        if (button && textElement) {
            button.dataset.action = action;
            textElement.textContent = text;
            
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = action === 'start' ? 'fas fa-play-circle' : 
                                 action === 'pause' ? 'fas fa-pause-circle' : 
                                 'fas fa-stop-circle';
            }
        }
    }

    // =========================================================
    // FUNCIONES GLOBALES (accesibles desde HTML)
    // =========================================================
    window.handleMainAction = function() {
        const button = document.getElementById('btn-action-main');
        const action = button?.dataset.action || 'start';
        
        if (action === 'start') {
            startNavigation();
        }
    };

    window.togglePanel = function() {
        state.isPanelExpanded = !state.isPanelExpanded;
        
        const panelCompact = document.getElementById('panel-compact');
        const panelExpanded = document.getElementById('panel-expanded');
        
        if (state.isPanelExpanded) {
            panelCompact.style.display = 'none';
            panelExpanded.style.display = 'block';
        } else {
            panelCompact.style.display = 'block';
            panelExpanded.style.display = 'none';
        }
    };

    window.centerOnDriver = function() {
        if (state.currentLocation) {
            state.map.setView(state.currentLocation, CONFIG.MAP_ZOOM, {
                animate: true,
                duration: 0.5
            });
        }
    };

    window.toggleVoice = function() {
        state.isVoiceEnabled = !state.isVoiceEnabled;
        const button = document.getElementById('btn-voice');
        
        if (button) {
            const icon = button.querySelector('i');
            icon.className = state.isVoiceEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
            button.classList.toggle('active', state.isVoiceEnabled);
        }
        
        showNotification(
            state.isVoiceEnabled ? 'Instrucciones de voz activadas' : 'Instrucciones de voz desactivadas',
            'info'
        );
    };

    window.toggleTraffic = function() {
        // TODO: Implementar capa de tráfico
        showNotification('Información de tráfico no disponible', 'info');
    };

    window.toggleMenu = function() {
        const overlay = document.getElementById('menu-overlay');
        const drawer = document.getElementById('menu-drawer');
        
        overlay.classList.toggle('show');
        drawer.classList.toggle('show');
    };

    window.recalculateRoute = function() {
        if (state.currentLocation && state.destination) {
            showNotification('Recalculando ruta...', 'info');
            calculateRoute(state.currentLocation, state.destination);
            window.toggleMenu();
        }
    };

    window.reportIssue = function() {
        showNotification('Función en desarrollo', 'info');
        window.toggleMenu();
    };

    window.viewOrderDetails = function() {
        window.togglePanel();
        window.toggleMenu();
    };

    window.cancelNavigation = function() {
        if (confirm('¿Deseas cancelar la navegación?')) {
            stopNavigation();
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }
    };

    window.confirmExit = function() {
        if (state.isNavigating) {
            if (confirm('¿Deseas salir de la navegación? El progreso se guardará.')) {
                stopNavigation();
                window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
            }
        } else {
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }
    };

    // =========================================================
    // DETENER NAVEGACIÓN
    // =========================================================
    function stopNavigation() {
        state.isNavigating = false;
        
        if (state.updateInterval) {
            clearInterval(state.updateInterval);
            state.updateInterval = null;
        }
        
        if (state.routeCheckInterval) {
            clearInterval(state.routeCheckInterval);
            state.routeCheckInterval = null;
        }
        
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
        
        console.log('🛑 Navegación detenida');
    }

    // =========================================================
    // SÍNTESIS DE VOZ
    // =========================================================
    function speak(text) {
        if (!state.isVoiceEnabled || !('speechSynthesis' in window)) {
            return;
        }
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'es-ES';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        
        window.speechSynthesis.speak(utterance);
    }

    // =========================================================
    // NOTIFICACIONES
    // =========================================================
    function showNotification(message, type = 'info') {
        const container = document.getElementById('notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        notification.innerHTML = `
            <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // =========================================================
    // LIMPIAR AL SALIR
    // =========================================================
    window.addEventListener('beforeunload', function() {
        stopNavigation();
    });

})();
