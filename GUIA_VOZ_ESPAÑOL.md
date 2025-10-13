# 🎤 SOLUCIÓN: Voz en Español para Navegación

## ✅ Problema Resuelto

Tu sistema Windows solo tiene voces en **inglés** instaladas localmente. La solución implementada usa **VoiceRSS**, una API gratuita con voces en español de alta calidad.

## 🆓 VoiceRSS - API Gratuita

### Características:
- ✅ **100% Gratuita** (hasta 350 solicitudes/día)
- ✅ **Voces en español nativo** (México, España, Argentina, etc.)
- ✅ **Alta calidad de audio** (44kHz, 16-bit, stereo)
- ✅ **Sin necesidad de instalar voces** en Windows
- ✅ **Funciona en cualquier dispositivo**

### Límites Gratuitos:
- 350 solicitudes por día
- Suficiente para ~50-70 entregas con navegación por voz
- Si necesitas más: planes desde $5/mes (50,000 solicitudes)

## 🔑 Obtener tu API Key (GRATIS)

1. Ve a: https://www.voicerss.org/
2. Click en **"SIGN UP"** (esquina superior derecha)
3. Completa el formulario:
   - Name: Tu nombre
   - Email: Tu correo
   - Company: Angelow Delivery (o tu empresa)
   - Website: localhost o tu dominio
4. Click en **"REGISTER"**
5. Revisa tu correo y confirma la cuenta
6. En el dashboard verás tu **API Key**

## 🛠️ Configurar tu API Key

### Opción 1: Modificar el archivo PHP (Recomendado)

Edita el archivo `delivery/api/text_to_speech.php`:

```php
// Línea 14
$apiKey = 'TU_API_KEY_AQUI'; // Reemplaza con tu key
```

### Opción 2: Variable de entorno

Crea un archivo `.env` en la raíz:

```env
VOICERSS_API_KEY=TU_API_KEY_AQUI
```

Y modifica `text_to_speech.php`:

```php
$apiKey = getenv('VOICERSS_API_KEY') ?: 'cc12dcef65f148e9b6a212fd83ca2d9d';
```

## 🎯 Funcionamiento

```
1. Usuario inicia navegación
2. JavaScript llama a speak("Texto")
3. Se envía request a: /delivery/api/text_to_speech.php
4. PHP hace request a VoiceRSS API
5. VoiceRSS devuelve audio MP3 en español
6. Audio se reproduce en el navegador
```

### Flujo con Fallback:

```
VoiceRSS (español nativo)
    ↓ (si falla)
Web Speech API (voces del sistema)
    ↓ (si falla)
ResponsiveVoice (si está disponible)
```

## 🧪 Probar la Instalación

### 1. Probar el proxy PHP directamente:

Abre en tu navegador:
```
http://localhost/angelow/delivery/api/text_to_speech.php?text=Hola%20esto%20es%20una%20prueba
```

Deberías escuchar "Hola esto es una prueba" en español.

### 2. Probar desde la consola JavaScript:

Abre la consola del navegador en la página de navegación y ejecuta:

```javascript
// Probar VoiceHelper
const helper = new VoiceHelper();
await helper.speak('Esta es una prueba de voz en español');
```

### 3. Verificar en logs:

Deberías ver en la consola:
```
🎙️ Inicializando VoiceHelper...
✅ Usando VoiceRSS (API gratuita con español nativo)
🔊 Intentando hablar: Esta es una prueba de voz en español
⏳ Cargando audio VoiceRSS...
✅ Audio VoiceRSS listo
▶️ Reproduciendo con VoiceRSS: Esta es una prueba de voz en español
✅ Reproducción VoiceRSS completada
```

## 🗣️ Voces Disponibles

VoiceRSS tiene voces en diferentes variantes de español:

| Idioma | Código | Voz | Género |
|--------|--------|-----|--------|
| Español (México) | es-mx | Rosa | Femenino |
| Español (México) | es-mx | Diego | Masculino |
| Español (España) | es-es | Conchita | Femenino |
| Español (España) | es-es | Enrique | Masculino |
| Español (USA) | es-us | Penélope | Femenino |
| Español (USA) | es-us | Miguel | Masculino |

### Cambiar la voz:

En `voice-helper.js`, línea ~174:

