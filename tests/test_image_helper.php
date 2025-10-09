<?php
// tests/test_image_helper.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/ImageHelper.php';

// Test logo handling
echo "Testing logo handling:\n";
echo "Logo path: " . ImageHelper::getLogoPath() . "\n";
echo "Logo base64: " . (ImageHelper::getLogoBase64() ? "Successfully loaded" : "Failed to load") . "\n\n";

// Test default product image
echo "Testing default product image:\n";
$defaultImagePath = ImageHelper::getProductImagePath('');
echo "Default product image path: " . $defaultImagePath . "\n";
echo "Default product image base64: " . (ImageHelper::convertToBase64($defaultImagePath) ? "Successfully loaded" : "Failed to load") . "\n\n";

// Test product image handling
$testProductImage = 'uploads/productos/test.jpg';
echo "Testing product image handling:\n";
echo "Product image path: " . ImageHelper::getProductImagePath($testProductImage) . "\n";

// Test URL generation
echo "\nTesting URL generation:\n";
echo "Full URL for logo: " . ImageHelper::getFullUrl('images/logo2.png') . "\n";
echo "Full URL for product: " . ImageHelper::getFullUrl('uploads/productos/test.jpg') . "\n";

?>