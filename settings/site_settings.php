<?php
/**
 * Utilidades centrales para la configuración dinámica del sitio / panel.
 */



if (!function_exists('site_settings_definitions')) {
    function site_settings_definitions(): array {
        return [
            'store_name' => ['type' => 'string', 'category' => 'brand', 'default' => 'Angelow', 'max_length' => 140],
            'store_tagline' => ['type' => 'string', 'category' => 'brand', 'default' => 'Moda con proposito', 'max_length' => 180],
            'brand_logo' => ['type' => 'string', 'category' => 'brand', 'default' => '', 'max_length' => 255],
            'primary_color' => ['type' => 'string', 'category' => 'brand', 'default' => '#0077b6', 'pattern' => '/^#([A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['type' => 'string', 'category' => 'brand', 'default' => '#0f172a', 'pattern' => '/^#([A-Fa-f0-9]{6})$/'],
            'support_email' => ['type' => 'string', 'category' => 'support', 'default' => 'soporte@angelow.com', 'max_length' => 180],
            'support_phone' => ['type' => 'string', 'category' => 'support', 'default' => '+57 300 000 0000', 'max_length' => 40],
            'support_whatsapp' => ['type' => 'string', 'category' => 'support', 'default' => '+57 300 000 0000', 'max_length' => 40],
            'support_hours' => ['type' => 'string', 'category' => 'support', 'default' => 'L-V 8:00 a 18:00', 'max_length' => 120],
            'support_address' => ['type' => 'string', 'category' => 'support', 'default' => 'Medellin, Colombia', 'max_length' => 240],
            'order_auto_cancel_hours' => ['type' => 'int', 'category' => 'operations', 'default' => 48, 'min' => 1, 'max' => 720],
            'order_review_window_days' => ['type' => 'int', 'category' => 'operations', 'default' => 15, 'min' => 1, 'max' => 60],
            'low_stock_threshold' => ['type' => 'int', 'category' => 'operations', 'default' => 5, 'min' => 1, 'max' => 200],
            'review_auto_approve' => ['type' => 'bool', 'category' => 'operations', 'default' => true],
            'currency_code' => ['type' => 'string', 'category' => 'operations', 'default' => 'COP', 'pattern' => '/^[A-Z]{3}$/'],
            'analytics_timezone' => ['type' => 'string', 'category' => 'operations', 'default' => 'America/Bogota', 'max_length' => 64],
            'dashboard_welcome' => ['type' => 'string', 'category' => 'brand', 'default' => 'Bienvenido al panel Angelow', 'max_length' => 255],
            'social_instagram' => ['type' => 'string', 'category' => 'social', 'default' => 'https://instagram.com/angelow', 'max_length' => 255],
            'social_facebook' => ['type' => 'string', 'category' => 'social', 'default' => 'https://facebook.com/angelow', 'max_length' => 255],
            'social_tiktok' => ['type' => 'string', 'category' => 'social', 'default' => '', 'max_length' => 255],
            'social_whatsapp' => ['type' => 'string', 'category' => 'social', 'default' => 'https://wa.me/573000000000', 'max_length' => 255],
        ];
    }
}

if (!function_exists('ensure_site_settings_table')) {
    function ensure_site_settings_table(PDO $conn): void {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            category VARCHAR(40) DEFAULT 'general',
            updated_by VARCHAR(64) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);
        $ensured = true;
    }
}

if (!function_exists('fetch_site_settings')) {
    function fetch_site_settings(PDO $conn, bool $withDefaults = true): array {
        ensure_site_settings_table($conn);

        $stmt = $conn->query('SELECT setting_key, setting_value FROM site_settings');
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        if (!$withDefaults) {
            return $rows;
        }

        $definitions = site_settings_definitions();
        foreach ($definitions as $key => $definition) {
            if (!array_key_exists($key, $rows)) {
                $rows[$key] = $definition['default'] ?? null;
            } elseif (isset($definition['type']) && $definition['type'] === 'bool') {
                $rows[$key] = filter_var($rows[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }

        return $rows;
    }
}

if (!function_exists('sanitize_setting_value')) {
    function sanitize_setting_value(string $key, $value) {
        $definitions = site_settings_definitions();
        $definition = $definitions[$key] ?? ['type' => 'string'];
        $type = $definition['type'] ?? 'string';

        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            case 'int':
                $intVal = (int) $value;
                if (isset($definition['min'])) {
                    $intVal = max($definition['min'], $intVal);
                }
                if (isset($definition['max'])) {
                    $intVal = min($definition['max'], $intVal);
                }
                return (string) $intVal;
            default:
                $stringVal = trim((string) $value);
                if (isset($definition['pattern']) && $definition['pattern']) {
                    if (!preg_match($definition['pattern'], $stringVal)) {
                        return $definition['default'] ?? '';
                    }
                }
                if (isset($definition['max_length'])) {
                    $stringVal = mb_substr($stringVal, 0, (int) $definition['max_length']);
                }
                return $stringVal;
        }
    }
}

if (!function_exists('save_site_settings')) {
    function save_site_settings(PDO $conn, array $values, ?string $userId = null): array {
        ensure_site_settings_table($conn);
        $definitions = site_settings_definitions();
        $allowedKeys = array_keys($definitions);

        $payload = [];
        foreach ($values as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }
            $payload[$key] = sanitize_setting_value($key, $value);
        }

        if (empty($payload)) {
            return [];
        }

        $stmt = $conn->prepare('INSERT INTO site_settings (setting_key, setting_value, category, updated_by) VALUES (:key, :value, :category, :updated_by)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), category = VALUES(category), updated_by = VALUES(updated_by)');

        foreach ($payload as $key => $value) {
            $category = $definitions[$key]['category'] ?? 'general';
            $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':category' => $category,
                ':updated_by' => $userId
            ]);
        }

        return $payload;
    }
}

if (!function_exists('get_site_setting')) {
    function get_site_setting(string $key, $default = null, ?array $cachedSettings = null) {
        $settings = $cachedSettings ?? ($GLOBALS['ANG_SITE_SETTINGS'] ?? []);
        return $settings[$key] ?? $default;
    }
}
