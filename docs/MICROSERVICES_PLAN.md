# Plan de Microservicios - Angelow

Este documento resume el mapeo de tablas a microservicios, responsabilidades, pasos de migración y cómo comenzar con un PoC (prueba de concepto) en tu equipo local.

## ✅ Resumen rápido
- El proyecto actual es monolítico con más de 70 tablas.
- Se recomienda migrar por pasos: extraer servicios paquetizados y desacoplados.
- Uso recomendado: Docker + `docker-compose` para PoC local (puedes prescindir de Docker, pero perderás reproducibilidad y facilidad para escalar).

---

## 🗂 Microservicios sugeridos (Bounded Contexts)
A continuación se listan los microservicios propuestos y las tablas relacionadas. Cada microservicio será un proyecto independiente (Laravel o Lumen recomendado para PHP).

1. **User / Identity Service** (autenticación y perfiles)
   - Tablas: `users`, `access_tokens`, `password_resets`, `google_auth`, `login_attempts`, `user_addresses`, `audit_users`
   - Endpoints básicos: `POST /auth/login`, `POST /auth/register`, `GET /users/{id}`
   - Eventos: `user.created`, `user.updated`

2. **Product / Catalog Service**
   - Tablas: `products`, `product_images`, `product_color_variants`, `product_size_variants`, `variant_images`, `product_collections`, `product_reviews`, `product_questions`, `review_votes`
   - Endpoints: `GET /products`, `GET /products/{id}`
   - Eventos: `product.created`, `product.updated`, `product.stock_changed`

3. **Inventory Service**
   - Tablas: `stock_history`, `sizes`, `colors` (o manejar stock en `product-service`)
   - Endpoints: `GET /inventory/{product_id}`
   - Eventos: `stock.reserved`, `stock.released`

4. **Cart Service**
   - Tablas: `carts`, `cart_items`
   - Endpoints: `GET /carts/{user_id}`, `POST /carts/{user_id}/items`

5. **Order Service**
   - Tablas: `orders`, `order_items`, `order_deliveries`, `order_status_history`, `order_views`, `audit_orders`
   - Endpoints: `POST /orders`, `GET /orders/{id}`
   - Eventos: `order.created`, `order.paid`, `order.cancelled`

6. **Payment Service**
   - Tablas: `payment_transactions`, `bank_account_config`, `colombian_banks`
   - Endpoints: `POST /payments/charge`

7. **Discount / Promotions Service**
   - Tablas: `discount_codes`, `discount_code_products`, `discount_code_usage`, `discount_types`, `fixed_amount_discounts`, `percentage_discounts`, `free_shipping_discounts`, `user_applied_discounts`, `bulk_discount_rules`

8. **Shipping / Delivery**
   - Tablas: `shipping_methods`, `shipping_price_rules`, `delivery_cities`, `delivery_waypoints`, `location_tracking`, `delivery_navigation_*`, `driver_statistics`, `delivery_problem_reports`
   - Eventos: `delivery.assigned`, `delivery.started`, `delivery.completed`

9. **Notification Service**
   - Tablas: `notifications`, `notification_queue`, `notification_types`, `notification_preferences`
   - Responsabilidad: envío de correos, push y SMS; suscribirse a eventos de otros servicios (ej. order.created).

10. **Marketing Service**
    - Tablas: `announcements`, `sliders`, `popular_searches`

11. **Wishlist**
    - Tablas: `wishlist`

12. **Audit / Reporting** (opcional centralizado)
    - Tablas: `audit_users`, `audit_orders`, `productos_auditoria`, `eliminaciones_auditoria` (puede centralizar eventos para análisis)

13. **Search / Analytics**
    - Tablas: `search_history`, `popular_searches`, vistas `v_*` para dashboards

---

## 🛣 Estrategia de migración incremental (pasos)
1. Preparar infra local con Docker Compose: DBs separadas y servicios iniciales (PoC).
2. Extraer `user-service` (autenticación): migrar tablas relacionadas y redirigir `angelow` para usar el API login/register.
3. Extraer `product-service` (catalogo): migrar tablas de productos y variantes.
4. Extraer `cart-service` y luego `order-service`. Usar Sagas para manejar transacciones que crucen servicios.
5. Extraer `payment-service`, `inventory-service` y `shipping-service`.
6. Extraer `notification-service` y `discount-service`.
7. Limpiar el monolito gradualmente: mantener solo UI o gateway hasta que todas las piezas estén migradas.

---

## 🔁 Comunicación entre servicios
- **Síncrono**: HTTP/REST (o gRPC para performance) entre servicios para consultas directas.
- **Asíncrono**: RabbitMQ/Kafka para eventos (ej. `order.created`, `product.updated`).
- **API Gateway**: centraliza ruteo, autenticación y rate-limiting.
- **Idempotencia**: importante en eventos y llamadas reintentadas.

---

## 🐳 Docker PoC (ejemplo rápido)
Crea `docker-compose.yml` para tu carpeta de microservicios. Ejemplo mínimo con `user-service` y `mysql`:

```yaml
version: "3.8"
services:
  user-db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: example
      MYSQL_DATABASE: user_service_db
      MYSQL_USER: user
      MYSQL_PASSWORD: secret
    ports:
      - "33061:3306"
    volumes:
      - user-db-data:/var/lib/mysql

  user-service:
    build:
      context: ./user-service
      dockerfile: Dockerfile
    environment:
      DB_HOST: user-db
      DB_DATABASE: user_service_db
      DB_USERNAME: user
      DB_PASSWORD: secret
    depends_on:
      - user-db
    ports:
      - "8001:80"
    volumes:
      - ./user-service:/var/www/html
volumes:
  user-db-data:
```

Asegúrate de crear un `Dockerfile` en `user-service` con PHP + Apache o usa Lumen/Laravel base.

---

## 💾 Migración de datos (paso a paso)
1. Exporta tablas desde el monolito usando `mysqldump`.
2. Crea la nueva BD del servicio y ejecuta las migraciones necesarias.
3. Importa el dump en la BD del microservicio.
4. Implementa endpoints read-only y cambia el monolito para llamarlos.
5. Habilita endpoints write y replica o limpia datos en el monolito.

Comando ejemplo:
```powershell
# Export
mysqldump -u root -p angelow users user_addresses > c:\temp\user_service.sql
# Import en el servicio
mysql -u root -p user_service_db < c:\temp\user_service.sql
```

---

## 🔐 Autenticación y seguridad
- Centralizar identidad (Identity server / OAuth2). Laravel Passport o Keycloak son opciones.
- Los microservicios validan JWT o usan tokens para comunicarse (mTLS recomendado en producción).

---

## 🚀 PoC sugerido
1. `user-service` (intrídice) — extrae tablas de `users`, endpoints `login/register`.
2. `notification-service` — escucha `user.created` y envía bienvenidas.
3. `product-service` — para utilizar en tiempo real en la tienda.

---

## ⚠️ Notas finales
- No migrar todo a microservicios desde el principio si trabajas solo: la complejidad técnica y operativa puede ser alta.
- Si tienes tráfico o equipos que lo justifiquen, sigue migrando por dominios con monitorización y pruebas.

---

## 📌 ¿Quieres que implemente el PoC en tu repo?
Puedo generar la estructura inicial (`user-service` con Dockerfile, `docker-compose.yml` y endpoints básicos). Solo dime si prefieres `Laravel` (más funcionalidades) o `Lumen` (más ligero).