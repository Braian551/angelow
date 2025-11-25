# PowerShell script to add offers filter to productos.php
$file = "c:\laragon\www\angelow\tienda\productos.php"
$content = Get-Content $file -Raw

# 1. Add offers filter parameter
$content = $content -replace '(\$priceMax = isset\(\$_GET\[''max_price''\]\) \? floatval\(\$_GET\[''max_price''\]\) : null;)\r?\n(\$sortBy)', '$1`r`n$showOffersOnly = isset($_GET[''offers'']) && $_GET[''offers''] === ''1'';`r`n$2'

# 2. Update stored procedure call
$content = $content -replace 'CALL GetFilteredProducts\(\?, \?, \?, \?, \?, \?, \?, \?, \?\)', 'CALL GetFilteredProducts(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'

# 3. Add 10th parameter binding
$content = $content -replace '(\$stmt->bindValue\(9, \$userId, \$userId !== null \? PDO::PARAM_STR : PDO::PARAM_NULL\);)\r?\n(\s+\$stmt->execute\(\);)', '$1`r`n    $stmt->bindValue(10, $showOffersOnly, PDO::PARAM_INT);`r`n$2'

# 4. Add offers filter UI
$offersFilterHTML = @"
            </div>

            <!-- Filtro por ofertas -->
            <div class="filter-group">
                <div class="filter-title" data-toggle="offers-filter">
                    <h4>Ofertas</h4>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="filter-options" id="offers-filter">
                    <div class="filter-option">
                        <input type="checkbox" name="offers" id="offers-only" value="1" <?= `$showOffersOnly ? 'checked' : '' ?>>
                        <label for="offers-only">Solo productos en oferta</label>
                    </div>
                </div>
            </div>

            <!-- Botón aplicar filtros -->
"@

$content = $content -replace '(\s+</div>\r?\n\r?\n\s+<!-- Botón aplicar filtros -->)', $offersFilterHTML

# Save the file
$content | Set-Content $file -NoNewline

Write-Host "Successfully updated productos.php with offers filter"
