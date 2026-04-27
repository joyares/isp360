<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../../app/Modules/Settings/SettingsIspts.php';

use App\Core\Database;
use App\Modules\Settings\SettingsIspts;

$pdo = Database::getConnection();
$settings = new SettingsIspts($pdo);
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'sms_api_name' => trim((string)($_POST['sms_api_name'] ?? '')),
        'sms_gateway' => trim((string)($_POST['sms_gateway'] ?? '')),
        'sms_api_method' => trim((string)($_POST['sms_api_method'] ?? '')),
        'sms_api_url' => trim((string)($_POST['sms_api_url'] ?? '')),
        'sms_status' => isset($_POST['sms_status']) ? '1' : '0',
    ];

    $settings->ispts_save_setting('sms', $data);
    header('Location: sms.php?saved=1');
    exit;
}

if (isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'SMS settings saved successfully.'];
}

$rows = $settings->ispts_get_settings('sms');
$sms_map = [];
foreach ($rows as $row) {
    $sms_map[$row['key']] = $row['value'];
}

include __DIR__ . '/../../../views/settings/sms.php';
require_once __DIR__ . '/../../includes/footer.php';
