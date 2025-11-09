# 🚀 Guía Rápida: Visualizar Diagrama de Clases UML

## ⚡ Visualización Rápida

### Método 1: VS Code (Recomendado) ✅

1. **Instalar extensión PlantUML:**
   ```
   Ext: PlantUML (jebbs.plantuml)
   ```

2. **Abrir el archivo:**
   - Navegar a `docs/DIAGRAMA_CLASES_UML.puml`

3. **Ver el diagrama:**
   - Presionar `Alt + D` (Windows/Linux)
   - O `Cmd + D` (Mac)
   - O clic derecho → "Preview Current Diagram"

4. **Exportar imagen:**
   - Clic derecho en el diagrama
   - "Export Current Diagram" → Seleccionar formato (PNG, SVG, PDF)

---

### Método 2: Online PlantUML Editor 🌐

1. **Ir a:** https://www.plantuml.com/plantuml/uml/

2. **Copiar contenido:**
   - Abrir `docs/DIAGRAMA_CLASES_UML.puml`
   - Copiar todo el contenido

3. **Pegar en el editor online**

4. **Ver el diagrama generado automáticamente**

5. **Descargar imagen:**
   - Botón "PNG" o "SVG" en la parte superior

---

### Método 3: Línea de Comandos (Avanzado) 💻

**Requisitos:**
- Java instalado
- PlantUML JAR descargado

**Comandos:**

```bash
# Navegar a la carpeta docs
cd c:\laragon\www\angelow\docs

# Generar imagen PNG
java -jar plantuml.jar DIAGRAMA_CLASES_UML.puml

# O si tienes PlantUML en PATH:
plantuml DIAGRAMA_CLASES_UML.puml

# Generar SVG (escalable)
plantuml -tsvg DIAGRAMA_CLASES_UML.puml
```

Esto generará:
- `DIAGRAMA_CLASES_UML.png` (imagen)
- `DIAGRAMA_CLASES_UML.svg` (vector escalable)

---

## 📖 Documentación Completa

Para entender el diagrama en detalle, leer:
- **`DIAGRAMA_CLASES_EXPLICACION.md`** - Explicación completa con:
  - Descripción de todas las clases
  - Relaciones y multiplicidades
  - Atributos y métodos
  - Flujos de negocio
  - Convenciones utilizadas

---

## 🔍 Navegación del Diagrama

El diagrama incluye:

### 📌 Clases Principales
- **Usuario** (abstracta) → Cliente, Administrador
- **Producto** → VarianteColor → VarianteTalla
- **Carrito** → ItemCarrito
- **Orden** → ItemOrden
- **CodigoDescuento** → DescuentoPorcentaje, DescuentoMontoFijo, DescuentoEnvioGratis

### 🔗 Relaciones Visuales
- `<|--` Herencia (triángulo vacío)
- `*--` Composición (rombo relleno)
- `o--` Agregación (rombo vacío)
- `--` Asociación (línea simple)
- `..>` Dependencia (línea punteada)

### 📊 Multiplicidades
- `1` - Uno
- `0..1` - Cero o uno
- `0..*` - Cero o muchos
- `1..*` - Uno o muchos

---

## 💡 Consejos

1. **Vista previa en VS Code:**
   - Usa zoom con `Ctrl + Scroll` para ver detalles
   - Navega el diagrama con clic y arrastre

2. **Exportar para presentaciones:**
   - SVG: mejor calidad para documentos
   - PNG: fácil de insertar en cualquier lado
   - PDF: ideal para imprimir

3. **Modificar el diagrama:**
   - Editar `DIAGRAMA_CLASES_UML.puml`
   - Guardar y la vista previa se actualiza automáticamente

---

## 🎨 Personalización

El diagrama usa colores definidos:

```plantuml
!define POSITIVECOLOR #10b981  // Verde (éxito)
!define NEUTRALCOLOR #667eea   // Azul/Púrpura (neutro)
!define NEGATIVECOLOR #ef4444  // Rojo (errores)
```

Para modificar colores, editar estas líneas en el archivo `.puml`.

---

## 🆘 Problemas Comunes

### El diagrama no se muestra en VS Code
**Solución:**
1. Verificar que la extensión PlantUML esté instalada
2. Verificar que Java esté instalado (`java -version`)
3. Reiniciar VS Code

### Error de generación
**Solución:**
1. Verificar sintaxis PlantUML
2. Verificar que el archivo tenga extensión `.puml`
3. Verificar conexión a internet (para renderizado online)

### Diagrama muy grande
**Solución:**
1. Usar zoom en VS Code
2. Exportar como SVG y abrir en navegador
3. Dividir en sub-diagramas si es necesario

---

## 📚 Referencias

- **PlantUML Official:** https://plantuml.com/
- **PlantUML Class Diagram:** https://plantuml.com/class-diagram
- **VS Code Extension:** https://marketplace.visualstudio.com/items?itemName=jebbs.plantuml

---

**¡Listo! Ahora puedes visualizar el diagrama completo de clases del sistema AngeloW.** 🎉

Para más información, consultar [`DIAGRAMA_CLASES_EXPLICACION.md`](DIAGRAMA_CLASES_EXPLICACION.md).
