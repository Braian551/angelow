// ===== VALIDACIONES PROGRESIVAS - UNA POR UNA =====

// Variables del formulario de registro
var nombre = document.forms['registrar']['username'];
var email = document.forms['registrar']['email']; 
var password = document.forms['registrar']['password'];
var confirmar = document.querySelector( 'input[name="c_pass"]');

var name_error = document.getElementById('name-availability');
var email_error = document.getElementById('email-status');
var password_error = document.getElementById('password-strength');
var confirmar_error = document.getElementById('password-match');

// Regex para validar email
var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

// Lista de palabras no válidas para nombres
const invalidNamePatterns = [
    /^(ja){2,}$/i, /^(he){2,}$/i, /^(lo){2,}$/i, /^(ha){2,}$/i, /^(ka){2,}$/i,
    /^[aeiouAEIOU]{4,}$/, /^[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{5,}$/,
    /^(.)\\1{3,}$/, /^[0-9]+$/, /^[!@#$%^&*(),.?":{}|<>]+$/,
    /test/i, /demo/i, /admin/i, /user/i, /sample/i, /example/i,
    /asdf/i, /qwer/i, /zxcv/i, /1234/i, /abcd/i, /spam/i, /fake/i, /temp/i
];

// Dominios de email sospechosos
const suspiciousEmailDomains = [
    '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
    'mailinator.com', 'yopmail.com', 'temp-mail.org',
    'throwaway.email', 'maildrop.cc', 'mailnesia.com',
    'example.com', 'test.com', 'fake.com', 'spam.com'
];

// ===== VALIDACIONES PROGRESIVAS PARA NOMBRES =====

// Definir el orden de validaciones para nombres
const nameValidationSteps = [
    {
        check: (name) => name.trim() !== '',
        message: "El campo nombre no puede estar vacío"
    },
    {
        check: (name) => name.trim().length >= 2,
        message: "El nombre debe tener al menos 2 caracteres"
    },
    {
        check: (name) => /^[a-zA-ZÀ-ÿ\u00f1\u00d1\s'-]+$/.test(name),
        message: "El nombre solo puede contener letras, espacios, guiones y apostrofes"
    },
    {
        check: (name) => !/^[aeiouAEIOU]{4,}$/.test(name),
        message: "El nombre no puede ser solo vocales repetidas"
    },
    {
        check: (name) => !/^[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{5,}$/.test(name),
        message: "El nombre no puede ser solo consonantes"
    },
    {
        check: (name) => !/^(ja){2,}$/i.test(name) && !/^(he){2,}$/i.test(name) && !/^(lo){2,}$/i.test(name),
        message: "Por favor, ingresa tu nombre real, no expresiones como 'jajaja'"
    },
    {
        check: (name) => !/(.)\1{3,}/.test(name),
        message: "El nombre no puede tener más de 3 caracteres consecutivos iguales"
    },
    {
        check: (name) => !(name.length > 3 && name === name.toUpperCase() && /[A-Z]/.test(name)),
        message: "Por favor, usa formato normal de nombre (no todo en mayúsculas)"
    },
    {
        check: (name) => !/^[\s'-]+$/.test(name),
        message: "El nombre debe contener letras, no solo espacios o signos"
    },
    {
        check: (name) => /[aeiouAEIOUÀ-ÿ]/.test(name),
        message: "El nombre debe contener al menos una vocal"
    },
    {
        check: (name) => /[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZÑñ]/.test(name),
        message: "El nombre debe contener al menos una consonante"
    },
    {
        check: (name) => name.replace(/\s+/g, '').length >= 2,
        message: "El nombre debe tener al menos 2 caracteres sin contar espacios"
    },
    {
        check: (name) => {
            const spamWords = ['test', 'demo', 'admin', 'user', 'sample', 'example', 'asdf', 'qwer'];
            return !spamWords.some(word => name.toLowerCase().includes(word));
        },
        message: "Por favor, ingresa tu nombre real"
    }
];

// ===== VALIDACIONES PROGRESIVAS PARA EMAIL =====

const emailValidationSteps = [
    {
        check: (email) => email.trim() !== '',
        message: "El campo de email no puede estar vacío"
    },
    {
        check: (email) => emailRegex.test(email),
        message: "El formato del email no es válido. Ejemplo: usuario@dominio.com"
    },
    {
        check: (email) => {
            const localPart = email.split('@')[0].toLowerCase();
            return !/^[0-9]+$/.test(localPart);
        },
        message: "El email no puede ser solo números antes del @"
    },
    {
        check: (email) => {
            const localPart = email.split('@')[0].toLowerCase();
            return !/^(.)\1{4,}/.test(localPart);
        },
        message: "El email no puede tener caracteres repetidos excesivamente"
    },
    {
        check: (email) => {
            const localPart = email.split('@')[0].toLowerCase();
            const spamPatterns = ['test', 'demo', 'admin', 'user', 'sample', 'fake', 'spam'];
            return !spamPatterns.some(pattern => localPart.includes(pattern));
        },
        message: "Por favor, usa tu email personal real"
    },
    {
        check: (email) => {
            const domain = email.split('@')[1]?.toLowerCase();
            return domain && domain.includes('.');
        },
        message: "El dominio del email debe tener un punto (ej: .com, .org)"
    },
    {
        check: (email) => {
            const domain = email.split('@')[1]?.toLowerCase();
            return domain && !/\d+$/.test(domain);
        },
        message: "El dominio no puede terminar en números"
    },
    {
        check: (email) => {
            const domain = email.split('@')[1]?.toLowerCase();
            return domain && !suspiciousEmailDomains.includes(domain);
        },
        message: "Por favor, usa un email personal válido, no temporal"
    }
];

// ===== VALIDACIONES PROGRESIVAS PARA CONTRASEÑA =====

const passwordValidationSteps = [
    {
        check: (password) => password !== '',
        message: "La contraseña es obligatoria"
    },
    {
        check: (password) => password.length >= 8,
        message: "Debe tener al menos 8 caracteres"
    },
    {
        check: (password) => password.length <= 50,
        message: "No puede tener más de 50 caracteres"
    },
    {
        check: (password) => /[A-Z]/.test(password),
        message: "Debe contener al menos una letra mayúscula"
    },
    {
        check: (password) => /[a-z]/.test(password),
        message: "Debe contener al menos una letra minúscula"
    },
    {
        check: (password) => /[0-9]/.test(password),
        message: "Debe contener al menos un número"
    },
    {
        check: (password) => /[!@#$%^&*(),.?":{}|<>]/.test(password),
        message: "Debe contener al menos un carácter especial"
    },
    {
        check: (password) => !/^123+$/.test(password),
        message: "No uses secuencias numéricas simples como '123456'"
    },
    {
        check: (password) => !/^abc+$/i.test(password),
        message: "No uses secuencias alfabéticas como 'abcdef'"
    },
    {
        check: (password) => !/^qwe+$/i.test(password),
        message: "No uses patrones de teclado como 'qwerty'"
    },
    {
        check: (password) => !/^password/i.test(password),
        message: "No uses la palabra 'password' en tu contraseña"
    },
    {
        check: (password) => !/^(.)\\1{2,}$/.test(password),
        message: "No uses caracteres repetidos como 'aaa' o '111'"
    },
    {
        check: (password) => !/(.)\1{3,}/.test(password),
        message: "No uses más de 3 caracteres consecutivos iguales"
    },
    {
        check: (password) => !/^[a-zA-Z]+$/.test(password),
        message: "No uses solo letras, incluye números y símbolos"
    },
    {
        check: (password) => !/^[0-9]+$/.test(password),
        message: "No uses solo números, incluye letras y símbolos"
    },
    {
        check: (password) => {
            const commonWords = ['admin', 'user', 'login', 'pass', 'secret', 'welcome'];
            return !commonWords.some(word => password.toLowerCase().includes(word));
        },
        message: "No uses palabras comunes como 'admin', 'user', etc."
    }
];

// ===== FUNCIONES DE VALIDACIÓN PROGRESIVA =====

function validateProgressively(value, validationSteps) {
    for (let i = 0; i < validationSteps.length; i++) {
        const step = validationSteps[i];
        if (!step.check(value)) {
            return {
                isValid: false,
                message: step.message,
                stepIndex: i
            };
        }
    }
    return {
        isValid: true,
        message: '',
        stepIndex: validationSteps.length
    };
}

// ===== FUNCIONES DE VERIFICACIÓN INDIVIDUAL PROGRESIVAS =====

function name_Verify() {
    if (!nombre) return;
    
    const value = nombre.value.trim();
    const result = validateProgressively(value, nameValidationSteps);
    
    if (!result.isValid) {
        setFieldError(nombre, name_error, result.message);
    } else {
        setFieldValid(nombre, name_error);
    }
}

function email_Verify() {
    if (!email) return;
    
    const value = email.value.trim();
    const result = validateProgressively(value, emailValidationSteps);
    
    if (!result.isValid) {
        setFieldError(email, email_error, result.message);
    } else {
        setFieldValid(email, email_error);
    }
}

function password_Verify() {
    if (!password) return;
    
    const value = password.value;
    const result = validateProgressively(value, passwordValidationSteps);
    
    if (!result.isValid) {
        setFieldError(password, password_error, result.message);
    } else {
        setFieldValid(password, password_error);
    }
}

function confirmar_Verify() {
    if (!confirmar) return;
    
    if (confirmar.value === "") {
        setFieldError(confirmar, confirmar_error, "Debes confirmar tu contraseña");
    } else if (confirmar.value !== (password ? password.value : '')) {
        setFieldError(confirmar, confirmar_error, "Las contraseñas no coinciden");
    } else {
        setFieldValid(confirmar, confirmar_error);
    }
}

// ===== FUNCIONES DE UTILIDAD MEJORADAS =====

function setFieldError(field, errorElement, message) {
    if (field) {
        field.style.border = "2px solid #ff0040";
        field.classList.remove("input-valid");
        field.classList.add("input-error");
    }
    if (errorElement) {
        errorElement.innerHTML = message;
        errorElement.style.display = "block";
        errorElement.style.color = "#ff0040";
        errorElement.style.fontSize = "12px";
     
        errorElement.style.fontWeight = "normal";
    }
}

function setFieldValid(field, errorElement, successMessage = "") {
    if (field) {
        field.style.border = "2px solid #00bcd4";
        field.classList.add("input-valid");
        field.classList.remove("input-error");
    }
    if (errorElement && successMessage) {
        errorElement.innerHTML = successMessage;
        errorElement.style.display = "block";
        errorElement.style.color = "#00bcd4";
        errorElement.style.fontSize = "12px";
       
        errorElement.style.fontWeight = "bold";
    } else if (errorElement) {
        errorElement.style.display = "none";
    }
}

// ===== VALIDACIÓN FINAL DEL FORMULARIO =====

function validated() {
    let isValid = true;
    let firstError = null;

    // Validar nombre
    if (nombre) {
        const nameResult = validateProgressively(nombre.value.trim(), nameValidationSteps);
        if (!nameResult.isValid) {
            setFieldError(nombre, name_error, nameResult.message);
            if (!firstError) firstError = nombre;
            isValid = false;
        }
    }

    // Validar email
    if (email) {
        const emailResult = validateProgressively(email.value.trim(), emailValidationSteps);
        if (!emailResult.isValid) {
            setFieldError(email, email_error, emailResult.message);
            if (!firstError) firstError = email;
            isValid = false;
        }
    }

    // Validar contraseña
    if (password) {
        const passwordResult = validateProgressively(password.value, passwordValidationSteps);
        if (!passwordResult.isValid) {
            setFieldError(password, password_error, passwordResult.message);
            if (!firstError) firstError = password;
            isValid = false;
        }
    }

    // Validar confirmación
    if (confirmar) {
        if (confirmar.value === "") {
            setFieldError(confirmar, confirmar_error, "Debes confirmar tu contraseña");
            if (!firstError) firstError = confirmar;
            isValid = false;
        } else if (confirmar.value !== (password ? password.value : '')) {
            setFieldError(confirmar, confirmar_error, "Las contraseñas no coinciden");
            if (!firstError) firstError = confirmar;
            isValid = false;
        }
    }

    if (firstError) {
        scrollToField(firstError);
    }

    return isValid;
}

// ===== VALIDACIONES PARA LOGIN (SIMPLIFICADAS) =====

var emailLogin = document.getElementById('correo_login');
var passwordLogin = document.getElementById('contrasena_login');
var email_error_login = document.getElementById('login-email-status');
var password_error_login = document.getElementById('login-attempts');

const loginEmailSteps = [
    {
        check: (email) => email.trim() !== '',
        message: "El campo de email no puede estar vacío"
    },
    {
        check: (email) => emailRegex.test(email.trim()),
        message: "El formato del email no es válido"
    }
];

const loginPasswordSteps = [
    {
        check: (password) => password.trim() !== '',
        message: "El campo de contraseña no puede estar vacío"
    },
    {
        check: (password) => password.length >= 6,
        message: "La contraseña debe tener al menos 6 caracteres"
    }
];

function email_Verify_Login() {
    if (!emailLogin) return;
    
    const value = emailLogin.value.trim();
    const result = validateProgressively(value, loginEmailSteps);
    
    if (!result.isValid) {
        setFieldError(emailLogin, email_error_login, result.message);
    } else {
        setFieldValid(emailLogin, email_error_login);
    }
}

function password_Verify_Login() {
    if (!passwordLogin) return;
    
    const value = passwordLogin.value;
    const result = validateProgressively(value, loginPasswordSteps);
    
    if (!result.isValid) {
        setFieldError(passwordLogin, password_error_login, result.message);
    } else {
        setFieldValid(passwordLogin, password_error_login);
    }
}

function validatedLogin() {
    let isValid = true;
    let firstError = null;

    if (emailLogin) {
        const emailResult = validateProgressively(emailLogin.value.trim(), loginEmailSteps);
        if (!emailResult.isValid) {
            setFieldError(emailLogin, email_error_login, emailResult.message);
            if (!firstError) firstError = emailLogin;
            isValid = false;
        }
    }

    if (passwordLogin) {
        const passwordResult = validateProgressively(passwordLogin.value, loginPasswordSteps);
        if (!passwordResult.isValid) {
            setFieldError(passwordLogin, password_error_login, passwordResult.message);
            if (!firstError) firstError = passwordLogin;
            isValid = false;
        }
    }

    if (firstError) {
        scrollToField(firstError);
    }

    return isValid;
}

// ===== EVENT LISTENERS =====

// Listeners para formulario de registro
if (nombre) nombre.addEventListener('input', name_Verify);
if (email) email.addEventListener('input', email_Verify);
if (password) password.addEventListener('input', password_Verify);
if (confirmar) confirmar.addEventListener('input', confirmar_Verify);

// Listeners para formulario de login
if (emailLogin) emailLogin.addEventListener('input', email_Verify_Login);
if (passwordLogin) passwordLogin.addEventListener('input', password_Verify_Login);

// Listener para submit del formulario de registro
var registrarForm = document.forms['registrar'];
if (registrarForm) {
    registrarForm.addEventListener('submit', function(event) {
        if (!validated()) {
            event.preventDefault();
        }
    });
}

// Listener para submit del formulario de login
var loginForm = document.querySelector('form[action*="login.php"]');
if (loginForm) {
    loginForm.addEventListener('submit', function(event) {
        if (!validatedLogin()) {
            event.preventDefault();
        }
    });
}

// ===== FUNCIONES ADICIONALES =====

function scrollToField(element) {
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center',
            inline: 'nearest'
        });
        setTimeout(() => {
            element.focus();
        }, 500);
    }
}

// Prevención de caracteres no válidos en tiempo real
if (nombre) {
    nombre.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/[a-zA-ZÀ-ÿ\u00f1\u00d1\s'-]/.test(char)) {
            e.preventDefault();
        }
    });
}

if (email) {
    email.addEventListener('keypress', function(e) {
        if (e.key === ' ') {
            e.preventDefault();
        }
    });
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del formulario
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    if (action === 'register') {
        document.getElementById('register')?.click();
    } else if (action === 'login') {
        document.getElementById('login')?.click();
    }
});

// Toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.password-input');
            const isPassword = input.type === 'password';
            
            input.type = isPassword ? 'text' : 'password';
            
            const eyeIcon = this.querySelector('.fa-eye');
            const eyeSlashIcon = this.querySelector('.fa-eye-slash');
            
            if (isPassword) {
                eyeIcon?.classList.add('slash');
                eyeSlashIcon?.classList.remove('slash');
            } else {
                eyeIcon?.classList.remove('slash');
                eyeSlashIcon?.classList.add('slash');
            }
            
            input.focus();
        });
    });
});
// Agregar esta variable al inicio con las demás
var terms = document.forms['registrar']['terms'];
var terms_error = document.getElementById('terms-error');

// Agregar esta validación a las funciones existentes
function terms_Verify() {
    if (!terms) return;
    
    if (!terms.checked) {
        setFieldError(terms, terms_error, "Debes aceptar los términos y condiciones para continuar");
    } else {
        setFieldValid(terms, terms_error);
    }
}

// Modificar la función validated() para incluir la validación de términos
function validated() {
    let isValid = true;
    let firstError = null;

    // ... (validaciones existentes de nombre, email, password, etc.)
    
    // Validar términos y condiciones
    if (terms) {
        if (!terms.checked) {
            setFieldError(terms, terms_error, "Debes aceptar los términos y condiciones para continuar");
            if (!firstError) firstError = terms;
            isValid = false;
        } else {
            setFieldValid(terms, terms_error);
        }
    }

    if (firstError) {
        scrollToField(firstError);
    }

    return isValid;
}

