<?php
$defaultImages = !empty($variantsData['variantsByColor'][$variantsData['defaultColorId']]['images']) ? 
    $variantsData['variantsByColor'][$variantsData['defaultColorId']]['images'] : 
    [['image_path' => $product['primary_image'], 'alt_text' => $product['name'] . ' - Imagen principal']];
?>

<div class="product-gallery">
    <div class="gallery-main">
        <?php foreach ($defaultImages as $index => $image): ?>
            <div class="main-image <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                <button class="zoom-btn" aria-label="Ampliar imagen">
                    <i class="fas fa-search-plus"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="thumbnails-container">
        <button class="thumb-nav prev hidden" aria-label="Miniaturas anteriores">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="thumbnails-track">
            <?php foreach ($defaultImages as $index => $image): ?>
                <div class="thumb-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="thumb-nav next" aria-label="Siguientes miniaturas">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    
    <?php if (!empty($additionalImages)): ?>
    <div class="gallery-additional">
        <h4>Más vistas del producto</h4>
        <div class="additional-images">
            <?php foreach ($additionalImages as $image): ?>
                <div class="additional-item">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>