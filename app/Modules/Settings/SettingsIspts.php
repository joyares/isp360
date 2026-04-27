<?php
namespace App\Modules\Settings;


require_once __DIR__ . '/../../Core/BaseModel.php';
// SettingsIspts.php
// Handles Settings logic for General, Notifications, SMS, Email

use App\Core\BaseModel;

class SettingsIspts extends BaseModel {
    public function __construct($db = null) {
        parent::__construct($db);
        $this->db->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            `key` VARCHAR(100) NOT NULL,
            `value` TEXT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uk_settings_type_key (type, `key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function ispts_get_settings($type) {
        $stmt = $this->db->prepare("SELECT * FROM settings WHERE type = :type AND status = 1");
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }
    public function ispts_save_setting($type, $data) {
        // Upsert logic for settings
        foreach ($data as $key => $value) {
            $stmt = $this->db->prepare("INSERT INTO settings (`type`, `key`, `value`, `status`) VALUES (:type, :key, :value, 1) ON DUPLICATE KEY UPDATE value = :value, status = 1");
            $stmt->execute([
                'type' => $type,
                'key' => $key,
                'value' => $value
            ]);
        }
        return true;
    }
}
