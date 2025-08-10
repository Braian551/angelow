<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let variantCounter = <?= count($productVariants) ?>;
    let imagesToDelete = [];
    let variantImageSelections = {};
    
    // Inicializar Select2 para selects
    $('.variant-color, .variant-size').select2({
        width: '100%',
        theme: 'bootstrap4'
    });
    
    // Inicializar selecciones de imágenes para variantes existentes
    <?php foreach ($productVariants as $index => $variant): ?>
        <?php if (isset($variantImages[$variant['id']])): ?>
            variantImageSelections[<?= $index ?>] = [<?= implode(',', array_column($variantImages[$variant['id']], 'id')) ?>];
        <?php else: ?>
            variantImageSelections[<?= $index ?>] = [];
        <?php endif; ?>
    <?php endforeach; ?>
    
    // --------------------------
    // 1. MÓDULO DE IMÁGENES
    // --------------------------
    const imagesModule = (function() {
        const container = document.getElementById('image-upload-container');
        const template = container.querySelector('.template');
        const addBtn = document.getElementById('add-image-btn');
        let imageCounter = <?= count($productImages) ?>;
        let uploadedImages = [];
        
        // Cargar imágenes existentes
        <?php foreach ($productImages as $index => $image): ?>
            uploadedImages[<?= $index ?>] = {
                id: <?= $image['id'] ?>,
                preview: '<?= BASE_URL . '/' . $image['image_path'] ?>',
                element: null
            };
        <?php endforeach; ?>
        
        function addImage(file = null, previewUrl = null, imageId = null, altText = '') {
            const newImage = template.cloneNode(true);
            newImage.style.display = 'flex';
            const currentIndex = imageCounter;
            newImage.dataset.index = currentIndex;
            newImage.dataset.imageId = imageId || '';

            const fileInput = newImage.querySelector('.image-file-input');
            const previewImg = newImage.querySelector('.preview-image');
            const removeBtn = newImage.querySelector('.remove-image-btn');
            const altInput = newImage.querySelector('input[name="image_alt[]"]');

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (previewUrl) {
                previewImg.src = previewUrl;
                previewImg.style.display = 'block';
            }

            if (altText) {
                altInput.value = altText;
            }

            fileInput.addEventListener('change', function() {
                if (this.files?.[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';

                        if (uploadedImages[currentIndex]) {
                            uploadedImages[currentIndex].preview = e.target.result;
                        }

                        updateVariantImageOptions();
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            removeBtn.addEventListener('click', () => {
                const imageIdToDelete = newImage.dataset.imageId;
                if (imageIdToDelete) {
                    imagesToDelete.push(imageIdToDelete);
                    document.getElementById('delete-images-input').value = imagesToDelete.join(',');
                }
                
                // Eliminar esta imagen de todas las selecciones de variantes
                Object.keys(variantImageSelections).forEach(variantIndex => {
                    variantImageSelections[variantIndex] = variantImageSelections[variantIndex].filter(
                        imgId => imgId !== parseInt(imageIdToDelete)
                    );
                    updateVariantImagesInput(variantIndex);
                });
                
                delete uploadedImages[currentIndex];
                container.removeChild(newImage);
                updateVariantImageOptions();
            });

            container.insertBefore(newImage, addBtn);

            uploadedImages[currentIndex] = {
                element: newImage,
                preview: previewUrl || null,
                alt: altText,
                id: imageId || null
            };

            imageCounter++;

            return newImage;
        }

        function getImageOptions() {
            return uploadedImages.filter(img => img !== undefined && img.preview).map((img, idx) => {
                return {
                    id: img.id || idx,
                    text: `Imagen ${idx + 1}`,
                    preview: img.preview,
                    index: idx
                };
            });
        }

        function hasValidImages() {
            return uploadedImages.some(img => img !== undefined && img.preview);
        }

        // Agregar imágenes existentes al cargar
        <?php foreach ($productImages as $index => $image): ?>
            addImage(null, '<?= BASE_URL . '/' . $image['image_path'] ?>', <?= $image['id'] ?>, '<?= addslashes($image['alt_text']) ?>');
        <?php endforeach; ?>

        addBtn.addEventListener('click', () => {
            addImage();
            updateVariantImageOptions();
        });

        return {
            add: addImage,
            getImageOptions: getImageOptions,
            hasValidImages: hasValidImages
        };
    })();

    // --------------------------
    // 2. MÓDULO DE VARIANTES
    // --------------------------
    const variantsModule = (function() {
        const container = document.getElementById('variants-container');
        const template = document.getElementById('variant-template');
        const addBtn = document.getElementById('add-variant-btn');
        
        function updateImageGrids() {
            const imageOptions = imagesModule.getImageOptions();
            const variantItems = container.querySelectorAll('.variant-item');
            
            variantItems.forEach(variant => {
                const variantIndex = variant.dataset.index;
                const grid = variant.querySelector('.variant-images-grid');
                const input = variant.querySelector('.variant-images-input');
                
                if (grid) {
                    // Limpiar grid
                    grid.innerHTML = '';
                    
                    if (imageOptions.length === 0) {
                        grid.classList.add('empty');
                        return;
                    }
                    
                    grid.classList.remove('empty');
                    
                    imageOptions.forEach(opt => {
                        if (!opt.preview) return;
                        
                        const thumbContainer = document.createElement('div');
                        thumbContainer.className = 'variant-image-thumb-container';
                        thumbContainer.title = `Imagen ${opt.index + 1}`;
                        thumbContainer.dataset.imageId = opt.id;
                        
                        const thumb = document.createElement('img');
                        thumb.className = 'variant-image-thumb';
                        thumb.src = opt.preview;
                        thumb.alt = `Imagen ${opt.index + 1}`;
                        
                        const checkIcon = document.createElement('div');
                        checkIcon.className = 'selected-check';
                        checkIcon.innerHTML = '<i class="fas fa-check"></i>';
                        
                        thumbContainer.appendChild(thumb);
                        thumbContainer.appendChild(checkIcon);
                        
                        // Verificar si esta imagen está seleccionada para esta variante
                        if (variantImageSelections[variantIndex] && variantImageSelections[variantIndex].includes(parseInt(opt.id))) {
                            thumbContainer.classList.add('selected');
                        }
                        
                        thumbContainer.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const imageId = parseInt(this.dataset.imageId);
                            
                            if (!variantImageSelections[variantIndex]) {
                                variantImageSelections[variantIndex] = [];
                            }
                            
                            if (this.classList.contains('selected')) {
                                this.classList.remove('selected');
                                variantImageSelections[variantIndex] = variantImageSelections[variantIndex].filter(
                                    id => id !== imageId
                                );
                            } else {
                                this.classList.add('selected');
                                if (!variantImageSelections[variantIndex].includes(imageId)) {
                                    variantImageSelections[variantIndex].push(imageId);
                                }
                            }
                            
                            updateVariantImagesInput(variantIndex);
                        });
                        
                        grid.appendChild(thumbContainer);
                    });
                    
                    // Actualizar input hidden
                    if (variantImageSelections[variantIndex]) {
                        input.value = variantImageSelections[variantIndex].join(',');
                    }
                }
            });
        }
        
        function updateVariantImagesInput(variantIndex) {
            const variant = document.querySelector(`.variant-item[data-index="${variantIndex}"]`);
            const input = variant.querySelector('.variant-images-input');
            const selectedImages = variantImageSelections[variantIndex] || [];
            input.value = selectedImages.join(',');
        }

        function generateSKU(variantElement) {
            const brandInput = document.getElementById('brand');
            const productNameInput = document.getElementById('name');
            const sizeSelect = variantElement.querySelector('[name*="size_id"]');
            const colorSelect = variantElement.querySelector('[name*="color_id"]');
            const skuInput = variantElement.querySelector('.variant-sku');
            
            if (!brandInput || !productNameInput || !skuInput) return;
            
            const brand = brandInput.value.trim().toUpperCase();
            const productName = productNameInput.value.trim().toUpperCase();
            const brandAbbr = brand.replace(/[^A-Z]/g, '').substring(0, 3);
            
            if (brandAbbr.length < 3) {
                console.warn('La marca necesita al menos 3 letras para generar SKU');
                return;
            }
            
            const productAbbr = productName.replace(/[^A-Z0-9]/g, '').substring(0, 4);
            const sizeAbbr = sizeSelect && sizeSelect.value ? 
                sizeSelect.selectedOptions[0].text.replace(/[^A-Z0-9]/g, '').substring(0, 3).toUpperCase() : 'TAL';
            
            let colorAbbr = 'COL';
            if (colorSelect && colorSelect.value) {
                const colorName = colorSelect.selectedOptions[0].text;
                const cleanColorName = colorName.replace(/\s+/g, '');
                colorAbbr = cleanColorName.substring(0, 3).toUpperCase();
                
                if (colorName.includes(' ')) {
                    const colorParts = colorName.split(' ');
                    if (colorParts.length >= 2) {
                        colorAbbr = (colorParts[0].substring(0, 2) + colorParts[1].substring(0, 1)).toUpperCase();
                    }
                }
            }
            
            skuInput.value = `${brandAbbr}-${productAbbr}-${sizeAbbr}-${colorAbbr}-${<?= $productId ?>}`;
        }

        function addVariant(variantData = null) {
            const newVariant = template.cloneNode(true);
            newVariant.style.display = 'block';
            const variantIndex = variantCounter;
            newVariant.dataset.index = variantIndex;
            newVariant.innerHTML = newVariant.innerHTML.replace(/variants\[0\]/g, `variants[${variantIndex}]`);
            
            const sizeSelect = newVariant.querySelector('[name*="size_id"]');
            const colorSelect = newVariant.querySelector('[name*="color_id"]');
            const removeBtn = newVariant.querySelector('.remove-variant');
            const imageInput = newVariant.querySelector('.variant-images-input');
            const isDefaultCheckbox = newVariant.querySelector('[name*="is_default"]');
            const quantityInput = newVariant.querySelector('[name*="quantity"]');
            const priceInput = newVariant.querySelector('[name*="price"]');
            const comparePriceInput = newVariant.querySelector('[name*="compare_price"]');
            const barcodeInput = newVariant.querySelector('[name*="barcode"]');
            const isActiveCheckbox = newVariant.querySelector('[name*="is_active"]');
            const skuInput = newVariant.querySelector('.variant-sku');

            // Inicializar selecciones para esta variante
            variantImageSelections[variantIndex] = variantData?.images || [];

            // Configurar eventos para generar SKU
            const skuGenerationHandler = () => generateSKU(newVariant);
            sizeSelect?.addEventListener('change', skuGenerationHandler);
            colorSelect?.addEventListener('change', skuGenerationHandler);
            document.getElementById('brand')?.addEventListener('input', skuGenerationHandler);
            document.getElementById('name')?.addEventListener('input', skuGenerationHandler);
            
            // Manejar la selección de variante principal
            isDefaultCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Desmarcar todas las otras variantes principales
                    document.querySelectorAll('[name*="is_default"]').forEach(checkbox => {
                        if (checkbox !== this) {
                            checkbox.checked = false;
                        }
                    });
                }
            });

            // Rellenar datos si se proporcionan
            if (variantData) {
                if (variantData.size_id) sizeSelect.value = variantData.size_id;
                if (variantData.color_id) colorSelect.value = variantData.color_id;
                if (variantData.quantity) quantityInput.value = variantData.quantity;
                if (variantData.price) priceInput.value = variantData.price;
                if (variantData.compare_price) comparePriceInput.value = variantData.compare_price;
                if (variantData.barcode) barcodeInput.value = variantData.barcode;
                if (variantData.is_default) isDefaultCheckbox.checked = true;
                if (variantData.is_active) isActiveCheckbox.checked = true;
                if (variantData.sku) skuInput.value = variantData.sku;
                
                // Inicializar Select2 con valores
                $(sizeSelect).trigger('change');
                $(colorSelect).trigger('change');
            }
            
            removeBtn.addEventListener('click', () => {
                if (confirm('¿Estás seguro de eliminar esta variante?')) {
                    delete variantImageSelections[variantIndex];
                    container.removeChild(newVariant);
                    reindexVariants();
                    updateImageGrids();
                }
            });
            
            container.appendChild(newVariant);
            variantCounter++;
            updateImageGrids();
            
            skuGenerationHandler();
            return newVariant;
        }

        function reindexVariants() {
            const variants = container.querySelectorAll('.variant-item');
            variantCounter = variants.length;
            const newSelections = {};
            
            variants.forEach((variant, newIndex) => {
                const oldIndex = variant.dataset.index;
                variant.dataset.index = newIndex;
                
                if (variantImageSelections[oldIndex]) {
                    newSelections[newIndex] = variantImageSelections[oldIndex];
                }
                
                // Actualizar nombres de los inputs
                variant.querySelectorAll('input, select').forEach(input => {
                    input.name = input.name.replace(/variants\[\d+\]/, `variants[${newIndex}]`);
                });
            });
            
            // Actualizar el objeto de selecciones
            variantImageSelections = newSelections;
            
            return variants;
        }

        function validateVariants() {
            const variants = container.querySelectorAll('.variant-item');
            const errors = [];
            
            if (variants.length === 0) {
                errors.push('Debe agregar al menos una variante');
                return {
                    isValid: false,
                    errors
                };
            }
            
            variants.forEach((variant, index) => {
                const variantIndex = variant.dataset.index;
                const sizeSelect = variant.querySelector('[name*="size_id"]');
                const colorSelect = variant.querySelector('[name*="color_id"]');
                const quantityInput = variant.querySelector('[name*="quantity"]');
                const priceInput = variant.querySelector('[name*="price"]');
                
                if (!sizeSelect?.value) errors.push(`Variante ${index+1}: Falta seleccionar talla`);
                if (!colorSelect?.value) errors.push(`Variante ${index+1}: Falta seleccionar color`);
                if (!quantityInput?.value || isNaN(quantityInput.value)) errors.push(`Variante ${index+1}: Cantidad inválida`);
                if (!priceInput?.value || isNaN(priceInput.value) || parseFloat(priceInput.value) <= 0) {
                    errors.push(`Variante ${index+1}: Precio inválido`);
                }
                
                // Validar que cada variante tenga al menos una imagen seleccionada
                if (!variantImageSelections[variantIndex] || variantImageSelections[variantIndex].length === 0) {
                    errors.push(`Variante ${index+1}: Debe seleccionar al menos una imagen`);
                }
            });
            
            // Validar que solo haya una variante principal
            const defaultVariants = document.querySelectorAll('[name*="is_default"]:checked');
            if (defaultVariants.length !== 1) {
                errors.push('Debe marcar exactamente una variante como principal');
            }
            
            return {
                isValid: errors.length === 0,
                errors
            };
        }

        // Agregar variantes existentes al cargar
        <?php foreach ($productVariants as $index => $variant): ?>
            addVariant({
                id: <?= $variant['id'] ?>,
                size_id: <?= $variant['size_id'] ? json_encode($variant['size_id']) : 'null' ?>,
                color_id: <?= $variant['color_id'] ? json_encode($variant['color_id']) : 'null' ?>,
                quantity: <?= $variant['quantity'] ?>,
                price: <?= $variant['price'] ?>,
                compare_price: <?= $variant['compare_price'] ? json_encode($variant['compare_price']) : 'null' ?>,
                barcode: <?= $variant['barcode'] ? json_encode($variant['barcode']) : 'null' ?>,
                is_default: <?= $variant['is_default'] ? 'true' : 'false' ?>,
                is_active: <?= $variant['is_active'] ? 'true' : 'false' ?>,
                sku: <?= $variant['sku'] ? json_encode($variant['sku']) : 'null' ?>,
                images: <?= isset($variantImages[$variant['id']]) ? json_encode(array_column($variantImages[$variant['id']], 'id')) : '[]' ?>
            });
        <?php endforeach; ?>
        
        return {
            add: addVariant,
            validate: validateVariants,
            updateImageSelects: updateImageGrids
        };
    })();

    // Función para actualizar opciones de imágenes en variantes
    function updateVariantImageOptions() {
        variantsModule.updateImageSelects();
    }

    // --------------------------
    // 3. MANEJO DEL FORMULARIO
    // --------------------------
    document.getElementById('product-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validar marca
        const brand = document.getElementById('brand').value.trim();
        const brandLetters = brand.replace(/[^A-Za-z]/g, '');
        if (brandLetters.length < 3) {
            return showAlert('La marca debe contener al menos 3 letras para generar SKUs válidos', 'error');
        }

        // Validar imágenes
        if (!imagesModule.hasValidImages()) {
            return showAlert('Debe subir al menos una imagen del producto', 'error');
        }

        // Validar variantes
        const variantValidation = variantsModule.validate();
        if (!variantValidation.isValid) {
            return showAlert('Corrija estos errores:<br>' + variantValidation.errors.join('<br>'), 'error');
        }

        // Mostrar loader
        showAlert('Guardando cambios...', 'info', {
            showButtons: false
        });

        const formData = new FormData(this);

        // Agregar datos de variantes
        document.querySelectorAll('.variant-item').forEach((variant, index) => {
            variant.querySelectorAll('input, select').forEach(input => {
                const name = input.name.replace(/variants\[\d+\]/, `variants[${index}]`);
                if (input.type === 'checkbox') {
                    formData.append(name, input.checked ? '1' : '0');
                } else if (input.type !== 'file') {
                    if (input.multiple) {
                        Array.from(input.selectedOptions).forEach(option => {
                            formData.append(name, option.value);
                        });
                    } else {
                        // Para campos de precio, enviar el valor numérico limpio
                        if (input.name.includes('[price]') || input.name.includes('[compare_price]')) {
                            const numericValue = input.value.replace(/[^0-9.]/g, '');
                            formData.append(name, numericValue);
                        } else {
                            formData.append(name, input.value);
                        }
                    }
                }
            });
        });

        // Enviar formulario via AJAX
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            closeAlert();
            if (data.success) {
                showAlert(data.message, 'success', {
                    onConfirm: () => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }
                });
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            closeAlert();
            showAlert('Error al enviar el formulario: ' + error.message, 'error');
        });
    });

    // --------------------------
    // 4. BOTÓN AGREGAR VARIANTE
    // --------------------------
    document.getElementById('add-variant-btn').addEventListener('click', variantsModule.add);

    // --------------------------
    // 5. FORMATO DE PRECIOS
    // --------------------------
    function formatPriceInput(input) {
        // Obtener valor actual
        let value = input.value;
        
        // Eliminar todos los caracteres no numéricos excepto punto
        let numericValue = value.replace(/[^0-9.]/g, '');
        
        // Guardar posición del cursor
        const cursorPosition = input.selectionStart - (value.length - numericValue.length);
        
        // Formatear con puntos cada 3 dígitos
        if (numericValue.length > 0) {
            // Convertir a número para eliminar ceros a la izquierda
            const parts = numericValue.split('.');
            let integerPart = parts[0].replace(/\D/g, '');
            let decimalPart = parts[1] ? '.' + parts[1].replace(/\D/g, '').substring(0, 2) : '';
            
            // Aplicar formato de miles solo a la parte entera
            let formattedValue = '';
            for (let i = integerPart.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedValue = '.' + formattedValue;
                }
                formattedValue = integerPart[i] + formattedValue;
            }
            
            // Actualizar el valor en el input
            input.value = formattedValue + decimalPart;
            
            // Restaurar posición del cursor
            const newCursorPosition = cursorPosition + (input.value.length - value.length);
            input.setSelectionRange(newCursorPosition, newCursorPosition);
        } else {
            input.value = '';
        }
    }

    // Manejar el evento input para los campos de precio
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[price]"], input[name*="[compare_price]"]')) {
            formatPriceInput(e.target);
        }
    });

    // Aplicar formato inicial a los campos de precio existentes
    document.querySelectorAll('input[name*="[price]"], input[name*="[compare_price]"]').forEach(input => {
        formatPriceInput(input);
    });

    // --------------------------
    // 6. MANEJO DE COLORES EN SELECT
    // --------------------------
    $(document).on('select2:open', () => {
        document.querySelector('.select2-container--open .select2-search__field').focus();
    });

    // Actualizar el SKU cuando cambia cualquier campo relevante
    document.addEventListener('change', function(e) {
        if (e.target.matches('[name*="size_id"], [name*="color_id"]') || 
            e.target.matches('#brand, #name')) {
            const variantItem = e.target.closest('.variant-item');
            if (variantItem) {
                generateSKU(variantItem);
            }
        }
    });
});
</script>