# REQUISITOS NO FUNCIONALES - PROYECTO ANGELOW
## Sistema de Gestión de Ropa Infantil

**Estándar:** ISO/IEC 25010  
**Fecha:** 2 de Noviembre, 2025  
**Versión:** 1.0

---

## ESTÁNDAR ISO/IEC 25010

### REQUISITOS NO FUNCIONALES

| Nro. | Descripción | Atributo | Criterios |
|------|-------------|----------|-----------|
| **RNF-001** | La aplicación debe cargar sus páginas principales en menos de 4 segundos para ofrecer una mejor experiencia al cliente final | **RENDIMIENTO** | • Comportamiento en el tiempo<br>• Utilización de recursos<br>• Capacidad |
| **RNF-002** | La aplicación debe manejar al menos 100 usuarios navegando simultáneamente sin disminuir su velocidad de respuesta | **RENDIMIENTO** | • Comportamiento en el tiempo<br>• Utilización de recursos<br>• Capacidad |
| **RNF-003** | Las imágenes de productos deben cargarse gradualmente mientras el cliente navega, mostrando primero las que están visibles en pantalla | **RENDIMIENTO** | • Comportamiento en el tiempo<br>• Utilización de recursos<br>• Capacidad |
| **RNF-004** | El sistema debe guardar automáticamente las búsquedas y productos frecuentes para mostrarlos más rápido en futuras visitas | **RENDIMIENTO** | • Comportamiento en el tiempo<br>• Utilización de recursos<br>• Capacidad |
| **RNF-005** | La aplicación debe estar protegida contra intentos de acceso no autorizado y ataques comunes de internet | **SEGURIDAD** | • Confidencialidad<br>• Integridad<br>• Autenticidad<br>• Responsabilidad |
| **RNF-006** | Tu contraseña debe estar protegida y nadie, ni siquiera los administradores, pueden verla una vez que la guardas | **SEGURIDAD** | • Confidencialidad<br>• Integridad<br>• Autenticidad<br>• Responsabilidad |
| **RNF-007** | La aplicación debe tener un sistema de roles que controle qué puede hacer cada tipo de usuario: clientes, administradores y repartidores | **SEGURIDAD** | • Confidencialidad<br>• Integridad<br>• Autenticidad<br>• Responsabilidad |
| **RNF-008** | Todos los datos personales, direcciones y comprobantes de pago están protegidos y encriptados para mantener tu privacidad | **SEGURIDAD** | • Confidencialidad<br>• Integridad<br>• Autenticidad<br>• Responsabilidad |
| **RNF-009** | El sistema debe verificar que los datos que ingresas (correos, teléfonos, direcciones) sean correctos antes de guardarlos | **SEGURIDAD** | • Confidencialidad<br>• Integridad<br>• Autenticidad<br>• Responsabilidad |
| **RNF-010** | La tienda debe estar disponible las 24 horas del día, todos los días de la semana para que puedas comprar cuando lo necesites | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| **RNF-011** | Si ocurre algún error mientras realizas una compra, el sistema debe proteger tu información y permitirte intentarlo nuevamente | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| **RNF-012** | El carrito de compras debe mantener tus productos guardados incluso si cierras el navegador o se interrumpe tu conexión a internet | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| **RNF-013** | El sistema debe enviar notificaciones automáticas por correo electrónico sobre tus pedidos, cambios de estado y ofertas especiales | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| **RNF-014** | Si tienes problemas al cargar una página, el sistema debe mostrarte un mensaje claro explicando qué sucedió y cómo solucionarlo | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |
| **RNF-015** | La aplicación debe incluir todas las funcionalidades descritas en los requisitos funcionales: catálogo, carrito, pagos, seguimiento y administración | **ADECUACIÓN FUNCIONAL** | • Completitud<br>• Corrección<br>• Pertinencia |
| **RNF-016** | Puedes navegar y ver el catálogo de productos sin necesidad de registrarte, solo necesitas crear cuenta para comprar | **ADECUACIÓN FUNCIONAL** | • Completitud<br>• Corrección<br>• Pertinencia |
| **RNF-017** | Cada funcionalidad del sistema (búsqueda, carrito, pago, seguimiento) debe hacer exactamente lo que se espera de ella sin errores | **ADECUACIÓN FUNCIONAL** | • Completitud<br>• Corrección<br>• Pertinencia |
| **RNF-018** | El sistema debe permitirte realizar todas las acciones necesarias para una compra completa: desde buscar productos hasta recibir tu pedido | **ADECUACIÓN FUNCIONAL** | • Completitud<br>• Corrección<br>• Pertinencia |
| **RNF-019** | El sistema debe ser fácil de actualizar y corregir sin necesidad de detener el servicio por largos períodos | **MANTENIBILIDAD** | • Modularidad<br>• Analizable<br>• Capacidad de ser modificado<br>• Capacidad de ser probado |
| **RNF-020** | Cuando se agreguen nuevas funcionalidades o se corrijan errores, no deben afectar las partes del sistema que ya funcionan correctamente | **MANTENIBILIDAD** | • Modularidad<br>• Analizable<br>• Capacidad de ser modificado<br>• Capacidad de ser probado |
| **RNF-021** | El sistema debe estar organizado en módulos independientes (usuarios, productos, pedidos, entregas) para facilitar mejoras futuras | **MANTENIBILIDAD** | • Modularidad<br>• Analizable<br>• Capacidad de ser modificado<br>• Capacidad de ser probado |
| **RNF-022** | Cada cambio o actualización debe probarse antes de aplicarse en la tienda para asegurar que no cause problemas a los clientes | **MANTENIBILIDAD** | • Modularidad<br>• Analizable<br>• Capacidad de ser modificado<br>• Capacidad de ser probado |
| **RNF-023** | La aplicación debe funcionar correctamente en los navegadores web más utilizados: Chrome, Firefox, Safari y Edge | **PORTABILIDAD** | • Adaptabilidad<br>• Facilidad de instalación<br>• Intercambiable |
| **RNF-024** | La tienda debe adaptarse automáticamente a cualquier tamaño de pantalla: computadoras, tablets y teléfonos móviles | **PORTABILIDAD** | • Adaptabilidad<br>• Facilidad de instalación<br>• Intercambiable |
| **RNF-025** | En dispositivos móviles, todos los botones e imágenes deben tener el tamaño adecuado para tocar fácilmente con el dedo | **PORTABILIDAD** | • Adaptabilidad<br>• Facilidad de instalación<br>• Intercambiable |
| **RNF-026** | Los administradores deben poder exportar información de productos y pedidos para analizarla en Excel o importarla a otros sistemas | **PORTABILIDAD** | • Adaptabilidad<br>• Facilidad de instalación<br>• Intercambiable |
| **RNF-027** | El sistema debe permitir integrarse con servicios externos como Google para inicio de sesión y pasarelas de pago | **COMPATIBILIDAD** | • Coexistencia<br>• Facilidad para interoperar |
| **RNF-028** | La aplicación debe poder compartir información con otros sistemas de la empresa sin perder datos o generar conflictos | **COMPATIBILIDAD** | • Coexistencia<br>• Facilidad para interoperar |
| **RNF-029** | El sistema debe permitir la integración futura con aplicaciones móviles nativas sin requerir cambios mayores | **COMPATIBILIDAD** | • Coexistencia<br>• Facilidad para interoperar |
| **RNF-030** | La tienda debe poder conectarse con servicios de mensajería para notificaciones (correo, SMS) y servicios de mapas para entregas | **COMPATIBILIDAD** | • Coexistencia<br>• Facilidad para interoperar |
| **RNF-031** | La interfaz debe ser fácil de entender e intuitiva, permitiéndote realizar compras sin necesidad de instrucciones complicadas | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-032** | Si es tu primera vez usando la tienda, debes poder aprender a navegar y comprar en menos de 5 minutos | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-033** | El sistema debe mostrar mensajes de confirmación cuando realizas acciones importantes como agregar productos al carrito o completar un pedido | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-034** | Si cometes un error al llenar un formulario (correo mal escrito, dirección incompleta), el sistema debe avisarte antes de continuar | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-035** | El diseño visual debe ser atractivo, profesional y consistente en todas las páginas de la tienda | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-036** | Los repartidores deben poder verificar fácilmente el estado y ubicación de las entregas asignadas desde sus dispositivos móviles | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-037** | Los usuarios registrados deben poder modificar su información personal, direcciones de envío y preferencias de privacidad | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-038** | El sistema debe contar con soporte técnico disponible si tienes dificultades para utilizar la aplicación | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-039** | Los textos, botones e imágenes deben tener suficiente contraste de colores para que sean legibles por personas con dificultades visuales | **USABILIDAD** | • Inteligibilidad<br>• Aprendizaje<br>• Operabilidad<br>• Protección contra errores<br>• Estética<br>• Accesibilidad |
| **RNF-040** | El sistema debe registrar automáticamente todas las acciones importantes: compras, cambios de estado de pedidos, modificaciones de productos y accesos al sistema | **FIABILIDAD** | • Madurez<br>• Disponibilidad<br>• Tolerancia a fallos<br>• Capacidad de recuperación |

