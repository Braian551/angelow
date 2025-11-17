// ===== VARIABLES DEL FORMULARIO =====
const form = document.getElementById('registerForm');
const steps = document.querySelectorAll('.form-step');
const progressSteps = document.querySelectorAll('.progress-steps .step');
const progressBar = document.querySelector('.progress');
let currentStep = 1;
const totalSteps = steps.length;

// Elementos del formulario
const usernameInput = document.getElementById('username');
const emailInput = document.getElementById('email');
const phoneInput = document.getElementById('phone');
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('password_confirm');
const termsCheckbox = document.getElementById('terms');

// Elementos para mostrar errores
const usernameError = document.createElement('div');
usernameError.className = 'error-message';
usernameInput.parentNode.appendChild(usernameError);

const emailError = document.createElement('div');
emailError.className = 'error-message';
emailInput.parentNode.appendChild(emailError);

const phoneError = document.createElement('div');
phoneError.className = 'error-message';
phoneInput.parentNode.appendChild(phoneError);

const passwordError = document.createElement('div');
passwordError.className = 'error-message';
passwordInput.parentNode.parentNode.appendChild(passwordError);

const passwordConfirmError = document.createElement('div');
passwordConfirmError.className = 'error-message';
// Attach confirm error after its input container (same parent as password)
if (passwordConfirmInput) {
    passwordConfirmInput.parentNode.parentNode.appendChild(passwordConfirmError);
}

