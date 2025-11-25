# Manual Steps to Complete Offers Filter

Due to file editing limitations, please apply the following changes manually:

## 1. Update `tienda/productos.php`

### Add offers parameter (around line 16):
```php
$priceMin = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$priceMax = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$showOffersOnly = isset($_GET['offers']) && $_GET['offers'] === '1';  // ADD THIS LINE
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
```

### Update stored procedure call (around line 26):
Change from 9 parameters to 10:
```php
$stmt = $conn->prepare("CALL GetFilteredProducts(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");  // Add one more ?
```

### Add 10th parameter binding (after line 35):
```php
$stmt->bindValue(9, $userId, $userId !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(10, $showOffersOnly, PDO::PARAM_INT);  // ADD THIS LINE
$stmt->execute();
```

### Add offers filter UI (after line 173, before "Botón aplicar filtros"):
```html
            <!-- Filtro por ofertas -->
            <div class="filter-group">
                <div class="filter-title" data-toggle="offers-filter">
                    <h4>Ofertas</h4>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="filter-options" id="offers-filter">
                    <div class="filter-option">
                        <input type="checkbox" name="offers" id="offers-only" value="1" <?= $showOffersOnly ? 'checked' : '' ?>>
                        <label for="offers-only">Solo productos en oferta</label>
                    </div>
                </div>
            </div>
```

## 2. Update `js/tienda/productosjs.php`

Find the section where filters are collected and add:
```javascript
// Get offers filter
const offersCheckbox = document.querySelector('input[name="offers"]');
if (offersCheckbox && offersCheckbox.checked) {
    params.offers = '1';
}
```

Also update the clear filters function to reset the offers checkbox:
```javascript
const offersCheckbox = document.querySelector('input[name="offers"]');
if (offersCheckbox) offersCheckbox.checked = false;
```

## Database Changes (Already Applied)
✅ The stored procedure `GetFilteredProducts` has been updated to accept a 10th parameter `p_show_offers_only` (BOOLEAN)
✅ The procedure now filters products where `compare_price > price` when offers filter is active

## Testing
After applying the manual changes:
1. Navigate to `/tienda/productos.php`
2. Check the "Solo productos en oferta" checkbox
3. Click "Aplicar Filtros"
4. Verify only products with discount badges are shown
5. Test with other filters combined