---

## RESUMEN POR CATEGORÍAS

### 📊 RENDIMIENTO (4 requisitos)
La aplicación está optimizada para cargar rápidamente, manejar múltiples usuarios simultáneos y mostrar información de forma eficiente. Se implementan técnicas de caché y carga progresiva de imágenes.

### 🔒 SEGURIDAD (5 requisitos)
Sistema robusto de protección que incluye encriptación de contraseñas, control de acceso por roles, protección contra ataques comunes y validación de todos los datos ingresados.

### 🛡️ FIABILIDAD (6 requisitos)
La tienda está disponible 24/7 con mecanismos de recuperación ante errores, persistencia del carrito de compras, notificaciones automáticas y mensajes claros ante problemas.

### ✅ ADECUACIÓN FUNCIONAL (4 requisitos)
El sistema cumple completamente con todas las funcionalidades necesarias para la operación de la tienda: catálogo, carrito, pagos, seguimiento y administración.

### 🔧 MANTENIBILIDAD (4 requisitos)
Arquitectura modular que facilita actualizaciones, correcciones y nuevas funcionalidades sin afectar el servicio. Cada cambio se prueba antes de aplicarse.

### 🌐 PORTABILIDAD (4 requisitos)
Compatibilidad con todos los navegadores principales y dispositivos (computadoras, tablets, móviles). Capacidad de exportar e importar datos para integración con otros sistemas.

