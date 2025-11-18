<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Incluir el modal al DOM
        const sizeModalWrapper = document.createElement('div');
        sizeModalWrapper.innerHTML = `<?php include __DIR__ . '/../../../admin/modals/sub/size_modal.php'; ?>`;
        document.body.appendChild(sizeModalWrapper);

        const sizeModal = document.getElementById('size-modal');
        const modalPrice = document.getElementById('modal-price');
        const modalQuantity = document.getElementById('modal-quantity');
        const modalComparePrice = document.getElementById('modal-compare-price');
        const modalSku = document.getElementById('modal-sku');
        const modalBarcode = document.getElementById('modal-barcode');
        const modalSaveButton = document.querySelector('#size-modal .save-size');
        const modalCloseTriggers = document.querySelectorAll('#size-modal [data-size-modal-close]');

        let activeSizeOption = null;
        let activeVariantIndex = null;
        let activeSizeId = null;

        function normalizeNumericString(value) {
            if (value === null || value === undefined) return '';
            let str = String(value).trim();
            if (!str) return '';

            str = str.replace(/\s+/g, '');
            const hasComma = str.includes(',');
            const hasDot = str.includes('.');
            const lastComma = str.lastIndexOf(',');
            const lastDot = str.lastIndexOf('.');

            if (hasComma && hasDot) {
                if (lastComma > lastDot) {
                    str = str.replace(/\./g, '').replace(',', '.');
                } else {
                    str = str.replace(/,/g, '');
                }
            } else if (hasComma) {
                const decimal = str.substring(lastComma + 1);
                if (decimal.length > 0 && decimal.length <= 2) {
                    const intPart = str.substring(0, lastComma).replace(/[.,]/g, '');
                    str = `${intPart || '0'}.${decimal}`;
                } else {
                    str = str.replace(/,/g, '');
                }
            } else if (hasDot) {
                const decimal = str.substring(lastDot + 1);
                if (decimal.length > 0 && decimal.length <= 2) {
                    const intPart = str.substring(0, lastDot).replace(/\./g, '');
                    str = `${intPart || '0'}.${decimal}`;
                } else {
                    str = str.replace(/\./g, '');
                }
            }

            str = str.replace(/[^0-9.\-]/g, '');
            return str;
        }

        function parsePesosColombianos(formattedNumber) {
            if (formattedNumber === null || formattedNumber === undefined || formattedNumber === '') return 0;
            const normalized = normalizeNumericString(formattedNumber);
            const num = parseFloat(normalized);
            return Number.isFinite(num) ? num : 0;
        }

        function formatPesosColombianos(number) {
            if (number === null || number === undefined || number === '') return '0';
            const numericValue = typeof number === 'number' ? number : parsePesosColombianos(number);
            if (!Number.isFinite(numericValue)) return '0';
            return new Intl.NumberFormat('es-CO', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(numericValue);
        }

        function initializePriceInputs() {
            document.querySelectorAll('.price-input').forEach(input => {
                if (input.value && input.value.trim() !== '') {
                    input.value = formatPesosColombianos(input.value);
                }

                input.addEventListener('input', function(e) {
                    const rawValue = e.target.value;
                    if (rawValue === '') {
                        return;
                    }

                    const normalized = normalizeNumericString(rawValue);
                    if (!normalized) {
                        e.target.value = '';
                        return;
                    }

                    const numericValue = parseFloat(normalized);
                    if (!Number.isFinite(numericValue)) {
                        e.target.value = '';
                        return;
                    }

                    e.target.value = formatPesosColombianos(numericValue);
                });
            });
        }

        initializePriceInputs();

        function closeSizeModal() {
            if (!sizeModal) return;
            sizeModal.classList.remove('open');
            sizeModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            activeSizeOption = null;
            activeVariantIndex = null;
            activeSizeId = null;
        }

        if (modalCloseTriggers.length) {
            modalCloseTriggers.forEach(trigger => {
                trigger.addEventListener('click', closeSizeModal);
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sizeModal && sizeModal.classList.contains('open')) {
                closeSizeModal();
            }
        });

        function openSizeModal(sizeOption, variantIndex, sizeId) {
            if (!sizeModal || !sizeOption) return;

            activeSizeOption = sizeOption;
            activeVariantIndex = variantIndex;
            activeSizeId = sizeId;

            const basePriceInput = document.getElementById('price');
            const basePrice = basePriceInput && basePriceInput.value ? parsePesosColombianos(basePriceInput.value) : 0;

            const hiddenInputs = sizeOption.querySelectorAll('input[type="hidden"]');
            const isSelected = sizeOption.classList.contains('selected') && hiddenInputs.length >= 6;

            if (isSelected) {
                modalPrice.value = hiddenInputs[1].value ? formatPesosColombianos(hiddenInputs[1].value) : formatPesosColombianos(basePrice);
                modalQuantity.value = hiddenInputs[2].value || '';
                modalComparePrice.value = hiddenInputs[3].value ? formatPesosColombianos(hiddenInputs[3].value) : '';
                modalSku.value = hiddenInputs[4].value || '';
                modalBarcode.value = hiddenInputs[5].value || '';
            } else {
                modalPrice.value = basePrice ? formatPesosColombianos(basePrice) : '';
                modalQuantity.value = '';
                modalComparePrice.value = '';
                modalBarcode.value = '';

                const nameInput = document.getElementById('name');
                const productName = nameInput ? nameInput.value : '';
                const variantCard = sizeOption.closest('.variant-card');
                const colorSelect = variantCard ? variantCard.querySelector('.color-select') : null;
                const colorId = colorSelect ? colorSelect.value : null;

                modalSku.value = generarSKU(productName, colorId, sizeId);
            }

            sizeModal.classList.add('open');
            sizeModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            modalPrice?.focus();
        }

        function handleSaveSize() {
            if (!activeSizeOption || activeVariantIndex === null || !activeSizeId) {
                closeSizeModal();
                return;
            }

            if (!modalPrice.value || !modalQuantity.value) {
                showAlert('Debes ingresar precio y cantidad', 'error');
                return;
            }

            const priceValue = parsePesosColombianos(modalPrice.value);
            const comparePriceValue = modalComparePrice.value ? parsePesosColombianos(modalComparePrice.value) : null;

            const hiddenInputs = activeSizeOption.querySelectorAll('input[type="hidden"]');
            if (hiddenInputs.length < 6) {
                closeSizeModal();
                return;
            }

            hiddenInputs[0].name = `variant_size[${activeSizeId}][${activeVariantIndex}]`;
            hiddenInputs[0].value = activeSizeId;
            hiddenInputs[1].name = `variant_price[${activeVariantIndex}][${activeSizeId}]`;
            hiddenInputs[1].value = priceValue;
            hiddenInputs[2].name = `variant_quantity[${activeVariantIndex}][${activeSizeId}]`;
            hiddenInputs[2].value = modalQuantity.value;
            hiddenInputs[3].name = `variant_compare_price[${activeVariantIndex}][${activeSizeId}]`;
            hiddenInputs[3].value = comparePriceValue ?? '';
            hiddenInputs[4].name = `variant_sku[${activeVariantIndex}][${activeSizeId}]`;
            hiddenInputs[4].value = modalSku.value;
            hiddenInputs[5].name = `variant_barcode[${activeVariantIndex}][${activeSizeId}]`;
            hiddenInputs[5].value = modalBarcode.value;

            activeSizeOption.classList.add('selected');
            updateCombinationDisplay(activeSizeOption.closest('.variant-card'));

            closeSizeModal();
        }

        if (modalSaveButton) {
            modalSaveButton.addEventListener('click', handleSaveSize);
        }

        function attachCurrencyFormatter(input) {
            if (!input) return;
            input.addEventListener('input', function(e) {
                const rawValue = e.target.value;
                if (rawValue === '') {
                    return;
                }

                const normalized = normalizeNumericString(rawValue);
                if (!normalized) {
                    e.target.value = '';
                    return;
                }

                const numericValue = parseFloat(normalized);
                if (!Number.isFinite(numericValue)) {
                    e.target.value = '';
                    return;
                }

                e.target.value = formatPesosColombianos(numericValue);
            });
        }

        attachCurrencyFormatter(modalPrice);
        attachCurrencyFormatter(modalComparePrice);

        const variantsContainer = document.getElementById('variants-container');
        if (variantsContainer) {
            variantsContainer.addEventListener('click', function(event) {
                const sizeOption = event.target.closest('.size-option');
                if (!sizeOption || !variantsContainer.contains(sizeOption)) {
                    return;
                }

                const variantCard = sizeOption.closest('.variant-card');
                if (!variantCard) {
                    return;
                }

                const variantIndex = variantCard.getAttribute('data-variant-index');
                const sizeId = sizeOption.getAttribute('data-size-id');
                if (!sizeId) {
                    return;
                }

                event.preventDefault();
                openSizeModal(sizeOption, variantIndex, sizeId);
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


        document.getElementById('add-variant-btn').addEventListener('click', function() {
            const newIndex = document.querySelectorAll('.variant-card').length;

            const newVariant = document.createElement('div');
            newVariant.className = 'variant-card';
            newVariant.setAttribute('data-variant-index', newIndex);

            newVariant.innerHTML = `
            <div class="variant-header">
                <h3><i class="fas fa-palette"></i> Variante #${newIndex + 1}</h3>
                <button type="button" class="btn btn-danger remove-variant">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>

            <div class="variant-body">
                <div class="variant-combination" id="combination-display-${newIndex}">
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
                        <div class="sizes-grid" id="size-container-${newIndex}">
                            <?php foreach ($sizes as $size): ?>
                                <div class="size-option" data-size-id="<?= $size['id'] ?>">
                                    <input type="hidden" name="variant_size[<?= $size['id'] ?>][${newIndex}]" value="">
                                    <input type="hidden" name="variant_price[${newIndex}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_quantity[${newIndex}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_compare_price[${newIndex}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_sku[${newIndex}][<?= $size['id'] ?>]" value="">
                                    <input type="hidden" name="variant_barcode[${newIndex}][<?= $size['id'] ?>]" value="">
                                    
                                    <div class="size-label"><?= htmlspecialchars($size['name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>SKU Base</label>
                        <input type="text" name="variant_sku[${newIndex}]" class="sku-input" readonly>
                        <small class="sku-generate-text">Se generará automáticamente</small>
                    </div>

                    <div class="form-group">
                        <label>Código de barras base</label>
                        <input type="text" name="variant_barcode[${newIndex}]">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="radio" name="variant_is_default" value="${newIndex}" id="variant_default_${newIndex}">
                        <label for="variant_default_${newIndex}">Hacer variante principal</label>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-images"></i> Imágenes de la variante *</label>
                        <div class="image-uploader">
                            <div class="upload-area" id="upload-area-${newIndex}">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastra y suelta imágenes aquí o haz clic para seleccionar</p>
                                <div class="file-info"></div>
                                <input type="file" name="variant_images[${newIndex}][]" multiple accept="image/*" class="file-input" required>
                            </div>
                            <div class="preview-container" id="preview-container-${newIndex}"></div>
                        </div>
                        <small>Estas imágenes se asociarán a este color para todas las tallas</small>
                    </div>
                </div>
            </div>
        `;

            document.getElementById('variants-container').appendChild(newVariant);

            attachVariantEvents(newVariant);
            initImageUploader(newIndex);
            reindexVariants();
        });

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

        function updateCombinationDisplay(variantElement) {
            if (!variantElement) return;

            const colorSelect = variantElement.querySelector('.color-select');
            const displayElement = variantElement.querySelector('.variant-combination');

            const colorOption = colorSelect ? colorSelect.options[colorSelect.selectedIndex] : null;
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

            if (displayElement) displayElement.innerHTML = combinationText;
            updateColorSelects();
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

        function updateRemoveButtonsVisibility() {
            const variants = document.querySelectorAll('.variant-card');
            const showButtons = variants.length > 1;

            variants.forEach(variant => {
                const btn = variant.querySelector('.remove-variant');
                if (btn) {
                    btn.style.display = showButtons ? 'block' : 'none';
                }
            });
        }

        function reindexVariants() {
            const variants = document.querySelectorAll('.variant-card');

            variants.forEach((variant, index) => {
                variant.setAttribute('data-variant-index', index);

                const header = variant.querySelector('.variant-header h3');
                if (header) {
                    header.innerHTML = `<i class="fas fa-palette"></i> Variante #${index + 1}`;
                }

                const combination = variant.querySelector('.variant-combination');
                if (combination) {
                    combination.id = `combination-display-${index}`;
                }

                const sizesGrid = variant.querySelector('.sizes-grid');
                if (sizesGrid) {
                    sizesGrid.id = `size-container-${index}`;
                }

                variant.querySelectorAll('.size-option').forEach(option => {
                    const sizeId = option.getAttribute('data-size-id');
                    if (!sizeId) return;

                    const hiddenInputs = option.querySelectorAll('input[type="hidden"]');
                    if (hiddenInputs.length >= 6) {
                        hiddenInputs[0].name = `variant_size[${sizeId}][${index}]`;
                        hiddenInputs[1].name = `variant_price[${index}][${sizeId}]`;
                        hiddenInputs[2].name = `variant_quantity[${index}][${sizeId}]`;
                        hiddenInputs[3].name = `variant_compare_price[${index}][${sizeId}]`;
                        hiddenInputs[4].name = `variant_sku[${index}][${sizeId}]`;
                        hiddenInputs[5].name = `variant_barcode[${index}][${sizeId}]`;
                    }
                });

                const skuInput = variant.querySelector('.sku-input');
                if (skuInput) {
                    skuInput.name = `variant_sku[${index}]`;
                }

                const barcodeInput = variant.querySelector('input[type="text"][name^="variant_barcode"]');
                if (barcodeInput) {
                    barcodeInput.name = `variant_barcode[${index}]`;
                }

                const radio = variant.querySelector('input[name="variant_is_default"]');
                if (radio) {
                    radio.value = index;
                    radio.id = `variant_default_${index}`;
                    const radioContainer = radio.closest('.checkbox-group');
                    if (radioContainer) {
                        const label = radioContainer.querySelector('label');
                        if (label) {
                            label.setAttribute('for', radio.id);
                        }
                    }
                }

                const uploadArea = variant.querySelector('.upload-area');
                if (uploadArea) {
                    uploadArea.id = `upload-area-${index}`;
                    const fileInput = uploadArea.querySelector('.file-input');
                    if (fileInput) {
                        fileInput.name = `variant_images[${index}][]`;
                    }
                }

                const previewContainer = variant.querySelector('.preview-container');
                if (previewContainer) {
                    previewContainer.id = `preview-container-${index}`;
                }
            });

            updateRemoveButtonsVisibility();
            updateColorSelects();
        }

        function setupVariantRemoval(variantElement) {
            const removeBtn = variantElement.querySelector('.remove-variant');
            if (!removeBtn) return;

            removeBtn.addEventListener('click', function() {
                const totalVariants = document.querySelectorAll('.variant-card').length;
                if (totalVariants <= 1) {
                    showAlert('Debe existir al menos una variante', 'error');
                    return;
                }

                const removeVariant = () => {
                    const radio = variantElement.querySelector('input[name="variant_is_default"]');
                    const wasDefault = radio && radio.checked;

                    variantElement.remove();
                    reindexVariants();

                    if (wasDefault) {
                        const firstRadio = document.querySelector('.variant-card input[name="variant_is_default"]');
                        if (firstRadio) {
                            firstRadio.checked = true;
                        }
                    }
                };

                if (typeof window.openConfirmationModal === 'function') {
                    window.openConfirmationModal({
                        message: 'Esta variante y sus tallas asociadas se eliminarán definitivamente.',
                        title: 'Eliminar variante',
                        confirmText: 'Eliminar',
                        cancelText: 'Cancelar',
                        type: 'warning',
                        onConfirm: removeVariant
                    });
                    return;
                }

                if (confirm('¿Estás seguro de eliminar esta variante?')) {
                    removeVariant();
                }
            });
        }

        function setupDefaultVariantRadio(variantElement) {
            const radioBtn = variantElement.querySelector('input[name="variant_is_default"]');
            if (!radioBtn) return;

            radioBtn.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('input[name="variant_is_default"]').forEach(rb => {
                        if (rb !== this) rb.checked = false;
                    });
                }
            });
        }

        function attachVariantEvents(variantElement) {
            setupSkuGeneration(variantElement);
            setupCombinationDisplay(variantElement);
            setupVariantRemoval(variantElement);
            setupDefaultVariantRadio(variantElement);
        }

        document.querySelectorAll('.variant-card').forEach((variant, index) => {
            variant.setAttribute('data-variant-index', index);
            attachVariantEvents(variant);
            initImageUploader(index);
        });
        updateRemoveButtonsVisibility();

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

        initImageUploader(null, true);

        // Handler for deleting existing images
        document.querySelectorAll('.existing-image .remove-image').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const preview = this.closest('.image-preview');
                const imageId = preview.getAttribute('data-image-id');
                const isMain = preview.closest('#main-preview-container') !== null;
                const inputName = isMain ? 'delete_main_images[]' : 'delete_variant_images[]';

                let deleteInput = document.querySelector(`input[name="${inputName}"][value="${imageId}"]`);
                if (!deleteInput) {
                    deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = inputName;
                    deleteInput.value = imageId;
                    document.getElementById('product-form').appendChild(deleteInput);
                }
                preview.remove();
            });
        });

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

                    // Allow existing images (already uploaded) — only require upload when there are
                    // no existing images and no new files selected for this variant
                    const hasExistingVariantImage = card.querySelector('.existing-image') !== null;
                    if (!imageInput || (imageInput.files.length === 0 && !hasExistingVariantImage)) {
                        isValid = false;
                        errorMessages.push('Debes subir al menos una imagen para cada variante');
                        const grp = imageInput ? imageInput.closest('.form-group') : card.querySelector('.features');
                        if (grp) grp.classList.add('has-error');
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