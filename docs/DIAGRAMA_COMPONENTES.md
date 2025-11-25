# Diagrama de Componentes - AngeloW (Resumen)

Este documento acompaña el diagrama `DIAGRAMA_COMPONENTES.puml` y resume los componentes principales del proyecto y sus relaciones.

## Componentes principales

- Cliente / Navegador (Frontend):
  - Consume HTML y CSS/JS, hace peticiones AJAX a `tienda/api/*`, `api/*`, y `users/api/*`.
  - Interactúa con páginas públicas (home, productos, colecciones) y paneles de usuario (mi cuenta, wishlist, carrito).

- Servidor Web / PHP (Back-end):
  - Código PHP organizado en módulos: `tienda/`, `api/`, `admin/`, `auth/`, `users/`, `ajax/`.
  - Sirve páginas SSR (plantillas en `layouts/`) y expone endpoints REST-like para AJAX.
  - Usa librerías de terceros en `vendor/` (PHPMailer, TCPDF, MongoDB client, etc.).

- Base de datos relacional (MySQL):
  - Almacena usuarios, productos, pedidos, carritos, anuncios, sliders, colecciones, etc.
  - Migrations y scripts en `database/migrations/` y `database/scripts/`.

- Base de datos NoSQL (MongoDB Atlas):
  - Usado para datos complementarios (p. ej., analytics, notificaciones o sesiones) según `conexionmongo.php`.

- Almacenamiento de archivos (Uploads):
  - Carpeta `uploads/` para imágenes y recursos subidos por admin/usuario.

- Servicios externos:
  - Payment Gateway (integración en `tienda/api/pay/`)
  - SMTP / Email (PHPMailer, Gmail/SMTP)
  - Geocoding / Map APIs (OpenStreetMap / Google)
  - PDF generator (TCPDF) para facturas e informes

- Background / CLI:
  - Scripts, migraciones y tareas periódicas en `database/scripts`, `tests/` y `database/fixes/`.

## Principales rutas de interacción

- Cliente -> Servidor: HTTP(S) para páginas y API (por ejemplo: `index.php`, `producto/verproducto.php`, `tienda/api/cart`).
- Servidor -> MySQL: consultas para CRUD en productos, usuarios, pedidos y configuraciones del sitio.
- Servidor -> MongoDB: operaciones en colecciones específicas.
- Servidor -> SMTP: envíos de correo para confirmación y notificaciones.
- Servidor -> Payment Gateway: procesamiento y callbacks de pagos.
- Servidor -> PDFLib: generación de PDFs para facturación.

## Recomendaciones (mejoras arquitectónicas opcionales)

- Separar claramente la API en un subdominio `api.angelow.local` o similar para facilitar el versionado.
- Considerar externalizar storage (S3 o similar) y usar un CDN para assets.
- Implementar colas (Redis/RabbitMQ) para trabajo asíncrono (envío de emails, generación de PDFs) para evitar bloqueos en el tiempo de respuesta.

---
Si deseas, puedo ahora:

1. Convertir este diagrama en un diagrama C4 (niveles: System, Container) con más detalle.
2. Añadir una versión que explique las dependencias internas por módulo (ej. `auth -> users table`, `tienda -> orders table`).
3. Integrarlo en CI/CD con un step para generar imágenes SVG/PNG desde PlantUML usando Docker o GitHub Actions.

Di tu preferencia y procedo.  
