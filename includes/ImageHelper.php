<?php
// includes/ImageHelper.php

class ImageHelper {
    /**
     * Convierte una ruta relativa a URL absoluta usando BASE_URL
     */
    public static function getFullUrl($path) {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Convierte una ruta relativa a ruta absoluta del servidor
     */
    public static function getServerPath($path) {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/angelow/' . ltrim($path, '/');
    }

    /**
     * Obtiene la ruta del logo
     */
    public static function getLogoPath($useDefault = true) {
        $logo2Path = self::getServerPath('images/logo2.png');
        if (file_exists($logo2Path)) {
            return $logo2Path;
        }
        
        if ($useDefault) {
            $logoPath = self::getServerPath('images/logo.png');
            if (file_exists($logoPath)) {
                return $logoPath;
            }
        }
        
        return null;
    }

    /**
     * Obtiene la ruta de una imagen de producto
     */
    public static function getProductImagePath($relativePath) {
        if (empty($relativePath)) {
            return self::getServerPath('images/default-product.jpg');
        }

        // Asegurar que la ruta sea relativa a uploads/productos
        if (strpos($relativePath, 'uploads/productos/') === false) {
            $relativePath = 'uploads/productos/' . basename($relativePath);
        }

        return self::getServerPath($relativePath);
    }

    /**
     * Convierte una imagen a base64
     */
    public static function convertToBase64($path) {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Obtiene el logo como cadena base64
     */
    public static function getLogoBase64() {
        $logoPath = self::getLogoPath();
        return $logoPath ? self::convertToBase64($logoPath) : null;
    }
}