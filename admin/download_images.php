<?php
$dir = __DIR__ . '/../uploads/productos/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

echo "Downloading images to $dir...\n";

for ($i = 1; $i <= 5; $i++) {
    $url = "https://loremflickr.com/800/800/clothing?lock=$i";
    $file = $dir . "real_product_$i.jpg";
    
    echo "Downloading image $i from $url...\n";
    $content = file_get_contents($url);
    if ($content) {
        file_put_contents($file, $content);
        echo "Saved to $file\n";
    } else {
        echo "Failed to download image $i\n";
    }
}
echo "Done.\n";
