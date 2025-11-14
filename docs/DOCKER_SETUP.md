# Docker Setup para Angelow (PoC)

Este documento está diseñado para ayudarte a configurar Docker en Windows y usarlo para correr un PoC de microservicios localmente (ej. `user-service`).

## ✅ Requisitos
- Docker Desktop para Windows (con WSL2 si es posible)
- CPU 4+, 8GB RAM recomendada
- Git, Composer y PHP (opcional si desarrollas localmente fuera de los contenedores)

## 🧩 Por qué usar Docker
- Empaqueta la aplicación y sus dependencias en imágenes reproducibles.
- Aisla servicios (cada microservicio con su propio contenedor y base de datos).
- Facilita el despliegue y la escalabilidad (Kubernetes, Docker Compose).

## 🔧 Pasos para preparar Docker Desktop en Windows
1. Instala Docker Desktop: https://www.docker.com/products/docker-desktop
2. Habilita WSL 2 en Windows (recomendado):
   - En PowerShell (administrador):
```powershell
wsl --install
# o seguir las instrucciones de Docker Desktop para activar WSL 2
```
3. En Docker Desktop -> Settings -> General -> activa "Use the WSL 2 based engine".
4. Ajusta recursos: Docker Desktop -> Resources -> CPU / Memory (ej. 4 CPU, 8GB RAM).
5. En "File sharing" o "Resources -> File Sharing" añade el directorio donde trabajarás si necesitas montar rutas desde Windows (ej. C:\dev\angelow-microservices). Preferible trabajar desde WSL (`/home/<user>`) por rendimiento.

## 🧭 Recomendaciones de rendimiento en Windows
- Trabajar dentro de WSL2 es mucho más rápido que montar carpetas de Windows en Linux contenedores.
- Si trabajas desde `C:\`, puedes usar `:cached` o `:delegated` en volúmenes para mejorar rendimiento.
- Si usas Laravel, ejecuta `composer install` en WSL o dentro del contenedor.

## 🗃 Archivos de ejemplo (PoC)
A continuación incluimos un `docker-compose.poc.yml` de ejemplo para levantar un `user-service` (PHP), `mysql` y `rabbitmq`.

### `docker-compose.poc.yml`

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
    volumes:
      - user-db-data:/var/lib/mysql
    ports:
      - "33061:3306"

  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "15672:15672" # UI
      - "5672:5672"   # AMQP port

  user-service:
    build:
      context: ./user-service
      dockerfile: Dockerfile
    environment:
      APP_ENV: local
      DB_HOST: user-db
      DB_DATABASE: user_service_db
      DB_USERNAME: user
      DB_PASSWORD: secret
      RABBITMQ_HOST: rabbitmq
    depends_on:
      - user-db
      - rabbitmq
    ports:
      - "8001:80"
    volumes:
      - ./user-service:/var/www/html

volumes:
  user-db-data:
```

> Nota: este PoC asume que tienes una carpeta `user-service/` con Dockerfile y un proyecto PHP (Laravel o Lumen).

### `Dockerfile` ejemplo para `user-service`

```dockerfile
FROM php:8.1-apache

RUN apt-get update && apt-get install -y git unzip libzip-dev
RUN docker-php-ext-install pdo pdo_mysql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Configuración de permisos y módulos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN a2enmod rewrite

# Instalar dependencias PHP
RUN composer install --no-dev --no-interaction --prefer-dist

EXPOSE 80
```

## ⚙️ Comandos principales (PowerShell / CMD)
- Levantar los servicios:
```powershell
cd C:\dev\angelow-microservices
docker compose -f docker-compose.poc.yml up -d --build
```
- Ver logs:
```powershell
docker compose -f docker-compose.poc.yml logs -f user-service
```
- Ejecutar migraciones dentro del contenedor (Laravel example):
```powershell
# entra en el contenedor (ej. user-service)
docker compose -f docker-compose.poc.yml exec user-service bash
php artisan migrate
```
- Importar un dump SQL (desde Windows):
```powershell
docker cp C:\ruta\user_service.sql $(docker compose -f docker-compose.poc.yml ps -q user-db):/tmp/user_service.sql
docker compose exec user-db bash -c "mysql -u root -pexample user_service_db < /tmp/user_service.sql"
```

## 🔒 Tips de seguridad y networking
- En producción no expongas puertos de DB a internet; usa redes internas o VPN.
- Usa secretos (Docker secrets) para variables sensibles -> no guardes contraseñas en `.env` dentro del repo.

## ♻️ Dev Tip: live reload
- Para desarrollo, monta el volumen `./user-service:/var/www/html` y en Laravel ejecuta `php artisan serve` o usa `supervisord` para procesos en background.

## ⛑️ Resolución de problemas
- Docker Desktop consume mucha RAM; ajusta en Settings.
- Si los contenedores no inician, revisa `docker compose ps` y `docker compose logs`.
- En Windows, problemas de permisos en `storage/` -> `chown -R www-data:www-data` dentro del contenedor.

---

## ¿Qué sigo creando para ti?
Si quieres, creo ahora la estructura `user-service` con endpoints `login` y `register` y archivos Docker listos para probar con `docker compose`.
