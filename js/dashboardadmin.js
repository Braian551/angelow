document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const adminContent = document.querySelector('.admin-content');
    
    // Función para detectar móvil
    const isMobile = () => window.innerWidth <= 992;
    
    // Mostrar/ocultar sidebar
    const toggleSidebar = () => {
        if (isMobile()) {
            sidebar.classList.toggle('active');
            
            // Bloquear scroll del body cuando el sidebar está abierto
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        } else {
            sidebar.classList.toggle('collapsed');
            adminContent.classList.toggle('sidebar-collapsed');
        }
    };
    
    // Event listeners
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Cerrar al hacer clic fuera en móvil
    document.addEventListener('click', (e) => {
        if (isMobile() && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
    
    // Cerrar al hacer scroll en móvil
    window.addEventListener('scroll', () => {
        if (isMobile() && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Manejar cambios de tamaño
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Inicialización
    if (isMobile()) {
        sidebar.classList.remove('collapsed');
        adminContent.classList.remove('sidebar-collapsed');
    }
});