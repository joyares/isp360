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
    ispts_csrf_validate();
    $data = [
        'create_ticket_sms' => isset($_POST['create_ticket_sms']) ? '1' : '0',
        'create_ticket_email' => isset($_POST['create_ticket_email']) ? '1' : '0',
    ];

    $settings->ispts_save_setting('notifications', $data);
    header('Location: notifications.php?saved=1');
    exit;
}

if (isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Notification settings saved successfully.'];
}

$rows = $settings->ispts_get_settings('notifications');
$notification_map = [];
foreach ($rows as $row) {
    $notification_map[$row['key']] = $row['value'];
}

include __DIR__ . '/../../../views/settings/notifications.php';
require_once __DIR__ . '/../../includes/footer.php';
