<div class="product-info">
    <div class="product-header">
        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
        <?php if (!empty($product['collection_name'])): ?>
            <div class="product-collection">
                <span>Colección:</span>
                <a href="<?= BASE_URL ?>/tienda/coleccion/<?= $product['collection_slug'] ?>"><?= $product['collection_name'] ?></a>
            </div>
        <?php endif; ?>
        
        <div class="product-rating">
            <div class="stars">
                <?php
                $avgRating = $reviewsData['stats']['average_rating'] ? round($reviewsData['stats']['average_rating']) : 0;
                for ($i = 1; $i <= 5; $i++):
                    $class = $i <= $avgRating ? 'fas fa-star' : 'far fa-star';
                ?>
                    <i class="<?= $class ?>"></i>
                <?php endfor; ?>
            </div>
            <?php if ($reviewsData['stats']['total_reviews'] > 0): ?>
                <a href="#reviews" class="review-count">
                    <?= $reviewsData['stats']['total_reviews'] ?> opiniones
                </a>
            <?php else: ?>
                <span class="review-count">Sin opiniones</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="product-pricing">
        <?php if (!empty($variantsData['defaultVariant'])): ?>
            <div class="current-price">$<?= number_format($variantsData['defaultVariant']['price'], 0, ',', '.') ?></div>
            <?php if ($product['compare_price'] && $product['compare_price'] > $variantsData['defaultVariant']['price']): ?>
                <div class="original-price">$<?= number_format($product['compare_price'], 0, ',', '.') ?></div>
                <div class="discount-badge">
                    <?= round(100 - ($variantsData['defaultVariant']['price'] / $product['compare_price'] * 100)) ?>% OFF
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="current-price">Precio no disponible</div>
        <?php endif; ?>
    </div>
    
    <div class="product-description">
        <h3>Descripción</h3>
        <p><?= nl2br(htmlspecialchars($product['description'] ?? 'Descripción no disponible')) ?></p>
        
        <?php if (!empty($product['material'])): ?>
            <div class="product-spec">
                <span>Material:</span>
                <span><?= htmlspecialchars($product['material']) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($product['care_instructions'])): ?>
            <div class="product-spec">
                <span>Cuidados:</span>
                <span><?= htmlspecialchars($product['care_instructions']) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($product['gender']) && $product['gender'] !== 'unisex'): ?>
            <div class="product-spec">
                <span>Género:</span>
                <span><?= 
                    $product['gender'] === 'niño' ? 'Niño' : 
                    ($product['gender'] === 'niña' ? 'Niña' : 'Bebé')
                ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Selector de variantes -->
    <div class="product-variants">
        <?php if (!empty($variantsData['variantsByColor'])): ?>
            <div class="variant-selector color-selector">
                <h4>Color: <span id="selected-color-name"><?= $variantsData['variantsByColor'][$variantsData['defaultColorId']]['color_name'] ?? 'No disponible' ?></span></h4>
                <div class="color-options">
                    <?php foreach ($variantsData['variantsByColor'] as $colorId => $colorData): ?>
                        <div class="color-option <?= $colorId == $variantsData['defaultColorId'] ? 'selected' : '' ?>" 
                             data-color-id="<?= $colorId ?>"
                             data-color-name="<?= htmlspecialchars($colorData['color_name']) ?>"
                             title="<?= htmlspecialchars($colorData['color_name']) ?>">
                            <?php if ($colorData['color_hex']): ?>
                                <span class="color-swatch" style="background-color: <?= $colorData['color_hex'] ?>"></span>
                            <?php else: ?>
                                <span class="color-swatch"><?= substr($colorData['color_name'], 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="variant-selector size-selector">
                <h4>Talla: <span id="selected-size-name"><?= $variantsData['defaultVariant']['size_name'] ?? 'No disponible' ?></span></h4>
                <div class="size-options" id="size-options">
                    <?php if (!empty($variantsData['variantsByColor'][$variantsData['defaultColorId']]['sizes'])): ?>
                        <?php foreach ($variantsData['variantsByColor'][$variantsData['defaultColorId']]['sizes'] as $sizeId => $sizeData): ?>
                            <div class="size-option <?= $sizeId == $variantsData['defaultSizeId'] ? 'selected' : '' ?>" 
                                 data-size-id="<?= $sizeId ?>"
                                 data-size-name="<?= htmlspecialchars($sizeData['size_name']) ?>"
                                 data-variant-id="<?= $sizeData['variant_id'] ?>">
                                <?= htmlspecialchars($sizeData['size_name']) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-sizes">No hay tallas disponibles</div>
                    <?php endif; ?>
                </div>
                <a href="#size-guide" class="size-guide-link">Guía de tallas</a>
            </div>
            
          <div class="variant-info">
        <div class="stock-info">
            <?php if (!empty($variantsData['defaultVariant'])): ?>
                <?php if ($variantsData['defaultVariant']['quantity'] > 5): ?>
                    <i class="fas fa-check-circle in-stock"></i> 
                    <span class="stock-quantity">Disponible (<?= $variantsData['defaultVariant']['quantity'] ?> unidades)</span>
                <?php elseif ($variantsData['defaultVariant']['quantity'] > 0): ?>
                    <i class="fas fa-exclamation-circle low-stock"></i> 
                    <span class="stock-quantity">Últimas <?= $variantsData['defaultVariant']['quantity'] ?> unidades</span>
                <?php else: ?>
                    <i class="fas fa-times-circle out-of-stock"></i> 
                    <span class="stock-quantity">Agotado</span>
                <?php endif; ?>
            <?php else: ?>
                <i class="fas fa-times-circle out-of-stock"></i> 
                <span class="stock-quantity">No disponible</span>
            <?php endif; ?>
        </div>
    </div>


        <?php else: ?>
            <div class="no-variants">
                <i class="fas fa-exclamation-triangle"></i> Este producto no tiene variantes disponibles
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Acciones del producto -->
    <div class="product-actions">
        <?php if (!empty($variantsData['defaultVariant'])): ?>
            <div class="quantity-selector">
                <button class="qty-btn minus" aria-label="Reducir cantidad">-</button>
                <input type="number" id="product-quantity" min="1" max="<?= min($variantsData['defaultVariant']['quantity'], 10) ?>" value="1">
                <button class="qty-btn plus" aria-label="Aumentar cantidad">+</button>
            </div>
            
            <div class="action-buttons">
                <button id="add-to-cart" class="btn-primary" 
                        data-variant-id="<?= $variantsData['defaultVariant']['variant_id'] ?>"
                        <?= $variantsData['defaultVariant']['quantity'] <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shopping-cart"></i> Añadir al carrito
                </button>
                
                <button id="buy-now" class="btn-secondary"
                        data-variant-id="<?= $variantsData['defaultVariant']['variant_id'] ?>"
                        <?= $variantsData['defaultVariant']['quantity'] <= 0 ? 'disabled' : '' ?>>
                    Comprar ahora
                </button>
                
                <button id="add-to-wishlist" class="wishlist-btn" aria-label="Añadir a favoritos">
                    <i class="far fa-heart"></i>
                </button>
            </div>
            
            <?php if ($variantsData['defaultVariant']['quantity'] <= 0): ?>
                <div class="out-of-stock-alert">
                    <i class="fas fa-bell"></i> ¿Quieres que te avisemos cuando esté disponible?
                    <button id="notify-me" class="btn-link">Avísame</button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-actions">
                <button class="btn-primary" disabled>
                    <i class="fas fa-shopping-cart"></i> Producto no disponible
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Envío y devoluciones -->
    <div class="shipping-info">

        <div class="info-item">
            <i class="fas fa-undo"></i>
            <div>
                <span>Devoluciones gratuitas</span>
                <a href="#returns-info" class="info-link">Conoce nuestra política</a>
            </div>
        </div>
    </div>
</div>