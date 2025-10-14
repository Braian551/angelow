// Funcionalidad avanzada para la página de pedidos
document.addEventListener('DOMContentLoaded', function() {
    // 0. Animación de la línea de tiempo moderna
    initializeModernTimeline();
    
    // 1. Filtrado y búsqueda de pedidos
    const searchInput = document.getElementById('order-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.orders-table tbody tr');
            
            rows.forEach(row => {
                const orderNumber = row.querySelector('td:first-child').textContent.toLowerCase();
                const orderDate = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const orderTotal = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const orderStatus = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                
                if (orderNumber.includes(searchTerm) || 
                    orderDate.includes(searchTerm) || 
                    orderTotal.includes(searchTerm) || 
                    orderStatus.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // 2. Filtrado por estado
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('.orders-table tbody tr');
            
            rows.forEach(row => {
                const rowStatus = row.querySelector('td:nth-child(5) .status-badge').textContent.toLowerCase();
                
                if (selectedStatus === 'all' || rowStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // 3. Descargar factura
    const downloadInvoiceButtons = document.querySelectorAll('.download-invoice');
    downloadInvoiceButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-order-id');
            
            // Simular descarga (en producción sería una llamada AJAX real)
            console.log(`Descargando factura para el pedido ${orderId}`);
            
            // Mostrar notificación
            const notification = document.createElement('div');
            notification.className = 'download-notification';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>La factura se está descargando...</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        });
    });
    
    // 4. Cancelar pedido
    const cancelOrderButtons = document.querySelectorAll('.cancel-order');
    cancelOrderButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas cancelar este pedido? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // 5. Copiar datos de transferencia
    const copyButtons = document.querySelectorAll('.copy-payment-info');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Mostrar feedback visual
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copiado';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Error al copiar texto: ', err);
            });
        });
    });
    
    // 6. Vista de detalles responsive
    function handleResponsiveDetailsView() {
        const detailsContainer = document.querySelector('.order-details-container');
        if (!detailsContainer) return;
        
        if (window.innerWidth < 768) {
            // Mover la sección de información después de los productos
            const itemsSection = document.querySelector('.order-items-section');
            const infoSection = document.querySelector('.order-info-section');
            
            if (itemsSection && infoSection) {
                itemsSection.parentNode.insertBefore(infoSection, itemsSection.nextSibling);
            }
        } else {
            // Restaurar el grid original
            const summaryGrid = document.querySelector('.order-summary-grid');
            const itemsSection = document.querySelector('.order-items-section');
            const infoSection = document.querySelector('.order-info-section');
            
            if (summaryGrid && itemsSection && infoSection) {
                summaryGrid.innerHTML = '';
                summaryGrid.appendChild(itemsSection);
                summaryGrid.appendChild(infoSection);
            }
        }
    }
    
    // Ejecutar al cargar y al redimensionar
    handleResponsiveDetailsView();
    window.addEventListener('resize', handleResponsiveDetailsView);
});

// Notificaciones dinámicas
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Estilos dinámicos para notificaciones
const notificationStyles = document.createElement('style');
notificationStyles.innerHTML = `
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        background-color: #4CAF50;
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .notification.error {
        background-color: #f44336;
    }
    
    .notification.warning {
        background-color: #ff9800;
    }
    
    .notification.fade-out {
        opacity: 0;
        transform: translateY(20px);
    }
`;
document.head.appendChild(notificationStyles);

// Función para inicializar la línea de tiempo moderna
function initializeModernTimeline() {
    const timelines = document.querySelectorAll('.order-timeline-modern');
    
    timelines.forEach(timeline => {
        const progressFill = timeline.querySelector('.timeline-progress-fill');
        const steps = timeline.querySelectorAll('.timeline-step-modern');
        
        if (!progressFill) return;
        
        // Obtener el ancho/alto actual
        const currentWidth = progressFill.style.width;
        
        // Para móvil (vertical)
        if (window.innerWidth <= 480) {
            const progressBar = timeline.querySelector('.timeline-progress-bar');
            if (progressBar) {
                const totalHeight = progressBar.offsetHeight;
                const completedSteps = Array.from(steps).filter(step => 
                    step.classList.contains('completed') || step.classList.contains('active')
                ).length;
                
                const progressHeight = (completedSteps / steps.length) * 100;
                progressFill.style.setProperty('--progress-height', progressHeight + '%');
                
                // Animar la altura
                setTimeout(() => {
                    progressFill.style.height = progressHeight + '%';
                }, 100);
            }
        } else {
            // Para desktop (horizontal) - ya está animado con CSS
            // Forzar reflow para activar animación
            progressFill.style.width = '0%';
            setTimeout(() => {
                progressFill.style.width = currentWidth;
            }, 50);
        }
    });
    
    // Re-inicializar en cambio de tamaño
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            initializeModernTimeline();
        }, 250);
    });
}
