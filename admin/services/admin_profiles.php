<?php
if (!function_exists('ensure_admin_profiles_table')) {
    function ensure_admin_profiles_table(PDO $conn): void {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        // admin_profiles is deprecated: the table is removed via migration.
        // Keep stub for compatibility so existing calls don't break.
        $ensured = true;
    }
}

if (!function_exists('save_admin_profile')) {
    function save_admin_profile(PDO $conn, string $userId, array $payload): void {
        // admin_profiles table removed — no-op to keep compatibility
        return;
    }
}

if (!function_exists('get_admin_profile')) {
    function get_admin_profile(PDO $conn, string $userId): array {
        // admin_profiles table removed — return defaults
        return [
            'job_title' => null,
            'department' => null,
            'responsibilities' => null,
            'emergency_contact' => null
        ];
    }
}
