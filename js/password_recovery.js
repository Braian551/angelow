(function () {
    const form = document.getElementById('recoveryForm');
    if (!form) return;

    const requestBtn = document.getElementById('requestCodeBtn');
    const verifyBtn = document.getElementById('verifyCodeBtn');
    const resendBtn = document.getElementById('resendCodeBtn');
    const resetBtn = document.getElementById('resetPasswordBtn');
    const identifierInput = document.getElementById('recoveryIdentifier');
    const codeInput = document.getElementById('verificationCode');
    const timerLabel = document.getElementById('codeTimer');
    const codeInfo = document.getElementById('codeInfo');
    const codeStatus = document.getElementById('codeStatus');

    const steps = Array.from(document.querySelectorAll('.form-step'));
    const progressSteps = Array.from(document.querySelectorAll('.progress-steps .step'));
    const progressBar = document.querySelector('.progress-steps .progress');

    let currentStep = 1;
    let identifierValue = '';
    let countdownInterval = null;
    let resendInterval = null;
    let activeSessionToken = null;
    let lastExpiresIn = 0;

    const endpoint = window.ANGELOW_RECOVERY_ENDPOINT || '/auth/password_reset_controller.php';

    function setStep(step) {
        currentStep = step;
        steps.forEach((node, idx) => {
            node.classList.toggle('active', idx === step - 1);
        });
        progressSteps.forEach((node, idx) => {
            node.classList.toggle('active', idx === step - 1);
            node.classList.toggle('completed', idx < step - 1);
        });
        if (progressBar) {
            const percent = ((step - 1) / (steps.length - 1)) * 100;
            progressBar.style.width = `${percent}%`;
        }
    }

    function setFieldError(field, message) {
        const errorNode = document.querySelector(`[data-error-for="${field}"]`);
        if (errorNode) {
            errorNode.textContent = message || '';
            errorNode.style.display = message ? 'block' : 'none';
        }
    }

    function clearAllErrors() {
        document.querySelectorAll('.error-message').forEach(node => {
            node.textContent = '';
            node.style.display = 'none';
        });
    }

    function formatTimer(seconds) {
        const clamped = Math.max(0, Math.floor(seconds));
        const minutes = String(Math.floor(clamped / 60)).padStart(2, '0');
        const secs = String(clamped % 60).padStart(2, '0');
        return `${minutes}:${secs}`;
    }

    function startCountdown(seconds) {
        stopCountdown();
        lastExpiresIn = seconds;
        updateTimerLabel(seconds);
        if (codeStatus) {
            codeStatus.classList.remove('valid', 'expired');
            codeStatus.classList.add('pending');
            codeStatus.textContent = 'Pendiente de validación';
        }
        countdownInterval = setInterval(() => {
            seconds -= 1;
            lastExpiresIn = seconds;
            updateTimerLabel(seconds);
            if (seconds <= 0) {
                stopCountdown();
                if (codeStatus) {
                    codeStatus.classList.remove('pending', 'valid');
                    codeStatus.classList.add('expired');
                    codeStatus.textContent = 'Código expirado';
                }
            }
        }, 1000);
    }

    function updateTimerLabel(seconds) {
        if (timerLabel) {
            timerLabel.textContent = formatTimer(seconds);
        }
    }

    function stopCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }

    function startResendCooldown(seconds) {
        stopResendCooldown();
        if (!resendBtn) return;
        resendBtn.disabled = true;
        resendBtn.textContent = `Reenviar código (${seconds}s)`;
        resendInterval = setInterval(() => {
            seconds -= 1;
            if (seconds <= 0) {
                stopResendCooldown();
                resendBtn.disabled = false;
                resendBtn.textContent = 'Reenviar código';
                return;
            }
            resendBtn.textContent = `Reenviar código (${seconds}s)`;
        }, 1000);
    }

    function stopResendCooldown() {
        if (resendInterval) {
            clearInterval(resendInterval);
            resendInterval = null;
        }
        if (resendBtn) {
            resendBtn.textContent = 'Reenviar código';
        }
    }

    function resetVerificationState(clearIdentifier = false) {
        stopCountdown();
        stopResendCooldown();
        codeInput.value = '';
        activeSessionToken = null;
        lastExpiresIn = 0;
        identifierValue = '';
        if (codeStatus) {
            codeStatus.textContent = 'Pendiente de validación';
        }
        if (clearIdentifier) {
            identifierInput.value = '';
        }
    }

    async function callEndpoint(action, payload) {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action, ...payload })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            const msg = data && data.message ? data.message : 'No pudimos procesar la solicitud.';
            throw new Error(msg);
        }
        return data;
    }

    function validateIdentifier(value) {
        const trimmed = value.trim();
        if (!trimmed) {
            setFieldError('identifier', 'Debes ingresar tu correo o teléfono registrado.');
            return null;
        }
        const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
        const digits = trimmed.replace(/[^0-9]/g, '');
        if (!isEmail && (digits.length < 7 || digits.length > 15)) {
            setFieldError('identifier', 'Ingresa un correo válido o un teléfono de 7 a 15 dígitos.');
            return null;
        }
        setFieldError('identifier', '');
        return trimmed;
    }

    function validateCode(value) {
        const code = value.trim();
        if (!/^[0-9]{6}$/.test(code)) {
            setFieldError('code', 'El código debe tener 6 dígitos.');
            return null;
        }
        setFieldError('code', '');
        return code;
    }

    function validatePasswords(password, confirm) {
        let isValid = true;
        if (!password || password.length < 8) {
            setFieldError('password', 'La contraseña debe tener al menos 8 caracteres.');
            isValid = false;
        } else {
            setFieldError('password', '');
        }

        if (password !== confirm) {
            setFieldError('password_confirm', 'Las contraseñas no coinciden.');
            isValid = false;
        } else if (confirm.length >= 8) {
            setFieldError('password_confirm', '');
        }
        return isValid;
    }

    requestBtn?.addEventListener('click', async () => {
        clearAllErrors();
        const value = validateIdentifier(identifierInput.value);
        if (!value) return;

        requestBtn.disabled = true;
        try {
            const response = await callEndpoint('request_code', { identifier: value });
            identifierValue = value;
            const data = response.data || {};
            const expiresIn = typeof data.expires_in === 'number' ? data.expires_in : 900;
            const masked = data.identifier || 'tu correo';
            const cooldown = typeof data.resend_cooldown === 'number' ? data.resend_cooldown : 60;

            codeInfo.textContent = `Enviamos un código a ${masked}. Revisa tu bandeja principal y spam.`;
            startCountdown(expiresIn);
            startResendCooldown(cooldown);
            setStep(2);
            showSuccess(response.message || 'Código enviado.');
        } catch (error) {
            setFieldError('identifier', error.message);
        } finally {
            requestBtn.disabled = false;
        }
    });

    verifyBtn?.addEventListener('click', async () => {
        clearAllErrors();
        if (!identifierValue) {
            showError('Primero solicita un código.');
            setStep(1);
            return;
        }
        const code = validateCode(codeInput.value);
        if (!code) return;

        verifyBtn.disabled = true;
        try {
            const response = await callEndpoint('verify_code', {
                identifier: identifierValue,
                code
            });
            activeSessionToken = response.data && response.data.session_token ? response.data.session_token : null;
            if (!activeSessionToken) {
                throw new Error('No pudimos validar la sesión de recuperación.');
            }
            stopCountdown();
            stopResendCooldown();
            if (codeStatus) {
                codeStatus.classList.remove('pending', 'expired');
                codeStatus.classList.add('valid');
                codeStatus.textContent = 'Código validado';
            }
            setStep(3);
            showSuccess(response.message || 'Código verificado.');
        } catch (error) {
            setFieldError('code', error.message);
        } finally {
            verifyBtn.disabled = false;
        }
    });

    resendBtn?.addEventListener('click', async () => {
        if (!identifierValue || resendBtn.disabled) return;
        try {
            resendBtn.disabled = true;
            const response = await callEndpoint('resend_code', { identifier: identifierValue });
            const data = response.data || {};
            const expiresIn = typeof data.expires_in === 'number' ? data.expires_in : lastExpiresIn || 900;
            const cooldown = typeof data.resend_cooldown === 'number' ? data.resend_cooldown : 60;
            startCountdown(expiresIn);
            startResendCooldown(cooldown);
            showSuccess(response.message || 'Nuevo código enviado.');
        } catch (error) {
            showError(error.message || 'No pudimos reenviar el código.');
            resendBtn.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (currentStep !== 3) return;
        if (!activeSessionToken) {
            showError('Necesitas validar un código antes de continuar.');
            setStep(1);
            return;
        }

        const password = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        if (!validatePasswords(password, confirm)) return;

        resetBtn.disabled = true;
        try {
            const response = await callEndpoint('reset_password', {
                session_token: activeSessionToken,
                password,
                password_confirm: confirm
            });
            showSuccess(response.message || 'Contraseña actualizada.', () => {
                window.location.href = `${window.ANGELOW_BASE_URL || ''}/users/formlogin.php`;
            });
            form.reset();
            resetVerificationState(true);
            setStep(1);
        } catch (error) {
            showError(error.message || 'No pudimos actualizar la contraseña.');
        } finally {
            resetBtn.disabled = false;
        }
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) {
                if (currentStep === 2) {
                    resetVerificationState(false);
                    setStep(1);
                } else if (currentStep === 3) {
                    setStep(2);
                }
            }
        });
    });

    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    });

    function showSuccess(message, onConfirm) {
        if (typeof showAlert === 'function') {
            showAlert(message, 'success', {
                buttonText: onConfirm ? 'Continuar' : 'OK',
                onConfirm
            });
            return;
        }
        alert(message);
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    }

    function showError(message) {
        if (typeof showAlert === 'function') {
            showAlert(message, 'error');
            return;
        }
        alert(message);
    }
})();