const termsError = document.createElement('div');
termsError.className = 'error-message';
termsCheckbox.parentNode.parentNode.insertBefore(termsError, termsCheckbox.parentNode.nextSibling);
// ===== EXPRESIONES REGULARES =====
const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const phoneRegex = /^[0-9]{10,15}$/;
const nameRegex = /^[a-zA-ZÀ-ÿ\u00f1\u00d1\s'-]+$/;

// Dominios de email sospechosos
const suspiciousEmailDomains = [
    '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
    'mailinator.com', 'yopmail.com', 'temp-mail.org',
    'throwaway.email', 'maildrop.cc', 'mailnesia.com',
    'example.com', 'test.com', 'fake.com', 'spam.com'
];

// ===== VALIDACIONES PARA NOMBRE =====
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
        check: (name) => nameRegex.test(name),
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

// ===== VALIDACIONES PARA EMAIL =====
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

// ===== VALIDACIONES PARA TELÉFONO =====
const phoneValidationSteps = [
    {
        check: (phone) => phone === '' || phoneRegex.test(phone),
        message: "El teléfono debe tener entre 10 y 15 dígitos"
    },
    {
        check: (phone) => {
            if (phone === '') return true;
            return !/^(\d)\1{9,}$/.test(phone);
        },
        message: "El teléfono no puede ser una secuencia de números repetidos"
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
        message: "Debe tener al menos 6 caracteres"
    },
    {
        check: (password) => password.length <= 20,
        message: "No puede tener más de 20 caracteres"
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

// ===== VALIDACIONES PARA CONFIRMACION DE CONTRASEÑA =====
const passwordConfirmValidationSteps = [
    {
        check: (confirm) => confirm !== '',
        message: "Debes confirmar tu contraseña"
    },
    {
        check: (confirm) => confirm === passwordInput.value,
        message: "Las contraseñas no coinciden"
    }
];

// Flag para activar validación en confirm solo después de empezar a escribir
let passwordConfirmDirty = false;

// ===== VALIDACIONES PARA TÉRMINOS =====
const termsValidationSteps = [
    {
        check: (isChecked) => isChecked,
        message: "Debes aceptar los términos y condiciones para continuar"
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
            const nameResult = validateProgressively(usernameInput.value.trim(), nameValidationSteps);
            if (!nameResult.isValid) {
                setFieldError(usernameInput, usernameError, nameResult.message);
                isValid = false;
            } else {
                setFieldValid(usernameInput, usernameError);
            }
            break;
            
        case 2:
            const emailResult = validateProgressively(emailInput.value.trim(), emailValidationSteps);
            if (!emailResult.isValid) {
                setFieldError(emailInput, emailError, emailResult.message);
                isValid = false;
            } else {
                setFieldValid(emailInput, emailError);
            }
            break;
            
        case 3:
            if (phoneInput.value.trim() !== '') {
                const phoneResult = validateProgressively(phoneInput.value.trim(), phoneValidationSteps);
                if (!phoneResult.isValid) {
                    setFieldError(phoneInput, phoneError, phoneResult.message);
                    isValid = false;
                } else {
                    setFieldValid(phoneInput, phoneError);
                }
            }
            break;
            
        case 4:
            const passwordResult = validateProgressively(passwordInput.value, passwordValidationSteps);
            if (!passwordResult.isValid) {
                setFieldError(passwordInput, passwordError, passwordResult.message);
                isValid = false;
            } else {
                setFieldValid(passwordInput, passwordError);
            }
            // Confirmación de contraseña (validación progresiva)
            if (passwordConfirmInput) {
                // Si aún no se empezó a escribir en confirm, no mostrar el error obligatorio
                if (!passwordConfirmDirty && passwordConfirmInput.value === '') {
                    // No forzar el error de 'Debes confirmar tu contraseña' hasta que el usuario escriba
                } else {
                    const confirmResult = validateProgressively(passwordConfirmInput.value, passwordConfirmValidationSteps);
                    if (!confirmResult.isValid) {
                        setFieldError(passwordConfirmInput, passwordConfirmError, confirmResult.message);
                        isValid = false;
                    } else {
                        setFieldValid(passwordConfirmInput, passwordConfirmError);
                    }
                }
            }
            
            const termsResult = validateProgressively(termsCheckbox.checked, termsValidationSteps);
            if (!termsResult.isValid) {
                setFieldError(termsCheckbox, termsError, termsResult.message);
                isValid = false;
            } else {
                setFieldValid(termsCheckbox, termsError);
            }
            break;
    }
    
    return isValid;
}

// ===== NAVEGACIÓN ENTRE PASOS =====
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
 
    if (step === 4) {
        progressBar.style.width = '88%';
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
            const currentFormStep = this.closest('.form-step');
            const stepNumber = parseInt(currentFormStep.dataset.step);
            
            if (validateStep(stepNumber)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            } else {
                // Hacer scroll al primer error
                const errorField = currentFormStep.querySelector('.error');
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
    
    // Validación en tiempo real para nombre
    usernameInput.addEventListener('input', function() {
        const result = validateProgressively(this.value.trim(), nameValidationSteps);
        if (!result.isValid) {
            setFieldError(this, usernameError, result.message);
        } else {
            setFieldValid(this, usernameError);
        }
    });
    
    // Validación en tiempo real para email
    emailInput.addEventListener('input', function() {
        const result = validateProgressively(this.value.trim(), emailValidationSteps);
        if (!result.isValid) {
            setFieldError(this, emailError, result.message);
        } else {
            setFieldValid(this, emailError);
        }
    });
    
    // Validación en tiempo real para teléfono
    phoneInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            const result = validateProgressively(this.value.trim(), phoneValidationSteps);
            if (!result.isValid) {
                setFieldError(this, phoneError, result.message);
            } else {
                setFieldValid(this, phoneError);
            }
        } else {
            setFieldValid(this, phoneError);
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
        updatePasswordStrength(this.value);
        // Re-validar confirmación de contraseña en tiempo real (solo si el usuario empezó a escribir)
        if (passwordConfirmInput && (passwordConfirmDirty || passwordConfirmInput.value !== '')) {
            const confirmResult = validateProgressively(passwordConfirmInput.value, passwordConfirmValidationSteps);
            if (!confirmResult.isValid) {
                setFieldError(passwordConfirmInput, passwordConfirmError, confirmResult.message);
            } else {
                setFieldValid(passwordConfirmInput, passwordConfirmError);
            }
        }
    });

    // Validación en tiempo real para confirmación de contraseña
    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', function() {
            // activamos validación sólo cuando el usuario empieza a escribir
            passwordConfirmDirty = true;

            // Si está vacío, no mostramos el error aún (salvo en el submit/validateStep)
            if (this.value === '') {
                setFieldValid(this, passwordConfirmError);
                return;
            }

            const confirmResult = validateProgressively(this.value, passwordConfirmValidationSteps);
            if (!confirmResult.isValid) {
                setFieldError(this, passwordConfirmError, confirmResult.message);
            } else {
                setFieldValid(this, passwordConfirmError);
            }
        });
    }
    
    // Validación en tiempo real para términos
    termsCheckbox.addEventListener('change', function() {
        const result = validateProgressively(this.checked, termsValidationSteps);
        if (!result.isValid) {
            setFieldError(this, termsError, result.message);
        } else {
            setFieldValid(this, termsError);
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
    form.addEventListener('submit', function(e) {
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

// ===== INDICADOR DE FUERZA DE CONTRASEÑA =====
function updatePasswordStrength(password) {
    const strengthBar = document.getElementById('password-strength-bar');
    if (!strengthBar) return;
    
    strengthBar.className = 'password-strength';
    if (!password) {
        strengthBar.style.width = '0%';
        return;
    }
    
    let strength = 0;
    
    // Longitud mínima
    if (password.length >= 6) strength += 20;
    if (password.length >= 8) strength += 20;
    
    // Complejidad
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[a-z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 15;
    
    // Asegurarnos de que no supere el 100%
    strength = Math.min(strength, 100);
    strengthBar.style.width = strength + '%';
    
    // Clases de color según la fuerza
    if (strength < 40) {
        strengthBar.classList.add('weak');
    } else if (strength < 70) {
        strengthBar.classList.add('medium');
    } else {
        strengthBar.classList.add('strong');
    }
}