```javascript
const params = new URLSearchParams({
    text: text,
    lang: 'es-mx',        // Cambiar idioma
    voice: 'Diego',       // Cambiar voz (Rosa, Diego, etc.)
    rate: '0'             // -10 (lento) a +10 (rápido)
});
```

## 📊 Monitorear Uso de API

1. Inicia sesión en: https://www.voicerss.org/personel/
2. Ve a **"Statistics"**
3. Verás:
   - Solicitudes usadas hoy
   - Solicitudes restantes
   - Historial de uso

## 🚨 Solución de Problemas

### Error: "Invalid API Key"

**Causa:** La API key no es válida o no está configurada.

**Solución:**
1. Verifica que obtuviste tu API key en https://www.voicerss.org/
2. Reemplaza en `text_to_speech.php` línea 14
3. Reinicia el servidor Apache

### Error: "The daily limit of 350 requests has been exceeded"

**Causa:** Superaste el límite gratuito diario.

**Soluciones:**
- Esperar a mañana (el límite se reinicia a medianoche UTC)
- Actualizar a plan de pago (desde $5/mes)
- El sistema usará Web Speech API como fallback automáticamente

### No se escucha nada

**Verificar:**
1. Volumen del navegador y sistema
2. Permisos de audio del navegador
3. Abrir: `chrome://settings/content/sound`
4. Verificar que el sitio no esté silenciado

**Probar manualmente:**
```
http://localhost/angelow/delivery/api/text_to_speech.php?text=Prueba
```

### Audio con acento extraño

**Causa:** Voz incorrecta para el idioma.

**Solución:** Cambiar la voz en `voice-helper.js`:
```javascript
lang: 'es-mx',    // México (acento neutro latinoamericano)
voice: 'Rosa',    // Voz femenina natural
```

## 🔐 Seguridad

### Proteger tu API Key:

1. **No subir a repositorios públicos:**

Añade a `.gitignore`:
```
delivery/api/text_to_speech.php
.env
```

2. **Limitar por dominio:**

En `text_to_speech.php`, añade:
```php
// Verificar dominio
$allowedDomains = ['localhost', 'tudominio.com'];
$origin = $_SERVER['HTTP_HOST'] ?? '';
if (!in_array($origin, $allowedDomains)) {
    http_response_code(403);
    die('Acceso denegado');
}
```

3. **Limitar solicitudes:**

Implementa rate limiting:
```php
// Cache simple con archivos
$cacheKey = md5($text . $lang);
$cacheFile = sys_get_temp_dir() . "/tts_cache_{$cacheKey}.mp3";
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
    readfile($cacheFile);
    exit;
}
```

## 📈 Optimizaciones Opcionales

### 1. Cache de Audio

Ya implementado en el proxy PHP:
```php
header('Cache-Control: public, max-age=86400'); // 24 horas
```

### 2. Comprimir Respuestas

En tu `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE audio/mpeg
</IfModule>
```

### 3. Precarga de Mensajes Comunes

En `voice-helper.js`:
```javascript
// Precargar mensajes frecuentes al iniciar
this.preloadCommonPhrases = async () => {
    const phrases = [
        'Navegación iniciada',
        'Gira a la derecha',
        'Gira a la izquierda',
        'Continúa recto',
        'Has llegado a tu destino'
    ];
    
    for (const phrase of phrases) {
        const audio = new Audio(this.getTTSUrl(phrase));
        audio.load(); // Precarga sin reproducir
    }
};
```

## 🎉 Resultado Final

Con esta implementación tendrás:

✅ **Voces en español nativo** (no robóticas)
✅ **Alta calidad de audio**
✅ **Funciona en cualquier dispositivo**
✅ **Sin necesidad de instalar nada**
✅ **Gratis hasta 350 usos/día**
✅ **Fallback automático** si falla la API

---

## 📝 Archivos Modificados

- ✅ `js/delivery/voice-helper.js` - Integración VoiceRSS
- ✅ `delivery/api/text_to_speech.php` - Proxy PHP

## 🔗 Enlaces Útiles

- VoiceRSS: https://www.voicerss.org/
- Documentación API: http://www.voicerss.org/api/
- Registro gratuito: https://www.voicerss.org/registration.aspx
- Precios: http://www.voicerss.org/pricing.aspx

---

**Fecha:** 13 de Octubre, 2025  
**Autor:** GitHub Copilot  
**Estado:** ✅ Implementado y funcionando
