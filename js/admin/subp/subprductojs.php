<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Incluir el modal al DOM
        const sizeModal = document.createElement('div');
        sizeModal.innerHTML = `<?php include __DIR__ . '/../../../admin/modals/sub/size_modal.php'; ?>`;
        document.body.appendChild(sizeModal);

        // Variables para el modal
        let currentSizeOption = null;
        let currentVariantIndex = null;
        let currentSizeId = null;

        function formatPesosColombianos(number) {
            if (number === null || number === undefined || number === '') return '0';
            const num = typeof number === 'string' ? parseFloat(number.replace(/\./g, '')) : number;
            if (isNaN(num)) return '0';
            return new Intl.NumberFormat('es-CO', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        function parsePesosColombianos(formattedNumber) {
            if (!formattedNumber) return 0;
            const num = parseFloat(formattedNumber.replace(/\./g, ''));
            return isNaN(num) ? 0 : num;
        }

        function initializePriceInputs() {
            document.querySelectorAll('.price-input').forEach(input => {
                if (input.value) {
                    input.value = formatPesosColombianos(input.value);
                }

                input.addEventListener('input', function(e) {
                    const value = e.target.value.replace(/\./g, '');
                    if (!isNaN(value) || value === '') {
                        e.target.value = formatPesosColombianos(value);
                    }
                });
            });
        }

        initializePriceInputs();

        function showSizeModal(sizeOption, variantIndex, sizeId) {
            const modal = document.getElementById('size-modal');
            const modalPrice = document.getElementById('modal-price');
            const modalQuantity = document.getElementById('modal-quantity');
            const modalComparePrice = document.getElementById('modal-compare-price');
            const modalSku = document.getElementById('modal-sku');
            const modalBarcode = document.getElementById('modal-barcode');

            const basePriceInput = document.getElementById('price');
            let basePrice = 0;
            if (basePriceInput && basePriceInput.value) {
                basePrice = parsePesosColombianos(basePriceInput.value);
            }

            modalPrice.value = basePrice ? formatPesosColombianos(basePrice) : '';
            modalQuantity.value = '';
            modalComparePrice.value = '';
            modalBarcode.value = '';

            const productName = document.getElementById('name').value;
            const colorSelect = sizeOption.closest('.variant-card').querySelector('.color-select');
            const colorId = colorSelect.value;

            modalSku.value = generarSKU(productName, colorId, sizeId);

            modal.style.display = 'block';

            const closeButtons = modal.querySelectorAll('.close-modal');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            });

            const saveBtn = modal.querySelector('.save-size');
            saveBtn.onclick = function() {
                if (!modalPrice.value || !modalQuantity.value) {
                    showAlert('Debes ingresar precio y cantidad', 'error');
                    return;
                }

                const priceValue = parsePesosColombianos(modalPrice.value);
                const comparePriceValue = modalComparePrice.value ? parsePesosColombianos(modalComparePrice.value) : null;

                const hiddenInputs = sizeOption.querySelectorAll('input[type="hidden"]');
                
                // Actualizar los nombres y valores de los inputs ocultos
                hiddenInputs[0].name = `variant_size[${sizeId}][${variantIndex}]`;
                hiddenInputs[0].value = sizeId;
                hiddenInputs[1].name = `variant_price[${variantIndex}][${sizeId}]`;
                hiddenInputs[1].value = priceValue;
                hiddenInputs[2].name = `variant_quantity[${variantIndex}][${sizeId}]`;
                hiddenInputs[2].value = modalQuantity.value;
                hiddenInputs[3].name = `variant_compare_price[${variantIndex}][${sizeId}]`;
                hiddenInputs[3].value = comparePriceValue;
                hiddenInputs[4].name = `variant_sku[${variantIndex}][${sizeId}]`;
                hiddenInputs[4].value = modalSku.value;
                hiddenInputs[5].name = `variant_barcode[${variantIndex}][${sizeId}]`;
                hiddenInputs[5].value = modalBarcode.value;

                const sizeDetails = sizeOption.querySelector('.size-details');
                if (sizeDetails) {
                    sizeDetails.querySelector('.price').textContent = formatPesosColombianos(priceValue);
                    sizeDetails.querySelector('.quantity').textContent = modalQuantity.value;
                }

                sizeOption.classList.add('selected');
                modal.style.display = 'none';

                updateCombinationDisplay(sizeOption.closest('.variant-card'));
            };

            modal.querySelector('.modal-overlay').addEventListener('click', () => {
                modal.style.display = 'none';
            });

            modalPrice.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\./g, '');
                if (!isNaN(value) || value === '') {
                    e.target.value = formatPesosColombianos(value);
                }
            });

            modalComparePrice.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\./g, '');
                if (!isNaN(value) || value === '') {
                    e.target.value = formatPesosColombianos(value);
                }
            });
        }

        function generarSKU(nombre, colorId, sizeId) {
            if (!nombre) return '';

            const iniciales = nombre.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') || 'PRO';

            let colorCode = 'GEN';
            if (colorId) {
                const colorSelect = document.querySelector(`.color-select[value="${colorId}"]`);
                if (colorSelect) {
                    const colorName = colorSelect.options[colorSelect.selectedIndex].text;
                    colorCode = colorName.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') || 'COL';
                }
            }

            let sizeCode = 'GEN';
            if (sizeId) {
                const sizeOption = document.querySelector(`.size-option[data-size-id="${sizeId}"] .size-label`);
                if (sizeOption) {
                    const sizeName = sizeOption.textContent.trim();
                    sizeCode = sizeName.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') || 'TAL';
                }
            }

            const randomPart = Math.random().toString(36).substring(2, 6).toUpperCase();
            return `${iniciales}-${colorCode}-${sizeCode}-${randomPart}`;
        }

        // Manejo de pestañas
        const tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');

                document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });

        let variantCounter = 1;

        document.getElementById('add-variant-btn').addEventListener('click', function() {
            variantCounter++;

            const newVariant = document.createElement('div');
            newVariant.className = 'variant-card';
            newVariant.setAttribute('data-variant-index', variantCounter - 1);

            newVariant.innerHTML = `
            <div class="variant-header">
                <h3><i class="fas fa-palette"></i> Variante #${variantCounter}</h3>
                <button type="button" class="btn btn-danger remove-variant">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>

            <div class="variant-body">
                <div class="variant-combination" id="combination-display-${variantCounter - 1}">
                    <i class="fas fa-info-circle"></i> Seleccione color y tallas
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Color *</label>
                        <select name="variant_color[]" class="color-select" required>
                            <option value="">Seleccione color</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?= $color['id'] ?>" data-hex="<?= $color['hex_code'] ?? '' ?>">
                                    <?= htmlspecialchars($color['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-ruler"></i> Tallas y Stock *</label>
                        <div class="sizes-grid" id="size-container-${variantCounter - 1}">
                            <?php foreach ($sizes as $size): ?>
                                <div class="size-option" data-size-id="<?= $size['id'] ?>">
                                    <input type="hidden" name="variant_size[<?= $size['id'] ?>][${variantCounter - 1}]" value="">
                                    <input type="hidden" name="variant_price[${variantCounter - 1}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_quantity[${variantCounter - 1}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_compare_price[${variantCounter - 1}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_sku[${variantCounter - 1}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_barcode[${variantCounter - 1}][<?= $size['id'] ?>]" value="">
                                    
                                    <div class="size-label"><?= htmlspecialchars($size['name']) ?></div>
                                    <div class="size-details" style="display: none;">
                                        <span class="price"></span>
                                        <span class="quantity"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>SKU Base</label>
                        <input type="text" name="variant_sku[${variantCounter - 1}]" class="sku-input" readonly>
                        <small class="sku-generate-text">Se generará automáticamente</small>
                    </div>

                    <div class="form-group">
                        <label>Código de barras base</label>
                        <input type="text" name="variant_barcode[${variantCounter - 1}]">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="radio" name="variant_is_default" value="${variantCounter - 1}" id="variant_default_${variantCounter - 1}">
                        <label for="variant_default_${variantCounter - 1}">Hacer variante principal</label>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-images"></i> Imágenes de la variante *</label>
                        <div class="image-uploader">
                            <div class="upload-area" id="upload-area-${variantCounter - 1}">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastra y suelta imágenes aquí o haz clic para seleccionar</p>
                                <div class="file-info"></div>
                                <input type="file" name="variant_images[${variantCounter - 1}][]" multiple accept="image/*" class="file-input" required>
                            </div>
                            <div class="preview-container" id="preview-container-${variantCounter - 1}"></div>
                        </div>
                        <small>Estas imágenes se asociarán a este color para todas las tallas</small>
                    </div>
                </div>
            </div>
        `;

            document.getElementById('variants-container').appendChild(newVariant);

            setupSkuGeneration(newVariant);
            setupCombinationDisplay(newVariant);
            setupSizeOptions(newVariant);
            initImageUploader(variantCounter - 1);

            const radioBtn = newVariant.querySelector('input[type="radio"]');
            radioBtn.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('input[name="variant_is_default"]').forEach(rb => {
                        if (rb !== this) rb.checked = false;
                    });
                }
            });

            newVariant.querySelector('.remove-variant').addEventListener('click', function() {
                if (confirm('¿Estás seguro de eliminar esta variante?')) {
                    const isDefault = newVariant.querySelector('input[type="radio"]:checked');

                    newVariant.remove();
                    const variants = document.querySelectorAll('.variant-card');
                    variants.forEach((variant, index) => {
                        variant.setAttribute('data-variant-index', index);
                        variant.querySelector('h3').textContent = `Variante #${index + 1}`;

                        const radio = variant.querySelector('input[type="radio"]');
                        if (radio) {
                            radio.value = index;
                            radio.id = `variant_default_${index}`;
                            const label = variant.querySelector('label[for^="variant_default_"]');
                            if (label) {
                                label.htmlFor = `variant_default_${index}`;
                            }

                            if (isDefault && index === 0) {
                                radio.checked = true;
                            }
                        }
                    });
                    variantCounter = variants.length;
                }
            });
        });

        function setupSizeOptions(variantElement) {
            const sizeOptions = variantElement.querySelectorAll('.size-option');
            const variantIndex = variantElement.getAttribute('data-variant-index');

            sizeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const sizeId = this.getAttribute('data-size-id');

                    if (this.classList.contains('selected')) {
                        return;
                    }

                    showSizeModal(this, variantIndex, sizeId);
                });
            });
        }

        function setupSkuGeneration(variantElement) {
            const productNameInput = document.getElementById('name');
            const skuInput = variantElement.querySelector('.sku-input');
            const colorSelect = variantElement.querySelector('.color-select');

            function updateSku() {
                const productName = productNameInput.value.trim();
                const color = colorSelect.value;

                if (productName && color) {
                    const productCode = productName.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '');

                    const colorOption = colorSelect.options[colorSelect.selectedIndex];
                    let colorCode = 'GEN';
                    if (colorOption && colorOption.text) {
                        colorCode = colorOption.text.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '');
                    }

                    const randomPart = Math.random().toString(36).substring(2, 6).toUpperCase();
                    const skuBase = `${productCode}-${colorCode}-${randomPart}`;
                    skuInput.value = skuBase;
                }
            }

            productNameInput.addEventListener('input', updateSku);
            colorSelect.addEventListener('change', updateSku);
            updateSku();
        }

        function setupCombinationDisplay(variantElement) {
            const colorSelect = variantElement.querySelector('.color-select');
            const displayElement = variantElement.querySelector('.variant-combination');
            const index = variantElement.getAttribute('data-variant-index');

            function updateCombination() {
                const colorOption = colorSelect.options[colorSelect.selectedIndex];
                const colorName = colorOption ? colorOption.text : '';
                const colorHex = colorOption ? colorOption.getAttribute('data-hex') : '';

                const selectedSizes = [];
                const sizeOptions = variantElement.querySelectorAll('.size-option.selected');
                sizeOptions.forEach(option => {
                    selectedSizes.push(option.querySelector('.size-label').textContent);
                });

                let combinationText = '';

                if (colorName) {
                    combinationText = `<span class="color-option"><span class="color-swatch" style="background-color: ${colorHex || '#ccc'}"></span>${colorName}</span>`;
                }

                if (selectedSizes.length > 0) {
                    combinationText += combinationText ? ' / ' + selectedSizes.join(', ') : selectedSizes.join(', ');
                }

                if (!combinationText) {
                    combinationText = '<i class="fas fa-info-circle"></i> Seleccione color y tallas';
                }

                displayElement.innerHTML = combinationText;
                updateColorSelects();
            }

            colorSelect.addEventListener('change', updateCombination);

            const observer = new MutationObserver(updateCombination);
            variantElement.querySelector('.sizes-grid').querySelectorAll('.size-option').forEach(option => {
                observer.observe(option, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            });

            updateCombination();
        }

        function updateColorSelects() {
            const colorSelects = document.querySelectorAll('.color-select');
            const selectedColors = [];

            colorSelects.forEach(select => {
                if (select.value) {
                    selectedColors.push(select.value);
                }
            });

            colorSelects.forEach(select => {
                const currentValue = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value && option.value !== currentValue && selectedColors.includes(option.value)) {
                        option.disabled = true;
                    } else if (option.value) {
                        option.disabled = false;
                    }
                });
            });
        }

        const firstVariant = document.querySelector('.variant-card');
        if (firstVariant) {
            setupSkuGeneration(firstVariant);
            setupCombinationDisplay(firstVariant);
            setupSizeOptions(firstVariant);

            const radioBtn = firstVariant.querySelector('input[type="radio"]');
            if (radioBtn) {
                radioBtn.addEventListener('change', function() {
                    if (this.checked) {
                        document.querySelectorAll('input[name="variant_is_default"]').forEach(rb => {
                            if (rb !== this) rb.checked = false;
                        });
                    }
                });
            }
        }

        function initImageUploader(index, isMain = false) {
            const uploadArea = isMain ?
                document.getElementById('main-upload-area') :
                document.getElementById(`upload-area-${index}`);

            const fileInput = isMain ?
                document.getElementById('main_image') :
                uploadArea.querySelector('.file-input');

            const previewContainer = isMain ?
                document.getElementById('main-preview-container') :
                document.getElementById(`preview-container-${index}`);

            const fileInfo = uploadArea.querySelector('.file-info');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                uploadArea.classList.add('highlight');
            }

            function unhighlight() {
                uploadArea.classList.remove('highlight');
            }

            uploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files, fileInput, previewContainer, fileInfo);
            }

            fileInput.addEventListener('change', function() {
                handleFiles(this.files, fileInput, previewContainer, fileInfo);
            });

            uploadArea.addEventListener('click', function(e) {
                if (!e.target.closest('.remove-image') && e.target !== fileInput) {
                    fileInput.click();
                }
            });

            if (fileInput.files && fileInput.files.length > 0) {
                updateFileInfo(fileInput, fileInfo);
                updatePreviews(fileInput, previewContainer);
            }
        }

        function handleFiles(files, fileInput, previewContainer, fileInfo) {
            if (!files || files.length === 0) return;

            const dataTransfer = new DataTransfer();

            if (fileInput.files) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    dataTransfer.items.add(fileInput.files[i]);
                }
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!file.type.match('image.*')) continue;

                let fileExists = false;
                if (fileInput.files) {
                    for (let j = 0; j < fileInput.files.length; j++) {
                        if (fileInput.files[j].name === file.name &&
                            fileInput.files[j].size === file.size &&
                            fileInput.files[j].lastModified === file.lastModified) {
                            fileExists = true;
                            break;
                        }
                    }
                }

                if (!fileExists) {
                    dataTransfer.items.add(file);
                }
            }

            fileInput.files = dataTransfer.files;
            updateFileInfo(fileInput, fileInfo);
            updatePreviews(fileInput, previewContainer);
        }

        function updateFileInfo(fileInput, fileInfo) {
            if (!fileInfo) return;

            if (fileInput.files.length === 0) {
                fileInfo.textContent = '';
                return;
            }

            if (fileInput.files.length === 1) {
                fileInfo.innerHTML = `1 archivo seleccionado: <strong>${fileInput.files[0].name}</strong>`;
            } else {
                fileInfo.innerHTML = `< ${fileInput.files.length} archivos seleccionados`;
            }
        }

        function updatePreviews(fileInput, previewContainer) {
            if (!previewContainer) return;

            previewContainer.innerHTML = '';

            for (let i = 0; i < fileInput.files.length; i++) {
                const file = fileInput.files[i];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `
                    <img src="${e.target.result}" alt="Previsualización">
                    <button type="button" class="remove-image" data-index="${i}">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="image-name">${file.name}</div>
                `;
                    previewContainer.appendChild(preview);

                    preview.querySelector('.remove-image').addEventListener('click', function(e) {
                        e.stopPropagation();
                        removeFileFromInput(fileInput, parseInt(this.getAttribute('data-index')), previewContainer);
                    });
                };

                reader.readAsDataURL(file);
            }
        }

        function removeFileFromInput(fileInput, index, previewContainer) {
            const dataTransfer = new DataTransfer();

            for (let i = 0; i < fileInput.files.length; i++) {
                if (i !== index) {
                    dataTransfer.items.add(fileInput.files[i]);
                }
            }

            fileInput.files = dataTransfer.files;
            updatePreviews(fileInput, previewContainer);

            const fileInfo = fileInput.closest('.upload-area').querySelector('.file-info');
            if (fileInfo) {
                updateFileInfo(fileInput, fileInfo);
            }
        }

        initImageUploader(0);
        initImageUploader(null, true);

        document.getElementById('product-form').addEventListener('submit', function(e) {
            document.querySelectorAll('.price-input').forEach(input => {
                input.value = parsePesosColombianos(input.value);
            });

            let isValid = true;
            const errorMessages = [];

            if (!document.getElementById('name').value.trim()) {
                isValid = false;
                errorMessages.push('El nombre del producto es requerido');
                document.getElementById('name').closest('.form-group').classList.add('has-error');
            }

            if (!document.getElementById('category_id').value) {
                isValid = false;
                errorMessages.push('La categoría es requerida');
                document.getElementById('category_id').closest('.form-group').classList.add('has-error');
            }

            if (!document.getElementById('price').value) {
                isValid = false;
                errorMessages.push('El precio base es requerido');
                document.getElementById('price').closest('.form-group').classList.add('has-error');
            }

            const variantCards = document.querySelectorAll('.variant-card');
            if (variantCards.length === 0) {
                isValid = false;
                errorMessages.push('Debe agregar al menos una variante');
            } else {
                variantCards.forEach(card => {
                    const colorSelect = card.querySelector('select[name="variant_color[]"]');
                    const variantIndex = card.getAttribute('data-variant-index');
                    const sizeOptions = card.querySelectorAll('.size-option.selected');
                    const imageInput = card.querySelector(`input[name="variant_images[${variantIndex}][]"]`);

                    if (!colorSelect.value) {
                        isValid = false;
                        errorMessages.push('Debes seleccionar un color para cada variante');
                        colorSelect.closest('.form-group').classList.add('has-error');
                    }

                    if (sizeOptions.length === 0) {
                        isValid = false;
                        errorMessages.push('Debes seleccionar al menos una talla para cada variante');
                        card.querySelector('.sizes-grid').closest('.form-group').classList.add('has-error');
                    }

                    if (!imageInput || imageInput.files.length === 0) {
                        isValid = false;
                        errorMessages.push('Debes subir al menos una imagen para cada variante');
                        imageInput.closest('.form-group').classList.add('has-error');
                    }

                    sizeOptions.forEach(option => {
                        const sizeId = option.getAttribute('data-size-id');
                        const priceInput = card.querySelector(`input[name="variant_price[${variantIndex}][${sizeId}]"]`);
                        const quantityInput = card.querySelector(`input[name="variant_quantity[${variantIndex}][${sizeId}]"]`);

                        if (!priceInput || !priceInput.value) {
                            isValid = false;
                            errorMessages.push(`Debes especificar un precio para la talla ${option.querySelector('.size-label').textContent}`);
                            option.style.border = '2px solid var(--error-color)';
                        }

                        if (!quantityInput || !quantityInput.value) {
                            isValid = false;
                            errorMessages.push(`Debes especificar una cantidad para la talla ${option.querySelector('.size-label').textContent}`);
                            option.style.border = '2px solid var(--error-color)';
                        }
                    });
                });

                const colors = new Set();
                const colorSelects = document.querySelectorAll('select[name="variant_color[]"]');
                colorSelects.forEach(select => {
                    if (colors.has(select.value)) {
                        isValid = false;
                        errorMessages.push('No puede haber dos variantes con el mismo color');
                        select.closest('.form-group').classList.add('has-error');
                    }
                    if (select.value) colors.add(select.value);
                });

                const defaultVariants = document.querySelectorAll('input[name="variant_is_default"]:checked');
                if (defaultVariants.length !== 1) {
                    isValid = false;
                    errorMessages.push('Debes seleccionar exactamente una variante como principal');
                }
            }

            if (!isValid) {
                e.preventDefault();

                const uniqueErrors = [...new Set(errorMessages)];
                showAlert(uniqueErrors.join('<br>'), 'error');

                document.querySelector('.tab-btn[data-tab="variants"]').click();

                const firstError = document.querySelector('.has-error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });

        document.querySelectorAll('input[required], select[required]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value) {
                    this.closest('.form-group').classList.remove('has-error');
                }
            });
        });

        function enhanceImagePreview() {
            document.querySelectorAll('.image-preview').forEach(preview => {
                preview.addEventListener('mouseenter', function() {
                    const removeBtn = this.querySelector('.remove-image');
                    if (removeBtn) removeBtn.style.opacity = '1';
                });
                preview.addEventListener('mouseleave', function() {
                    const removeBtn = this.querySelector('.remove-image');
                    if (removeBtn) removeBtn.style.opacity = '0.5';
                });
            });
        }

        setInterval(enhanceImagePreview, 1000);
    });
</script>