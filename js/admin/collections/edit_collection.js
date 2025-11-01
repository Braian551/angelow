// edit_collection.js - Editar colección
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('collectionForm');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const removeImageBtn = document.getElementById('removeImage');
    const submitBtn = document.getElementById('submitBtn');
    
    // Guardar slug original para comparar
    const originalSlug = slugInput.value;
    
    // Generar slug automáticamente desde el nombre (solo si se cambia)
    nameInput.addEventListener('input', function() {
        if (!slugInput.dataset.manualEdit && slugInput.value === originalSlug) {
            slugInput.value = generateSlug(this.value);
        }
    });
    
    // Marcar que el slug fue editado manualmente
    slugInput.addEventListener('input', function() {
        slugInput.dataset.manualEdit = 'true';
        this.value = generateSlug(this.value);
    });
    
    // Vista previa de imagen
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (validateImage(file)) {
                showImagePreview(file);
                if (removeImageBtn) {
                    removeImageBtn.style.display = 'inline-block';
                }
            } else {
                imageInput.value = '';
            }
        }
    });
    
    // Quitar imagen
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            imageInput.value = '';
            resetImagePreview();
            
            // Limpiar también la imagen actual en el campo oculto
            const currentImageInput = document.querySelector('input[name="current_image"]');
            if (currentImageInput) {
                currentImageInput.value = '';
            }
            
            this.style.display = 'none';
        });
    }
    
    // Enviar formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        
        const formData = new FormData(form);
        
        fetch('save_collection.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    window.location.href = 'collections_list.php';
                }, 1500);
            } else {
                showToast(data.message || 'Error al actualizar la colección', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar Colección';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error de conexión', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar Colección';
        });
    });
});

// Generar slug
function generateSlug(text) {
    return text
        .toLowerCase()
        .replace(/[áàäâ]/g, 'a')
        .replace(/[éèëê]/g, 'e')
        .replace(/[íìïî]/g, 'i')
        .replace(/[óòöô]/g, 'o')
        .replace(/[úùüû]/g, 'u')
        .replace(/ñ/g, 'n')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Validar imagen
function validateImage(file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];
    const maxSize = 15 * 1024 * 1024; // 15MB
    
    if (!allowedTypes.includes(file.type)) {
        showToast('Tipo de archivo no permitido. Solo JPG, PNG, WebP o AVIF', 'error');
        return false;
    }
    
    if (file.size > maxSize) {
        showToast('El archivo es demasiado grande. Máximo 15MB', 'error');
        return false;
    }
    
    return true;
}

// Mostrar vista previa de imagen
function showImagePreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const imagePreview = document.getElementById('imagePreview');
        imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="preview-image">`;
    };
    reader.readAsDataURL(file);
}

// Resetear vista previa
function resetImagePreview() {
    const imagePreview = document.getElementById('imagePreview');
    imagePreview.innerHTML = `
        <div class="preview-placeholder">
            <i class="fas fa-layer-group"></i>
            <p>Sin imagen</p>
        </div>
    `;
}

// Validar formulario
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const slug = document.getElementById('slug').value.trim();
    
    if (!name) {
        showToast('El nombre es obligatorio', 'error');
        return false;
    }
    
    if (!slug) {
        showToast('El slug es obligatorio', 'error');
        return false;
    }
    
    if (!/^[a-z0-9-]+$/.test(slug)) {
        showToast('El slug solo puede contener letras minúsculas, números y guiones', 'error');
        return false;
    }
    
    return true;
}

// Mostrar notificación toast
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast toast-${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
