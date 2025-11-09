# Diagrama de Clases UML - Sistema AngeloW

## 📋 Tabla de Contenido
- [Visión General](#visión-general)
- [Jerarquía de Clases](#jerarquía-de-clases)
- [Clases Principales](#clases-principales)
- [Relaciones](#relaciones)
- [Notaciones Utilizadas](#notaciones-utilizadas)
- [Cómo Visualizar el Diagrama](#cómo-visualizar-el-diagrama)

---

## 🎯 Visión General

Este diagrama representa la arquitectura orientada a objetos del sistema **AngeloW**, un e-commerce completo con gestión de usuarios, productos, carritos, órdenes, pagos y descuentos.

### Características del Diagrama:
- ✅ **Clases completas** con atributos tipados
- ✅ **Constructores** para cada clase
- ✅ **Métodos públicos** (+), **protegidos** (#) y **privados** (-)
- ✅ **Herencia** de Usuario → Cliente/Administrador
- ✅ **Relaciones** (composición, agregación, asociación, dependencia)
- ✅ **Multiplicidades** claramente definidas
- ✅ **Notas explicativas** para conceptos clave

---

## 🏗️ Jerarquía de Clases

```
Usuario (abstracta)
├── Cliente
└── Administrador
```

### Usuario (Clase Abstracta)
**Atributos protegidos (#):**
- `id: String` - Identificador único del usuario
- `name: String` - Nombre completo
- `email: String` - Correo electrónico (único)
- `phone: String` - Teléfono de contacto
- `identification_type: String` - Tipo de documento (CC, TI, CE, etc.)
- `identification_number: String` - Número de documento
- `password: String` - Contraseña hasheada
- `image: String` - Ruta de imagen de perfil
- `role: Enum('customer', 'admin')` - Rol del usuario
- `is_blocked: Boolean` - Estado de bloqueo
- `remember_token: String` - Token de "recordar sesión"
- `token_expiry: DateTime` - Expiración del token
- `created_at: DateTime` - Fecha de creación
- `updated_at: DateTime` - Última actualización
- `last_access: DateTime` - Último acceso al sistema

**Métodos públicos (+):**
- `__construct(name, email, phone, password)` - Constructor
- `login(email, password): Boolean` - Autenticación
- `logout(): void` - Cerrar sesión
- `updateProfile(name, phone, image): Boolean` - Actualizar perfil
- `changePassword(oldPassword, newPassword): Boolean` - Cambiar contraseña
- `verifyPassword(password): Boolean` - Verificar contraseña
- `isBlocked(): Boolean` - Verificar si está bloqueado
- `getRole(): String` - Obtener rol
- `authenticate(): Boolean` - Verificar autenticación
- `updateLastAccess(): void` - Actualizar último acceso
- `setRememberToken(token, expiry): void` - Establecer token de sesión

---

## 📦 Clases Principales

### 1. Cliente (Hereda de Usuario)

**Atributos privados (-):**
- `direcciones: List<Direccion>` - Lista de direcciones de envío
- `ordenes: List<Orden>` - Historial de órdenes
- `carrito: Carrito` - Carrito de compras actual
- `donaciones: List<Donacion>` - Historial de donaciones

**Métodos públicos (+):**
- `__construct(name, email, phone, password)` - Constructor específico
- `crearOrden(carritoId): Orden` - Crear orden desde carrito
- `verOrdenes(): List<Orden>` - Obtener órdenes del cliente
- `agregarDireccion(direccion): Boolean` - Agregar nueva dirección
- `obtenerDirecciones(): List<Direccion>` - Obtener todas las direcciones
- `actualizarDireccion(direccionId, datos): Boolean` - Actualizar dirección
- `eliminarDireccion(direccionId): Boolean` - Eliminar dirección
- `verCarrito(): Carrito` - Obtener carrito actual
- `aplicarDescuento(codigo): Boolean` - Aplicar código de descuento
- `realizarDonacion(monto): Donacion` - Realizar donación
- `cancelarOrden(ordenId): Boolean` - Cancelar orden

**Relaciones:**
- 1 Cliente → 0..* Direcciones (Composición)
- 1 Cliente → 0..* Órdenes (Composición)
- 1 Cliente → 1 Carrito (Asociación)
- 1 Cliente → 0..* Donaciones (Composición)

---

### 2. Administrador (Hereda de Usuario)

**Atributos privados (-):**
- `nivel_acceso: String` - Nivel de permisos administrativos

**Métodos públicos (+):**
- `__construct(name, email, phone, password, nivel_acceso)` - Constructor
- `gestionarProductos(): List<Producto>` - Listar productos
- `crearProducto(producto): Boolean` - Crear nuevo producto
- `editarProducto(productoId, datos): Boolean` - Editar producto
- `eliminarProducto(productoId): Boolean` - Eliminar producto
- `gestionarOrdenes(): List<Orden>` - Listar órdenes
- `actualizarEstadoOrden(ordenId, estado): Boolean` - Cambiar estado de orden
- `gestionarCategorias(): List<Categoria>` - Listar categorías
- `crearCategoria(categoria): Boolean` - Crear categoría
- `gestionarDescuentos(): List<CodigoDescuento>` - Listar descuentos
- `generarInformes(): Array` - Generar reportes estadísticos
- `gestionarUsuarios(): List<Usuario>` - Listar usuarios
- `bloquearUsuario(usuarioId): Boolean` - Bloquear/desbloquear usuario
- `verEstadisticas(): Array` - Ver estadísticas del sistema
- `configurarMetodosEnvio(): List<MetodoEnvio>` - Configurar envíos

**Relaciones de dependencia (..>):**
- Gestiona Productos
- Gestiona Órdenes
- Gestiona Categorías
- Gestiona Códigos de Descuento
- Gestiona Usuarios

---

### 3. Producto

**Sistema de Variantes Jerárquico:**
```
Producto
└── VarianteColor (1:N)
    └── VarianteTalla (1:N)
```

**Atributos privados (-):**
- `id: Int` - ID único
- `name: String` - Nombre del producto
- `slug: String` - URL amigable
- `description: Text` - Descripción detallada
- `price: Decimal(10,2)` - Precio base
- `compare_price: Decimal(10,2)` - Precio de comparación
- `category_id: Int` - ID de categoría
- `sku: String` - Código SKU
- `barcode: String` - Código de barras
- `is_active: Boolean` - Estado activo/inactivo
- `is_featured: Boolean` - Producto destacado
- `stock_quantity: Int` - Cantidad en stock
- `low_stock_threshold: Int` - Umbral de stock bajo
- `weight_kg: Decimal(8,2)` - Peso en kilogramos
- `dimensions_cm: String` - Dimensiones
- `material: String` - Material del producto
- `care_instructions: Text` - Instrucciones de cuidado
- `tags: String` - Etiquetas (separadas por comas)
- `meta_title: String` - Título SEO
- `meta_description: String` - Descripción SEO
- `view_count: Int` - Contador de vistas
- `created_at: DateTime` - Fecha de creación
- `updated_at: DateTime` - Última actualización

**Métodos públicos (+):**
- `__construct(name, price, categoryId)` - Constructor
- `agregarImagen(imagen): Boolean` - Agregar imagen
- `agregarVarianteColor(color): VarianteColor` - Agregar color
- `agregarVarianteTalla(talla, precio, cantidad): VarianteTalla` - Agregar talla
- `actualizarStock(cantidad): Boolean` - Actualizar inventario
- `verificarStock(cantidad): Boolean` - Verificar disponibilidad
- `obtenerPrecioFinal(): Decimal` - Calcular precio final
- `obtenerImagenPrincipal(): String` - Obtener imagen principal
- `obtenerVariantes(): List<VarianteTalla>` - Listar variantes
- `actualizarDetalles(datos): Boolean` - Actualizar información
- `activar(): void` - Activar producto
- `desactivar(): void` - Desactivar producto
- `estaActivo(): Boolean` - Verificar estado
- `incrementarVistas(): void` - Incrementar contador de vistas

**Relaciones:**
- N Productos → 1 Categoría (Agregación)
- 1 Producto → 0..* VarianteColor (Composición)
- 1 Producto → 0..* ImagenProducto (Composición)
- N Productos → N Colecciones (Asociación muchos a muchos)

---

### 4. Carrito e ItemCarrito

**Carrito:**
```php
+ __construct(userId, sessionId)
+ agregarItem(productoId, colorVariantId, sizeVariantId, cantidad): Boolean
+ actualizarCantidad(itemId, cantidad): Boolean
+ eliminarItem(itemId): Boolean
+ obtenerItems(): List<ItemCarrito>
+ calcularTotal(): Decimal
+ vaciar(): void
+ transferirCarrito(sessionId, userId): Boolean
+ verificarStock(): Boolean
```

**ItemCarrito:**
```php
+ __construct(cartId, productId, quantity)
+ actualizarCantidad(cantidad): Boolean
+ calcularSubtotal(): Decimal
+ obtenerDetallesProducto(): Array
+ obtenerDetallesVariantes(): Array
```

**Relaciones:**
- 1 Carrito → 0..* ItemCarrito (Composición)
- N ItemCarrito → 1 Producto (Agregación)
- N ItemCarrito → 0..1 VarianteTalla (Agregación opcional)

---

### 5. Orden e ItemOrden

**Orden:**

**Atributos clave:**
- `order_number: String` - Número de orden único
- `status: Enum` - Estados: pending, processing, shipped, delivered, cancelled, returned
- `payment_status: Enum` - Estados: pending, paid, failed, refunded
- `subtotal: Decimal(10,2)` - Subtotal antes de descuentos/envío
- `discount_amount: Decimal(10,2)` - Monto de descuento aplicado
- `shipping_cost: Decimal(10,2)` - Costo de envío
- `tax: Decimal(10,2)` - Impuestos
- `total: Decimal(10,2)` - Total final

**Métodos:**
```php
+ __construct(userId, orderNumber, total)
+ agregarItem(item): Boolean
+ actualizarEstado(estado): Boolean
+ actualizarEstadoPago(estado): Boolean
+ calcularTotal(): Decimal
+ aplicarDescuento(descuento): void
+ obtenerItems(): List<ItemOrden>
+ obtenerDireccionEnvio(): Direccion
+ obtenerMetodoEnvio(): MetodoEnvio
+ generarPDF(): String
+ enviarConfirmacion(): Boolean
+ cancelar(razon): Boolean
```

**Relaciones:**
- N Órdenes → 1 Cliente (Agregación)
- 1 Orden → 1..* ItemOrden (Composición)
- N Órdenes → 1 Dirección (Agregación)
- N Órdenes → 1 MétodoEnvío (Agregación)
- 1 Orden → 1..* TransacciónPago (Asociación)

---

### 6. Sistema de Descuentos

**Estructura:**
```
CodigoDescuento (código base)
├── DescuentoPorcentaje (% con tope máximo)
├── DescuentoMontoFijo ($ con pedido mínimo)
└── DescuentoEnvioGratis (método específico)
```

**CodigoDescuento:**
```php
+ __construct(code, discountTypeId)
+ aplicar(monto): Decimal
+ validar(): Boolean
+ incrementarUso(): void
+ activar(): void
+ desactivar(): void
+ estaVigente(): Boolean
```

**DescuentoPorcentaje:**
```php
+ __construct(discountCodeId, percentage)
+ calcularDescuento(monto): Decimal
// Aplica porcentaje con tope máximo (max_discount_amount)
```

**DescuentoMontoFijo:**
```php
+ __construct(discountCodeId, amount)
+ calcularDescuento(monto): Decimal
// Aplica monto fijo si se cumple pedido mínimo
```

**DescuentoEnvioGratis:**
```php
+ __construct(discountCodeId, shippingMethodId)
+ aplicar(): Boolean
// Aplica envío gratis para método específico
```

**DescuentoAplicado:**
```php
+ __construct(userId, discountCodeId, amount)
+ marcarComoUsado(): void
+ validarVigencia(): Boolean
```

**Relaciones:**
- 1 CodigoDescuento → 0..1 DescuentoPorcentaje (Asociación opcional)
- 1 CodigoDescuento → 0..1 DescuentoMontoFijo (Asociación opcional)
- 1 CodigoDescuento → 0..1 DescuentoEnvioGratis (Asociación opcional)
- 1 Cliente → 0..* DescuentoAplicado (Asociación)
- 1 CodigoDescuento → 0..* DescuentoAplicado (Asociación)

---

## 🔗 Relaciones

### Tipos de Relaciones Utilizadas:

| Símbolo | Tipo | Descripción | Ejemplo |
|---------|------|-------------|---------|
| `<\|--` | **Herencia** | Clase hija extiende clase padre | Cliente extends Usuario |
| `*--` | **Composición** | El contenedor posee y controla el ciclo de vida | Cliente *-- Direccion |
| `o--` | **Agregación** | Relación más débil, el objeto puede existir independiente | Producto o-- Categoria |
| `--` | **Asociación** | Relación bidireccional simple | Cliente -- Carrito |
| `..>` | **Dependencia** | Una clase usa otra temporalmente | Administrador ..> Producto |

### Multiplicidades:

| Notación | Significado |
|----------|-------------|
| `1` | Exactamente uno |
| `0..1` | Cero o uno (opcional) |
| `0..*` | Cero o muchos |
| `1..*` | Uno o muchos |

---

## 🔢 Notaciones Utilizadas

### Modificadores de Acceso:
- `+` **Público** - Accesible desde cualquier lugar
- `-` **Privado** - Solo accesible dentro de la clase
- `#` **Protegido** - Accesible en clase e hijos

### Tipos de Datos:
- `String` - Cadena de texto
- `Int` - Número entero
- `Decimal(10,2)` - Número decimal con 10 dígitos totales, 2 decimales
- `Boolean` - Verdadero/Falso
- `DateTime` - Fecha y hora
- `Text` - Texto largo
- `Enum('valor1', 'valor2')` - Tipo enumerado
- `List<Tipo>` - Lista de elementos
- `Array` - Arreglo genérico
- `JSON` - Objeto JSON

---

## 🖼️ Cómo Visualizar el Diagrama

### Opción 1: PlantUML en VS Code

1. **Instalar extensión:**
   ```
   Ext: PlantUML (jebbs.plantuml)
   ```

2. **Abrir el archivo:**
   ```
   docs/DIAGRAMA_CLASES_UML.puml
   ```

3. **Generar vista previa:**
   - Presionar `Alt + D` (Windows/Linux)
   - O `Cmd + D` (Mac)
   - O clic derecho → "Preview Current Diagram"

### Opción 2: Online PlantUML Editor

1. Ir a: https://www.plantuml.com/plantuml/uml/
2. Copiar el contenido de `DIAGRAMA_CLASES_UML.puml`
3. Pegar en el editor online
4. Ver el diagrama generado

### Opción 3: Generar Imagen PNG/SVG

Usar PlantUML desde línea de comandos:

```bash
# Instalar PlantUML (requiere Java)
java -jar plantuml.jar DIAGRAMA_CLASES_UML.puml

# O si tienes PlantUML instalado:
plantuml DIAGRAMA_CLASES_UML.puml
```

Esto generará:
- `DIAGRAMA_CLASES_UML.png` (imagen)
- `DIAGRAMA_CLASES_UML.svg` (vector escalable)

---

## 📊 Estadísticas del Diagrama

- **Total de clases:** 27
- **Clases abstractas:** 1 (Usuario)
- **Clases concretas:** 26
- **Relaciones de herencia:** 2
- **Relaciones de composición:** 15+
- **Relaciones de agregación:** 10+
- **Relaciones de asociación:** 8+
- **Relaciones de dependencia:** 5+

---

## 🎨 Convenciones de Color

El diagrama incluye definiciones de color para mejorar la legibilidad:

```plantuml
!define POSITIVECOLOR #10b981  // Verde (acciones exitosas)
!define NEUTRALCOLOR #667eea   // Azul/Púrpura (neutro)
!define NEGATIVECOLOR #ef4444  // Rojo (errores/alertas)
```

---

## 📝 Notas Importantes

### 1. Sistema de Variantes
El sistema de productos utiliza una estructura jerárquica de variantes:
- **Producto** contiene múltiples **VariantesColor**
- Cada **VarianteColor** contiene múltiples **VariantesTalla**
- Cada **VarianteTalla** tiene precio y stock independiente
- Las imágenes pueden estar asociadas al producto o a variantes específicas

### 2. Estados de Orden
**Estados del pedido:**
- `pending` - Pendiente
- `processing` - En proceso
- `shipped` - Enviado
- `delivered` - Entregado
- `cancelled` - Cancelado
- `returned` - Devuelto

**Estados de pago:**
- `pending` - Pendiente
- `paid` - Pagado
- `failed` - Fallido
- `refunded` - Reembolsado

### 3. Tipos de Descuento
El sistema soporta 3 tipos de descuentos:
1. **Porcentaje** - Descuento por porcentaje con tope máximo opcional
2. **Monto Fijo** - Descuento de monto fijo con pedido mínimo
3. **Envío Gratis** - Envío gratuito para método específico

### 4. Carrito Persistente
- El carrito puede estar asociado a un **usuario registrado** (`user_id`)
- O a una **sesión anónima** (`session_id`)
- Al registrarse, se transfiere el carrito de sesión al usuario
- Los carritos expiran después de un tiempo configurado

---

## 🔄 Flujos Principales

### Flujo de Compra:
```
1. Cliente → verCarrito()
2. Carrito → agregarItem(producto, variante, cantidad)
3. Carrito → calcularTotal()
4. Cliente → aplicarDescuento(codigo)
5. Cliente → crearOrden(carritoId)
6. Orden → generada con items, dirección, método envío
7. TransaccionPago → procesarPago()
8. Orden → actualizarEstadoPago('paid')
9. Orden → enviarConfirmacion()
10. Orden → actualizarEstado('processing')
```

### Flujo de Gestión de Productos (Admin):
```
1. Administrador → crearProducto(producto)
2. Producto → agregarVarianteColor(color)
3. VarianteColor → agregarVarianteTalla(talla, precio, stock)
4. Producto → agregarImagen(imagen)
5. Producto → activar()
```

---

## 📚 Referencias

- **Archivo fuente:** `docs/DIAGRAMA_CLASES_UML.puml`
- **Base de datos:** `angelow.sql`
- **Documentación técnica:** `docs/README.md`
- **Sistema de roles:** `docs/SISTEMA_ROLES.md`

---

## 🔧 Mantenimiento

Este diagrama debe actualizarse cuando:
- ✅ Se agreguen nuevas tablas a la base de datos
- ✅ Se modifiquen estructuras de tablas existentes
- ✅ Se agreguen nuevas relaciones entre entidades
- ✅ Se cambien reglas de negocio importantes
- ✅ Se agreguen nuevos módulos al sistema

**Última actualización:** $(date)
**Versión:** 1.0
**Autor:** Sistema AngeloW Development Team

---

## 📞 Soporte

Para consultas sobre este diagrama o el sistema:
- **Email:** contacto@angelow.com
- **Documentación:** `/docs/`
- **Issues:** Reportar en el sistema de control de versiones

---

*Este diagrama representa una arquitectura orientada a objetos conceptual del sistema AngeloW, mapeando las entidades de la base de datos y la lógica de negocio procedural a un diseño de clases para mejor comprensión y documentación.*
