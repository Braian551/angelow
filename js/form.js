const container= document.getElementById('container');
const registrerBtn= document.getElementById('register');
const loginBtn= document.getElementById('login');

registrerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
});