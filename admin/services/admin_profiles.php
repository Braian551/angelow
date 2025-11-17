<?php
if (!function_exists('ensure_admin_profiles_table')) {
    function ensure_admin_profiles_table(PDO $conn): void {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $conn->exec("CREATE TABLE IF NOT EXISTS admin_profiles (
            user_id VARCHAR(64) PRIMARY KEY,
            job_title VARCHAR(120) NULL,
            department VARCHAR(120) NULL,
            responsibilities TEXT NULL,
            emergency_contact VARCHAR(120) NULL,
            last_notified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $ensured = true;
    }
}

if (!function_exists('save_admin_profile')) {
    function save_admin_profile(PDO $conn, string $userId, array $payload): void {
        ensure_admin_profiles_table($conn);
        $stmt = $conn->prepare('INSERT INTO admin_profiles (user_id, job_title, department, responsibilities, emergency_contact) VALUES (:id, :title, :department, :responsibilities, :contact)
            ON DUPLICATE KEY UPDATE job_title = VALUES(job_title), department = VALUES(department), responsibilities = VALUES(responsibilities), emergency_contact = VALUES(emergency_contact)');
        $stmt->execute([
            ':id' => $userId,
            ':title' => $payload['job_title'] ?? null,
            ':department' => $payload['department'] ?? null,
            ':responsibilities' => $payload['responsibilities'] ?? null,
            ':contact' => $payload['emergency_contact'] ?? null
        ]);
    }
}

if (!function_exists('get_admin_profile')) {
    function get_admin_profile(PDO $conn, string $userId): array {
        ensure_admin_profiles_table($conn);
        $stmt = $conn->prepare('SELECT job_title, department, responsibilities, emergency_contact FROM admin_profiles WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'job_title' => null,
            'department' => null,
            'responsibilities' => null,
            'emergency_contact' => null
        ];
    }
}
