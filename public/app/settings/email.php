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
        'email_form_name' => trim((string)($_POST['email_form_name'] ?? '')),
        'email_form_email' => trim((string)($_POST['email_form_email'] ?? '')),
        'email_smtp_username' => trim((string)($_POST['email_smtp_username'] ?? '')),
        'email_smtp_password' => trim((string)($_POST['email_smtp_password'] ?? '')),
        'email_encryption' => trim((string)($_POST['email_encryption'] ?? '')),
        'email_smtp_host' => trim((string)($_POST['email_smtp_host'] ?? '')),
        'email_smtp_port' => trim((string)($_POST['email_smtp_port'] ?? '')),
        'email_signature' => trim((string)($_POST['email_signature'] ?? '')),
        'email_status' => isset($_POST['email_status']) ? '1' : '0',
    ];

    $settings->ispts_save_setting('email', $data);
    header('Location: email.php?saved=1');
    exit;
}

if (isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Email settings saved successfully.'];
}

$rows = $settings->ispts_get_settings('email');
$email_map = [];
foreach ($rows as $row) {
    $email_map[$row['key']] = $row['value'];
}

include __DIR__ . '/../../../views/settings/email.php';
require_once __DIR__ . '/../../includes/footer.php';
