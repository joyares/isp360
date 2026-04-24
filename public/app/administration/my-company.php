<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS mycompany (
        id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
        UNIQUE KEY uk_mycompany_username (username),
        KEY idx_mycompany_enabled (enabled),
        KEY idx_mycompany_status (status),
        KEY idx_mycompany_deleted (deleted_at),
        KEY idx_mycompany_parent (parentId)
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
    'username' => "ALTER TABLE mycompany ADD COLUMN username VARCHAR(90) NULL",
    'password' => "ALTER TABLE mycompany ADD COLUMN password VARCHAR(160) NULL",
    'firstname' => "ALTER TABLE mycompany ADD COLUMN firstname VARCHAR(200) NULL",
    'lastname' => "ALTER TABLE mycompany ADD COLUMN lastname VARCHAR(200) NULL",
    'email' => "ALTER TABLE mycompany ADD COLUMN email VARCHAR(300) NULL",
    'phone' => "ALTER TABLE mycompany ADD COLUMN phone VARCHAR(200) NULL",
    'company' => "ALTER TABLE mycompany ADD COLUMN company VARCHAR(200) NULL",
    'logo_icon' => "ALTER TABLE mycompany ADD COLUMN logo_icon VARCHAR(255) NULL",
    'logo_main' => "ALTER TABLE mycompany ADD COLUMN logo_main VARCHAR(255) NULL",
    'contact_person_name' => "ALTER TABLE mycompany ADD COLUMN contact_person_name VARCHAR(200) NULL",
    'contact_person_phone' => "ALTER TABLE mycompany ADD COLUMN contact_person_phone VARCHAR(200) NULL",
    'contact_person_alt_phone' => "ALTER TABLE mycompany ADD COLUMN contact_person_alt_phone VARCHAR(200) NULL",
    'contact_person_email' => "ALTER TABLE mycompany ADD COLUMN contact_person_email VARCHAR(300) NULL",
    'address' => "ALTER TABLE mycompany ADD COLUMN address VARCHAR(500) NULL",
    'notes' => "ALTER TABLE mycompany ADD COLUMN notes MEDIUMTEXT NULL",
    'enabled' => "ALTER TABLE mycompany ADD COLUMN enabled TINYINT(1) NOT NULL DEFAULT 1",
    'user_type' => "ALTER TABLE mycompany ADD COLUMN user_type INT NOT NULL DEFAULT 1",
    'branch_access_type' => "ALTER TABLE mycompany ADD COLUMN branch_access_type VARCHAR(255) NOT NULL DEFAULT '0'",
    'partner_access_type' => "ALTER TABLE mycompany ADD COLUMN partner_access_type VARCHAR(255) NOT NULL DEFAULT '0'",
    'parentId' => "ALTER TABLE mycompany ADD COLUMN parentId INT NOT NULL DEFAULT 0",
    'partnerId' => "ALTER TABLE mycompany ADD COLUMN partnerId INT NOT NULL DEFAULT 0",
    'departmentId' => "ALTER TABLE mycompany ADD COLUMN departmentId INT NOT NULL DEFAULT 0",
    'roleId' => "ALTER TABLE mycompany ADD COLUMN roleId INT NOT NULL DEFAULT 0",
    'deleted_at' => "ALTER TABLE mycompany ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL",
    'created_by' => "ALTER TABLE mycompany ADD COLUMN created_by INT NULL",
    'updated_by' => "ALTER TABLE mycompany ADD COLUMN updated_by INT NULL",
    'last_login' => "ALTER TABLE mycompany ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL",
  ];

  foreach ($legacySafeColumns as $column => $sql) {
    if (!$ispts_has_column($pdo, 'mycompany', $column)) {
      $pdo->exec($sql);
    }
  }

  if ($ispts_has_column($pdo, 'mycompany', 'first_name') && $ispts_has_column($pdo, 'mycompany', 'firstname')) {
    $pdo->exec('UPDATE mycompany SET firstname = COALESCE(NULLIF(firstname, ""), first_name)');
  }

  if ($ispts_has_column($pdo, 'mycompany', 'last_name') && $ispts_has_column($pdo, 'mycompany', 'lastname')) {
    $pdo->exec('UPDATE mycompany SET lastname = COALESCE(NULLIF(lastname, ""), last_name)');
  }

  $partnerIdColumn = $ispts_has_column($pdo, 'mycompany', 'id') ? 'id' : ($ispts_has_column($pdo, 'mycompany', 'partner_id') ? 'partner_id' : 'id');
  $partnerDeletedCondition = $ispts_has_column($pdo, 'mycompany', 'deleted_at') ? 'deleted_at IS NULL' : '1=1';
  $partnerDeletedConditionAliased = $ispts_has_column($pdo, 'mycompany', 'deleted_at') ? 'p.deleted_at IS NULL' : '1=1';
  $partnerEnabledColumn = $ispts_has_column($pdo, 'mycompany', 'enabled') ? 'enabled' : ($ispts_has_column($pdo, 'mycompany', 'status') ? 'status' : 'enabled');
  $partnerRoleColumn = $ispts_has_column($pdo, 'mycompany', 'roleId') ? 'roleId' : ($ispts_has_column($pdo, 'mycompany', 'role_id') ? 'role_id' : 'roleId');
  $partnerParentColumn = $ispts_has_column($pdo, 'mycompany', 'parentId') ? 'parentId' : ($ispts_has_column($pdo, 'mycompany', 'parent_id') ? 'parent_id' : 'parentId');

