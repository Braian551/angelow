/**
 * Helper de Voz para Navegación
 * Soporta múltiples APIs de síntesis de voz con fallback automático
 */

class VoiceHelper {
    constructor() {
        this.enabled = true;
        this.currentEngine = null;
        this.engines = {
            voicerss: {
                name: 'VoiceRSS Free API',
                available: true,
                apiKey: 'YOUR_FREE_KEY', // Se obtiene en https://www.voicerss.org/
                url: 'https://api.voicerss.org/'
            },
            webspeech: {
                name: 'Web Speech API',
                available: 'speechSynthesis' in window
            },
            responsivevoice: {
                name: 'ResponsiveVoice',
                available: typeof responsiveVoice !== 'undefined'
            }
        };
        
        this.bestSpanishVoice = null;
        this.audioCache = new Map(); // Cache para audio
        this.initialize();
    }
    
    initialize() {
        console.log('🎙️ Inicializando VoiceHelper...');
        
        // Intentar con VoiceRSS primero (mejor calidad y español nativo)
        if (this.engines.voicerss.available) {
            this.currentEngine = 'voicerss';
            console.log('✅ Usando VoiceRSS (API gratuita con español nativo)');
        } else if (this.engines.webspeech.available) {
            this.currentEngine = 'webspeech';
            console.log('✅ Usando Web Speech API (nativo del navegador)');
            this.loadWebSpeechVoices();
        } else if (this.engines.responsivevoice.available) {
            this.currentEngine = 'responsivevoice';
            console.log('✅ Usando ResponsiveVoice');
        } else {
            console.error('❌ No hay ningún motor de voz disponible');
            this.enabled = false;
        }
    }
    
    loadWebSpeechVoices() {
        if (!this.engines.webspeech.available) return;
        
        const loadVoices = () => {
            const voices = window.speechSynthesis.getVoices();
            console.log(`🔍 Total de voces disponibles: ${voices.length}`);
            
            // Mostrar todas las voces para debug
            voices.forEach((voice, i) => {
                console.log(`  ${i + 1}. ${voice.name} (${voice.lang}) ${voice.localService ? '[Local]' : '[Remota]'}`);
            });
            
            // Si no hay voces disponibles, simplemente usar el idioma sin voz específica
            if (voices.length === 0) {
                console.log('⚠️ No hay voces cargadas aún, Web Speech API usará la voz predeterminada del sistema');
                this.bestSpanishVoice = null;
                return;
            }
            
            // Priorizar voces de español de forma más flexible
            const voicePriority = [
                // Voces de Google (muy buenas)
                { pattern: /google.*español/i, priority: 10 },
                { pattern: /google.*spanish/i, priority: 9 },
                // Voces de Microsoft
                { pattern: /helena/i, priority: 8 },
                { pattern: /sabina/i, priority: 8 },
                // Voces de Apple
                { pattern: /monica/i, priority: 8 },
                { pattern: /paulina/i, priority: 8 },
                { pattern: /juan/i, priority: 7 },
                // Cualquier voz que tenga "spanish" o "español"
                { pattern: /spanish|español/i, priority: 5 },
                // Voces con código de idioma español
                { pattern: /es[-_]/i, priority: 4 }
            ];
            
            let selectedVoice = null;
            let highestPriority = 0;
            
            // Buscar la mejor voz según prioridad
            voices.forEach(voice => {
                // Buscar voces en cualquier idioma español
                const langLower = voice.lang.toLowerCase();
                const isSpanish = langLower.includes('es') || 
                                  langLower.startsWith('es') ||
                                  voice.name.toLowerCase().includes('spanish') ||
                                  voice.name.toLowerCase().includes('español');
                
                if (!isSpanish) return;
                
                for (const prio of voicePriority) {
                    if (prio.pattern.test(voice.name) || prio.pattern.test(voice.lang)) {
                        if (prio.priority > highestPriority) {
                            highestPriority = prio.priority;
                            selectedVoice = voice;
                        }
                    }
                }
                
                // Si no hay coincidencia de patrón pero el idioma es español, seleccionarla como backup
                if (!selectedVoice && isSpanish) {
                    selectedVoice = voice;
                }
            });
            
            this.bestSpanishVoice = selectedVoice;
            
            if (selectedVoice) {
                console.log(`✅ Voz Web Speech seleccionada: ${selectedVoice.name} (${selectedVoice.lang})`);
            } else {
                console.log('ℹ️ No se encontró voz específica en español, Web Speech usará voz predeterminada con lang=es-MX');
            }
        };
        
        // Cargar voces inmediatamente
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            loadVoices();
        }
        
