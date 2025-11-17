// Guarda defensiva: proteger accesos a elementos que pueden no existir en todas las páginas
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('container');
    const registrerBtn = document.getElementById('register');
    const loginBtn = document.getElementById('login');

    if (registrerBtn && container) {
        registrerBtn.addEventListener('click', () => container.classList.add('active'));
    }

    if (loginBtn && container) {
        loginBtn.addEventListener('click', () => container.classList.remove('active'));
    }
});