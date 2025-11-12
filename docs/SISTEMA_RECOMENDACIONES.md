# Sistema de Recomendaciones Inteligente - Dashboard Usuario

## 🎯 Descripción General

El sistema de recomendaciones implementado en el dashboard de usuario utiliza un algoritmo multicapa sofisticado que analiza múltiples factores para ofrecer productos personalizados y relevantes para cada usuario.

## 🧠 Algoritmo de Recomendación

### Factores Considerados (Score 0-100)

El sistema calcula un `recommendation_score` para cada producto basándose en:

#### 1. Categorías Preferidas (40 puntos máx.)
- **Peso: 40%**
- Analiza las categorías de productos en el wishlist del usuario
- Analiza las categorías de productos comprados previamente
- Mayor frecuencia de compra = mayor puntuación
- **Fórmula**: Si el producto pertenece a una categoría preferida = +40 puntos

#### 2. Popularidad del Producto (25 puntos máx.)
- **Peso: 25%**
- Basado en el número total de ventas del producto
- Productos más vendidos obtienen mayor puntuación
- **Fórmula**: `MIN(total_ventas / 10, 25)` puntos

#### 3. Valoración de Usuarios (20 puntos máx.)
- **Peso: 20%**
- Promedio de valoraciones de usuarios (1-5 estrellas)
- Productos mejor valorados = mayor relevancia
- **Fórmula**: `promedio_valoración * 4` puntos

#### 4. Novedad del Producto (15 puntos máx.)
- **Peso: 15%**
- Productos nuevos reciben impulso temporal
- La puntuación decae con el tiempo
- **Escala**:
  - Menos de 30 días: 15 puntos
  - Entre 30-60 días: 10 puntos
  - Entre 60-90 días: 5 puntos
  - Más de 90 días: 0 puntos

## 📊 Proceso de Recomendación

```
1. Análisis de Preferencias del Usuario
   ├── Obtener categorías de wishlist
   ├── Obtener categorías de compras anteriores
   └── Calcular score por categoría

2. Consulta de Productos Candidatos
   ├── Aplicar filtros de exclusión
   ├── Calcular recommendation_score
   └── Ordenar por score descendente

3. Filtros de Exclusión
   ├── Productos ya en wishlist
   ├── Productos comprados en últimos 30 días
   └── Productos inactivos

4. Complementar Resultados (si necesario)
   ├── Si menos de 6 productos recomendados
   └── Agregar productos populares generales

5. Retornar Top 6 Productos
```

## 🔍 Consulta SQL Optimizada

La consulta principal utiliza:
- **JOINs optimizados** para obtener datos relacionados
- **Subconsultas agregadas** para calcular ventas y valoraciones
- **Índices implícitos** en foreign keys (user_id, product_id, category_id)
- **COALESCE** para manejar valores NULL
- **LIMIT 6** para retornar exactamente 6 productos

## 💡 Ventajas del Sistema

1. **Personalización Real**: Basado en comportamiento real del usuario
2. **Multicapa**: Combina múltiples señales para mayor precisión
3. **Balanceado**: Mezcla preferencias personales con tendencias globales
4. **Novedad**: Promueve productos nuevos sin sacrificar relevancia
5. **Exclusión Inteligente**: Evita recomendar productos ya conocidos/comprados
6. **Fallback**: Siempre muestra productos, incluso para usuarios nuevos

## 📈 Casos de Uso

### Usuario Nuevo (Sin Historial)
- **Resultado**: Productos populares y mejor valorados
- **Lógica**: Fallback a productos con alto total_sales y avg_rating
- **Beneficio**: Experiencia inmediata sin datos previos

### Usuario con Wishlist
- **Resultado**: Productos de categorías similares
- **Lógica**: Bonus de 40 puntos por categoría preferida
- **Beneficio**: Recomendaciones altamente relevantes

### Usuario con Compras
- **Resultado**: Productos complementarios o similares
- **Lógica**: Score basado en frecuencia de compra por categoría
- **Beneficio**: Sugiere productos que probablemente le interesen

### Usuario Activo
- **Resultado**: Mix de preferencias personales y novedades
- **Lógica**: Combina todos los factores del algoritmo
- **Beneficio**: Balance entre familiaridad y descubrimiento

## 🎨 Características de UI/UX

### Shimmer Loading
- Placeholders animados mientras cargan los productos
- Mejora la percepción de velocidad
- Experiencia profesional y pulida

### Tarjetas Interactivas
- Animaciones suaves en hover
- Botón de wishlist con feedback visual
- Lazy loading de imágenes
- Transiciones fluidas

### Sistema de Notificaciones
- Toast notifications para acciones del usuario
- 4 tipos: success, error, warning, info
- Auto-cierre después de 5 segundos
- Animaciones CSS personalizadas

## 🔧 Mantenimiento y Mejoras Futuras

### Posibles Mejoras

1. **Machine Learning**
   - Implementar algoritmos de filtrado colaborativo
   - Usar TensorFlow.js para predicciones en tiempo real

2. **A/B Testing**
   - Experimentar con diferentes pesos de factores
   - Medir tasa de conversión por tipo de recomendación

3. **Tiempo Real**
   - Actualizar recomendaciones basadas en navegación actual
   - Usar WebSockets para actualizaciones en vivo

4. **Análisis Avanzado**
   - Tracking de clicks en recomendaciones
   - Métricas de efectividad (CTR, conversión)
   - Dashboard de analytics para administradores

5. **Personalización Demográfica**
   - Considerar edad de los hijos
   - Género preferido (niño/niña)
   - Rango de precios preferido

## 📊 Métricas de Éxito

Para medir la efectividad del sistema:

- **CTR (Click-Through Rate)**: % de clicks en productos recomendados
- **Conversión**: % de productos recomendados que se compran
- **Engagement**: Tiempo promedio en productos recomendados
- **Diversidad**: Variedad de categorías recomendadas
- **Satisfacción**: Feedback directo de usuarios

## 🚀 Performance

### Optimizaciones Implementadas

1. **Consulta Única**: Un solo query para obtener todas las recomendaciones
2. **Índices**: Uso de índices en columnas clave (user_id, product_id)
3. **LIMIT**: Restringir resultados desde la base de datos
4. **Lazy Loading**: Cargar imágenes solo cuando sean visibles
5. **Cache**: Posibilidad de cachear recomendaciones (futuro)

### Tiempo de Respuesta Esperado
- **Usuario nuevo**: ~50-100ms
- **Usuario con historial**: ~100-200ms
- **Carga de imágenes**: Progresiva (lazy loading)

## 📝 Ejemplo de Implementación

```php
// El sistema calcula automáticamente:
$recommendedProducts = getRecommendations($userId);

// Cada producto incluye:
// - Datos básicos (id, name, price, etc.)
// - recommendation_score (para debug/ordenamiento)
// - Información de categoría
// - Valoraciones y reviews
// - Estado de wishlist
```

## 🎓 Referencias

- [Collaborative Filtering](https://en.wikipedia.org/wiki/Collaborative_filtering)
- [Content-Based Filtering](https://en.wikipedia.org/wiki/Recommender_system)
- [Hybrid Recommender Systems](https://www.sciencedirect.com/topics/computer-science/hybrid-recommender-system)

---

**Versión**: 1.0  
**Fecha**: 12 de Noviembre, 2025  
**Autor**: Sistema Angelow  
**Estado**: ✅ Implementado y Funcional