        // También escuchar el evento por si las voces se cargan después
        if ('onvoiceschanged' in window.speechSynthesis) {
            window.speechSynthesis.onvoiceschanged = loadVoices;
        }
        
        // Timeout para intentar cargar voces después de un momento
        setTimeout(() => {
            if (!this.bestSpanishVoice || window.speechSynthesis.getVoices().length === 0) {
                loadVoices();
            }
        }, 1000);
    }
    
    /**
     * Hablar texto usando el mejor motor disponible
     */
    async speak(text, options = {}) {
        if (!this.enabled) {
            console.log('🔇 Voz desactivada');
            return;
        }
        
        console.log('🔊 Intentando hablar:', text);
        
        try {
            // Intentar primero con VoiceRSS (mejor calidad para español)
            if (this.currentEngine === 'voicerss') {
                await this.speakWithVoiceRSS(text, options);
                return;
            }
            
            // Fallback a Web Speech API
            if (this.engines.webspeech.available) {
                await this.speakWithWebSpeech(text, options);
                return;
            }
            
            // Último fallback a ResponsiveVoice
            if (this.engines.responsivevoice.available) {
                await this.speakWithResponsiveVoice(text, options);
                return;
            }
            
            console.warn('⚠️ No hay motor de voz disponible');
        } catch (error) {
            console.error('❌ Error al hablar:', error.message);
            
            // Si VoiceRSS falla, usar Web Speech como fallback
            if (this.currentEngine === 'voicerss' && this.engines.webspeech.available) {
                console.log('🔄 Fallback a Web Speech API...');
                try {
                    await this.speakWithWebSpeech(text, options);
                } catch (fallbackError) {
                    console.error('❌ Fallback también falló');
                }
            }
        }
    }
    
    /**
     * VoiceRSS - API Gratuita con voces en español nativo
     * Registro gratuito: https://www.voicerss.org/
     * Límite: 350 solicitudes/día (suficiente para navegación)
     */
    async speakWithVoiceRSS(text, options = {}) {
        // Asegurar encoding UTF-8 correcto
        const textEncoded = encodeURIComponent(text);
        
        // Crear URL con parámetros correctamente codificados
        const params = new URLSearchParams({
            text: text, // URLSearchParams maneja el encoding automáticamente
            lang: options.lang || 'es-mx',
            rate: options.rate || '0'
        });
        
        const baseUrl = window.location.origin || 'http://localhost';
        const url = `${baseUrl}/angelow/delivery/api/text_to_speech.php?${params.toString()}`;
        
        console.log('🔗 URL VoiceRSS:', url);
        
        return new Promise((resolve, reject) => {
            const audio = new Audio(url);
            
            audio.onloadstart = () => {
                console.log('⏳ Cargando audio VoiceRSS...');
            };
            
            audio.oncanplaythrough = () => {
                console.log('✅ Audio VoiceRSS listo');
            };
            
            audio.onplay = () => {
                console.log('▶️ Reproduciendo con VoiceRSS:', text);
            };
            
            audio.onended = () => {
                console.log('✅ Reproducción VoiceRSS completada');
                resolve();
            };
            
            audio.onerror = async (e) => {
                console.warn('⚠️ Error en VoiceRSS, verificando causa...');
                
                // Intentar obtener más información del error
                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('❌ Respuesta del servidor:', errorText);
                    }
                } catch (fetchError) {
                    console.error('❌ Error al verificar:', fetchError.message);
                }
                
                reject(new Error('Error al reproducir con VoiceRSS'));
            };
            
            // Intentar reproducir
            audio.play().catch(e => {
                console.warn('⚠️ Error al iniciar reproducción VoiceRSS');
                reject(e);
            });
        });
    }
    
    /**
     * ResponsiveVoice API
     */
    async speakWithResponsiveVoice(text, options = {}) {
        return new Promise((resolve, reject) => {
            if (typeof responsiveVoice === 'undefined') {
                reject(new Error('ResponsiveVoice no está disponible'));
                return;
            }
            
            responsiveVoice.cancel();
            responsiveVoice.speak(text, options.voice || "Spanish Latin American Female", {
                rate: options.rate || 0.95,
                pitch: options.pitch || 1.0,
                volume: options.volume || 1.0,
                onstart: () => {
                    console.log('▶️ Reproduciendo con ResponsiveVoice:', text);
                },
                onend: () => {
                    console.log('✅ Reproducción ResponsiveVoice completada');
                    resolve();
                },
                onerror: (e) => {
                    console.error('❌ Error en ResponsiveVoice:', e);
                    reject(e);
                }
            });
        });
    }
    
    /**
     * Web Speech API (Motor principal, nativo del navegador)
     */
    async speakWithWebSpeech(text, options = {}) {
        return new Promise((resolve, reject) => {
            if (!this.engines.webspeech.available) {
                reject(new Error('Web Speech API no disponible'));
                return;
            }
            
            // Cancelar cualquier voz previa
            window.speechSynthesis.cancel();
            
            // Esperar un momento para asegurar que se canceló
            setTimeout(() => {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = options.rate || 0.9; // Velocidad natural
                utterance.pitch = options.pitch || 1.0;
                utterance.volume = options.volume || 1.0;
                
                // Intentar cargar voces una vez más por si acaso
                const voices = window.speechSynthesis.getVoices();
                
                if (this.bestSpanishVoice && voices.includes(this.bestSpanishVoice)) {
                    utterance.voice = this.bestSpanishVoice;
                    utterance.lang = this.bestSpanishVoice.lang;
                    console.log(`🔊 Usando voz: ${this.bestSpanishVoice.name} (${this.bestSpanishVoice.lang})`);
                } else {
                    // Buscar cualquier voz en español disponible
                    const spanishVoice = voices.find(v => 
                        v.lang.toLowerCase().includes('es') || 
                        v.name.toLowerCase().includes('spanish') ||
                        v.name.toLowerCase().includes('español')
                    );
                    
                    if (spanishVoice) {
                        utterance.voice = spanishVoice;
                        utterance.lang = spanishVoice.lang;
                        console.log(`🔊 Voz encontrada dinámicamente: ${spanishVoice.name} (${spanishVoice.lang})`);
                    } else {
                        // Probar diferentes variantes de español
                        const langs = ['es-MX', 'es-ES', 'es-US', 'es-AR', 'es-CO', 'es'];
                        utterance.lang = langs[0];
                        console.log(`🔊 Usando idioma sin voz específica: ${utterance.lang}`);
                    }
                }
                
                utterance.onstart = () => {
                    console.log('▶️ Reproduciendo con Web Speech:', text);
                };
                
                utterance.onend = () => {
                    console.log('✅ Reproducción Web Speech completada');
                    resolve();
                };
                
                utterance.onerror = (e) => {
                    // Solo mostrar error si es relevante
                    if (e.error !== 'interrupted' && e.error !== 'canceled') {
                        console.warn('⚠️ Web Speech:', e.error);
                    }
                    // Siempre resolver para evitar bloquear la aplicación
                    resolve();
                };
                
                try {
                    window.speechSynthesis.speak(utterance);
                    console.log('✅ Web Speech: comando speak() ejecutado');
                } catch (e) {
                    console.warn('⚠️ Error al llamar speak():', e.message);
                    resolve(); // No rechazar, solo resolver
                }
            }, 100);
        });
    }
    
    /**
     * Cancelar cualquier reproducción actual
     */
    cancel() {
        if (this.currentEngine === 'responsivevoice' && typeof responsiveVoice !== 'undefined') {
            responsiveVoice.cancel();
        }
        if (this.engines.webspeech.available) {
            window.speechSynthesis.cancel();
        }
    }
    
    /**
     * Activar/Desactivar voz
     */
    toggle() {
        this.enabled = !this.enabled;
        if (!this.enabled) {
            this.cancel();
        }
        console.log(this.enabled ? '🔊 Voz activada' : '🔇 Voz desactivada');
        return this.enabled;
    }
    
    /**
     * Verificar estado
     */
    isEnabled() {
        return this.enabled;
    }
    
    /**
     * Obtener información del motor actual
     */
    getEngineInfo() {
        return {
            current: this.currentEngine,
            name: this.engines[this.currentEngine]?.name || 'Ninguno',
            enabled: this.enabled
        };
    }
}

// Exportar instancia global
window.VoiceHelper = VoiceHelper;
