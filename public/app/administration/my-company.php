<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
  "CREATE TABLE IF NOT EXISTS companies (
        id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_type        VARCHAR(20) NOT NULL DEFAULT 'partner' COMMENT 'mother or partner',
        partnerId           INT NOT NULL DEFAULT 0,
        departmentId        INT NOT NULL DEFAULT 0,
        roleId              INT NOT NULL DEFAULT 0,
        parentId            INT NOT NULL DEFAULT 0,
        branch_access_type  VARCHAR(255) NOT NULL DEFAULT '0',
        partner_access_type VARCHAR(255) NOT NULL DEFAULT '0',
        username            VARCHAR(90) NOT NULL,
        password            VARCHAR(160) NOT NULL,
        enabled             TINYINT(1) NOT NULL DEFAULT 1,
        status              TINYINT(1) NOT NULL DEFAULT 1,
        firstname           VARCHAR(200) NULL,
        lastname            VARCHAR(200) NULL,
        email               VARCHAR(300) NULL,
        phone               VARCHAR(200) NULL,
        company             VARCHAR(200) NULL,
        logo_icon           VARCHAR(255) NULL,
        logo_main           VARCHAR(255) NULL,
        contact_person_img  VARCHAR(255) NULL,
        contact_person_name VARCHAR(200) NULL,
        contact_person_phone VARCHAR(200) NULL,
        contact_person_alt_phone VARCHAR(200) NULL,
        contact_person_email VARCHAR(300) NULL,
        address             VARCHAR(500) NULL,
        notes               MEDIUMTEXT NULL,
        user_type           INT NOT NULL DEFAULT 1,
        created_at          DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at          DATETIME NULL DEFAULT NULL,
        avatar              VARCHAR(90) NULL,
        created_by          INT NULL,
        updated_by          INT NULL,
        last_login          TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uk_companies_username (username),
        KEY idx_companies_enabled (enabled),
        KEY idx_companies_status (status),
        KEY idx_companies_deleted (deleted_at),
        KEY idx_companies_parent (parentId),
        KEY idx_companies_type (company_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$ispts_has_column = static function (\PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
  );
  $stmt->bindValue(':table', $table);
  $stmt->bindValue(':column', $column);
  $stmt->execute();

  return (int) $stmt->fetchColumn() > 0;
};

$legacySafeColumns = [
  'company_type' => "ALTER TABLE companies ADD COLUMN company_type VARCHAR(20) NOT NULL DEFAULT 'partner' COMMENT 'mother or partner' AFTER id",
  'username' => "ALTER TABLE companies ADD COLUMN username VARCHAR(90) NULL",
  'password' => "ALTER TABLE companies ADD COLUMN password VARCHAR(160) NULL",
  'firstname' => "ALTER TABLE companies ADD COLUMN firstname VARCHAR(200) NULL",
  'lastname' => "ALTER TABLE companies ADD COLUMN lastname VARCHAR(200) NULL",
  'email' => "ALTER TABLE companies ADD COLUMN email VARCHAR(300) NULL",
  'phone' => "ALTER TABLE companies ADD COLUMN phone VARCHAR(200) NULL",
  'company' => "ALTER TABLE companies ADD COLUMN company VARCHAR(200) NULL",
  'logo_icon' => "ALTER TABLE companies ADD COLUMN logo_icon VARCHAR(255) NULL",
  'logo_main' => "ALTER TABLE companies ADD COLUMN logo_main VARCHAR(255) NULL",
  'contact_person_img' => "ALTER TABLE companies ADD COLUMN contact_person_img VARCHAR(255) NULL",
  'contact_person_name' => "ALTER TABLE companies ADD COLUMN contact_person_name VARCHAR(200) NULL",
  'contact_person_phone' => "ALTER TABLE companies ADD COLUMN contact_person_phone VARCHAR(200) NULL",
  'contact_person_alt_phone' => "ALTER TABLE companies ADD COLUMN contact_person_alt_phone VARCHAR(200) NULL",
  'contact_person_email' => "ALTER TABLE companies ADD COLUMN contact_person_email VARCHAR(300) NULL",
  'address' => "ALTER TABLE companies ADD COLUMN address VARCHAR(500) NULL",
  'notes' => "ALTER TABLE companies ADD COLUMN notes MEDIUMTEXT NULL",
  'enabled' => "ALTER TABLE companies ADD COLUMN enabled TINYINT(1) NOT NULL DEFAULT 1",
  'user_type' => "ALTER TABLE companies ADD COLUMN user_type INT NOT NULL DEFAULT 1",
  'branch_access_type' => "ALTER TABLE companies ADD COLUMN branch_access_type VARCHAR(255) NOT NULL DEFAULT '0'",
  'partner_access_type' => "ALTER TABLE companies ADD COLUMN partner_access_type VARCHAR(255) NOT NULL DEFAULT '0'",
  'parentId' => "ALTER TABLE companies ADD COLUMN parentId INT NOT NULL DEFAULT 0",
  'partnerId' => "ALTER TABLE companies ADD COLUMN partnerId INT NOT NULL DEFAULT 0",
  'departmentId' => "ALTER TABLE companies ADD COLUMN departmentId INT NOT NULL DEFAULT 0",
  'roleId' => "ALTER TABLE companies ADD COLUMN roleId INT NOT NULL DEFAULT 0",
  'deleted_at' => "ALTER TABLE companies ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL",
  'created_by' => "ALTER TABLE companies ADD COLUMN created_by INT NULL",
  'updated_by' => "ALTER TABLE companies ADD COLUMN updated_by INT NULL",
  'last_login' => "ALTER TABLE companies ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL",
];

foreach ($legacySafeColumns as $column => $sql) {
  if (!$ispts_has_column($pdo, 'companies', $column)) {
    $pdo->exec($sql);
  }
}

if ($ispts_has_column($pdo, 'companies', 'first_name') && $ispts_has_column($pdo, 'companies', 'firstname')) {
  $pdo->exec('UPDATE companies SET firstname = COALESCE(NULLIF(firstname, ""), first_name)');
}

if ($ispts_has_column($pdo, 'companies', 'last_name') && $ispts_has_column($pdo, 'companies', 'lastname')) {
  $pdo->exec('UPDATE companies SET lastname = COALESCE(NULLIF(lastname, ""), last_name)');
}

if ($ispts_has_column($pdo, 'companies', 'avatar') && $ispts_has_column($pdo, 'companies', 'contact_person_img')) {
  $pdo->exec('UPDATE companies SET contact_person_img = COALESCE(NULLIF(contact_person_img, ""), avatar)');
}

$partnerIdColumn = $ispts_has_column($pdo, 'companies', 'id') ? 'id' : ($ispts_has_column($pdo, 'companies', 'partner_id') ? 'partner_id' : 'id');
$partnerDeletedCondition = $ispts_has_column($pdo, 'companies', 'deleted_at') ? 'deleted_at IS NULL' : '1=1';
$partnerDeletedConditionAliased = $ispts_has_column($pdo, 'companies', 'deleted_at') ? 'p.deleted_at IS NULL' : '1=1';
$partnerEnabledColumn = $ispts_has_column($pdo, 'companies', 'enabled') ? 'enabled' : ($ispts_has_column($pdo, 'companies', 'status') ? 'status' : 'enabled');
$partnerRoleColumn = $ispts_has_column($pdo, 'companies', 'roleId') ? 'roleId' : ($ispts_has_column($pdo, 'companies', 'role_id') ? 'role_id' : 'roleId');
$partnerParentColumn = $ispts_has_column($pdo, 'companies', 'parentId') ? 'parentId' : ($ispts_has_column($pdo, 'companies', 'parent_id') ? 'parent_id' : 'parentId');
$partnerContactImageColumn = $ispts_has_column($pdo, 'companies', 'contact_person_img')
  ? 'contact_person_img'
  : ($ispts_has_column($pdo, 'companies', 'avatar') ? 'avatar' : '');
$partnerContactImageSelect = $partnerContactImageColumn !== '' ? ($partnerContactImageColumn . ' AS contact_person_img') : "'' AS contact_person_img";
$partnerContactImageSelectAliased = $partnerContactImageColumn !== '' ? ('p.' . $partnerContactImageColumn . ' AS contact_person_img') : "'' AS contact_person_img";

$alert = null;
$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$partnerTabEditId = isset($_GET['partner_edit_id']) ? (int) $_GET['partner_edit_id'] : 0;
$branchTabEditId = isset($_GET['branch_edit_id']) ? (int) $_GET['branch_edit_id'] : 0;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/my-company.php';
$activeTab = (string) ($_GET['tab'] ?? 'company');

if (!in_array($activeTab, ['company', 'partners', 'branches'], true)) {
  $activeTab = 'company';
}

$uploadDirectory = dirname(__DIR__, 2) . '/assets/uploads/companies';

$ispts_save_logo = static function (array $file, string $prefix, int $targetWidth, int $targetHeight) use ($uploadDirectory): array {
  if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
    return ['path' => null, 'error' => null];
  }

  if ((int) $file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
    return ['path' => null, 'error' => 'Upload failed for ' . $prefix . ' logo.'];
  }

  $imageInfo = @getimagesize((string) $file['tmp_name']);
  if ($imageInfo === false || !isset($imageInfo['mime'])) {
    return ['path' => null, 'error' => 'Invalid image file for ' . $prefix . ' logo.'];
  }

  $mime = (string) $imageInfo['mime'];
  $sourceImage = null;
  if ($mime === 'image/jpeg') {
    $sourceImage = @imagecreatefromjpeg((string) $file['tmp_name']);
  } elseif ($mime === 'image/png') {
    $sourceImage = @imagecreatefrompng((string) $file['tmp_name']);
  } elseif ($mime === 'image/gif') {
    $sourceImage = @imagecreatefromgif((string) $file['tmp_name']);
  } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
    $sourceImage = @imagecreatefromwebp((string) $file['tmp_name']);
  }

  if (!$sourceImage) {
    return ['path' => null, 'error' => 'Unsupported image type for ' . $prefix . ' logo.'];
  }

  $destinationImage = imagecreatetruecolor($targetWidth, $targetHeight);
  imagealphablending($destinationImage, false);
  imagesavealpha($destinationImage, true);
  $transparent = imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
  imagefilledrectangle($destinationImage, 0, 0, $targetWidth, $targetHeight, $transparent);

  imagecopyresampled(
    $destinationImage,
    $sourceImage,
    0,
    0,
    0,
    0,
    $targetWidth,
    $targetHeight,
    (int) imagesx($sourceImage),
    (int) imagesy($sourceImage)
  );

  if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
    imagedestroy($sourceImage);
    imagedestroy($destinationImage);
    return ['path' => null, 'error' => 'Failed to create upload directory for logos.'];
  }

  $fileName = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.png';
  $absolutePath = $uploadDirectory . '/' . $fileName;
  $saved = imagepng($destinationImage, $absolutePath, 7);

  imagedestroy($sourceImage);
  imagedestroy($destinationImage);

  if (!$saved) {
    return ['path' => null, 'error' => 'Failed to save ' . $prefix . ' logo.'];
  }

  return ['path' => 'assets/uploads/companies/' . $fileName, 'error' => null];
};

$formData = [
  'id' => 0,
  'firstname' => '',
  'lastname' => '',
  'username' => '',
  'email' => '',
  'phone' => '',
  'company' => '',
  'logo_icon' => '',
  'logo_main' => '',
  'contact_person_img' => '',
  'contact_person_name' => '',
  'contact_person_phone' => '',
  'contact_person_alt_phone' => '',
  'contact_person_email' => '',
  'address' => '',
  'notes' => '',
  'user_type' => 1,
  'branch_access_type' => '0',
  'partner_access_type' => '0',
  'parentId' => 0,
  'partnerId' => 0,
  'departmentId' => 0,
  'roleId' => 0,
  'enabled' => 1,
];