### 🔗 COMPATIBILIDAD (4 requisitos)
Preparado para integrarse con servicios externos (Google OAuth, pasarelas de pago, servicios de mapas) y sistemas empresariales. Base sólida para futuras aplicaciones móviles.

### 👥 USABILIDAD (9 requisitos)
Interfaz intuitiva y fácil de usar con curva de aprendizaje mínima, mensajes de confirmación, prevención de errores, diseño atractivo y accesible para todos los usuarios.

---

## CARACTERÍSTICAS DESTACADAS

### 🚀 Optimización de Rendimiento
- ✅ Carga de páginas en menos de 4 segundos
- ✅ Soporte para 100+ usuarios simultáneos
- ✅ Carga progresiva de imágenes
- ✅ Sistema de caché inteligente

### 🔐 Seguridad Avanzada
- ✅ Contraseñas encriptadas
- ✅ Sistema de roles (cliente, admin, delivery)
- ✅ Protección contra ataques comunes
- ✅ Validación y sanitización de datos

### 📱 Responsive Design
- ✅ Compatible con todos los dispositivos
- ✅ Interfaz adaptable automáticamente
- ✅ Optimizado para pantallas táctiles
- ✅ Funciona en todos los navegadores principales

### 🛠️ Mantenibilidad
- ✅ Arquitectura modular
- ✅ Pruebas antes de cada actualización
- ✅ Documentación completa
- ✅ Fácil de actualizar y corregir

### 🌟 Experiencia de Usuario
- ✅ Interfaz intuitiva y atractiva
- ✅ Mensajes claros y confirmaciones
- ✅ Prevención de errores
- ✅ Soporte técnico disponible
- ✅ Accesible para todos

---

## CUMPLIMIENTO DE ESTÁNDARES

Este proyecto cumple con el **estándar internacional ISO/IEC 25010** que define los criterios de calidad para sistemas de software, garantizando:

- ✅ **Calidad del producto**: Funcionalidad, confiabilidad, usabilidad, eficiencia, mantenibilidad y portabilidad
- ✅ **Calidad en uso**: Efectividad, eficiencia, satisfacción, libertad de riesgo y cobertura de contexto
- ✅ **Seguridad**: Confidencialidad, integridad, autenticidad y responsabilidad

---

**Documento generado:** 2 de Noviembre, 2025  
**Proyecto:** Angelow - Sistema de Gestión de Ropa Infantil  
**Versión:** 1.0  
**Total de Requisitos No Funcionales:** 40 RNF

---

## NOTAS IMPORTANTES

Este documento está diseñado para ser entendible por clientes y stakeholders no técnicos. Cada requisito describe **QUÉ** debe hacer el sistema y **POR QUÉ** es importante, sin entrar en detalles técnicos de **CÓMO** se implementa.

Para detalles técnicos de implementación, consultar la documentación técnica en `/docs/` y `/database/README.md`.
