<?php
if (function_exists('imagecreatefromwebp')) {
    echo "WebP support is ENABLED.";
} else {
    echo "WebP support is DISABLED.";
}
?>
