<?php if (!empty($relatedProducts)): ?>
<section class="related-products-section">
    <h2>Productos relacionados</h2>
    <div class="related-products-grid">
        <?php foreach ($relatedProducts as $related): 
            $displayPrice = $related['display_price'] ?? ($related['price'] ?? 0);
            $comparePrice = $related['compare_price'] ?? null;
            $hasDiscount = !empty($related['has_discount']) && $comparePrice !== null;
            $discountPercentage = $hasDiscount ? ($related['discount_percentage'] ?? 0) : 0;
        ?>
            <div class="product-card">
                <?php if (!empty($related['is_featured'])): ?>
                    <div class="product-badge">Destacado</div>
                <?php endif; ?>
                <!-- Mover badge de venta dentro del área de imagen para que no tape el badge 'Destacado' -->
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="<?= BASE_URL ?>/producto/<?= $related['slug'] ?>" class="product-image">
                    <?php if (!empty($related['image_path'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($related['image_path']) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
                    <?php else: ?>
                        <img src="<?= BASE_URL ?>/images/default-product.jpg" alt="<?= htmlspecialchars($related['name']) ?>">
                    <?php endif; ?>
                    <?php if ($hasDiscount && $discountPercentage > 0): ?>
                        <div class="product-badge sale"><?= $discountPercentage ?>% OFF</div>
                    <?php endif; ?>
                </a>
                    <div class="product-info">
                    <span class="product-category"><?= htmlspecialchars($related['category_name'] ?? $product['category_name']) ?></span>
                    <h3 class="product-title">
                        <a href="<?= BASE_URL ?>/producto/<?= $related['slug'] ?>"><?= htmlspecialchars($related['name']) ?></a>
                    </h3>
                    <!-- Valoración -->
                    <div class="product-rating">
                        <?php
                            $avgRating = isset($related['avg_rating']) ? round($related['avg_rating'], 1) : 0;
                            $reviewCount = isset($related['review_count']) ? $related['review_count'] : 0;

                            $fullStars = floor($avgRating);
                            $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                            $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        ?>
                        <div class="stars">
                            <?php
                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            if ($hasHalfStar) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            for ($i = 0; $i < $emptyStars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <span class="rating-count"><?php echo "($reviewCount)"; ?></span>
                    </div>

                    <div class="product-price">
                        <span class="current-price">$<?= number_format($displayPrice, 0, ',', '.') ?></span>
                        <?php if ($hasDiscount): ?>
                            <span class="original-price">$<?= number_format($comparePrice, 0, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="add-to-cart" data-product-id="<?= $related['id'] ?>">
                        <i class="fas fa-shopping-cart"></i> Añadir al carrito
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>