$alert       = null;
$editId      = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/my-company.php';

$uploadDirectory = dirname(__DIR__, 2) . '/assets/uploads/mycompany';

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

  return ['path' => 'assets/uploads/mycompany/' . $fileName, 'error' => null];
};

$formData = [
    'id'                  => 0,
    'firstname'           => '',
    'lastname'            => '',
    'username'            => '',
    'email'               => '',
    'phone'               => '',
    'company'             => '',
    'logo_icon'           => '',
    'logo_main'           => '',
    'contact_person_name' => '',
    'contact_person_phone' => '',
    'contact_person_alt_phone' => '',
    'contact_person_email' => '',
    'address'             => '',
    'notes'               => '',
    'user_type'           => 1,
    'branch_access_type'  => '0',
    'partner_access_type' => '0',
    'parentId'            => 0,
    'partnerId'           => 0,
    'departmentId'        => 0,
    'roleId'              => 0,
    'enabled'             => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    // ── Save (insert / update) ───────────────────────────────────────────────
    if ($action === 'save_mycompany') {
        $incomingId          = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $email               = trim((string) ($_POST['email'] ?? ''));
        $phone               = trim((string) ($_POST['phone'] ?? ''));
        $company             = trim((string) ($_POST['company'] ?? ''));
        $contactPersonName   = trim((string) ($_POST['contact_person_name'] ?? ''));
        $contactPersonPhone  = trim((string) ($_POST['contact_person_phone'] ?? ''));
        $contactPersonAltPhone = trim((string) ($_POST['contact_person_alt_phone'] ?? ''));
        $contactPersonEmail  = trim((string) ($_POST['contact_person_email'] ?? ''));
        $address             = trim((string) ($_POST['address'] ?? ''));
        $notes               = trim((string) ($_POST['notes'] ?? ''));
        $user_type           = isset($_POST['user_type']) ? (int) $_POST['user_type'] : 1;
        $branch_access_type  = trim((string) ($_POST['branch_access_type'] ?? '0'));
        $partner_access_type = trim((string) ($_POST['partner_access_type'] ?? '0'));
        $parentId_field      = isset($_POST['parentId']) ? (int) $_POST['parentId'] : 0;
        $partnerRef          = isset($_POST['partnerId']) ? (int) $_POST['partnerId'] : 0;
        $departmentId        = isset($_POST['departmentId']) ? (int) $_POST['departmentId'] : 0;
        $roleId              = isset($_POST['roleId']) ? (int) $_POST['roleId'] : 0;
        $enabled             = isset($_POST['enabled']) ? 1 : 0;
        $sessionUserId       = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

        $iconUpload = $ispts_save_logo($_FILES['logo_icon'] ?? [], 'icon', 100, 100);
        $mainUpload = $ispts_save_logo($_FILES['logo_main'] ?? [], 'main', 300, 100);

        if ($iconUpload['error'] !== null) {
          $alert = ['type' => 'danger', 'message' => (string) $iconUpload['error']];
        } elseif ($mainUpload['error'] !== null) {
          $alert = ['type' => 'danger', 'message' => (string) $mainUpload['error']];
        }

        if ($alert === null) {
            if ($incomingId > 0) {
                // Update
                $updateSql = 'UPDATE mycompany SET
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
                $updateSql .= ' WHERE ' . $partnerIdColumn . ' = :id AND ' . $partnerDeletedCondition;

                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindValue(':email',               $email !== '' ? $email : null);
                $updateStmt->bindValue(':phone',               $phone !== '' ? $phone : null);
                $updateStmt->bindValue(':company',             $company !== '' ? $company : null);
            $updateStmt->bindValue(':contact_person_name', $contactPersonName !== '' ? $contactPersonName : null);
            $updateStmt->bindValue(':contact_person_phone', $contactPersonPhone !== '' ? $contactPersonPhone : null);
            $updateStmt->bindValue(':contact_person_alt_phone', $contactPersonAltPhone !== '' ? $contactPersonAltPhone : null);
            $updateStmt->bindValue(':contact_person_email', $contactPersonEmail !== '' ? $contactPersonEmail : null);
                $updateStmt->bindValue(':address',             $address !== '' ? $address : null);
                $updateStmt->bindValue(':notes',               $notes !== '' ? $notes : null);
                $updateStmt->bindValue(':user_type',           $user_type, \PDO::PARAM_INT);
                $updateStmt->bindValue(':branch_access_type',  $branch_access_type);
                $updateStmt->bindValue(':partner_access_type', $partner_access_type);
                $updateStmt->bindValue(':parentId',            $parentId_field, \PDO::PARAM_INT);
                $updateStmt->bindValue(':partnerRef',          $partnerRef, \PDO::PARAM_INT);
                $updateStmt->bindValue(':departmentId',        $departmentId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':roleId',              $roleId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':enabled',             $enabled, \PDO::PARAM_INT);
                $updateStmt->bindValue(':status',              $enabled, \PDO::PARAM_INT);
                $updateStmt->bindValue(':updated_by',          $sessionUserId, $sessionUserId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
                if ($iconUpload['path'] !== null) {
                  $updateStmt->bindValue(':logo_icon', (string) $iconUpload['path']);
                }
                if ($mainUpload['path'] !== null) {
                  $updateStmt->bindValue(':logo_main', (string) $mainUpload['path']);
                }
                $updateStmt->bindValue(':id', $incomingId, \PDO::PARAM_INT);
                $updateStmt->execute();

                header('Location: ' . $currentPath . '?saved=updated');
                exit;
            }

            // Insert — username auto-generated, password placeholder
            $autoUsername = 'company_' . time() . '_' . random_int(100, 999);
            $insertStmt = $pdo->prepare(
                'INSERT INTO mycompany (
                  username, password, email, phone, company, logo_icon, logo_main,
                  contact_person_name, contact_person_phone, contact_person_alt_phone, contact_person_email,
                  address, notes,
                    user_type, branch_access_type, partner_access_type, parentId, partnerId,
                    departmentId, roleId, enabled, status, created_by
                 ) VALUES (
                  :username, :password, :email, :phone, :company, :logo_icon, :logo_main,
                  :contact_person_name, :contact_person_phone, :contact_person_alt_phone, :contact_person_email,
                  :address, :notes,
                    :user_type, :branch_access_type, :partner_access_type, :parentId, :partnerRef,
                    :departmentId, :roleId, :enabled, :status, :created_by
                 )'
            );
            $insertStmt->bindValue(':username',            $autoUsername);
            $insertStmt->bindValue(':password',            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
            $insertStmt->bindValue(':email',               $email !== '' ? $email : null);
            $insertStmt->bindValue(':phone',               $phone !== '' ? $phone : null);
            $insertStmt->bindValue(':company',             $company !== '' ? $company : null);
            $insertStmt->bindValue(':logo_icon',           $iconUpload['path'] !== null ? (string) $iconUpload['path'] : null, $iconUpload['path'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
            $insertStmt->bindValue(':logo_main',           $mainUpload['path'] !== null ? (string) $mainUpload['path'] : null, $mainUpload['path'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
            $insertStmt->bindValue(':contact_person_name', $contactPersonName !== '' ? $contactPersonName : null);
            $insertStmt->bindValue(':contact_person_phone', $contactPersonPhone !== '' ? $contactPersonPhone : null);
            $insertStmt->bindValue(':contact_person_alt_phone', $contactPersonAltPhone !== '' ? $contactPersonAltPhone : null);
            $insertStmt->bindValue(':contact_person_email', $contactPersonEmail !== '' ? $contactPersonEmail : null);
            $insertStmt->bindValue(':address',             $address !== '' ? $address : null);
            $insertStmt->bindValue(':notes',               $notes !== '' ? $notes : null);
            $insertStmt->bindValue(':user_type',           $user_type, \PDO::PARAM_INT);
            $insertStmt->bindValue(':branch_access_type',  $branch_access_type);
            $insertStmt->bindValue(':partner_access_type', $partner_access_type);
            $insertStmt->bindValue(':parentId',            $parentId_field, \PDO::PARAM_INT);
            $insertStmt->bindValue(':partnerRef',          $partnerRef, \PDO::PARAM_INT);
            $insertStmt->bindValue(':departmentId',        $departmentId, \PDO::PARAM_INT);
            $insertStmt->bindValue(':roleId',              $roleId, \PDO::PARAM_INT);
            $insertStmt->bindValue(':enabled',             $enabled, \PDO::PARAM_INT);
            $insertStmt->bindValue(':status',              $enabled, \PDO::PARAM_INT);
            $insertStmt->bindValue(':created_by',          $sessionUserId, $sessionUserId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            $insertStmt->execute();

            header('Location: ' . $currentPath . '?saved=created');
            exit;
        }

        // Repopulate form on validation error
        $formData = array_merge($formData, [
            'id'                  => $incomingId,
            'email'               => $email,
            'phone'               => $phone,
            'company'             => $company,
          'contact_person_name' => $contactPersonName,
          'contact_person_phone' => $contactPersonPhone,
          'contact_person_alt_phone' => $contactPersonAltPhone,
          'contact_person_email' => $contactPersonEmail,
            'address'             => $address,
            'notes'               => $notes,
            'user_type'           => $user_type,
            'branch_access_type'  => $branch_access_type,
            'partner_access_type' => $partner_access_type,
            'parentId'            => $parentId_field,
            'partnerId'           => $partnerRef,
            'departmentId'        => $departmentId,
            'roleId'              => $roleId,
            'enabled'             => $enabled,
        ]);
    }

    // ── Toggle active/inactive ───────────────────────────────────────────────
    if ($action === 'toggle_mycompany_status') {
        $toggleId     = isset($_POST['partner_id']) ? (int) $_POST['partner_id'] : 0;
        $targetEnabled = isset($_POST['target_enabled']) && (int) $_POST['target_enabled'] === 1 ? 1 : 0;

        if ($toggleId > 0) {
            $toggleStmt = $pdo->prepare(
              'UPDATE mycompany SET ' . $partnerEnabledColumn . ' = :enabled, status = :status WHERE ' . $partnerIdColumn . ' = :id AND ' . $partnerDeletedCondition
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
    } elseif ($savedFlag === 'status') {
        $alert = ['type' => 'success', 'message' => 'Partner status updated.'];
    }
}

// ── Load edit row ────────────────────────────────────────────────────────────
if ($editId > 0) {
    $editStmt = $pdo->prepare(
      'SELECT ' . $partnerIdColumn . ' AS id, firstname, lastname, username, email, phone, company,
                logo_icon, logo_main, contact_person_name, contact_person_phone, contact_person_alt_phone, contact_person_email,
                address, notes,
                user_type, branch_access_type, partner_access_type, parentId, partnerId,
                departmentId, roleId, enabled
         FROM mycompany
       WHERE ' . $partnerIdColumn . ' = :id AND ' . $partnerDeletedCondition . '
         LIMIT 1'
    );
    $editStmt->bindValue(':id', $editId, \PDO::PARAM_INT);
    $editStmt->execute();
    $editRow = $editStmt->fetch(\PDO::FETCH_ASSOC);

    if ($editRow) {
        $formData = [
            'id'                  => (int) $editRow['id'],
            'firstname'           => (string) ($editRow['firstname'] ?? ''),
            'lastname'            => (string) ($editRow['lastname'] ?? ''),
            'username'            => (string) $editRow['username'],
            'email'               => (string) ($editRow['email'] ?? ''),
            'phone'               => (string) ($editRow['phone'] ?? ''),
            'company'             => (string) ($editRow['company'] ?? ''),
            'logo_icon'           => (string) ($editRow['logo_icon'] ?? ''),
            'logo_main'           => (string) ($editRow['logo_main'] ?? ''),
            'contact_person_name' => (string) ($editRow['contact_person_name'] ?? ''),
            'contact_person_phone' => (string) ($editRow['contact_person_phone'] ?? ''),
            'contact_person_alt_phone' => (string) ($editRow['contact_person_alt_phone'] ?? ''),
            'contact_person_email' => (string) ($editRow['contact_person_email'] ?? ''),
            'address'             => (string) ($editRow['address'] ?? ''),
            'notes'               => (string) ($editRow['notes'] ?? ''),
            'user_type'           => (int) ($editRow['user_type'] ?? 1),
            'branch_access_type'  => (string) ($editRow['branch_access_type'] ?? '0'),
            'partner_access_type' => (string) ($editRow['partner_access_type'] ?? '0'),
            'parentId'            => (int) ($editRow['parentId'] ?? 0),
            'partnerId'           => (int) ($editRow['partnerId'] ?? 0),
            'departmentId'        => (int) ($editRow['departmentId'] ?? 0),
            'roleId'              => (int) ($editRow['roleId'] ?? 0),
            'enabled'             => (int) ($editRow['enabled'] ?? 1),
        ];
    }
}

// ── Lookups ──────────────────────────────────────────────────────────────────
$activePartnersStmt = $pdo->prepare(
  'SELECT ' . $partnerIdColumn . ' AS id, firstname, lastname, username, company
     FROM mycompany
   WHERE ' . $partnerEnabledColumn . ' = 1 AND ' . $partnerDeletedCondition
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
      p.company, p.' . $partnerEnabledColumn . ' AS enabled, p.user_type, p.last_login, p.created_at,
            r.role_name,
            pp.username AS parent_username
     FROM mycompany p
   LEFT JOIN roles      r  ON r.role_id  = p.' . $partnerRoleColumn . '
   LEFT JOIN mycompany pp ON pp.' . $partnerIdColumn . ' = p.' . $partnerParentColumn . '
  WHERE ' . $partnerDeletedConditionAliased . '
   ORDER BY p.' . $partnerIdColumn . ' DESC'
);
$partners = $partnersStmt->fetchAll(\PDO::FETCH_ASSOC);

$userTypeLabels = [1 => 'Standard', 2 => 'Premium', 3 => 'Reseller'];

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">My Company</li>
  </ol>
</nav>

<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">My Company</h1>
    </div>
  </div>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3">

  <!-- ═══════════════════ LIST (left col-xl-8) ═══════════════════ -->
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h5 class="mb-0">All Company Profiles</h5>
        <span class="badge badge-subtle-primary fs-11"><?= count($partners) ?> total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">#</th>
                <th class="text-800">Name</th>
                <th class="text-800">Username</th>
                <th class="text-800">Company</th>
                <th class="text-800">Email / Phone</th>
                <th class="text-800">Role</th>
                <th class="text-800">Type</th>
                <th class="text-800 text-center">Status</th>
                <th class="text-800">Last Login</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($partners)): ?>
                <tr>
                  <td colspan="10" class="text-center py-3 text-600">No company profile found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($partners as $p): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0 me-1"
                         href="<?= htmlspecialchars($currentPath) ?>?edit_id=<?= (int) $p['id'] ?>#partner-form"
                         data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <span class="fas fa-edit text-500"></span>
                      </a>
                      <form method="post" class="d-inline align-middle">
                        <input type="hidden" name="action"        value="toggle_mycompany_status" />
                        <input type="hidden" name="partner_id"    value="<?= (int) $p['id'] ?>" />
                        <input type="hidden" name="target_enabled" value="<?= (int) $p['enabled'] === 1 ? '0' : '1' ?>" />
                        <div class="form-check form-switch d-inline-flex m-0"
                             data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                          <input class="form-check-input" type="checkbox"
                                 id="partnerEnabled<?= (int) $p['id'] ?>"
                                 value="1"
                                 <?= (int) $p['enabled'] === 1 ? 'checked' : '' ?>
                                 onchange="this.form.submit()">
                        </div>
                      </form>
                    </td>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= htmlspecialchars(trim(($p['firstname'] ?? '') . ' ' . ($p['lastname'] ?? ''))) ?: '-' ?></td>
                    <td><?= htmlspecialchars((string) $p['username']) ?></td>
                    <td><?= htmlspecialchars((string) ($p['company'] ?: '-')) ?></td>
                    <td>
                      <?= htmlspecialchars((string) ($p['email'] ?: '-')) ?>
                      <?php if (!empty($p['phone'])): ?>
                        <br><small class="text-600"><?= htmlspecialchars((string) $p['phone']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($p['role_name'] ?: '-')) ?></td>
                    <td><?= htmlspecialchars($userTypeLabels[(int) ($p['user_type'] ?? 1)] ?? 'Standard') ?></td>
                    <td class="text-center">
                      <?php if ((int) $p['enabled'] === 1): ?>
                        <span class="badge badge-subtle-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-danger">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $p['last_login'] ? htmlspecialchars(date('Y-m-d h:i A', strtotime((string) $p['last_login']))) : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ FORM (right col-xl-4) ═══════════════════ -->
  <div class="col-xl-4" id="partner-form">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><?= $formData['id'] > 0 ? 'Update Company Profile' : 'Add Company Profile' ?></h6>
        <?php if ($formData['id'] > 0): ?>
          <a href="<?= htmlspecialchars($currentPath) ?>" class="btn btn-falcon-default btn-sm">
            <span class="fas fa-plus me-1"></span>New
          </a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($currentPath) ?>#partner-form" class="row g-2" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_mycompany" />
          <input type="hidden" name="id"     value="<?= (int) $formData['id'] ?>" />

          <!-- Email -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pEmail">Email</label>
            <input class="form-control form-control-sm" id="pEmail" name="email" type="email"
                   placeholder="email@example.com"
                   value="<?= htmlspecialchars($formData['email']) ?>" />
          </div>

          <!-- Phone -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pPhone">Phone</label>
            <input class="form-control form-control-sm" id="pPhone" name="phone" type="text"
                   placeholder="Phone number"
                   value="<?= htmlspecialchars($formData['phone']) ?>" />
          </div>

          <!-- Company -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pCompany">Company</label>
            <input class="form-control form-control-sm" id="pCompany" name="company" type="text"
                   placeholder="Company name"
                   value="<?= htmlspecialchars($formData['company']) ?>" />
          </div>

          <div class="col-12">
            <label class="form-label form-label-sm" for="pContactPersonName">Contact Person Name</label>
            <input class="form-control form-control-sm" id="pContactPersonName" name="contact_person_name" type="text"
                   placeholder="Contact person name"
                   value="<?= htmlspecialchars($formData['contact_person_name']) ?>" />
          </div>

          <div class="col-6">
            <label class="form-label form-label-sm" for="pContactPersonPhone">Contact Person Phone</label>
            <input class="form-control form-control-sm" id="pContactPersonPhone" name="contact_person_phone" type="text"
                   placeholder="Primary phone"
                   value="<?= htmlspecialchars($formData['contact_person_phone']) ?>" />
          </div>
          <div class="col-6">
            <label class="form-label form-label-sm" for="pContactPersonAltPhone">Alternative Phone</label>
            <input class="form-control form-control-sm" id="pContactPersonAltPhone" name="contact_person_alt_phone" type="text"
                   placeholder="Alternative phone"
                   value="<?= htmlspecialchars($formData['contact_person_alt_phone']) ?>" />
          </div>

          <div class="col-12">
            <label class="form-label form-label-sm" for="pContactPersonEmail">Contact Person Email</label>
            <input class="form-control form-control-sm" id="pContactPersonEmail" name="contact_person_email" type="email"
                   placeholder="contact@example.com"
                   value="<?= htmlspecialchars($formData['contact_person_email']) ?>" />
          </div>

          <div class="col-12">
            <label class="form-label form-label-sm" for="pLogoIcon">Icon Logo (100x100 px)</label>
            <input class="form-control form-control-sm" id="pLogoIcon" name="logo_icon" type="file" accept="image/*" />
            <?php if ($formData['logo_icon'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_icon'], '/')) ?>" alt="Icon logo" style="width: 100px; height: 100px; object-fit: contain;">
              </div>
            <?php endif; ?>
          </div>

          <div class="col-12">
            <label class="form-label form-label-sm" for="pLogoMain">Main Logo (300x100 px)</label>
            <input class="form-control form-control-sm" id="pLogoMain" name="logo_main" type="file" accept="image/*" />
            <?php if ($formData['logo_main'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_main'], '/')) ?>" alt="Main logo" style="max-width: 300px; height: auto;">
              </div>
            <?php endif; ?>
          </div>

          <!-- User Type / Role -->
          <div class="col-6">
            <label class="form-label form-label-sm" for="pUserType">User Type</label>
            <select class="form-select form-select-sm" id="pUserType" name="user_type">
              <option value="" disabled selected>Select type</option>
              <?php foreach ($userTypeLabels as $val => $label): ?>
                <option value="<?= $val ?>" <?= (int) $formData['user_type'] === $val ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label form-label-sm" for="pRole">Role</label>
            <select class="form-select form-select-sm" id="pRole" name="roleId">
              <option value="0" disabled selected>Select role</option>
              <?php foreach ($activeRoles as $role): ?>
                <option value="<?= (int) $role['role_id'] ?>"
                  <?= (int) $formData['roleId'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $role['role_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Parent Partner -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pParent">Parent Partner</label>
            <select class="form-select form-select-sm" id="pParent" name="parentId">
              <option value="0">None</option>
              <?php foreach ($activePartners as $ap): ?>
                <option value="<?= (int) $ap['id'] ?>"
                  <?= (int) $formData['parentId'] === (int) $ap['id'] ? 'selected' : '' ?>>
                  #<?= (int) $ap['id'] ?> —
                  <?= htmlspecialchars(trim(($ap['firstname'] ?? '') . ' ' . ($ap['lastname'] ?? '')) ?: (string) $ap['username']) ?>
                  <?php if (!empty($ap['company'])): ?>(<?= htmlspecialchars((string) $ap['company']) ?>)<?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Branch / Partner Access -->
          <div class="col-6">
            <label class="form-label form-label-sm" for="pBranchAccess">Branch Access</label>
            <input class="form-control form-control-sm" id="pBranchAccess" name="branch_access_type" type="text"
                   placeholder="e.g. all / own"
                   value="<?= htmlspecialchars($formData['branch_access_type']) ?>" />
          </div>
          <div class="col-6">
            <label class="form-label form-label-sm" for="pPartnerAccess">Partner Access</label>
            <input class="form-control form-control-sm" id="pPartnerAccess" name="partner_access_type" type="text"
                   placeholder="e.g. all / own"
                   value="<?= htmlspecialchars($formData['partner_access_type']) ?>" />
          </div>

          <!-- Department / Partner Ref -->
          <div class="col-6">
            <label class="form-label form-label-sm" for="pDept">Department ID</label>
            <input class="form-control form-control-sm" id="pDept" name="departmentId" type="number" min="0"
                   placeholder="0"
                   value="<?= (int) $formData['departmentId'] ?>" />
          </div>
          <div class="col-6">
            <label class="form-label form-label-sm" for="pPartnerRef">Partner Ref ID</label>
            <input class="form-control form-control-sm" id="pPartnerRef" name="partnerId" type="number" min="0"
                   placeholder="0"
                   value="<?= (int) $formData['partnerId'] ?>" />
          </div>

          <!-- Address -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pAddress">Address</label>
            <input class="form-control form-control-sm" id="pAddress" name="address" type="text"
                   placeholder="Address"
                   value="<?= htmlspecialchars($formData['address']) ?>" />
          </div>

          <!-- Notes -->
          <div class="col-12">
            <label class="form-label form-label-sm" for="pNotes">Notes</label>
            <textarea class="form-control form-control-sm" id="pNotes" name="notes" rows="2"
                      placeholder="Optional notes"><?= htmlspecialchars($formData['notes']) ?></textarea>
          </div>

          <!-- Status -->
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <label class="form-label mb-0">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="pEnabled" name="enabled" value="1"
                       <?= (int) $formData['enabled'] === 1 ? 'checked' : '' ?> />
                <label class="form-check-label" for="pEnabled">Active</label>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="col-12 mt-2">
            <button class="btn btn-primary btn-sm w-100" type="submit">
              <span class="fas fa-save me-1"></span>
              <?= $formData['id'] > 0 ? 'Update Company Profile' : 'Add Company Profile' ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<?php require '../../includes/footer.php'; ?>

