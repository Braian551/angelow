<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== MANEJO DE IMÁGENES CON LAZY LOADING =====
    const productImages = document.querySelectorAll('.product-image.loading img');
    
    productImages.forEach(img => {
        img.addEventListener('load', function() {
            this.closest('.product-image').classList.remove('loading');
        });

        img.addEventListener('error', function() {
            this.src = '<?= BASE_URL ?>/images/default-product.jpg';
            this.closest('.product-image').classList.remove('loading');
        });

        // Si la imagen ya está cargada
        if (img.complete) {
            img.closest('.product-image').classList.remove('loading');
        }
    });    // ===== FUNCIONALIDAD DE WISHLIST =====
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.dataset.productId;
            const icon = this.querySelector('i');
            const isActive = this.classList.contains('active');

            // Verificar si el usuario está logueado
            <?php if (!isset($_SESSION['user_id'])): ?>
                showNotification('Debes iniciar sesión para agregar productos a favoritos', 'warning');
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL ?>/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }, 1500);
                return;
            <?php endif; ?>

            // Animación de clic
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);

            try {
                const response = await fetch('<?= BASE_URL ?>/ajax/wishlist/toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                });

                const data = await response.json();

                if (data.success) {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        showNotification('Producto agregado a favoritos', 'success');
                        
                        // Actualizar contador en el header si existe
                        updateWishlistCount(1);
                    } else {
                        this.classList.remove('active');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        showNotification('Producto eliminado de favoritos', 'info');
                        
                        // Actualizar contador en el header si existe
                        updateWishlistCount(-1);
                    }
                } else {
                    showNotification(data.message || 'Error al procesar la solicitud', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión. Por favor, intenta nuevamente.', 'error');
            }
        });
    });

    // ===== FUNCIÓN PARA ACTUALIZAR CONTADOR DE WISHLIST =====
    function updateWishlistCount(change) {
        const wishlistCounters = document.querySelectorAll('.wishlist-count, .cart-count[data-type="wishlist"]');
        wishlistCounters.forEach(counter => {
            let currentCount = parseInt(counter.textContent) || 0;
            currentCount += change;
            if (currentCount < 0) currentCount = 0;
            counter.textContent = currentCount;
            
            if (currentCount > 0) {
                counter.style.display = 'flex';
            } else {
                counter.style.display = 'none';
            }
        });

        // También actualizar en la tarjeta de resumen del dashboard
        const favoritesCard = document.querySelector('.summary-card:nth-child(3) p');
        if (favoritesCard) {
            let currentCount = parseInt(favoritesCard.textContent) || 0;
            currentCount += change;
            if (currentCount < 0) currentCount = 0;
            const plural = currentCount !== 1 ? 's' : '';
            const pluralGuardado = currentCount !== 1 ? 's' : '';
            favoritesCard.textContent = `${currentCount} producto${plural} guardado${pluralGuardado}`;
        }
    }

    // ===== SISTEMA DE NOTIFICACIONES =====
    function showNotification(message, type = 'info') {
        // Eliminar notificaciones existentes
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(notif => notif.remove());

        const notification = document.createElement('div');
        notification.className = `notification-toast notification-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${icons[type] || icons.info}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(notification);

        // Animación de entrada
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // ===== ANIMACIÓN DE HOVER EN TARJETAS DE PRODUCTOS =====
    const productCards = document.querySelectorAll('.product-card:not(.shimmer)');
    
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // ===== ANIMACIÓN DE SCROLL PARA ELEMENTOS =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observar tarjetas de resumen
    document.querySelectorAll('.summary-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease';
        observer.observe(card);
    });

    // Observar tarjetas de pedidos
    document.querySelectorAll('.order-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `all 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // ===== MEJORAR UX DE ENLACES =====
    const viewAllLinks = document.querySelectorAll('.view-all');
    viewAllLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });

    // ===== LAZY LOADING DE IMÁGENES CON INTERSECTION OBSERVER =====
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // ===== SMOOTH SCROLL PARA ENLACES INTERNOS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    console.log('Dashboard de usuario inicializado correctamente');
});
</script>

<style>
/* Estilos adicionales para el dashboard */
.personalized-recommendations .section-subtitle {
    color: #666;
    font-size: 1.4rem;
    margin-top: 0.5rem;
}

.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2.5rem;
    margin-top: 2.5rem;
}

/* Estilos para tarjetas de pedidos */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 2rem;
}

.order-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.order-number {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.order-number strong {
    font-size: 1.6rem;
    color: #111827;
}

.order-date {
    color: #6b7280;
    font-size: 1.3rem;
}

.order-status {
    padding: 0.6rem 1.2rem;
    border-radius: 20px;
    font-size: 1.3rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-shipped {
    background: #e0e7ff;
    color: #4338ca;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.order-info {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #6b7280;
    font-size: 1.4rem;
}

.order-info i {
    color: #9ca3af;
}

.order-total {
    font-size: 1.8rem;
    color: #111827;
}

.order-actions {
    display: flex;
    gap: 1rem;
}

.btn-view-order {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: #FE5000;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.4rem;
    transition: all 0.3s ease;
}

.btn-view-order:hover {
    background: #e64800;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(254, 80, 0, 0.3);
}

/* Notificaciones Toast */
.notification-toast {
    position: fixed;
    top: 100px;
    right: 20px;
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    min-width: 300px;
    max-width: 450px;
    z-index: 10000;
    opacity: 0;
    transform: translateX(400px);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.notification-toast.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.notification-content i {
    font-size: 2rem;
}

.notification-success {
    border-left: 4px solid #10b981;
}

.notification-success i {
    color: #10b981;
}

.notification-error {
    border-left: 4px solid #ef4444;
}

.notification-error i {
    color: #ef4444;
}

.notification-warning {
    border-left: 4px solid #f59e0b;
}

.notification-warning i {
    color: #f59e0b;
}

.notification-info {
    border-left: 4px solid #3b82f6;
}

.notification-info i {
    color: #3b82f6;
}

.notification-close {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.5rem;
    font-size: 1.4rem;
    transition: color 0.2s ease;
}

.notification-close:hover {
    color: #4b5563;
}

/* Responsive */
@media (max-width: 768px) {
    .recommendations-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1.5rem;
    }

    .notification-toast {
        right: 10px;
        left: 10px;
        min-width: auto;
    }

    .order-header {
        flex-direction: column;
        gap: 1rem;
    }

    .order-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>
