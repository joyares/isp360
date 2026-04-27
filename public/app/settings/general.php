<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../../app/Modules/Settings/SettingsIspts.php';

use App\Core\Database;
use App\Modules\Settings\SettingsIspts;

$pdo = Database::getConnection();

// Ensure settings table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_settings_type_key (type, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$settings = new SettingsIspts($pdo);
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'site_title' => $_POST['site_title'] ?? '',
        'system_currency' => $_POST['system_currency'] ?? '',
    ];

    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['site_logo']['name']);
        $target = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target)) {
            $data['site_logo'] = $fileName;
        }
    }

    // Handle favicon upload
    if (!empty($_FILES['site_favicon']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['site_favicon']['name']);
        $target = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $target)) {
            $data['site_favicon'] = $fileName;
        }
    }

    $settings->ispts_save_setting('general', $data);
    header('Location: general.php?saved=1');
    exit;
}

if (isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'General settings saved successfully.'];
}

$general_rows = $settings->ispts_get_settings('general');
$general_map = [];
foreach ($general_rows as $row) {
    $general_map[$row['key']] = $row['value'];
}

include __DIR__ . '/../../../views/settings/general.php';
require_once __DIR__ . '/../../includes/footer.php';