$branchFormData = [
  'branch_id' => 0,
  'branch_name' => '',
  'display_company' => '',
  'partner_id' => 0,
  'location_id' => 0,
  'email' => '',
  'mobile' => '',
  'contact_person_name' => '',
  'contact_person_phone' => '',
  'contact_person_alt_phone' => '',
  'contact_person_email' => '',
  'address' => '',
  'branch_type' => '',
  'ratio' => '0',
  'status' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');

  // ── Save (insert / update) ───────────────────────────────────────────────
  if ($action === 'save_company') {
    $incomingId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $company = trim((string) ($_POST['company'] ?? ''));
    $contactPersonName = trim((string) ($_POST['contact_person_name'] ?? ''));
    $contactPersonPhone = trim((string) ($_POST['contact_person_phone'] ?? ''));
    $contactPersonAltPhone = trim((string) ($_POST['contact_person_alt_phone'] ?? ''));
    $contactPersonEmail = trim((string) ($_POST['contact_person_email'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $user_type = isset($_POST['user_type']) ? (int) $_POST['user_type'] : 1;
    $branch_access_type = trim((string) ($_POST['branch_access_type'] ?? '0'));
    $partner_access_type = trim((string) ($_POST['partner_access_type'] ?? '0'));
    $parentId_field = isset($_POST['parentId']) ? (int) $_POST['parentId'] : 0;
    $partnerRef = isset($_POST['partnerId']) ? (int) $_POST['partnerId'] : 0;
    $departmentId = isset($_POST['departmentId']) ? (int) $_POST['departmentId'] : 0;
    $roleId = isset($_POST['roleId']) ? (int) $_POST['roleId'] : 0;
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $sessionUserId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

    $iconUpload = $ispts_save_logo($_FILES['logo_icon'] ?? [], 'icon', 100, 100);
    $mainUpload = $ispts_save_logo($_FILES['logo_main'] ?? [], 'main', 300, 100);
    $contactPersonImageUpload = $ispts_save_logo($_FILES['contact_person_img'] ?? [], 'contact', 200, 300);

    if ($iconUpload['error'] !== null) {
      $alert = ['type' => 'danger', 'message' => (string) $iconUpload['error']];
    } elseif ($mainUpload['error'] !== null) {
      $alert = ['type' => 'danger', 'message' => (string) $mainUpload['error']];
    } elseif ($contactPersonImageUpload['error'] !== null) {
      $alert = ['type' => 'danger', 'message' => (string) $contactPersonImageUpload['error']];
    }

    if ($alert === null) {
      if ($incomingId > 0) {
        // Update
        $updateSql = 'UPDATE companies SET
                    email = :email, phone = :phone, company = :company, address = :address,
              contact_person_name = :contact_person_name,
              contact_person_phone = :contact_person_phone,
              contact_person_alt_phone = :contact_person_alt_phone,
              contact_person_email = :contact_person_email,
                    notes = :notes, user_type = :user_type,
                    branch_access_type = :branch_access_type, partner_access_type = :partner_access_type,
                    parentId = :parentId, partnerId = :partnerRef, departmentId = :departmentId,
                    roleId = :roleId, enabled = :enabled, status = :status, updated_by = :updated_by';
        if ($iconUpload['path'] !== null) {
          $updateSql .= ', logo_icon = :logo_icon';
        }
        if ($mainUpload['path'] !== null) {
          $updateSql .= ', logo_main = :logo_main';
        }
        if ($contactPersonImageUpload['path'] !== null) {
          $updateSql .= ', contact_person_img = :contact_person_img';
        }
        $updateSql .= ' WHERE ' . $partnerIdColumn . ' = :id AND ' . $partnerDeletedCondition;

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(':email', $email !== '' ? $email : null);
        $updateStmt->bindValue(':phone', $phone !== '' ? $phone : null);
        $updateStmt->bindValue(':company', $company !== '' ? $company : null);
        $updateStmt->bindValue(':contact_person_name', $contactPersonName !== '' ? $contactPersonName : null);
        $updateStmt->bindValue(':contact_person_phone', $contactPersonPhone !== '' ? $contactPersonPhone : null);
        $updateStmt->bindValue(':contact_person_alt_phone', $contactPersonAltPhone !== '' ? $contactPersonAltPhone : null);
        $updateStmt->bindValue(':contact_person_email', $contactPersonEmail !== '' ? $contactPersonEmail : null);
        $updateStmt->bindValue(':address', $address !== '' ? $address : null);
        $updateStmt->bindValue(':notes', $notes !== '' ? $notes : null);
        $updateStmt->bindValue(':user_type', $user_type, \PDO::PARAM_INT);
        $updateStmt->bindValue(':branch_access_type', $branch_access_type);
        $updateStmt->bindValue(':partner_access_type', $partner_access_type);
        $updateStmt->bindValue(':parentId', $parentId_field, \PDO::PARAM_INT);
        $updateStmt->bindValue(':partnerRef', $partnerRef, \PDO::PARAM_INT);
        $updateStmt->bindValue(':departmentId', $departmentId, \PDO::PARAM_INT);
        $updateStmt->bindValue(':roleId', $roleId, \PDO::PARAM_INT);
        $updateStmt->bindValue(':enabled', $enabled, \PDO::PARAM_INT);
        $updateStmt->bindValue(':status', $enabled, \PDO::PARAM_INT);
        $updateStmt->bindValue(':updated_by', $sessionUserId, $sessionUserId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        if ($iconUpload['path'] !== null) {
          $updateStmt->bindValue(':logo_icon', (string) $iconUpload['path']);
        }
        if ($mainUpload['path'] !== null) {
          $updateStmt->bindValue(':logo_main', (string) $mainUpload['path']);
        }
        if ($contactPersonImageUpload['path'] !== null) {
          $updateStmt->bindValue(':contact_person_img', (string) $contactPersonImageUpload['path']);
        }
        $updateStmt->bindValue(':id', $incomingId, \PDO::PARAM_INT);
        $updateStmt->execute();

        header('Location: ' . $currentPath . '?saved=updated');
        exit;
      }

      // Insert — username auto-generated, password placeholder
      $autoUsername = 'company_' . time() . '_' . random_int(100, 999);
      $insertStmt = $pdo->prepare(
        'INSERT INTO companies (
                  company_type, username, password, email, phone, company, logo_icon, logo_main, contact_person_img,
                  contact_person_name, contact_person_phone, contact_person_alt_phone, contact_person_email,
                  address, notes,
                    user_type, branch_access_type, partner_access_type, parentId, partnerId,
                    departmentId, roleId, enabled, status, created_by
                 ) VALUES (
                  :company_type, :username, :password, :email, :phone, :company, :logo_icon, :logo_main, :contact_person_img,
                  :contact_person_name, :contact_person_phone, :contact_person_alt_phone, :contact_person_email,
                  :address, :notes,
                    :user_type, :branch_access_type, :partner_access_type, :parentId, :partnerRef,
                    :departmentId, :roleId, :enabled, :status, :created_by
                 )'
      );
      $insertStmt->bindValue(':company_type', 'mother');
      $insertStmt->bindValue(':username', $autoUsername);
      $insertStmt->bindValue(':password', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
      $insertStmt->bindValue(':email', $email !== '' ? $email : null);
      $insertStmt->bindValue(':phone', $phone !== '' ? $phone : null);
      $insertStmt->bindValue(':company', $company !== '' ? $company : null);
      $insertStmt->bindValue(':logo_icon', $iconUpload['path'] !== null ? (string) $iconUpload['path'] : null, $iconUpload['path'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
      $insertStmt->bindValue(':logo_main', $mainUpload['path'] !== null ? (string) $mainUpload['path'] : null, $mainUpload['path'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
      $insertStmt->bindValue(':contact_person_img', $contactPersonImageUpload['path'] !== null ? (string) $contactPersonImageUpload['path'] : null, $contactPersonImageUpload['path'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
      $insertStmt->bindValue(':contact_person_name', $contactPersonName !== '' ? $contactPersonName : null);
      $insertStmt->bindValue(':contact_person_phone', $contactPersonPhone !== '' ? $contactPersonPhone : null);
      $insertStmt->bindValue(':contact_person_alt_phone', $contactPersonAltPhone !== '' ? $contactPersonAltPhone : null);
      $insertStmt->bindValue(':contact_person_email', $contactPersonEmail !== '' ? $contactPersonEmail : null);
      $insertStmt->bindValue(':address', $address !== '' ? $address : null);
      $insertStmt->bindValue(':notes', $notes !== '' ? $notes : null);
      $insertStmt->bindValue(':user_type', $user_type, \PDO::PARAM_INT);
      $insertStmt->bindValue(':branch_access_type', $branch_access_type);
      $insertStmt->bindValue(':partner_access_type', $partner_access_type);
      $insertStmt->bindValue(':parentId', $parentId_field, \PDO::PARAM_INT);
      $insertStmt->bindValue(':partnerRef', $partnerRef, \PDO::PARAM_INT);
      $insertStmt->bindValue(':departmentId', $departmentId, \PDO::PARAM_INT);
      $insertStmt->bindValue(':roleId', $roleId, \PDO::PARAM_INT);
      $insertStmt->bindValue(':enabled', $enabled, \PDO::PARAM_INT);
      $insertStmt->bindValue(':status', $enabled, \PDO::PARAM_INT);
      $insertStmt->bindValue(':created_by', $sessionUserId, $sessionUserId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
      $insertStmt->execute();

      header('Location: ' . $currentPath . '?saved=created');
      exit;
    }

    // Repopulate form on validation error
    $formData = array_merge($formData, [
      'id' => $incomingId,
      'email' => $email,
      'phone' => $phone,
      'company' => $company,
      'contact_person_img' => $contactPersonImageUpload['path'] !== null ? (string) $contactPersonImageUpload['path'] : $formData['contact_person_img'],
      'contact_person_name' => $contactPersonName,
      'contact_person_phone' => $contactPersonPhone,
      'contact_person_alt_phone' => $contactPersonAltPhone,
      'contact_person_email' => $contactPersonEmail,
      'address' => $address,
      'notes' => $notes,
      'user_type' => $user_type,
      'branch_access_type' => $branch_access_type,
      'partner_access_type' => $partner_access_type,
      'parentId' => $parentId_field,
      'partnerId' => $partnerRef,
      'departmentId' => $departmentId,
      'roleId' => $roleId,
      'enabled' => $enabled,
    ]);
  }

  // ── Save partner tab (insert / update) ──────────────────────────────────
  if ($action === 'save_partner') {
    $incomingId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $company = trim((string) ($_POST['company'] ?? ''));
    $contactPersonName = trim((string) ($_POST['contact_person_name'] ?? ''));
    $contactPersonPhone = trim((string) ($_POST['contact_person_phone'] ?? ''));
    $contactPersonAltPhone = trim((string) ($_POST['contact_person_alt_phone'] ?? ''));
    $contactPersonEmail = trim((string) ($_POST['contact_person_email'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $user_type = isset($_POST['user_type']) ? (int) $_POST['user_type'] : 1;
    $branch_access_type = trim((string) ($_POST['branch_access_type'] ?? '0'));
    $partner_access_type = trim((string) ($_POST['partner_access_type'] ?? '0'));
    $parentId_field = isset($_POST['parentId']) ? (int) $_POST['parentId'] : 0;
    $partnerRef = isset($_POST['partnerId']) ? (int) $_POST['partnerId'] : 0;
    $departmentId = isset($_POST['departmentId']) ? (int) $_POST['departmentId'] : 0;
    $roleId = isset($_POST['roleId']) ? (int) $_POST['roleId'] : 0;
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $sessionUserId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

    // Partners are now stored in companies table with company_type = 'partner'
    {
      $partnersHasColumn = static function (string $column) use ($ispts_has_column, $pdo): bool {
        return $ispts_has_column($pdo, 'companies', $column);
      };

      // company_type column is guaranteed by the CREATE TABLE above

      $partnersResolveColumn = static function (array $candidates) use ($partnersHasColumn): string {
        foreach ($candidates as $candidate) {
          if ($partnersHasColumn($candidate)) {
            return $candidate;
          }
        }

        return '';
      };

      $partnerLogoIconColumn = $partnersResolveColumn(['logo_icon', 'icon_logo']);
      $partnerLogoMainColumn = $partnersResolveColumn(['logo_main', 'main_logo']);

      $iconUpload = $ispts_save_logo($_FILES['logo_icon'] ?? [], 'partner_icon', 100, 100);
      $mainUpload = $ispts_save_logo($_FILES['logo_main'] ?? [], 'partner_main', 300, 100);
      $contactPersonImageUpload = $ispts_save_logo($_FILES['contact_person_img'] ?? [], 'partner_contact', 200, 300);

      if ($iconUpload['error'] !== null) {
        $alert = ['type' => 'danger', 'message' => (string) $iconUpload['error']];
      } elseif ($mainUpload['error'] !== null) {
        $alert = ['type' => 'danger', 'message' => (string) $mainUpload['error']];
      } elseif ($contactPersonImageUpload['error'] !== null) {
        $alert = ['type' => 'danger', 'message' => (string) $contactPersonImageUpload['error']];
      }

      if ($alert === null) {
        $whereDeleted = $partnersHasColumn('deleted_at') ? ' AND deleted_at IS NULL' : '';
        $contactImageColumn = $partnersResolveColumn(['contact_person_img', 'avatar']);
        $enabledColumn = $partnersResolveColumn(['enabled']);
        $statusColumn = $partnersResolveColumn(['status']);

        $valuesByField = [
          'email' => $email !== '' ? $email : null,
          'phone' => $phone !== '' ? $phone : null,
          'company' => $company !== '' ? $company : null,
          'contact_person_name' => $contactPersonName !== '' ? $contactPersonName : null,
          'contact_person_phone' => $contactPersonPhone !== '' ? $contactPersonPhone : null,
          'contact_person_alt_phone' => $contactPersonAltPhone !== '' ? $contactPersonAltPhone : null,
          'contact_person_email' => $contactPersonEmail !== '' ? $contactPersonEmail : null,
          'address' => $address !== '' ? $address : null,
          'notes' => $notes !== '' ? $notes : null,
          'user_type' => $user_type,
          'branch_access_type' => $branch_access_type,
          'partner_access_type' => $partner_access_type,
          'parentId' => $parentId_field,
          'partnerId' => $partnerRef,
          'departmentId' => $departmentId,
          'roleId' => $roleId,
        ];
        $fieldToColumnMap = [
          'email' => $partnersResolveColumn(['email']),
          'phone' => $partnersResolveColumn(['phone', 'mobile']),
          'company' => $partnersResolveColumn(['company', 'company_name']),
          'contact_person_name' => $partnersResolveColumn(['contact_person_name']),
          'contact_person_phone' => $partnersResolveColumn(['contact_person_phone']),
          'contact_person_alt_phone' => $partnersResolveColumn(['contact_person_alt_phone']),
          'contact_person_email' => $partnersResolveColumn(['contact_person_email']),
          'address' => $partnersResolveColumn(['address']),
          'notes' => $partnersResolveColumn(['notes']),
          'user_type' => $partnersResolveColumn(['user_type']),
          'branch_access_type' => $partnersResolveColumn(['branch_access_type']),
          'partner_access_type' => $partnersResolveColumn(['partner_access_type']),
          'parentId' => $partnersResolveColumn(['parentId', 'parent_id']),
          'partnerId' => $partnersResolveColumn(['partnerId', 'partner_id']),
          'departmentId' => $partnersResolveColumn(['departmentId', 'department_id']),
          'roleId' => $partnersResolveColumn(['roleId', 'role_id']),
        ];
        $intFields = ['user_type', 'parentId', 'partnerId', 'departmentId', 'roleId', 'enabled', 'status'];

        if ($incomingId > 0) {
          $setParts = [];
          $bindMap = [];

          foreach ($valuesByField as $field => $value) {
            $targetColumn = (string) ($fieldToColumnMap[$field] ?? '');
            if ($targetColumn !== '') {
              $setParts[] = $targetColumn . ' = :' . $field;
              $bindMap[$field] = $value;
            }
          }

          if ($enabledColumn !== '') {
            $setParts[] = $enabledColumn . ' = :enabled';
            $bindMap['enabled'] = $enabled;
          }
          if ($statusColumn !== '' && $statusColumn !== $enabledColumn) {
            $setParts[] = $statusColumn . ' = :status';
            $bindMap['status'] = $enabled;
          }
          if ($partnersHasColumn('updated_by')) {
            $setParts[] = 'updated_by = :updated_by';
            $bindMap['updated_by'] = $sessionUserId;
          }
          if ($iconUpload['path'] !== null && $partnerLogoIconColumn !== '') {
            $setParts[] = $partnerLogoIconColumn . ' = :logo_icon';
            $bindMap['logo_icon'] = (string) $iconUpload['path'];
          }
          if ($mainUpload['path'] !== null && $partnerLogoMainColumn !== '') {
            $setParts[] = $partnerLogoMainColumn . ' = :logo_main';
            $bindMap['logo_main'] = (string) $mainUpload['path'];
          }
          if ($contactPersonImageUpload['path'] !== null && $contactImageColumn !== '') {
            $setParts[] = $contactImageColumn . ' = :contact_person_img';
            $bindMap['contact_person_img'] = (string) $contactPersonImageUpload['path'];
          }

          if (!empty($setParts)) {
            $sql = 'UPDATE companies SET ' . implode(', ', $setParts) . ' WHERE id = :id AND company_type = \'partner\'' . $whereDeleted;
            $stmt = $pdo->prepare($sql);

            foreach ($bindMap as $key => $value) {
              if (in_array($key, $intFields, true)) {
                $stmt->bindValue(':' . $key, (int) $value, \PDO::PARAM_INT);
              } elseif ($key === 'updated_by') {
                $stmt->bindValue(':updated_by', $value, $value === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
              } elseif ($value === null) {
                $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
              } else {
                $stmt->bindValue(':' . $key, (string) $value);
              }
            }
            $stmt->bindValue(':id', $incomingId, \PDO::PARAM_INT);
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=partners&partner_edit_id=' . $incomingId . '&saved=updated#partner-tab-form');
            exit;
          } else {
            $alert = ['type' => 'danger', 'message' => 'Partner update failed: no writable columns found in companies table.'];
          }
        } else {
          $insertColumns = [];
          $insertPlaceholders = [];
          $bindMap = [];

          foreach ($valuesByField as $field => $value) {
            $targetColumn = (string) ($fieldToColumnMap[$field] ?? '');
            if ($targetColumn !== '') {
              $insertColumns[] = $targetColumn;
              $insertPlaceholders[] = ':' . $field;
              $bindMap[$field] = $value;
            }
          }

          if ($enabledColumn !== '') {
            $insertColumns[] = $enabledColumn;
            $insertPlaceholders[] = ':enabled';
            $bindMap['enabled'] = $enabled;
          }
          if ($statusColumn !== '' && $statusColumn !== $enabledColumn) {
            $insertColumns[] = $statusColumn;
            $insertPlaceholders[] = ':status';
            $bindMap['status'] = $enabled;
          }
          if ($partnersHasColumn('created_by')) {
            $insertColumns[] = 'created_by';
            $insertPlaceholders[] = ':created_by';
            $bindMap['created_by'] = $sessionUserId;
          }
          if ($partnersHasColumn('username')) {
            $insertColumns[] = 'username';
            $insertPlaceholders[] = ':username';
            $bindMap['username'] = 'partner_' . time() . '_' . random_int(100, 999);
          }
          if ($partnersHasColumn('password')) {
            $insertColumns[] = 'password';
            $insertPlaceholders[] = ':password';
            $bindMap['password'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
          }
          // Always set company_type to 'partner'
          $insertColumns[] = 'company_type';
          $insertPlaceholders[] = ':company_type';
          $bindMap['company_type'] = 'partner';
          if ($iconUpload['path'] !== null && $partnerLogoIconColumn !== '') {
            $insertColumns[] = $partnerLogoIconColumn;
            $insertPlaceholders[] = ':logo_icon';
            $bindMap['logo_icon'] = (string) $iconUpload['path'];
          }
          if ($mainUpload['path'] !== null && $partnerLogoMainColumn !== '') {
            $insertColumns[] = $partnerLogoMainColumn;
            $insertPlaceholders[] = ':logo_main';
            $bindMap['logo_main'] = (string) $mainUpload['path'];
          }
          if ($contactPersonImageUpload['path'] !== null && $contactImageColumn !== '') {
            $insertColumns[] = $contactImageColumn;
            $insertPlaceholders[] = ':contact_person_img';
            $bindMap['contact_person_img'] = (string) $contactPersonImageUpload['path'];
          }

          if (!empty($insertColumns)) {
            $sql = 'INSERT INTO companies (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertPlaceholders) . ')';
            $stmt = $pdo->prepare($sql);

            foreach ($bindMap as $key => $value) {
              if (in_array($key, $intFields, true)) {
                $stmt->bindValue(':' . $key, (int) $value, \PDO::PARAM_INT);
              } elseif (in_array($key, ['created_by'], true)) {
                $stmt->bindValue(':' . $key, $value, $value === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
              } elseif ($value === null) {
                $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
              } else {
                $stmt->bindValue(':' . $key, (string) $value);
              }
            }
            $stmt->execute();

            $newPartnerId = (int) $pdo->lastInsertId();
            header('Location: ' . $currentPath . '?tab=partners&partner_edit_id=' . $newPartnerId . '&saved=created#partner-tab-form');
            exit;
          }
        }
      }
    }

    $formData = array_merge($formData, [
      'id' => $incomingId,
      'email' => $email,
      'phone' => $phone,
      'company' => $company,
      'contact_person_img' => $contactPersonImageUpload['path'] !== null ? (string) $contactPersonImageUpload['path'] : $formData['contact_person_img'],
      'contact_person_name' => $contactPersonName,
      'contact_person_phone' => $contactPersonPhone,
      'contact_person_alt_phone' => $contactPersonAltPhone,
      'contact_person_email' => $contactPersonEmail,
      'address' => $address,
      'notes' => $notes,
      'user_type' => $user_type,
      'branch_access_type' => $branch_access_type,
      'partner_access_type' => $partner_access_type,
      'parentId' => $parentId_field,
      'partnerId' => $partnerRef,
      'departmentId' => $departmentId,
      'roleId' => $roleId,
      'enabled' => $enabled,
    ]);
  }

  // ── Save branch tab (insert / update) ──────────────────────────────────
  if ($action === 'save_branch') {
    $incomingBranchId = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : 0;
    $branchName = trim((string) ($_POST['branch_name'] ?? ''));
    $branchDisplayCompany = trim((string) ($_POST['display_company'] ?? ''));
    $branchPartnerId = isset($_POST['partner_id']) ? (int) $_POST['partner_id'] : 0;
    $branchLocationId = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
    $branchEmail = trim((string) ($_POST['email'] ?? ''));
    $branchMobile = trim((string) ($_POST['mobile'] ?? ''));
    $branchContactName = trim((string) ($_POST['contact_person_name'] ?? ''));
    $branchContactPhone = trim((string) ($_POST['contact_person_phone'] ?? ''));
    $branchContactAltPhone = trim((string) ($_POST['contact_person_alt_phone'] ?? ''));
    $branchContactEmail = trim((string) ($_POST['contact_person_email'] ?? ''));
    $branchAddress = trim((string) ($_POST['address'] ?? ''));
    $branchType = trim((string) ($_POST['branch_type'] ?? ''));
    $branchRatio = is_numeric((string) ($_POST['ratio'] ?? '')) ? (float) $_POST['ratio'] : 0.0;
    $branchStatus = isset($_POST['status']) ? 1 : 0;

    if ($branchName === '') {
      $alert = ['type' => 'danger', 'message' => 'Branch name is required.'];
    }

    if ($alert === null) {
      $branchesTableExistsStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
      );
      $branchesTableExistsStmt->bindValue(':table', 'branches');
      $branchesTableExistsStmt->execute();
      $branchesTableExists = (int) $branchesTableExistsStmt->fetchColumn() > 0;

      if (!$branchesTableExists) {
        $pdo->exec(
          "CREATE TABLE IF NOT EXISTS branches (
                  branch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  partner_id BIGINT NULL,
                  location_id BIGINT UNSIGNED NULL,
                  branch_name VARCHAR(150) NOT NULL,
                  branch_type VARCHAR(60) NULL,
                  email VARCHAR(190) NULL,
                  mobile VARCHAR(30) NULL,
                  contact_person_name VARCHAR(200) NULL,
                  contact_person_phone VARCHAR(200) NULL,
                  contact_person_alt_phone VARCHAR(200) NULL,
                  contact_person_email VARCHAR(300) NULL,
                  display_company VARCHAR(200) NULL,
                  address TEXT NULL,
                  notes TEXT NULL,
                  ratio DECIMAL(5,2) NULL DEFAULT 0.00,
                  status TINYINT(1) NOT NULL DEFAULT 1,
                  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
      }

      $branchesHasColumn = static function (string $column) use ($ispts_has_column, $pdo): bool {
        return $ispts_has_column($pdo, 'branches', $column);
      };
      $branchesLegacySafeColumns = [
        'partner_id' => "ALTER TABLE branches ADD COLUMN partner_id BIGINT NULL",
        'location_id' => "ALTER TABLE branches ADD COLUMN location_id BIGINT UNSIGNED NULL",
        'branch_name' => "ALTER TABLE branches ADD COLUMN branch_name VARCHAR(150) NOT NULL DEFAULT ''",
        'branch_type' => "ALTER TABLE branches ADD COLUMN branch_type VARCHAR(60) NULL",
        'email' => "ALTER TABLE branches ADD COLUMN email VARCHAR(190) NULL",
        'mobile' => "ALTER TABLE branches ADD COLUMN mobile VARCHAR(30) NULL",
        'contact_person_name' => "ALTER TABLE branches ADD COLUMN contact_person_name VARCHAR(200) NULL",
        'contact_person_phone' => "ALTER TABLE branches ADD COLUMN contact_person_phone VARCHAR(200) NULL",
        'contact_person_alt_phone' => "ALTER TABLE branches ADD COLUMN contact_person_alt_phone VARCHAR(200) NULL",
        'contact_person_email' => "ALTER TABLE branches ADD COLUMN contact_person_email VARCHAR(300) NULL",
        'display_company' => "ALTER TABLE branches ADD COLUMN display_company VARCHAR(200) NULL",
        'address' => "ALTER TABLE branches ADD COLUMN address TEXT NULL",
        'notes' => "ALTER TABLE branches ADD COLUMN notes TEXT NULL",
        'ratio' => "ALTER TABLE branches ADD COLUMN ratio DECIMAL(5,2) NULL DEFAULT 0.00",
        'status' => "ALTER TABLE branches ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1",
      ];
      foreach ($branchesLegacySafeColumns as $column => $sql) {
        if (!$branchesHasColumn($column)) {
          $pdo->exec($sql);
        }
      }

      $branchesResolveColumn = static function (array $candidates) use ($branchesHasColumn): string {
        foreach ($candidates as $candidate) {
          if ($branchesHasColumn($candidate)) {
            return $candidate;
          }
        }

        return '';
      };

      $branchIdColumn = $branchesResolveColumn(['branch_id', 'id']);
      $branchNameColumn = $branchesResolveColumn(['branch_name', 'name']);
      $branchPartnerColumn = $branchesResolveColumn(['partner_id', 'partnerId']);
      $branchLocationColumn = $branchesResolveColumn(['location_id', 'locationId']);
      $branchTypeColumn = $branchesResolveColumn(['branch_type', 'type']);
      $branchEmailColumn = $branchesResolveColumn(['email']);
      $branchMobileColumn = $branchesResolveColumn(['mobile', 'phone']);
      $branchContactNameColumn = $branchesResolveColumn(['contact_person_name']);
      $branchContactPhoneColumn = $branchesResolveColumn(['contact_person_phone']);
      $branchContactAltPhoneColumn = $branchesResolveColumn(['contact_person_alt_phone']);
      $branchContactEmailColumn = $branchesResolveColumn(['contact_person_email']);
      $branchDisplayCompanyColumn = $branchesResolveColumn(['display_company']);
      $branchAddressColumn = $branchesResolveColumn(['address']);
      $branchRatioColumn = $branchesResolveColumn(['ratio']);
      $branchStatusColumn = $branchesResolveColumn(['status', 'enabled']);

      if ($branchIdColumn === '' || $branchNameColumn === '') {
        $alert = ['type' => 'danger', 'message' => 'Branches table schema is invalid (missing ID or name columns).'];
      } else {
        $branchValuesByField = [
          'branch_name' => $branchName,
          'partner_id' => $branchPartnerId > 0 ? $branchPartnerId : null,
          'location_id' => $branchLocationId > 0 ? $branchLocationId : null,
          'display_company' => $branchDisplayCompany !== '' ? $branchDisplayCompany : null,
          'branch_type' => $branchType !== '' ? $branchType : null,
          'email' => $branchEmail !== '' ? $branchEmail : null,
          'mobile' => $branchMobile !== '' ? $branchMobile : null,
          'contact_person_name' => $branchContactName !== '' ? $branchContactName : null,
          'contact_person_phone' => $branchContactPhone !== '' ? $branchContactPhone : null,
          'contact_person_alt_phone' => $branchContactAltPhone !== '' ? $branchContactAltPhone : null,
          'contact_person_email' => $branchContactEmail !== '' ? $branchContactEmail : null,
          'address' => $branchAddress !== '' ? $branchAddress : null,
          'ratio' => $branchRatio,
          'status' => $branchStatus,
        ];
        $branchFieldToColumnMap = [
          'branch_name' => $branchNameColumn,
          'partner_id' => $branchPartnerColumn,
          'location_id' => $branchLocationColumn,
          'display_company' => $branchDisplayCompanyColumn,
          'branch_type' => $branchTypeColumn,
          'email' => $branchEmailColumn,
          'mobile' => $branchMobileColumn,
          'contact_person_name' => $branchContactNameColumn,
          'contact_person_phone' => $branchContactPhoneColumn,
          'contact_person_alt_phone' => $branchContactAltPhoneColumn,
          'contact_person_email' => $branchContactEmailColumn,
          'address' => $branchAddressColumn,
          'ratio' => $branchRatioColumn,
          'status' => $branchStatusColumn,
        ];
        $branchIntFields = ['partner_id', 'location_id', 'status'];
        $branchFloatFields = ['ratio'];

        if ($incomingBranchId > 0) {
          $setParts = [];
          $bindMap = [];
          foreach ($branchValuesByField as $field => $value) {
            $targetColumn = (string) ($branchFieldToColumnMap[$field] ?? '');
            if ($targetColumn !== '') {
              $setParts[] = $targetColumn . ' = :' . $field;
              $bindMap[$field] = $value;
            }
          }

          if (!empty($setParts)) {
            $sql = 'UPDATE branches SET ' . implode(', ', $setParts) . ' WHERE ' . $branchIdColumn . ' = :branch_id';
            $stmt = $pdo->prepare($sql);
            foreach ($bindMap as $key => $value) {
              if (in_array($key, $branchIntFields, true)) {
                if ($value === null) {
                  $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
                } else {
                  $stmt->bindValue(':' . $key, (int) $value, \PDO::PARAM_INT);
                }
              } elseif (in_array($key, $branchFloatFields, true)) {
                $stmt->bindValue(':' . $key, (string) ((float) $value));
              } elseif ($value === null) {
                $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
              } else {
                $stmt->bindValue(':' . $key, (string) $value);
              }
            }
            $stmt->bindValue(':branch_id', $incomingBranchId, \PDO::PARAM_INT);
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=branches&saved=branch_updated');
            exit;
          }

          $alert = ['type' => 'danger', 'message' => 'Branch update failed: no writable columns found.'];
        } else {
          $insertColumns = [];
          $insertPlaceholders = [];
          $bindMap = [];
          foreach ($branchValuesByField as $field => $value) {
            $targetColumn = (string) ($branchFieldToColumnMap[$field] ?? '');
            if ($targetColumn !== '') {
              $insertColumns[] = $targetColumn;
              $insertPlaceholders[] = ':' . $field;
              $bindMap[$field] = $value;
            }
          }

          if (!empty($insertColumns)) {
            $sql = 'INSERT INTO branches (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertPlaceholders) . ')';
            $stmt = $pdo->prepare($sql);
            foreach ($bindMap as $key => $value) {
              if (in_array($key, $branchIntFields, true)) {
                if ($value === null) {
                  $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
                } else {
                  $stmt->bindValue(':' . $key, (int) $value, \PDO::PARAM_INT);
                }
              } elseif (in_array($key, $branchFloatFields, true)) {
                $stmt->bindValue(':' . $key, (string) ((float) $value));
              } elseif ($value === null) {
                $stmt->bindValue(':' . $key, null, \PDO::PARAM_NULL);
              } else {
                $stmt->bindValue(':' . $key, (string) $value);
              }
            }
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=branches&saved=branch_created');
            exit;
          }

          $alert = ['type' => 'danger', 'message' => 'Branch save failed: no writable columns found.'];
        }
      }
    }

    $branchFormData = array_merge($branchFormData, [
      'branch_id' => $incomingBranchId,
      'branch_name' => $branchName,
      'display_company' => $branchDisplayCompany,
      'partner_id' => $branchPartnerId,
      'location_id' => $branchLocationId,
      'email' => $branchEmail,
      'mobile' => $branchMobile,
      'contact_person_name' => $branchContactName,
      'contact_person_phone' => $branchContactPhone,
      'contact_person_alt_phone' => $branchContactAltPhone,
      'contact_person_email' => $branchContactEmail,
      'address' => $branchAddress,
      'branch_type' => $branchType,
      'ratio' => (string) $branchRatio,
      'status' => $branchStatus,
    ]);
  }

  // ── Toggle active/inactive ───────────────────────────────────────────────
  if ($action === 'toggle_company_status') {
    $toggleId = isset($_POST['partner_id']) ? (int) $_POST['partner_id'] : 0;
    $targetEnabled = isset($_POST['target_enabled']) && (int) $_POST['target_enabled'] === 1 ? 1 : 0;

    if ($toggleId > 0) {
      $toggleStmt = $pdo->prepare(
        'UPDATE companies SET ' . $partnerEnabledColumn . ' = :enabled, status = :status WHERE ' . $partnerIdColumn . ' = :id AND ' . $partnerDeletedCondition
      );
      $toggleStmt->bindValue(':enabled', $targetEnabled, \PDO::PARAM_INT);
      $toggleStmt->bindValue(':status', $targetEnabled, \PDO::PARAM_INT);
      $toggleStmt->bindValue(':id', $toggleId, \PDO::PARAM_INT);
      $toggleStmt->execute();
    }

    header('Location: ' . $currentPath . '?saved=status');
    exit;
  }
}

// ── Flash messages ───────────────────────────────────────────────────────────
if ($alert === null) {
  $savedFlag = (string) ($_GET['saved'] ?? '');
  if ($savedFlag === 'created') {
    $alert = ['type' => 'success', 'message' => 'Partner added successfully.'];
  } elseif ($savedFlag === 'updated') {
    $alert = ['type' => 'success', 'message' => 'Partner updated successfully.'];
  } elseif ($savedFlag === 'branch_created') {
    $alert = ['type' => 'success', 'message' => 'Branch added successfully.'];
  } elseif ($savedFlag === 'branch_updated') {
    $alert = ['type' => 'success', 'message' => 'Branch updated successfully.'];
  } elseif ($savedFlag === 'status') {
    $alert = ['type' => 'success', 'message' => 'Partner status updated.'];
  }
}

// ── Load edit row ────────────────────────────────────────────────────────────
if ($editId > 0) {
  $editStmt = $pdo->prepare(
    'SELECT ' . $partnerIdColumn . ' AS id, firstname, lastname, username, email, phone, company,
                logo_icon, logo_main, ' . $partnerContactImageSelect . ', contact_person_name, contact_person_phone, contact_person_alt_phone, contact_person_email,
                address, notes,
                user_type, branch_access_type, partner_access_type, parentId, partnerId,
                departmentId, roleId, enabled
         FROM companies
       WHERE ' . $partnerIdColumn . ' = :id AND company_type = \'mother\' AND ' . $partnerDeletedCondition . '
         LIMIT 1'
  );
  $editStmt->bindValue(':id', $editId, \PDO::PARAM_INT);
  $editStmt->execute();
  $editRow = $editStmt->fetch(\PDO::FETCH_ASSOC);

  if ($editRow) {
    $formData = [
      'id' => (int) $editRow['id'],
      'firstname' => (string) ($editRow['firstname'] ?? ''),
      'lastname' => (string) ($editRow['lastname'] ?? ''),
      'username' => (string) $editRow['username'],
      'email' => (string) ($editRow['email'] ?? ''),
      'phone' => (string) ($editRow['phone'] ?? ''),
      'company' => (string) ($editRow['company'] ?? ''),
      'logo_icon' => (string) ($editRow['logo_icon'] ?? ''),
      'logo_main' => (string) ($editRow['logo_main'] ?? ''),
      'contact_person_img' => (string) ($editRow['contact_person_img'] ?? ''),
      'contact_person_name' => (string) ($editRow['contact_person_name'] ?? ''),
      'contact_person_phone' => (string) ($editRow['contact_person_phone'] ?? ''),
      'contact_person_alt_phone' => (string) ($editRow['contact_person_alt_phone'] ?? ''),
      'contact_person_email' => (string) ($editRow['contact_person_email'] ?? ''),
      'address' => (string) ($editRow['address'] ?? ''),
      'notes' => (string) ($editRow['notes'] ?? ''),
      'user_type' => (int) ($editRow['user_type'] ?? 1),
      'branch_access_type' => (string) ($editRow['branch_access_type'] ?? '0'),
      'partner_access_type' => (string) ($editRow['partner_access_type'] ?? '0'),
      'parentId' => (int) ($editRow['parentId'] ?? 0),
      'partnerId' => (int) ($editRow['partnerId'] ?? 0),
      'departmentId' => (int) ($editRow['departmentId'] ?? 0),
      'roleId' => (int) ($editRow['roleId'] ?? 0),
      'enabled' => (int) ($editRow['enabled'] ?? 1),
    ];
  }
}

// ── Lookups ──────────────────────────────────────────────────────────────────
$activePartnersStmt = $pdo->prepare(
  'SELECT ' . $partnerIdColumn . ' AS id, firstname, lastname, username, company
     FROM companies
   WHERE company_type = \'mother\' AND ' . $partnerEnabledColumn . ' = 1 AND ' . $partnerDeletedCondition
  . ($formData['id'] > 0 ? ' AND ' . $partnerIdColumn . ' != :self_id' : '')
  . ' ORDER BY ' . $partnerIdColumn . ' ASC'
);
if ($formData['id'] > 0) {
  $activePartnersStmt->bindValue(':self_id', $formData['id'], \PDO::PARAM_INT);
}
$activePartnersStmt->execute();
$activePartners = $activePartnersStmt->fetchAll(\PDO::FETCH_ASSOC);

$activeRolesStmt = $pdo->query(
  'SELECT role_id, role_name FROM roles WHERE status = 1 ORDER BY role_name ASC'
);
$activeRoles = $activeRolesStmt->fetchAll(\PDO::FETCH_ASSOC);

$partnersStmt = $pdo->query(
  'SELECT p.' . $partnerIdColumn . ' AS id, p.firstname, p.lastname, p.username, p.email, p.phone,
  p.company, p.logo_icon, p.logo_main, ' . $partnerContactImageSelectAliased . ', p.contact_person_name, p.contact_person_phone,
  p.contact_person_alt_phone, p.contact_person_email, p.address, p.notes,
  p.branch_access_type, p.partner_access_type, p.departmentId, p.partnerId,
  p.' . $partnerEnabledColumn . ' AS enabled, p.user_type, p.last_login, p.created_at,
            r.role_name,
            pp.username AS parent_username
     FROM companies p
   LEFT JOIN roles      r  ON r.role_id  = p.' . $partnerRoleColumn . '
   LEFT JOIN companies pp ON pp.' . $partnerIdColumn . ' = p.' . $partnerParentColumn . '
  WHERE p.company_type = \'mother\' AND ' . $partnerDeletedConditionAliased . '
   ORDER BY p.' . $partnerIdColumn . ' DESC'
);
$partners = $partnersStmt->fetchAll(\PDO::FETCH_ASSOC);

$userTypeLabels = [1 => 'Standard', 2 => 'Premium', 3 => 'Reseller'];

$partnerTabRows = [];
$partnerTabParentOptions = [];
$partnerTabFormData = $formData;
try {
  $partnerTabRowsStmt = $pdo->query(
    'SELECT p.*, p.id AS id, p.enabled AS enabled, p.user_type AS user_type, p.last_login AS last_login,
        r.role_name,
        pp.username AS parent_username
     FROM companies p
     LEFT JOIN roles r ON r.role_id = p.roleId
     LEFT JOIN companies pp ON pp.id = p.parentId
     WHERE p.company_type = \'partner\' AND p.deleted_at IS NULL
     ORDER BY p.id DESC'
  );
  $partnerTabRows = $partnerTabRowsStmt ? $partnerTabRowsStmt->fetchAll(\PDO::FETCH_ASSOC) : [];

  $partnerTabParentStmt = $pdo->query(
    'SELECT id, firstname, lastname, username, company, company_type
     FROM companies
     WHERE company_type IN (\'mother\', \'partner\') AND enabled = 1 AND deleted_at IS NULL
     ORDER BY company_type ASC, id ASC'
  );
  $partnerTabParentOptions = $partnerTabParentStmt ? $partnerTabParentStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
} catch (\Throwable $e) {
  $partnerTabRows = [];
  $partnerTabParentOptions = [];
}

$branchPartnerOptions = [];
if (!empty($partnerTabParentOptions)) {
  $branchPartnerOptions = $partnerTabParentOptions;
} else {
  $branchPartnerOptions = $activePartners;
}

$branchLocationOptions = [];
try {
  $locationsTableExistsStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
  );
  $locationsTableExistsStmt->bindValue(':table', 'locations');
  $locationsTableExistsStmt->execute();
  $locationsTableExists = (int) $locationsTableExistsStmt->fetchColumn() > 0;

  if ($locationsTableExists) {
    $branchLocationsStmt = $pdo->query(
      "SELECT l.location_id, l.location_name,
              p.location_name AS parent_location_name,
              d.location_name AS district_location_name
         FROM locations l
    LEFT JOIN locations p ON p.location_id = l.parent_location_id
    LEFT JOIN locations d ON d.location_id = p.parent_location_id
        WHERE l.status = 1 AND l.location_type = 'sub-area'
        ORDER BY d.location_name ASC, p.location_name ASC, l.location_name ASC"
    );
    $branchLocationOptions = $branchLocationsStmt ? $branchLocationsStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
  }
} catch (\Throwable $e) {
  $branchLocationOptions = [];
}

$branchListRows = [];
try {
  $branchesTableExistsStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
  );
  $branchesTableExistsStmt->bindValue(':table', 'branches');
  $branchesTableExistsStmt->execute();
  $branchesTableExists = (int) $branchesTableExistsStmt->fetchColumn() > 0;

  if ($branchesTableExists) {
    $branchesHasColumn = static function (string $column) use ($ispts_has_column, $pdo): bool {
      return $ispts_has_column($pdo, 'branches', $column);
    };
    $branchesResolveColumn = static function (array $candidates) use ($branchesHasColumn): string {
      foreach ($candidates as $candidate) {
        if ($branchesHasColumn($candidate)) {
          return $candidate;
        }
      }

      return '';
    };

    $branchIdColumn = $branchesResolveColumn(['branch_id', 'id']);
    $branchPartnerRefColumn = $branchesResolveColumn(['partner_id', 'partnerId']);
    $branchLocationRefColumn = $branchesResolveColumn(['location_id', 'locationId']);
    $branchNameColumn = $branchesResolveColumn(['branch_name', 'name']);
    $branchTypeColumn = $branchesResolveColumn(['branch_type', 'type']);
    $branchEmailColumn = $branchesResolveColumn(['email']);
    $branchMobileColumn = $branchesResolveColumn(['mobile', 'phone']);
    $branchAddressColumn = $branchesResolveColumn(['address']);
    $branchRatioColumn = $branchesResolveColumn(['ratio']);
    $branchStatusColumn = $branchesResolveColumn(['status', 'enabled']);
    $branchNotesColumn = $branchesResolveColumn(['notes']);
    $branchDisplayCompanyColumn = $branchesResolveColumn(['display_company']);
    $branchContactNameColumn = $branchesResolveColumn(['contact_person_name']);
    $branchContactPhoneColumn = $branchesResolveColumn(['contact_person_phone']);
    $branchContactAltPhoneColumn = $branchesResolveColumn(['contact_person_alt_phone']);
    $branchContactEmailColumn = $branchesResolveColumn(['contact_person_email']);

    $partnersTableExistsStmt = $pdo->prepare(
      'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $partnersTableExistsStmt->bindValue(':table', 'partners');
    $partnersTableExistsStmt->execute();
    $partnersTableExists = (int) $partnersTableExistsStmt->fetchColumn() > 0;

    $companiesTableExistsStmt = $pdo->prepare(
      'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $companiesTableExistsStmt->bindValue(':table', 'companies');
    $companiesTableExistsStmt->execute();
    $companiesTableExists = (int) $companiesTableExistsStmt->fetchColumn() > 0;

    $targetPartnerTable = $partnersTableExists ? 'partners' : ($companiesTableExists ? 'companies' : '');

    $partnersResolveColumn = static function (array $candidates) use ($ispts_has_column, $pdo, $targetPartnerTable): string {
      if ($targetPartnerTable === '') return '';
      foreach ($candidates as $candidate) {
        if ($ispts_has_column($pdo, $targetPartnerTable, $candidate)) {
          return $candidate;
        }
      }

      return '';
    };

    $partnerIdJoinColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['id', 'partner_id']) : '';
    $partnerCompanyColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['company', 'partner_name']) : '';
    $partnerFirstNameColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['firstname', 'first_name']) : '';
    $partnerLastNameColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['lastname', 'last_name']) : '';
    $partnerUsernameColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['username']) : '';
    $partnerPhoneColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['phone', 'mobile']) : '';
    $partnerEmailColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['email']) : '';
    $partnerAddressColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['address']) : '';
    $partnerLogoMainColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['logo_main', 'main_logo']) : '';
    $partnerContactImageColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['contact_person_img', 'avatar']) : '';
    $partnerContactNameColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['contact_person_name']) : '';
    $partnerContactPhoneColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['contact_person_phone']) : '';
    $partnerContactAltPhoneColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['contact_person_alt_phone']) : '';
    $partnerContactEmailColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['contact_person_email']) : '';
    $partnerBranchAccessColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['branch_access_type']) : '';
    $partnerAccessColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['partner_access_type']) : '';
    $partnerUserTypeColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['user_type']) : '';
    $partnerRoleIdColumn = $targetPartnerTable !== '' ? $partnersResolveColumn(['roleId', 'role_id']) : '';
    $partnerDeletedCondition = ($targetPartnerTable !== '' && $ispts_has_column($pdo, $targetPartnerTable, 'deleted_at')) ? ' AND p.deleted_at IS NULL' : '';

    $rolesTableExistsStmt = $pdo->prepare(
      'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $rolesTableExistsStmt->bindValue(':table', 'roles');
    $rolesTableExistsStmt->execute();
    $rolesTableExists = (int) $rolesTableExistsStmt->fetchColumn() > 0;

    $branchSelectOrDefault = static function (string $column, string $alias): string {
      return $column !== '' ? ('b.' . $column . ' AS ' . $alias) : ("'' AS " . $alias);
    };
    $partnerSelectOrDefault = static function (string $column, string $alias): string {
      return $column !== '' ? ('p.' . $column . ' AS ' . $alias) : ("'' AS " . $alias);
    };

    $locationsJoin = '';
    $locationSelect = "'' AS location_name";
    if ($locationsTableExists && $branchLocationRefColumn !== '') {
      $locationsJoin = ' LEFT JOIN locations l ON l.location_id = b.' . $branchLocationRefColumn;
      $locationSelect = 'l.location_name AS location_name';
    }

    $partnerJoin = '';
    if ($targetPartnerTable !== '' && $branchPartnerRefColumn !== '' && $partnerIdJoinColumn !== '') {
      $partnerJoin = ' LEFT JOIN ' . $targetPartnerTable . ' p ON p.' . $partnerIdJoinColumn . ' = b.' . $branchPartnerRefColumn;
      if ($targetPartnerTable === 'companies') {
        $partnerJoin .= ' AND p.company_type = \'partner\'';
      }
      $partnerJoin .= $partnerDeletedCondition;
    }

    $rolesJoin = '';
    $roleNameSelect = "'' AS partner_role_name";
    if ($rolesTableExists && $partnerRoleIdColumn !== '') {
      $rolesJoin = ' LEFT JOIN roles r ON r.role_id = p.' . $partnerRoleIdColumn;
      $roleNameSelect = 'r.role_name AS partner_role_name';
    }

    $selectParts = [
      $branchSelectOrDefault($branchIdColumn, 'branch_id'),
      $branchSelectOrDefault($branchNameColumn, 'branch_name'),
      $branchSelectOrDefault($branchTypeColumn, 'branch_type'),
      $branchSelectOrDefault($branchEmailColumn, 'branch_email'),
      $branchSelectOrDefault($branchMobileColumn, 'branch_mobile'),
      $branchSelectOrDefault($branchAddressColumn, 'branch_address'),
      $branchSelectOrDefault($branchRatioColumn, 'branch_ratio'),
      $branchSelectOrDefault($branchStatusColumn, 'branch_status'),
      $branchSelectOrDefault($branchNotesColumn, 'branch_notes'),
      $branchSelectOrDefault($branchDisplayCompanyColumn, 'branch_display_company'),
      $branchSelectOrDefault($branchContactNameColumn, 'branch_contact_person_name'),
      $branchSelectOrDefault($branchContactPhoneColumn, 'branch_contact_person_phone'),
      $branchSelectOrDefault($branchContactAltPhoneColumn, 'branch_contact_person_alt_phone'),
      $branchSelectOrDefault($branchContactEmailColumn, 'branch_contact_person_email'),
      $partnerSelectOrDefault($partnerCompanyColumn, 'partner_company'),
      $partnerSelectOrDefault($partnerFirstNameColumn, 'partner_firstname'),
      $partnerSelectOrDefault($partnerLastNameColumn, 'partner_lastname'),
      $partnerSelectOrDefault($partnerUsernameColumn, 'partner_username'),
      $partnerSelectOrDefault($partnerPhoneColumn, 'partner_phone'),
      $partnerSelectOrDefault($partnerEmailColumn, 'partner_email'),
      $partnerSelectOrDefault($partnerAddressColumn, 'partner_address'),
      $partnerSelectOrDefault($partnerLogoMainColumn, 'partner_logo_main'),
      $partnerSelectOrDefault($partnerContactImageColumn, 'partner_contact_person_img'),
      $partnerSelectOrDefault($partnerContactNameColumn, 'partner_contact_person_name'),
      $partnerSelectOrDefault($partnerContactPhoneColumn, 'partner_contact_person_phone'),
      $partnerSelectOrDefault($partnerContactAltPhoneColumn, 'partner_contact_person_alt_phone'),
      $partnerSelectOrDefault($partnerContactEmailColumn, 'partner_contact_person_email'),
      $partnerSelectOrDefault($partnerBranchAccessColumn, 'partner_branch_access_type'),
      $partnerSelectOrDefault($partnerAccessColumn, 'partner_access_type'),
      $partnerSelectOrDefault($partnerUserTypeColumn, 'partner_user_type'),
      $roleNameSelect,
      $locationSelect,
    ];

    $branchOrderColumn = $branchIdColumn !== '' ? ('b.' . $branchIdColumn) : 'b.created_at';
    $branchListStmt = $pdo->query(
      'SELECT ' . implode(', ', $selectParts)
      . ' FROM branches b'
      . $partnerJoin
      . $rolesJoin
      . $locationsJoin
      . ' ORDER BY ' . $branchOrderColumn . ' DESC'
    );
    $branchListRows = $branchListStmt ? $branchListStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
  }
} catch (\Throwable $e) {
  $branchListRows = [];
}

