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