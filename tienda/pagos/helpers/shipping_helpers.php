<?php
require_once __DIR__ . '/../../../config.php';

if (!function_exists('isStorePickupMethod')) {
    function isStorePickupMethod($shippingData): bool {
        if (empty($shippingData)) {
            return false;
        }

        $name = '';
        $description = '';

        if (is_array($shippingData)) {
            $name = strtolower(trim($shippingData['name'] ?? $shippingData['shipping_method_name'] ?? ''));
            $description = strtolower(trim($shippingData['description'] ?? $shippingData['shipping_method_description'] ?? ''));
        } else {
            $name = strtolower(trim((string) $shippingData));
        }

        if ($name === '' && $description === '' && isset($shippingData['shipping_method'])) {
            $name = strtolower(trim($shippingData['shipping_method']));
        }

        return strpos($name, 'recogida') !== false
            || strpos($name, 'tienda') !== false
            || strpos($description, 'recogida') !== false
            || strpos($description, 'tienda') !== false;
    }
}

if (!function_exists('getStorePickupAddress')) {
    function getStorePickupAddress(): string {
        return defined('STORE_PICKUP_ADDRESS') ? STORE_PICKUP_ADDRESS : 'Calle 120 # 49 B 24, Medellín';
    }
}

if (!function_exists('getStorePickupCoordinates')) {
    function getStorePickupCoordinates(): array {
        return [
            'lat' => defined('STORE_PICKUP_LATITUDE') ? STORE_PICKUP_LATITUDE : 6.259516,
            'lng' => defined('STORE_PICKUP_LONGITUDE') ? STORE_PICKUP_LONGITUDE : -75.566933,
        ];
    }
}

if (!function_exists('buildStoreRouteLink')) {
    function buildStoreRouteLink(string $fromAddress = ''): string {
        $encodedFrom = rawurlencode($fromAddress ?: 'Medellín');
        $encodedTo = rawurlencode(getStorePickupAddress());
        return "https://www.google.com/maps/dir/{$encodedFrom}/{$encodedTo}";
    }
}
?>