if ($activeTab === 'branches' && $branchTabEditId > 0) {
  try {
    $branchesTableExistsStmt = $pdo->prepare(
      'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $branchesTableExistsStmt->bindValue(':table', 'branches');
    $branchesTableExistsStmt->execute();
    $branchesTableExists = (int) $branchesTableExistsStmt->fetchColumn() > 0;

    if ($branchesTableExists) {
      $branchEditIdColumn = $ispts_has_column($pdo, 'branches', 'branch_id') ? 'branch_id' : ($ispts_has_column($pdo, 'branches', 'id') ? 'id' : '');
      if ($branchEditIdColumn !== '') {
        $branchEditStmt = $pdo->prepare('SELECT * FROM branches WHERE ' . $branchEditIdColumn . ' = :id LIMIT 1');
        $branchEditStmt->bindValue(':id', $branchTabEditId, \PDO::PARAM_INT);
        $branchEditStmt->execute();
        $branchEditRow = $branchEditStmt->fetch(\PDO::FETCH_ASSOC);

        if ($branchEditRow) {
          $branchFormData = array_merge($branchFormData, [
            'branch_id' => (int) ($branchEditRow[$branchEditIdColumn] ?? 0),
            'branch_name' => (string) (($branchEditRow['branch_name'] ?? $branchEditRow['name'] ?? '') ?: ''),
            'display_company' => (string) (($branchEditRow['display_company'] ?? '') ?: ''),
            'partner_id' => (int) (($branchEditRow['partner_id'] ?? $branchEditRow['partnerId'] ?? 0) ?: 0),
            'location_id' => (int) (($branchEditRow['location_id'] ?? $branchEditRow['locationId'] ?? 0) ?: 0),
            'email' => (string) (($branchEditRow['email'] ?? '') ?: ''),
            'mobile' => (string) (($branchEditRow['mobile'] ?? $branchEditRow['phone'] ?? '') ?: ''),
            'contact_person_name' => (string) (($branchEditRow['contact_person_name'] ?? '') ?: ''),
            'contact_person_phone' => (string) (($branchEditRow['contact_person_phone'] ?? '') ?: ''),
            'contact_person_alt_phone' => (string) (($branchEditRow['contact_person_alt_phone'] ?? '') ?: ''),
            'contact_person_email' => (string) (($branchEditRow['contact_person_email'] ?? '') ?: ''),
            'address' => (string) (($branchEditRow['address'] ?? '') ?: ''),
            'branch_type' => (string) (($branchEditRow['branch_type'] ?? $branchEditRow['type'] ?? '') ?: ''),
            'ratio' => (string) (($branchEditRow['ratio'] ?? '0') ?: '0'),
            'status' => (int) (($branchEditRow['status'] ?? $branchEditRow['enabled'] ?? 1) ?: 0),
          ]);
        }
      }
    }
  } catch (\Throwable $e) {
  }
}

if ($activeTab === 'partners' && $partnerTabEditId > 0) {
  try {
    $partnerTabEditStmt = $pdo->prepare(
      'SELECT *
         FROM companies
        WHERE id = :id AND company_type = \'partner\' AND deleted_at IS NULL
        LIMIT 1'
    );
    $partnerTabEditStmt->bindValue(':id', $partnerTabEditId, \PDO::PARAM_INT);
    $partnerTabEditStmt->execute();
    $partnerTabEditRow = $partnerTabEditStmt->fetch(\PDO::FETCH_ASSOC);

    if ($partnerTabEditRow) {
      $partnerTabFormData = array_merge($partnerTabFormData, [
        'id' => (int) ($partnerTabEditRow['id'] ?? 0),
        'firstname' => (string) ($partnerTabEditRow['firstname'] ?? ''),
        'lastname' => (string) ($partnerTabEditRow['lastname'] ?? ''),
        'username' => (string) ($partnerTabEditRow['username'] ?? ''),
        'email' => (string) ($partnerTabEditRow['email'] ?? ''),
        'phone' => (string) ($partnerTabEditRow['phone'] ?? ''),
        'company' => (string) ($partnerTabEditRow['company'] ?? ''),
        'logo_icon' => (string) (($partnerTabEditRow['logo_icon'] ?? $partnerTabEditRow['icon_logo'] ?? '') ?: ''),
        'logo_main' => (string) (($partnerTabEditRow['logo_main'] ?? $partnerTabEditRow['main_logo'] ?? '') ?: ''),
        'contact_person_img' => (string) (($partnerTabEditRow['contact_person_img'] ?? $partnerTabEditRow['avatar'] ?? '') ?: ''),
        'contact_person_name' => (string) ($partnerTabEditRow['contact_person_name'] ?? ''),
        'contact_person_phone' => (string) ($partnerTabEditRow['contact_person_phone'] ?? ''),
        'contact_person_alt_phone' => (string) ($partnerTabEditRow['contact_person_alt_phone'] ?? ''),
        'contact_person_email' => (string) ($partnerTabEditRow['contact_person_email'] ?? ''),
        'address' => (string) ($partnerTabEditRow['address'] ?? ''),
        'notes' => (string) ($partnerTabEditRow['notes'] ?? ''),
        'user_type' => (int) ($partnerTabEditRow['user_type'] ?? 1),
        'branch_access_type' => (string) ($partnerTabEditRow['branch_access_type'] ?? '0'),
        'partner_access_type' => (string) ($partnerTabEditRow['partner_access_type'] ?? '0'),
        'parentId' => (int) ($partnerTabEditRow['parentId'] ?? 0),
        'partnerId' => (int) ($partnerTabEditRow['partnerId'] ?? 0),
        'departmentId' => (int) ($partnerTabEditRow['departmentId'] ?? 0),
        'roleId' => (int) ($partnerTabEditRow['roleId'] ?? 0),
        'enabled' => (int) ($partnerTabEditRow['enabled'] ?? 1),
      ]);
    }
  } catch (\Throwable $e) {
  }
}

$showCompanyEditForm = $activeTab === 'company';

require '../../includes/header.php';
?>
<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">My Company</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage company profiles, contact details, access settings, and branding from one place.
        </p>
      </div>
    </div>
  </div>

  <?php if ($alert): ?>
    <div class="col-12">
      <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
        <?= htmlspecialchars($alert['message']) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12">
    <ul class="nav nav-pills mb-3">
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?>"
          href="<?= $appBasePath ?>/app/administration/my-company.php?tab=company">My Company</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'partners' ? 'active' : '' ?>"
          href="<?= $appBasePath ?>/app/administration/my-company.php?tab=partners">Partner Companies</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'branches' ? 'active' : '' ?>"
          href="<?= $appBasePath ?>/app/administration/my-company.php?tab=branches">Branches</a></li>
    </ul>
  </div>

  <?php if ($activeTab === 'company'): ?>
    <div class="<?= $showCompanyEditForm ? 'col-xl-6' : 'col-xl-6' ?>" id="company-list">
      <div class="card h-100 mb-3">
        <div class="card-header bg-body-tertiary d-flex flex-between-center py-2">
          <h6 class="mb-0">Mother Company</h6>
          <?php $motherCompanyId = $editId > 0 ? $editId : (isset($partners[0]['id']) ? (int) $partners[0]['id'] : 0); ?>
          <?php
          $motherCompanyEnabled = null;
          if ((int) ($formData['id'] ?? 0) > 0) {
            $motherCompanyEnabled = (int) ($formData['enabled'] ?? 0);
          } elseif (isset($partners[0]['enabled'])) {
            $motherCompanyEnabled = (int) $partners[0]['enabled'];
          }
          ?>
          <div class="dropdown font-sans-serif position-static d-inline-block btn-reveal-trigger">
            <?php if ($motherCompanyEnabled !== null): ?>
              <small
                class="badge rounded <?= $motherCompanyEnabled === 1 ? 'badge-subtle-success' : 'badge-subtle-danger' ?> me-2">
                <?= $motherCompanyEnabled === 1 ? 'Active' : 'Inactive' ?>
              </small>
            <?php endif; ?>
            <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal dropdown-caret-none" type="button"
              id="dropdown-mother-company" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
              aria-expanded="false" data-bs-reference="parent">
              <span class="fas fa-ellipsis-h fs-10"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-mother-company">
              <a class="dropdown-item"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=company#partner-form">View</a>
              <a class="dropdown-item"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=company<?= $motherCompanyId > 0 ? '&edit_id=' . $motherCompanyId : '' ?>#partner-form">Edit</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-danger"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=company">Refresh</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3 h-100">
            <?php if (empty($partners)): ?>
              <div class="col-12">
                <div class="text-center py-3 text-600">No company profile found.</div>
              </div>
            <?php else: ?>
              <?php foreach ($partners as $p): ?>
                <?php
                $fullName = trim(((string) ($p['firstname'] ?? '')) . ' ' . ((string) ($p['lastname'] ?? '')));
                $iconLogoUrl = !empty($p['logo_icon']) ? $appBasePath . '/' . ltrim((string) $p['logo_icon'], '/') : '';
                $mainLogoUrl = !empty($p['logo_main']) ? $appBasePath . '/' . ltrim((string) $p['logo_main'], '/') : '';
                $contactPersonImageUrl = !empty($p['contact_person_img']) ? $appBasePath . '/' . ltrim((string) $p['contact_person_img'], '/') : '';
                ?>
                <div class="col-sm-6 col-lg-12">
                  <div class="card position-relative rounded-4">
                    <div class="bg-holder bg-card rounded-4"
                      style="background-image:url(<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/icons/spot-illustrations/corner-2.png);">
                    </div>
                    <div class="card-body p-3 pt-4 pt-xxl-4">
                      <!-- Row 1: Only logo -->
                      <div class="row mb-2">
                        <div class="col text-end d-flex justify-content-end">
                          <?php if ($mainLogoUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($mainLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Main logo" height="72"
                              style="max-width: 240px; object-fit: contain;">
                          <?php else: ?>
                            <span class="fas fa-building text-primary fs-4"></span>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Row 2: Contact image (2), contact details (4), company info (6) -->
                      <div class="row align-items-stretch">


                        <div class="col-2 text-end">
                          <?php if ($contactPersonImageUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($contactPersonImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Contact person"
                              style="width: 68px; height: auto; object-fit: contain;">
                          <?php else: ?>
                            <span class="fas fa-user-circle text-400 fs-4"></span>
                          <?php endif; ?>
                        </div>

                        <div class="col-5 text-start">
                          <div class="fs-10 text-600 mb-1"><strong>Contact Person</strong></div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['contact_person_name'] ?? '') !== '' ? $p['contact_person_name'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['contact_person_phone'] ?? '') !== '' ? $p['contact_person_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['contact_person_alt_phone'] ?? '') !== '' ? $p['contact_person_alt_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['contact_person_email'] ?? '') !== '' ? $p['contact_person_email'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>
                        <div class="col-5 text-end">
                          <h6 class="text-primary font-base lh-1 mb-1">
                            <?= htmlspecialchars((string) ($p['company'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <h6 class="fs-11 fw-semi-bold text-facebook mb-1">
                            <?= htmlspecialchars($fullName !== '' ? $fullName : '-', ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['phone'] ?? '') !== '' ? $p['phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['address'] ?? '') !== '' ? $p['address'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($p['email'] ?? '') !== '' ? $p['email'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>
                      </div>

                      <!-- Row 3: Role, Type, Partner Access -->
                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span
                            class="text-600">Role:</span><br><?= htmlspecialchars((string) (($p['role_name'] ?? '') !== '' ? $p['role_name'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-4"><span
                            class="text-600">Type:</span><br><?= htmlspecialchars((string) ($userTypeLabels[(int) ($p['user_type'] ?? 1)] ?? 'Standard'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-4"><span class="text-600">Partner
                            Access:</span><br><?= htmlspecialchars((string) (($p['partner_access_type'] ?? '') !== '' ? $p['partner_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </div>

                      <!-- Row 4: Branch Access, Notes -->
                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span class="text-600">Branch
                            Access:</span><br><?= htmlspecialchars((string) (($p['branch_access_type'] ?? '') !== '' ? $p['branch_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-8"><span
                            class="text-600">Notes:</span><br><?= htmlspecialchars((string) (($p['notes'] ?? '') !== '' ? $p['notes'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </div>

                      <div class="row mt-3"></div>

                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if ($showCompanyEditForm): ?>
      <div class="col-xl-6" id="partner-form">
        <div class="card h-100">
          <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><?= $formData['id'] > 0 ? 'Update Company Profile' : 'Add Company Profile' ?></h6>
          </div>
          <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form" class="row g-2"
              enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_company" />
              <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>" />

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pEmail" name="email" type="email" placeholder="email@example.com"
                    value="<?= htmlspecialchars($formData['email']) ?>" />
                  <label for="pEmail">Email</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pPhone" name="phone" type="text" placeholder="Phone number"
                    value="<?= htmlspecialchars($formData['phone']) ?>" />
                  <label for="pPhone">Phone</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pCompany" name="company" type="text" placeholder="Company name"
                    value="<?= htmlspecialchars($formData['company']) ?>" />
                  <label for="pCompany">Company</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pContactPersonName" name="contact_person_name" type="text"
                    placeholder="Contact person name" value="<?= htmlspecialchars($formData['contact_person_name']) ?>" />
                  <label for="pContactPersonName">Contact Person Name</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pContactPersonPhone" name="contact_person_phone" type="text"
                    placeholder="Primary phone" value="<?= htmlspecialchars($formData['contact_person_phone']) ?>" />
                  <label for="pContactPersonPhone">Contact Person Phone</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pContactPersonAltPhone" name="contact_person_alt_phone" type="text"
                    placeholder="Alternative phone"
                    value="<?= htmlspecialchars($formData['contact_person_alt_phone']) ?>" />
                  <label for="pContactPersonAltPhone">Alternative Phone</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pContactPersonEmail" name="contact_person_email" type="email"
                    placeholder="contact@example.com" value="<?= htmlspecialchars($formData['contact_person_email']) ?>" />
                  <label for="pContactPersonEmail">Contact Person Email</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pAddress" name="address" type="text" placeholder="Address"
                    value="<?= htmlspecialchars($formData['address']) ?>" />
                  <label for="pAddress">Address</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <textarea class="form-control" id="pNotes" name="notes" placeholder="Notes"
                    style="height: 58px;"><?= htmlspecialchars($formData['notes']) ?></textarea>
                  <label for="pNotes">Notes</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pLogoIcon" name="logo_icon" type="file" accept="image/*"
                    placeholder="Icon Logo (100x100 px)" />
                  <label for="pLogoIcon">Icon Logo (100x100 px)</label>
                </div>
                <?php if ($formData['logo_icon'] !== ''): ?>
                  <div class="mt-2">
                    <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_icon'], '/')) ?>"
                      alt="Icon logo" style="width: 100px; height: 100px; object-fit: contain;">
                  </div>
                <?php endif; ?>
              </div>


              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pLogoMain" name="logo_main" type="file" accept="image/*"
                    placeholder="Main Logo (300x100 px)" />
                  <label for="pLogoMain">Main Logo (300x100 px)</label>
                </div>
                <?php if ($formData['logo_main'] !== ''): ?>
                  <div class="mt-2">
                    <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_main'], '/')) ?>"
                      alt="Main logo" style="max-width: 300px; height: auto;">
                  </div>
                <?php endif; ?>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pContactPersonImg" name="contact_person_img" type="file" accept="image/*"
                    placeholder="Contact Person IMG (200x300 px)" />
                  <label for="pContactPersonImg">Contact Person IMG (200x300 px)</label>
                </div>
                <?php if (!empty($formData['contact_person_img'])): ?>
                  <div class="mt-2">
                    <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['contact_person_img'], '/')) ?>"
                      alt="Contact Person IMG" style="width: 200px; height: 300px; object-fit: cover;">
                  </div>
                <?php endif; ?>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <select class="form-select" id="pUserType" name="user_type" aria-label="User Type">
                    <?php foreach ($userTypeLabels as $val => $label): ?>
                      <option value="<?= $val ?>" <?= (int) $formData['user_type'] === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <label for="pUserType">User Type</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <select class="form-select" id="pRole" name="roleId" aria-label="Role">
                    <option value="0" <?= (int) $formData['roleId'] === 0 ? 'selected' : '' ?>>Select role</option>
                    <?php foreach ($activeRoles as $role): ?>
                      <option value="<?= (int) $role['role_id'] ?>" <?= (int) $formData['roleId'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $role['role_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <label for="pRole">Role</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <select class="form-select" id="pParent" aria-label="Parent Company" disabled>
                    <option value="0" selected>None</option>
                  </select>
                  <input type="hidden" name="parentId" value="0">
                  <label for="pParent">Parent Company</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pBranchAccess" name="branch_access_type" type="text" placeholder="0"
                    value="<?= htmlspecialchars($formData['branch_access_type']) ?>" />
                  <label for="pBranchAccess">Branch Access</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pPartnerAccess" name="partner_access_type" type="text" placeholder="0"
                    value="<?= htmlspecialchars($formData['partner_access_type']) ?>" />
                  <label for="pPartnerAccess">Partner Access</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pDept" name="departmentId" type="number" min="0" placeholder="0"
                    value="<?= (int) $formData['departmentId'] ?>" />
                  <label for="pDept">Department ID</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="form-floating">
                  <input class="form-control" id="pPartnerRef" name="partnerId" type="number" min="0" placeholder="0"
                    value="<?= (int) $formData['partnerId'] ?>" />
                  <label for="pPartnerRef">Partner Ref ID</label>
                </div>
              </div>

              <div class="col-md-6 col-xl-4">
                <div class="border rounded px-3 py-2 h-100 d-flex align-items-center justify-content-between">
                  <span class="text-700">Status</span>
                  <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" id="pEnabled" name="enabled" value="1" <?= (int) $formData['enabled'] === 1 ? 'checked' : '' ?> />
                    <label class="form-check-label" for="pEnabled">Active</label>
                  </div>
                </div>
              </div>

              <!-- Submit -->
              <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a class="btn btn-falcon-default btn-sm"
                  href="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form">Reset</a>
                <button class="btn btn-primary btn-sm" type="submit">
                  <?= $formData['id'] > 0 ? 'Update' : 'Add' ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($activeTab === 'partners'): ?>
    <div class="col-xl-6" id="partner-list">
      <div class="card h-100 mb-3">
        <div class="card-header bg-body-tertiary d-flex flex-between-center py-2">
          <h6 class="mb-0">Partner Companies</h6>
          <?php $partnerListEditId = $partnerTabEditId > 0 ? $partnerTabEditId : (isset($partnerTabRows[0]['id']) ? (int) $partnerTabRows[0]['id'] : 0); ?>
          <?php
          $partnerHeaderEnabled = null;
          if ((int) ($partnerTabFormData['id'] ?? 0) > 0) {
            $partnerHeaderEnabled = (int) ($partnerTabFormData['enabled'] ?? 0);
          } elseif (isset($partnerTabRows[0]['enabled'])) {
            $partnerHeaderEnabled = (int) $partnerTabRows[0]['enabled'];
          }
          ?>
          <div class="dropdown font-sans-serif position-static d-inline-block btn-reveal-trigger">
            <?php if ($partnerHeaderEnabled !== null): ?>
              <small
                class="badge rounded <?= $partnerHeaderEnabled === 1 ? 'badge-subtle-success' : 'badge-subtle-danger' ?> me-2">
                <?= $partnerHeaderEnabled === 1 ? 'Active' : 'Inactive' ?>
              </small>
            <?php endif; ?>
            <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal dropdown-caret-none" type="button"
              id="dropdown-partners" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
              aria-expanded="false" data-bs-reference="parent">
              <span class="fas fa-ellipsis-h fs-10"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-partners">
              <a class="dropdown-item"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=partners#partner-tab-form">View</a>
              <a class="dropdown-item"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=partners<?= $partnerListEditId > 0 ? '&partner_edit_id=' . $partnerListEditId : '' ?>#partner-tab-form">Edit</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-danger"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=partners">Refresh</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3 h-100">
            <?php if (empty($partnerTabRows)): ?>
              <div class="col-12">
                <div class="text-center py-3 text-600">No partner companies found.</div>
              </div>
            <?php else: ?>
              <?php foreach ($partnerTabRows as $pr): ?>
                <?php
                $partnerFullName = trim(((string) ($pr['firstname'] ?? '')) . ' ' . ((string) ($pr['lastname'] ?? '')));
                $partnerRowId = (int) ($pr['id'] ?? 0);
                $partnerIconLogoValue = (string) (($pr['logo_icon'] ?? $pr['icon_logo'] ?? '') ?: '');
                $partnerMainLogoValue = (string) (($pr['logo_main'] ?? $pr['main_logo'] ?? '') ?: '');
                $partnerIconLogoUrl = $partnerIconLogoValue !== '' ? $appBasePath . '/' . ltrim($partnerIconLogoValue, '/') : '';
                $partnerMainLogoUrl = $partnerMainLogoValue !== '' ? $appBasePath . '/' . ltrim($partnerMainLogoValue, '/') : '';
                $partnerContactImageUrl = !empty($pr['contact_person_img']) ? $appBasePath . '/' . ltrim((string) $pr['contact_person_img'], '/') : (!empty($pr['avatar']) ? $appBasePath . '/' . ltrim((string) $pr['avatar'], '/') : '');
                ?>
                <div class="col-sm-6 col-lg-12" data-partner-card-id="<?= $partnerRowId ?>">
                  <div class="card position-relative rounded-4">
                    <div class="bg-holder bg-card rounded-4"
                      style="background-image:url(<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/icons/spot-illustrations/corner-1.png);">
                    </div>
                    <div class="card-body p-3 pt-4 pt-xxl-4">
                      <div class="row mb-2">
                        <div class="col text-end d-flex justify-content-end">
                          <?php if ($partnerMainLogoUrl !== ''): ?>
                            <img class="js-partner-main-logo"
                              src="<?= htmlspecialchars($partnerMainLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Main logo"
                              height="72" style="max-width: 240px; object-fit: contain;">
                          <?php else: ?>
                            <span class="fas fa-building text-primary fs-4 js-partner-main-logo-fallback"></span>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="row align-items-stretch">

                        <div class="col-2 text-end">
                          <?php if ($partnerContactImageUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($partnerContactImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Contact person"
                              style="width: 68px; height: auto; object-fit: contain;">
                          <?php else: ?>
                            <span class="fas fa-user-circle text-400 fs-4"></span>
                          <?php endif; ?>
                        </div>

                        <div class="col-5 text-start">
                          <div class="fs-10 text-600 mb-1"><strong>Contact Person</strong></div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['contact_person_name'] ?? '') !== '' ? $pr['contact_person_name'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['contact_person_phone'] ?? '') !== '' ? $pr['contact_person_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['contact_person_alt_phone'] ?? '') !== '' ? $pr['contact_person_alt_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['contact_person_email'] ?? '') !== '' ? $pr['contact_person_email'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>
                        <div class="col-5 text-end">
                          <h6 class="text-primary font-base lh-1 mb-1">
                            <?= htmlspecialchars((string) (($pr['company'] ?? '') !== '' ? $pr['company'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <h6 class="fs-11 fw-semi-bold text-facebook mb-1">
                            <?= htmlspecialchars($partnerFullName !== '' ? $partnerFullName : '-', ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['phone'] ?? '') !== '' ? $pr['phone'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['address'] ?? '') !== '' ? $pr['address'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars((string) (($pr['email'] ?? '') !== '' ? $pr['email'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>

                      </div>

                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span
                            class="text-600">Role:</span><br><?= htmlspecialchars((string) (($pr['role_name'] ?? '') !== '' ? $pr['role_name'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-4"><span
                            class="text-600">Type:</span><br><?= htmlspecialchars((string) ($userTypeLabels[(int) ($pr['user_type'] ?? 1)] ?? 'Standard'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-4"><span class="text-600">Partner
                            Access:</span><br><?= htmlspecialchars((string) (($pr['partner_access_type'] ?? '') !== '' ? $pr['partner_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </div>

                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span class="text-600">Branch
                            Access:</span><br><?= htmlspecialchars((string) (($pr['branch_access_type'] ?? '') !== '' ? $pr['branch_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="col-8"><span
                            class="text-600">Notes:</span><br><?= htmlspecialchars((string) (($pr['notes'] ?? '') !== '' ? $pr['notes'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </div>

                      <div class="row mt-3"></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6" id="partner-tab-form">
      <div class="card h-100">
        <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
          <h6 class="mb-0"><?= $partnerTabFormData['id'] > 0 ? 'Update Partner Company' : 'Add Partner Company' ?></h6>
        </div>
        <div class="card-body">
          <form method="post" action="<?= htmlspecialchars($currentPath) ?>?tab=partners#partner-tab-form" class="row g-2"
            enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_partner" />
            <input type="hidden" name="id" value="<?= (int) $partnerTabFormData['id'] ?>" />

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pEmail" name="email" type="email" placeholder="email@example.com"
                  value="<?= htmlspecialchars($partnerTabFormData['email']) ?>" />
                <label for="pEmail">Email</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pPhone" name="phone" type="text" placeholder="Phone number"
                  value="<?= htmlspecialchars($partnerTabFormData['phone']) ?>" />
                <label for="pPhone">Phone</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pCompany" name="company" type="text" placeholder="Company name"
                  value="<?= htmlspecialchars($partnerTabFormData['company']) ?>" />
                <label for="pCompany">Company</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pContactPersonName" name="contact_person_name" type="text"
                  placeholder="Contact person name"
                  value="<?= htmlspecialchars($partnerTabFormData['contact_person_name']) ?>" />
                <label for="pContactPersonName">Contact Person Name</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pContactPersonPhone" name="contact_person_phone" type="text"
                  placeholder="Primary phone"
                  value="<?= htmlspecialchars($partnerTabFormData['contact_person_phone']) ?>" />
                <label for="pContactPersonPhone">Contact Person Phone</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pContactPersonAltPhone" name="contact_person_alt_phone" type="text"
                  placeholder="Alternative phone"
                  value="<?= htmlspecialchars($partnerTabFormData['contact_person_alt_phone']) ?>" />
                <label for="pContactPersonAltPhone">Alternative Phone</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pContactPersonEmail" name="contact_person_email" type="email"
                  placeholder="contact@example.com"
                  value="<?= htmlspecialchars($partnerTabFormData['contact_person_email']) ?>" />
                <label for="pContactPersonEmail">Contact Person Email</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pAddress" name="address" type="text" placeholder="Address"
                  value="<?= htmlspecialchars($partnerTabFormData['address']) ?>" />
                <label for="pAddress">Address</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <textarea class="form-control" id="pNotes" name="notes" placeholder="Notes"
                  style="height: 58px;"><?= htmlspecialchars($partnerTabFormData['notes']) ?></textarea>
                <label for="pNotes">Notes</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pLogoIcon" name="logo_icon" type="file" accept="image/*"
                  placeholder="Icon Logo (100x100 px)" />
                <label for="pLogoIcon">Icon Logo (100x100 px)</label>
              </div>
              <?php if ($partnerTabFormData['logo_icon'] !== ''): ?>
                <div class="mt-2">
                  <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($partnerTabFormData['logo_icon'], '/')) ?>"
                    alt="Icon logo" style="width: 100px; height: 100px; object-fit: contain;">
                </div>
              <?php endif; ?>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pLogoMain" name="logo_main" type="file" accept="image/*"
                  placeholder="Main Logo (300x100 px)" data-partner-main-logo-input="1"
                  data-preview-partner-id="<?= (int) ($partnerTabFormData['id'] ?? 0) ?>" />
                <label for="pLogoMain">Main Logo (300x100 px)</label>
              </div>
              <div class="mt-2" data-partner-main-logo-form-preview-wrap="1"
                style="<?= $partnerTabFormData['logo_main'] !== '' ? '' : 'display:none;' ?>">
                <img data-partner-main-logo-form-preview="1"
                  src="<?= $partnerTabFormData['logo_main'] !== '' ? htmlspecialchars($appBasePath . '/' . ltrim($partnerTabFormData['logo_main'], '/')) : '' ?>"
                  alt="Main logo" style="max-width: 300px; height: auto;">
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pContactPersonImg" name="contact_person_img" type="file" accept="image/*"
                  placeholder="Contact Person IMG (200x300 px)" />
                <label for="pContactPersonImg">Contact Person IMG (200x300 px)</label>
              </div>
              <?php if (!empty($partnerTabFormData['contact_person_img'])): ?>
                <div class="mt-2">
                  <img
                    src="<?= htmlspecialchars($appBasePath . '/' . ltrim($partnerTabFormData['contact_person_img'], '/')) ?>"
                    alt="Contact Person IMG" style="width: 200px; height: 300px; object-fit: cover;">
                </div>
              <?php endif; ?>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <select class="form-select" id="pUserType" name="user_type" aria-label="User Type">
                  <?php foreach ($userTypeLabels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= (int) $partnerTabFormData['user_type'] === $val ? 'selected' : '' ?>>
                      <?= $label ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <label for="pUserType">User Type</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <select class="form-select" id="pRole" name="roleId" aria-label="Role">
                  <option value="0" <?= (int) $partnerTabFormData['roleId'] === 0 ? 'selected' : '' ?>>Select role</option>
                  <?php foreach ($activeRoles as $role): ?>
                    <option value="<?= (int) $role['role_id'] ?>" <?= (int) $partnerTabFormData['roleId'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars((string) $role['role_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <label for="pRole">Role</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <select class="form-select" id="pParent" name="parentId" aria-label="Parent Company">
                  <?php foreach ($partnerTabParentOptions as $ap): ?>
                    <option value="<?= (int) $ap['id'] ?>" <?= (int) $partnerTabFormData['parentId'] === (int) $ap['id'] ? 'selected' : '' ?>>
                      <?= (int) $ap['id'] ?>#<?= htmlspecialchars(!empty($ap['company']) ? (string) $ap['company'] : (trim(($ap['firstname'] ?? '') . ' ' . ($ap['lastname'] ?? '')) ?: (string) $ap['username'])) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <label for="pParent">Parent Company</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pBranchAccess" name="branch_access_type" type="text" placeholder="0"
                  value="<?= htmlspecialchars($partnerTabFormData['branch_access_type']) ?>" />
                <label for="pBranchAccess">Branch Access</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pPartnerAccess" name="partner_access_type" type="text" placeholder="0"
                  value="<?= htmlspecialchars($partnerTabFormData['partner_access_type']) ?>" />
                <label for="pPartnerAccess">Partner Access</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pDept" name="departmentId" type="number" min="0" placeholder="0"
                  value="<?= (int) $partnerTabFormData['departmentId'] ?>" />
                <label for="pDept">Department ID</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="pPartnerRef" name="partnerId" type="number" min="0" placeholder="0"
                  value="<?= (int) $partnerTabFormData['partnerId'] ?>" />
                <label for="pPartnerRef">Partner Ref ID</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="border rounded px-3 py-2 h-100 d-flex align-items-center justify-content-between">
                <span class="text-700">Status</span>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="pEnabled" name="enabled" value="1" <?= (int) $partnerTabFormData['enabled'] === 1 ? 'checked' : '' ?> />
                  <label class="form-check-label" for="pEnabled">Active</label>
                </div>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <a class="btn btn-falcon-default btn-sm"
                href="<?= htmlspecialchars($currentPath) ?>?tab=partners#partner-tab-form">Reset</a>
              <button class="btn btn-primary btn-sm"
                type="submit"><?= $partnerTabFormData['id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($activeTab === 'branches'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200">
          <h6 class="mb-0">Branch List</h6>
        </div>
        <div class="card-body">
          <div class="row g-3 h-100">
            <?php if (empty($branchListRows)): ?>
              <div class="col-12">
                <div class="text-center py-3 text-600">No branches found.</div>
              </div>
            <?php else: ?>
              <?php foreach ($branchListRows as $branch): ?>
                <?php
                $branchCardId = (int) ($branch['branch_id'] ?? ($branch['id'] ?? 0));
                $isStaticBranchCard = $branchCardId === 1;
                $branchMainLogoUrl = !empty($branch['partner_logo_main']) ? $appBasePath . '/' . ltrim((string) $branch['partner_logo_main'], '/') : '';
                if ($isStaticBranchCard) {
                  $branchMainLogoUrl = $appBasePath . '/assets/uploads/companies/main_20260426071101_3ef4b272.png';
                }
                $branchContactImageValue = (string) ($branch['partner_contact_person_img'] ?? '');
                $branchContactImageUrl = $branchContactImageValue !== '' ? $appBasePath . '/' . ltrim($branchContactImageValue, '/') : '';

                $branchContactName = trim((string) (($branch['branch_contact_person_name'] ?? '') !== '' ? $branch['branch_contact_person_name'] : ($branch['partner_contact_person_name'] ?? '')));
                $branchContactPhone = trim((string) (($branch['branch_contact_person_phone'] ?? '') !== '' ? $branch['branch_contact_person_phone'] : ($branch['partner_contact_person_phone'] ?? '')));
                $branchContactAltPhone = trim((string) (($branch['branch_contact_person_alt_phone'] ?? '') !== '' ? $branch['branch_contact_person_alt_phone'] : ($branch['partner_contact_person_alt_phone'] ?? '')));
                $branchContactEmail = trim((string) (($branch['branch_contact_person_email'] ?? '') !== '' ? $branch['branch_contact_person_email'] : ($branch['partner_contact_person_email'] ?? '')));

                $branchTitle = trim((string) ($branch['branch_name'] ?? ''));
                $partnerTitle = trim((string) ($branch['partner_company'] ?? ''));
                $branchDisplayCompany = trim((string) ($branch['branch_display_company'] ?? ''));
                if ($partnerTitle === '') {
                  $partnerFullName = trim(((string) ($branch['partner_firstname'] ?? '')) . ' ' . ((string) ($branch['partner_lastname'] ?? '')));
                  $partnerTitle = $partnerFullName !== '' ? $partnerFullName : (string) ($branch['partner_username'] ?? '-');
                }
                if ($isStaticBranchCard) {
                  $partnerTitle = $branchDisplayCompany !== '' ? $branchDisplayCompany : 'Friends online Bd';
                }

                $displayPhone = (string) (($branch['branch_mobile'] ?? '') !== '' ? $branch['branch_mobile'] : ($branch['partner_phone'] ?? ''));
                $displayAddress = (string) (($branch['branch_address'] ?? '') !== '' ? $branch['branch_address'] : ($branch['partner_address'] ?? ''));
                $displayEmail = (string) (($branch['branch_email'] ?? '') !== '' ? $branch['branch_email'] : ($branch['partner_email'] ?? ''));
                $displayRole = (string) (($branch['partner_role_name'] ?? '') !== '' ? $branch['partner_role_name'] : '-');
                $displayType = (string) ($userTypeLabels[(int) (($branch['partner_user_type'] ?? 1))] ?? 'Standard');
                $displayBranchAccess = (string) (($branch['partner_branch_access_type'] ?? '') !== '' ? $branch['partner_branch_access_type'] : '0');
                $displayPartnerAccess = (string) (($branch['partner_access_type'] ?? '') !== '' ? $branch['partner_access_type'] : '0');
                $displayNotes = (string) (($branch['branch_notes'] ?? '') !== '' ? $branch['branch_notes'] : ($branch['location_name'] ?? '-'));
                $displayLocation = (string) (($branch['location_name'] ?? '') !== '' ? $branch['location_name'] : '-');
                $branchStatus = isset($branch['branch_status']) ? (int) $branch['branch_status'] : 0;
                ?>
                <div class="col-sm-6 col-lg-6" data-branch-card-id="<?= $branchCardId ?>">
                  <div class="card position-relative rounded-4">
                    <div class="bg-holder bg-card rounded-4"
                      style="background-image:url(<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/icons/spot-illustrations/<?= $isStaticBranchCard ? 'corner-2' : 'corner-1' ?>.png);">
                    </div>
                    <div class="card-body p-3 pt-4 pt-xxl-4">
                      <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                        <a class="btn btn-link p-0 position-relative z-2 d-flex align-items-center gap-1"
                          href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=branches&branch_edit_id=<?= $branchCardId ?>#branch-tab-form"
                          data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit Branch">
                          <span class="fas fa-edit text-500"></span>
                          <small
                            class="badge rounded <?= $branchStatus === 1 ? 'badge-subtle-success' : 'badge-subtle-danger' ?>">
                            <?= $branchStatus === 1 ? 'Active' : 'Inactive' ?>
                          </small>
                        </a>
                        <div class="d-flex justify-content-end">
                          <?php if ($branchMainLogoUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($branchMainLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Main logo"
                              height="72" style="max-width: 240px; object-fit: contain;">
                          <?php else: ?>
                            <span class="fas fa-building text-primary fs-4"></span>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="row align-items-stretch">

                        <div class="col-6 text-start">
                          <div class="fs-10 text-600 mb-1"><strong>Contact Person</strong></div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($branchContactName !== '' ? $branchContactName : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($branchContactPhone !== '' ? $branchContactPhone : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($branchContactAltPhone !== '' ? $branchContactAltPhone : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($branchContactEmail !== '' ? $branchContactEmail : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>
                        <div class="col-6 text-end">
                          <h6 class="text-primary font-base lh-1 mb-1">
                            <?= htmlspecialchars($branchTitle !== '' ? $branchTitle : '-', ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <h6 class="fs-11 fw-semi-bold text-facebook mb-1">
                            <?= htmlspecialchars($partnerTitle !== '' ? $partnerTitle : '-', ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($displayPhone !== '' ? $displayPhone : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($displayAddress !== '' ? $displayAddress : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="fs-10 text-600 mb-0">
                            <?= htmlspecialchars($displayEmail !== '' ? $displayEmail : '-', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </div>
                      </div>

                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span
                            class="text-600">Role:</span><br><?= htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-4"><span
                            class="text-600">Type:</span><br><?= htmlspecialchars($displayType, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-4"><span class="text-600">Partner
                            Access:</span><br><?= htmlspecialchars($displayPartnerAccess, ENT_QUOTES, 'UTF-8') ?></div>
                      </div>

                      <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium" style="padding-left:28px;">
                        <div class="col-4"><span class="text-600">Branch
                            Access:</span><br><?= htmlspecialchars($displayBranchAccess, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-8"><span
                            class="text-600">Notes:</span><br><?= htmlspecialchars($displayNotes, ENT_QUOTES, 'UTF-8') ?><?php if ($displayLocation !== '-'): ?>
                            | Location: <?= htmlspecialchars($displayLocation, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></div>
                      </div>

                      <div class="row mt-3"></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4" id="branch-tab-form">
      <div class="card h-100">
        <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
          <h6 class="mb-0"><?= (int) ($branchFormData['branch_id'] ?? 0) > 0 ? 'Update Branch' : 'Add Branch' ?></h6>
        </div>
        <div class="card-body">
          <form class="row g-2"
            action="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=branches#branch-tab-form"
            method="post">
            <input type="hidden" name="action" value="save_branch" />
            <input type="hidden" name="branch_id" value="<?= (int) ($branchFormData['branch_id'] ?? 0) ?>" />
            <?php $isStaticBranchForm = (int) ($branchFormData['branch_id'] ?? 0) === 1; ?>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchName" name="branch_name" type="text" placeholder="Enter branch name"
                  required
                  value="<?= htmlspecialchars((string) ($branchFormData['branch_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchName">Branch Name</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                  <select class="form-select" id="branchCompany" name="partner_id" aria-label="Company">
                    <option value="" disabled <?= (int) ($branchFormData['partner_id'] ?? 0) === 0 ? 'selected' : '' ?>>Select Company</option>
                    <?php foreach ($branchPartnerOptions as $branchPartner): ?>
                      <?php
                      $branchPartnerOptionId = (int) ($branchPartner['id'] ?? 0);
                      $branchPartnerCompany = (string) ($branchPartner['company'] ?? '');
                      if (empty($branchPartnerCompany)) {
                        $branchPartnerCompany = trim(((string) ($branchPartner['firstname'] ?? '')) . ' ' . ((string) ($branchPartner['lastname'] ?? '')));
                        if (empty($branchPartnerCompany)) {
                          $branchPartnerCompany = (string) ($branchPartner['username'] ?? 'Company');
                        }
                      }
                      $branchPartnerDisplay = $branchPartnerOptionId . '#' . $branchPartnerCompany;
                      ?>
                      <option value="<?= $branchPartnerOptionId ?>" <?= (int) ($branchFormData['partner_id'] ?? 0) === $branchPartnerOptionId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($branchPartnerDisplay, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <label for="branchCompany">Company</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <select class="form-select" id="branchLocation" name="location_id"
                  aria-label="Inventory Location (Sub area)">
                  <option value="" disabled <?= (int) ($branchFormData['location_id'] ?? 0) === 0 ? 'selected' : '' ?>>Select
                    Location List (Sub area)</option>
                  <?php foreach ($branchLocationOptions as $location): ?>
                    <?php
                    $locationLabel = (string) ($location['location_name'] ?? '');
                    $parentName = (string) ($location['parent_location_name'] ?? '');
                    $districtName = (string) ($location['district_location_name'] ?? '');
                    if ($parentName !== '' || $districtName !== '') {
                      $locationLabel .= ' (' . trim($parentName . ($districtName !== '' ? ' - ' . $districtName : '')) . ')';
                    }
                    $locationOptionId = (int) ($location['location_id'] ?? 0);
                    ?>
                    <option value="<?= $locationOptionId ?>" <?= (int) ($branchFormData['location_id'] ?? 0) === $locationOptionId ? 'selected' : '' ?>>
                      <?= htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <label for="branchLocation">Inventory Location</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchEmail" name="email" type="email" placeholder="Enter email" required
                  value="<?= htmlspecialchars((string) ($branchFormData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchEmail">Email</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchMobile" name="mobile" type="text" placeholder="Enter mobile number"
                  required
                  value="<?= htmlspecialchars((string) ($branchFormData['mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchMobile">Mobile</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchContactPersonName" name="contact_person_name" type="text"
                  placeholder="Contact Person Name"
                  value="<?= htmlspecialchars((string) ($branchFormData['contact_person_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchContactPersonName">Contact Person Name</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchContactPersonPhone" name="contact_person_phone" type="text"
                  placeholder="Contact Person Phone"
                  value="<?= htmlspecialchars((string) ($branchFormData['contact_person_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchContactPersonPhone">Contact Person Phone</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchContactPersonAltPhone" name="contact_person_alt_phone" type="text"
                  placeholder="Alternative Phone"
                  value="<?= htmlspecialchars((string) ($branchFormData['contact_person_alt_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchContactPersonAltPhone">Alternative Phone</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchContactPersonEmail" name="contact_person_email" type="email"
                  placeholder="Contact Person Email"
                  value="<?= htmlspecialchars((string) ($branchFormData['contact_person_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchContactPersonEmail">Contact Person Email</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <textarea class="form-control" id="branchAddress" name="address" placeholder="Enter address"
                  style="height: 58px;"
                  required><?= htmlspecialchars((string) ($branchFormData['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <label for="branchAddress">Address</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <select class="form-select" id="branchType" name="branch_type" aria-label="Type">
                  <option value="" disabled <?= (string) ($branchFormData['branch_type'] ?? '') === '' ? 'selected' : '' ?>>
                    Select Type</option>
                  <option value="head_office" <?= (string) ($branchFormData['branch_type'] ?? '') === 'head_office' ? 'selected' : '' ?>>Head Office</option>
                  <option value="regional" <?= (string) ($branchFormData['branch_type'] ?? '') === 'regional' ? 'selected' : '' ?>>Regional</option>
                  <option value="local" <?= (string) ($branchFormData['branch_type'] ?? '') === 'local' ? 'selected' : '' ?>>
                    Local</option>
                  <option value="franchise" <?= (string) ($branchFormData['branch_type'] ?? '') === 'franchise' ? 'selected' : '' ?>>Franchise</option>
                </select>
                <label for="branchType">Type</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="form-floating">
                <input class="form-control" id="branchRatio" name="ratio" type="number" min="0" max="100"
                  placeholder="e.g. 30"
                  value="<?= htmlspecialchars((string) ($branchFormData['ratio'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="branchRatio">Ratio</label>
              </div>
            </div>

            <div class="col-md-6 col-xl-4">
              <div class="border rounded px-3 py-2 h-100 d-flex align-items-center justify-content-between">
                <span class="text-700">Status</span>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="branchStatus" name="status" value="1" <?= (int) ($branchFormData['status'] ?? 1) === 1 ? 'checked' : '' ?> />
                  <label class="form-check-label fs-10" for="branchStatus">Active</label>
                </div>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <a class="btn btn-falcon-default btn-sm"
                href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/my-company.php?tab=branches#branch-tab-form">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit">
                <?= (int) ($branchFormData['branch_id'] ?? 0) > 0 ? 'Update Branch' : 'Save Branch' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php if ($activeTab === 'partners'): ?>
  <script>
    (function () {
      var logoInput = document.querySelector('[data-partner-main-logo-input="1"]');
      if (!logoInput) {
        return;
      }

      logoInput.addEventListener('change', function (event) {
        var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
          return;
        }

        var previewUrl = URL.createObjectURL(file);
        var formPreviewWrap = document.querySelector('[data-partner-main-logo-form-preview-wrap="1"]');
        var formPreviewImage = document.querySelector('[data-partner-main-logo-form-preview="1"]');
        if (formPreviewWrap && formPreviewImage) {
          formPreviewImage.src = previewUrl;
          formPreviewWrap.style.display = '';
        }

        var partnerId = parseInt(logoInput.getAttribute('data-preview-partner-id') || '0', 10);
        if (!partnerId) {
          return;
        }

        var card = document.querySelector('[data-partner-card-id="' + partnerId + '"]');
        if (!card) {
          return;
        }

        var logoContainer = card.querySelector('.col.text-end.d-flex.justify-content-end');
        if (!logoContainer) {
          return;
        }

        var existingImage = logoContainer.querySelector('.js-partner-main-logo');
        if (existingImage) {
          existingImage.src = previewUrl;
          return;
        }

        var fallbackIcon = logoContainer.querySelector('.js-partner-main-logo-fallback');
        if (fallbackIcon) {
          fallbackIcon.remove();
        }

        var image = document.createElement('img');
        image.className = 'js-partner-main-logo';
        image.alt = 'Main logo';
        image.height = 72;
        image.style.maxWidth = '240px';
        image.style.objectFit = 'contain';
        image.src = previewUrl;
        logoContainer.appendChild(image);
      });
    })();
  </script>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>