<?php if (!empty($relatedProducts)): ?>
<section class="related-products-section">
    <h2>Productos relacionados</h2>
    <div class="related-products-grid">
        <?php foreach ($relatedProducts as $related): ?>
            <div class="product-card">
                <div class="product-wishlist">
                    <button aria-label="Añadir a favoritos">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <a href="<?= BASE_URL ?>/producto/<?= $related['slug'] ?>" class="product-image">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($related['image_path']) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
                </a>
                    <div class="product-info">
                    <span class="product-category"><?= $product['category_name'] ?></span>
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
                        <span class="current-price">$<?= number_format($related['price'], 0, ',', '.') ?></span>
                        <?php if ($related['compare_price'] && $related['compare_price'] > $related['price']): ?>
                            <span class="original-price">$<?= number_format($related['compare_price'], 0, ',', '.') ?></span>
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