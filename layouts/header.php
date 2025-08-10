<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'dashboardadmin.php') {
                $redirect_url = defined('BASE_URL') 
                    ? BASE_URL . '/admin/dashboardadmin.php' 
                    : '/admin/dashboardadmin.php';
                
                header("Location: $redirect_url");
                exit();
            }
        }


         if ($user && $user['role'] === 'delivery') {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'dashboardadmin.php') {
                $redirect_url = defined('BASE_URL') 
                    ? BASE_URL . '/delivery/dashboarddeli.php' 
                    : '/delivery/dashboarddeli.php';
                
                header("Location: $redirect_url");
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log('Error al verificar rol de usuario: ' . $e->getMessage());
    }
}
?>
    


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<header class="main-header">
    <div class="header-container">
        <!-- Logo -->
        <div class="content-logo2">
            <a href="<?= BASE_URL ?>/index.html">
                <img src="<?= BASE_URL ?>/images/logo2.png" alt="Angelow - Ropa Infantil" width="100">
            </a>
        </div>

        <div class="search-bar">
            <form action="<?= BASE_URL ?>/tienda/productos.php" method="get" class="search-form">
                <input type="text" name="search" id="header-search" placeholder="Buscar productos..." autocomplete="off">
                <button type="submit" aria-label="Buscar">
                    <i class="fas fa-search"></i>
                </button>
                <div class="search-results" id="search-results"></div>
            </form>
        </div>

        <!-- Iconos de navegación -->
        <div class="header-icons">
            <a href="<?= BASE_URL ?>/users/dashboarduser.php" aria-label="Mi cuenta">
                <i class="fas fa-user"></i>
            </a>
            <a href="<?= BASE_URL ?>/favoritos.html" aria-label="Favoritos">
                <i class="fas fa-heart"></i>
            </a>
            <a href="<?= BASE_URL ?>/tienda/carrito.php" aria-label="Carrito">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">0</span>
            </a>
        </div>
    </div>

    <!-- Navegación principal -->
    <nav class="main-nav">
        <ul>
            <li><a href="<?= BASE_URL ?>/index.html">Inicio</a></li>
            <li class="mega-menu">
                <a href="<?= BASE_URL ?>/ninas.html">Niñas</a>
                <div class="mega-menu-content">
                    <div class="mega-menu-column">
                        <h4>Por categoría</h4>
                        <ul>
                            <li><a href="<?= BASE_URL ?>/ninas-vestidos.html">Vestidos</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-conjuntos.html">Conjuntos</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-pijamas.html">Pijamas</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-zapatos.html">Zapatos</a></li>
                        </ul>
                    </div>
                    <div class="mega-menu-column">
                        <h4>Por edad</h4>
                        <ul>
                            <li><a href="<?= BASE_URL ?>/ninas-0-12m.html">0-12 meses</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-1-3a.html">1-3 años</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-4-6a.html">4-6 años</a></li>
                            <li><a href="<?= BASE_URL ?>/ninas-7-10a.html">7-10 años</a></li>
                        </ul>
                    </div>
                    <div class="mega-menu-column">
                        <img src="/images/mega-menu-ninas.jpg" alt="Colección niñas">
                    </div>
                </div>
            </li>
            <li class="mega-menu">
                <a href="<?= BASE_URL ?>/ninos.html">Niños</a>
                <!-- Contenido similar al de niñas -->
            </li>
            <li><a href="<?= BASE_URL ?>/bebes.html">Bebés</a></li>
            <li><a href="<?= BASE_URL ?>/novedades.html">Novedades</a></li>
            <li><a href="<?= BASE_URL ?>/ofertas.html">Ofertas</a></li>
            <li><a href="<?= BASE_URL ?>/colecciones.html">Colecciones</a></li>
        </ul>
    </nav>
</header>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('header-search');
    const searchResults = document.getElementById('search-results');
    let searchTimeout;
    
    // Variable para almacenar términos buscados previamente
    let searchedTerms = [];
    
    // Función para obtener el historial de búsqueda
    const fetchSearchHistory = async () => {
        try {
            const response = await fetch(`<?= BASE_URL ?>/ajax/busqueda/get_search_history.php`);
            
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON');
            }
            
            const data = await response.json();
            
            if (data && Array.isArray(data.terms)) {
                searchedTerms = data.terms
                    .filter(term => term && typeof term === 'string')
                    .map(term => term.toLowerCase());
            }
        } catch (error) {
            console.error('Error al obtener historial:', error);
            // Continuar sin historial en caso de error
            searchedTerms = [];
        }
    };
    
    // Obtener historial solo si el usuario está logueado
    <?php if (isset($_SESSION['user_id'])): ?>
    fetchSearchHistory();
    <?php endif; ?>
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const term = this.value.trim();
        
        if (term.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`<?= BASE_URL ?>/ajax/busqueda/search.php?term=${encodeURIComponent(term)}`);
                
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('La respuesta de búsqueda no es JSON');
                }
                
                const data = await response.json();
                
                let html = '';
                
                // Manejar sugerencias de productos
                if (data?.suggestions?.length > 0) {
                    const item = data.suggestions[0];
                    if (item?.slug && item?.image_path) {
                        html += `
                            <a href="<?= BASE_URL ?>/tienda/verproducto.php?slug=${item.slug}" class="product-item">
                                <img src="<?= BASE_URL ?>/${item.image_path.replace(/^\/+/, '')}" 
                                     alt="${item.name || 'Producto'}">
                                <div class="product-info2">
                                    <div>${item.name || 'Producto'}</div>
                                </div>
                            </a>
                        `;
                        
                     
                    }
                }
                
                // Manejar términos de búsqueda
                if (data?.terms?.length > 0) {
                   
                    
                    data.terms.slice(0, 5).forEach(term => {
                        if (!term || typeof term !== 'string') return;
                        
                        const termLower = term.toLowerCase();
                        const wasSearched = searchedTerms.includes(termLower);
                        
                        html += `
                            <div class="suggestion-item" onclick="window.location.href='<?= BASE_URL ?>/tienda/productos.php?search=${encodeURIComponent(term)}'">
                                <i class="fas ${wasSearched ? 'fa-clock' : 'fa-search'}"></i>
                                <span>${term}</span>
                            </div>
                        `;
                    });
                }
                
                if (html) {
                    searchResults.innerHTML = html;
                    searchResults.style.display = 'block';
                } else {
                    searchResults.style.display = 'none';
                }
            } catch (error) {
                console.error('Error en la búsqueda:', error);
                searchResults.style.display = 'none';
            }
        }, 300);
    });
    
    // Ocultar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Guardar búsqueda al enviar el formulario
    document.querySelector('.search-form').addEventListener('submit', function(e) {
        const term = searchInput.value.trim();
        if (term.length > 0 && <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            fetch(`<?= BASE_URL ?>/ajax/busqueda/save_search.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `term=${encodeURIComponent(term)}`
            }).catch(error => console.error('Error al guardar búsqueda:', error));
        }
    });
    
    // Manejar tecla Enter en las sugerencias
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const firstSuggestion = document.querySelector('.suggestion-item, .product-item');
            if (firstSuggestion) {
                firstSuggestion.click();
            } else {
                document.querySelector('.search-form').submit();
            }
        }
    });
});
</script>    