// Agregar event listener para la casilla de términos
if (terms) terms.addEventListener('change', terms_Verify);
// Agregar al array de variables
var terms = document.forms['registrar']['terms'];
var terms_error = document.getElementById('terms-error');

// Definir pasos de validación para términos
const termsValidationSteps = [
    {
        check: (isChecked) => isChecked,
        message: "Debes aceptar los términos y condiciones para continuar"
    }
];

// Función de verificación de términos
function terms_Verify() {
    if (!terms) return;
    
    const result = validateProgressively(terms.checked, termsValidationSteps);
    
    if (!result.isValid) {
        setFieldError(terms, terms_error, result.message);
    } else {
        setFieldValid(terms, terms_error);
    }
}

// Modificar la función validated()
function validated() {
    let isValid = true;
    let firstError = null;

    // ... (otras validaciones)
    
    // Validar términos
    if (terms) {
        const termsResult = validateProgressively(terms.checked, termsValidationSteps);
        if (!termsResult.isValid) {
            setFieldError(terms, terms_error, termsResult.message);
            if (!firstError) firstError = terms;
            isValid = false;
        }
    }

    if (firstError) {
        scrollToField(firstError);
    }

    return isValid;
}

// Agregar event listener
if (terms) terms.addEventListener('change', terms_Verify);