// ===== VARIABLES DEL FORMULARIO =====
const loginForm = document.getElementById('loginForm');
const steps = document.querySelectorAll('.form-step');
const progressSteps = document.querySelectorAll('.progress-steps .step');
const progressBar = document.querySelector('.progress');
let currentStep = 1;
const totalSteps = steps.length;

// Elementos del formulario
const emailPhoneInput = document.getElementById('correo_login');
const passwordInput = document.getElementById('loginPassword');
const rememberCheckbox = document.getElementById('remember');

// Elementos para mostrar errores
const emailPhoneError = document.createElement('div');
emailPhoneError.className = 'error-message';
emailPhoneInput.parentNode.appendChild(emailPhoneError);

const passwordError = document.createElement('div');
passwordError.className = 'error-message';
passwordInput.parentNode.parentNode.appendChild(passwordError);

// ===== EXPRESIONES REGULARES =====
const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const phoneRegex = /^[0-9]{10,15}$/;

// ===== VALIDACIONES PARA CORREO/TELÉFONO =====
const emailPhoneValidationSteps = [
    {
        check: (value) => value.trim() !== '',
        message: "El campo no puede estar vacío"
    },
    {
        check: (value) => {
            const input = value.trim();
            return emailRegex.test(input) || phoneRegex.test(input);
        },
        message: "Debes ingresar un correo electrónico válido o un número de teléfono (10-15 dígitos)"
    },
    {
        check: (value) => {
            const input = value.trim();
            if (emailRegex.test(input)) {
                const domain = input.split('@')[1]?.toLowerCase();
                return !['10minutemail.com', 'tempmail.org', 'mailinator.com'].includes(domain);
            }
            return true;
        },
        message: "No se permiten correos electrónicos temporales"
    }
];

// ===== VALIDACIONES PARA CONTRASEÑA =====
const passwordValidationSteps = [
    {
        check: (password) => password !== '',
        message: "La contraseña es obligatoria"
    },
    {
        check: (password) => password.length >= 6,
        message: "La contraseña debe tener al menos 6 caracteres"
    }
];

// ===== FUNCIONES DE VALIDACIÓN =====
function validateProgressively(value, validationSteps) {
    for (let i = 0; i < validationSteps.length; i++) {
        const step = validationSteps[i];
        if (!step.check(value)) {
            return {
                isValid: false,
                message: step.message
            };
        }
    }
    return {
        isValid: true,
        message: ''
    };
}

function setFieldError(field, errorElement, message) {
    field.classList.add('error');
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function setFieldValid(field, errorElement) {
    field.classList.remove('error');
    errorElement.style.display = 'none';
}

// ===== VALIDACIONES POR PASO =====
function validateStep(step) {
    let isValid = true;
    
    switch(step) {
        case 1:
            const emailPhoneResult = validateProgressively(emailPhoneInput.value.trim(), emailPhoneValidationSteps);
            if (!emailPhoneResult.isValid) {
                setFieldError(emailPhoneInput, emailPhoneError, emailPhoneResult.message);
                isValid = false;
            } else {
                setFieldValid(emailPhoneInput, emailPhoneError);
            }
            break;
            
        case 2:
            const passwordResult = validateProgressively(passwordInput.value, passwordValidationSteps);
            if (!passwordResult.isValid) {
                setFieldError(passwordInput, passwordError, passwordResult.message);
                isValid = false;
            } else {
                setFieldValid(passwordInput, passwordError);
            }
            break;
    }
    
    return isValid;
}

// ===== NAVEGACIÓN ENTRE PASOS =====
function showStep(step) {
    steps.forEach(s => s.classList.remove('active'));
    document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
    
    // Actualizar barra de progreso
    progressSteps.forEach((s, i) => {
        if (i < step - 1) s.classList.add('completed');
        else s.classList.remove('completed');
        s.classList.toggle('active', i + 1 === step);
    });
 
    if (step === 2) {
        progressBar.style.width = '70%';
    } else {
        progressBar.style.width = `${((step - 1) / (totalSteps - 1)) * 100}%`;
    }
}
// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar el primer paso al cargar
    showStep(currentStep);
    
    // Botones siguiente
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            } else {
                // Hacer scroll al primer error
                const errorField = document.querySelector('.error');
                if (errorField) {
                    errorField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    errorField.focus();
                }
            }
        });
    });
    
    // Botones anterior
    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });
    
    // Validación en tiempo real para correo/teléfono
    emailPhoneInput.addEventListener('input', function() {
        const result = validateProgressively(this.value.trim(), emailPhoneValidationSteps);
        if (!result.isValid) {
            setFieldError(this, emailPhoneError, result.message);
        } else {
            setFieldValid(this, emailPhoneError);
        }
    });
    
    // Validación en tiempo real para contraseña
    passwordInput.addEventListener('input', function() {
        const result = validateProgressively(this.value, passwordValidationSteps);
        if (!result.isValid) {
            setFieldError(this, passwordError, result.message);
        } else {
            setFieldValid(this, passwordError);
        }
    });
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    
    // Validación final al enviar el formulario
    loginForm.addEventListener('submit', function(e) {
        // Validar todos los pasos
        let allValid = true;
        for (let i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                allValid = false;
                // Mostrar el paso con errores
                if (currentStep !== i) {
                    currentStep = i;
                    showStep(currentStep);
                }
                break;
            }
        }
        
        if (!allValid) {
            e.preventDefault();
            // Hacer scroll al primer error
            const errorField = document.querySelector('.error');
            if (errorField) {
                errorField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                errorField.focus();
            }
        }
    });
});