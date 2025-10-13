<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Productos al Carrito - Prueba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .session-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .session-info h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        
        .session-info p {
            color: #424242;
            margin: 5px 0;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .product-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .product-price {
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .variant-select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-buttons button {
            flex: 1;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            display: none;
        }
        
        .message.show {
            display: block;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Agregar Productos al Carrito</h1>
        
        <div class="card">
            <div class="session-info">
                <h3><i class="fas fa-info-circle"></i> Información de Sesión</h3>
                <p id="sessionInfo">Cargando...</p>
            </div>
            
            <div id="message" class="message"></div>
            
            <h2>Productos Disponibles</h2>
            <div id="productsContainer" class="products-grid">
                <p style="text-align: center; padding: 20px;">Cargando productos...</p>
            </div>
            
            <div class="action-buttons">
                <button class="btn-secondary" onclick="window.location.href='<?= BASE_URL ?>/tienda/cart.php'">
                    <i class="fas fa-shopping-cart"></i> Ver Carrito
                </button>
                <button class="btn-success" onclick="window.location.href='<?= BASE_URL ?>'">
                    <i class="fas fa-home"></i> Ir a la Tienda
                </button>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = window.location.origin + '/angelow';
        
        // Mostrar información de sesión
        async function loadSessionInfo() {
            try {
                const response = await fetch(`${baseUrl}/ajax/cart/get_cart_count.php`);
                const data = await response.json();
                
                document.getElementById('sessionInfo').innerHTML = `
                    <strong>Items en carrito:</strong> ${data.count || 0}<br>
                    <strong>Estado:</strong> ${data.success ? 'Sesión activa' : 'Error'}
                `;
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Cargar productos
        async function loadProducts() {
            try {
                // Por ahora, vamos a crear productos de prueba manualmente
                // En producción, esto vendría de una API
                const products = await fetchProducts();
                
                const container = document.getElementById('productsContainer');
                
                if (products.length === 0) {
                    container.innerHTML = '<p style="text-align: center;">No hay productos disponibles</p>';
                    return;
                }
                
                container.innerHTML = '';
                
                products.forEach(product => {
                    const card = createProductCard(product);
                    container.appendChild(card);
                });
                
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('productsContainer').innerHTML = 
                    '<p style="text-align: center; color: red;">Error al cargar productos</p>';
            }
        }
        
        async function fetchProducts() {
            // Aquí deberías hacer una llamada a tu API de productos
            // Por ahora, vamos a obtener el producto de prueba
            const response = await fetch(`${baseUrl}/ajax/busqueda/search.php?term=ropa`);
            const data = await response.json();
            
            if (data.suggestions && data.suggestions.length > 0) {
                // Obtener detalles completos de cada producto
                const products = [];
                for (const suggestion of data.suggestions) {
                    const details = await getProductDetails(suggestion.id);
                    if (details) {
                        products.push(details);
                    }
                }
                return products;
            }
            
            return [];
        }
        
        async function getProductDetails(productId) {
            try {
                // Crear una petición para obtener detalles del producto
                const response = await fetch(`${baseUrl}/ajax/get_product_details.php?id=${productId}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error getting product details:', error);
                return null;
            }
        }
        
        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card';
            
            card.innerHTML = `
                <img src="${baseUrl}/${product.image_path}" 
                     alt="${product.name}" 
                     class="product-image"
                     onerror="this.src='${baseUrl}/uploads/products/default-product.jpg'">
                <div class="product-name">${product.name}</div>
                <div class="product-price">$${formatPrice(product.price)}</div>
                
                ${product.colors && product.colors.length > 0 ? `
                    <select class="variant-select" id="color-${product.id}">
                        <option value="">Selecciona un color</option>
                        ${product.colors.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                    </select>
                ` : ''}
                
                <div id="sizes-${product.id}"></div>
                
                <button onclick="addToCart(${product.id})">
                    <i class="fas fa-cart-plus"></i> Agregar al Carrito
                </button>
            `;
            
            // Si hay colores, agregar event listener para cargar tallas
            if (product.colors && product.colors.length > 0) {
                const colorSelect = card.querySelector(`#color-${product.id}`);
                colorSelect.addEventListener('change', async (e) => {
                    await loadSizes(product.id, e.target.value);
                });
            }
            
            return card;
        }
        
        async function loadSizes(productId, colorVariantId) {
            // Aquí deberías cargar las tallas disponibles para el color seleccionado
            const sizesContainer = document.getElementById(`sizes-${productId}`);
            
            if (!colorVariantId) {
                sizesContainer.innerHTML = '';
                return;
            }
            
            // Simulación - en producción esto vendría de tu API
            sizesContainer.innerHTML = `
                <select class="variant-select" id="size-${productId}">
                    <option value="">Selecciona una talla</option>
                    <option value="16">XS</option>
                    <option value="17">S</option>
                    <option value="18">M</option>
                    <option value="19">L</option>
                </select>
            `;
        }
        
        async function addToCart(productId) {
            const colorSelect = document.getElementById(`color-${productId}`);
            const sizeSelect = document.getElementById(`size-${productId}`);
            
            const colorVariantId = colorSelect ? colorSelect.value : null;
            const sizeVariantId = sizeSelect ? sizeSelect.value : null;
            
            if (colorSelect && !colorVariantId) {
                showMessage('Por favor selecciona un color', 'error');
                return;
            }
            
            if (sizeSelect && !sizeVariantId) {
                showMessage('Por favor selecciona una talla', 'error');
                return;
            }
            
            try {
                const response = await fetch(`${baseUrl}/tienda/api/cart/add-cart.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        color_variant_id: colorVariantId,
                        size_variant_id: sizeVariantId,
                        quantity: 1
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Producto agregado al carrito correctamente', 'success');
                    loadSessionInfo(); // Actualizar contador
                } else {
                    showMessage(data.error || 'Error al agregar al carrito', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error al agregar al carrito', 'error');
            }
        }
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = `message ${type} show`;
            
            setTimeout(() => {
                messageDiv.classList.remove('show');
            }, 5000);
        }
        
        function formatPrice(price) {
            return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        
        // Cargar al inicio
        loadSessionInfo();
        loadProducts();
    </script>
</body>
</html>
