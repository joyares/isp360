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
$activeTab   = (string) ($_GET['tab'] ?? 'company');

if (!in_array($activeTab, ['company', 'partners', 'branches'], true)) {
  $activeTab = 'company';
}

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
  p.company, p.logo_icon, p.logo_main, p.contact_person_name, p.contact_person_phone,
  p.contact_person_alt_phone, p.contact_person_email, p.address, p.notes,
  p.branch_access_type, p.partner_access_type, p.departmentId, p.partnerId,
  p.' . $partnerEnabledColumn . ' AS enabled, p.user_type, p.last_login, p.created_at,
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

$partnerTabRows = [];
$partnerTabParentOptions = [];
try {
  $partnerTabRowsStmt = $pdo->query(
    'SELECT p.id, p.firstname, p.lastname, p.username, p.email, p.phone, p.company,
        p.enabled, p.user_type, p.last_login, r.role_name
     FROM partners p
     LEFT JOIN roles r ON r.role_id = p.roleId
     WHERE p.deleted_at IS NULL
     ORDER BY p.id DESC'
  );
  $partnerTabRows = $partnerTabRowsStmt ? $partnerTabRowsStmt->fetchAll(\PDO::FETCH_ASSOC) : [];

  $partnerTabParentStmt = $pdo->query(
    'SELECT id, firstname, lastname, username, company
     FROM partners
     WHERE enabled = 1 AND deleted_at IS NULL
     ORDER BY id ASC'
  );
  $partnerTabParentOptions = $partnerTabParentStmt ? $partnerTabParentStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
} catch (\Throwable $e) {
  $partnerTabRows = [];
  $partnerTabParentOptions = [];
}

require '../../includes/header.php';
?>
<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">My Company</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage company profiles, contact details, access settings, and branding from one place.</p>
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
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/my-company.php?tab=company">My Company</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'partners' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/my-company.php?tab=partners">Partners</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'branches' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/my-company.php?tab=branches">Branches</a></li>
    </ul>
  </div>

  <?php if ($activeTab === 'company'): ?>
  <div class="col-xl-6" id="company-list">
    <div class="card h-100 mb-3">
      <div class="card-header bg-body-tertiary d-flex flex-between-center py-2">
        <h6 class="mb-0">Mother Company</h6>
        <div class="dropdown font-sans-serif position-static d-inline-block btn-reveal-trigger">
          <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal dropdown-caret-none" type="button" id="dropdown-mother-company" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
            <span class="fas fa-ellipsis-h fs-10"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-mother-company">
            <a class="dropdown-item" href="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form">View</a>
            <a class="dropdown-item" href="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form">Edit</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="<?= htmlspecialchars($currentPath) ?>?tab=company">Refresh</a>
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
              ?>
              <div class="col-sm-6 col-lg-12">
                <div class="card position-relative rounded-4">
                  <div class="bg-holder bg-card rounded-4" style="background-image:url(<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/icons/spot-illustrations/corner-2.png);"></div>
                  <div class="card-body p-3 pt-4 pt-xxl-4">
                    <!-- Row 1: Only logo -->
                    <div class="row mb-2">
                      <div class="col text-center">
                        <?php if ($mainLogoUrl !== ''): ?>
                          <img src="<?= htmlspecialchars($mainLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Main logo" height="72" style="max-width: 240px; object-fit: contain;">
                        <?php else: ?>
                          <span class="fas fa-building text-primary fs-4"></span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Row 2: 2 columns: Contact Person | Company Info -->
                    <div class="row mb-2">
                      <div class="col-6">
                        <div class="fs-10 text-600 mb-1"><strong>Contact Person</strong></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['contact_person_name'] ?? '') !== '' ? $p['contact_person_name'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['contact_person_phone'] ?? '') !== '' ? $p['contact_person_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['contact_person_alt_phone'] ?? '') !== '' ? $p['contact_person_alt_phone'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['contact_person_email'] ?? '') !== '' ? $p['contact_person_email'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <div class="col-6">
                        <h6 class="text-primary font-base lh-1 mb-1"><?= htmlspecialchars((string) ($p['company'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></h6>
                        <h6 class="fs-11 fw-semi-bold text-facebook mb-1"><?= htmlspecialchars($fullName !== '' ? $fullName : '-', ENT_QUOTES, 'UTF-8') ?></h6>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['phone'] ?? '') !== '' ? $p['phone'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['address'] ?? '') !== '' ? $p['address'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fs-10 text-600 mb-0"><?= htmlspecialchars((string) (($p['email'] ?? '') !== '' ? $p['email'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                    </div>

                    <!-- Row 3: Role, Type, Partner Access -->
                    <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium">
                      <div class="col-4"><span class="text-600">Role:</span><br><?= htmlspecialchars((string) (($p['role_name'] ?? '') !== '' ? $p['role_name'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="col-4"><span class="text-600">Type:</span><br><?= htmlspecialchars((string) ($userTypeLabels[(int) ($p['user_type'] ?? 1)] ?? 'Standard'), ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="col-4"><span class="text-600">Partner Access:</span><br><?= htmlspecialchars((string) (($p['partner_access_type'] ?? '') !== '' ? $p['partner_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <!-- Row 4: Branch Access, Notes -->
                    <div class="row mb-2 g-2 fs-10 font-sans-serif fw-medium">
                      <div class="col-4"><span class="text-600">Branch Access:</span><br><?= htmlspecialchars((string) (($p['branch_access_type'] ?? '') !== '' ? $p['branch_access_type'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="col-8"><span class="text-600">Notes:</span><br><?= htmlspecialchars((string) (($p['notes'] ?? '') !== '' ? $p['notes'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <!-- Row 5: Active badge and Edit icon -->
                    <div class="row mt-3">
                      <div class="col text-start">
                        <small class="badge rounded <?= (int) ($p['enabled'] ?? 0) === 1 ? 'badge-subtle-success' : 'badge-subtle-danger' ?>">
                          <?= (int) ($p['enabled'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                        </small>
                        <a class="btn btn-link p-0 ms-2 align-middle" href="<?= htmlspecialchars($currentPath) ?>?tab=company&edit_id=<?= (int) $p['id'] ?>#partner-form" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                          <span class="fas fa-edit text-500"></span>
                        </a>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6" id="partner-form">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><?= $formData['id'] > 0 ? 'Update Company Profile' : 'Add Company Profile' ?></h6>
      </div>
      <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form" class="row g-2" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_mycompany" />
          <input type="hidden" name="id"     value="<?= (int) $formData['id'] ?>" />

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
              <input class="form-control" id="pContactPersonName" name="contact_person_name" type="text" placeholder="Contact person name"
                     value="<?= htmlspecialchars($formData['contact_person_name']) ?>" />
              <label for="pContactPersonName">Contact Person Name</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonPhone" name="contact_person_phone" type="text" placeholder="Primary phone"
                     value="<?= htmlspecialchars($formData['contact_person_phone']) ?>" />
              <label for="pContactPersonPhone">Contact Person Phone</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonAltPhone" name="contact_person_alt_phone" type="text" placeholder="Alternative phone"
                     value="<?= htmlspecialchars($formData['contact_person_alt_phone']) ?>" />
              <label for="pContactPersonAltPhone">Alternative Phone</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonEmail" name="contact_person_email" type="email" placeholder="contact@example.com"
                     value="<?= htmlspecialchars($formData['contact_person_email']) ?>" />
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
              <textarea class="form-control" id="pNotes" name="notes" placeholder="Notes" style="height: 58px;"><?= htmlspecialchars($formData['notes']) ?></textarea>
              <label for="pNotes">Notes</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pLogoIcon" name="logo_icon" type="file" accept="image/*" placeholder="Icon Logo (100x100 px)" />
              <label for="pLogoIcon">Icon Logo (100x100 px)</label>
            </div>
            <?php if ($formData['logo_icon'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_icon'], '/')) ?>" alt="Icon logo" style="width: 100px; height: 100px; object-fit: contain;">
              </div>
            <?php endif; ?>
          </div>


          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pLogoMain" name="logo_main" type="file" accept="image/*" placeholder="Main Logo (300x100 px)" />
              <label for="pLogoMain">Main Logo (300x100 px)</label>
            </div>
            <?php if ($formData['logo_main'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_main'], '/')) ?>" alt="Main logo" style="max-width: 300px; height: auto;">
              </div>
            <?php endif; ?>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonImg" name="contact_person_img" type="file" accept="image/*" placeholder="Contact Person IMG (200x300 px)" />
              <label for="pContactPersonImg">Contact Person IMG (200x300 px)</label>
            </div>
            <?php if (!empty($formData['contact_person_img'])): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['contact_person_img'], '/')) ?>" alt="Contact Person IMG" style="width: 200px; height: 300px; object-fit: cover;">
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
                  <option value="<?= (int) $role['role_id'] ?>"
                    <?= (int) $formData['roleId'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $role['role_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <label for="pRole">Role</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <select class="form-select" id="pParent" name="parentId" aria-label="Parent Partner">
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
              <label for="pParent">Parent Partner</label>
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
                <input class="form-check-input" type="checkbox" id="pEnabled" name="enabled" value="1"
                       <?= (int) $formData['enabled'] === 1 ? 'checked' : '' ?> />
                <label class="form-check-label" for="pEnabled">Active</label>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="col-12 d-flex justify-content-end gap-2 mt-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= htmlspecialchars($currentPath) ?>?tab=company#partner-form">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit">
              <?= $formData['id'] > 0 ? 'Update' : 'Add' ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($activeTab === 'partners'): ?>
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">All Partners</h6>
        <span class="badge badge-subtle-primary fs-11"><?= count($partnerTabRows) ?> total</span>
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
              <?php if (empty($partnerTabRows)): ?>
                <tr>
                  <td colspan="10" class="text-center py-3 text-600">No partners found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($partnerTabRows as $pr): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0 me-1"
                         href="<?= htmlspecialchars($currentPath) ?>?tab=partners#partner-tab-form"
                         data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <span class="fas fa-edit text-500"></span>
                      </a>
                      <div class="form-check form-switch d-inline-flex m-0"
                           data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                        <input class="form-check-input" type="checkbox"
                               id="partnerTabEnabled<?= (int) ($pr['id'] ?? 0) ?>"
                               value="1"
                               <?= (int) ($pr['enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                      </div>
                    </td>
                    <td><?= (int) ($pr['id'] ?? 0) ?></td>
                    <td><?= htmlspecialchars(trim(((string) ($pr['firstname'] ?? '')) . ' ' . ((string) ($pr['lastname'] ?? ''))), ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                    <td><?= htmlspecialchars((string) ($pr['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) (($pr['company'] ?? '') !== '' ? $pr['company'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?= htmlspecialchars((string) (($pr['email'] ?? '') !== '' ? $pr['email'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                      <?php if (!empty($pr['phone'])): ?>
                        <br><small class="text-600"><?= htmlspecialchars((string) $pr['phone'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) (($pr['role_name'] ?? '') !== '' ? $pr['role_name'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($userTypeLabels[(int) ($pr['user_type'] ?? 1)] ?? 'Standard', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center">
                      <?php if ((int) ($pr['enabled'] ?? 0) === 1): ?>
                        <span class="badge badge-subtle-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-danger">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= !empty($pr['last_login']) ? htmlspecialchars(date('Y-m-d h:i A', strtotime((string) $pr['last_login'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6" id="partner-tab-form">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Add Partner</h6>
      </div>
      <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($currentPath) ?>?tab=partners#partner-tab-form" class="row g-2" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_partner" />
          <input type="hidden" name="id"     value="<?= (int) $formData['id'] ?>" />

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
              <input class="form-control" id="pContactPersonName" name="contact_person_name" type="text" placeholder="Contact person name"
                     value="<?= htmlspecialchars($formData['contact_person_name']) ?>" />
              <label for="pContactPersonName">Contact Person Name</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonPhone" name="contact_person_phone" type="text" placeholder="Primary phone"
                     value="<?= htmlspecialchars($formData['contact_person_phone']) ?>" />
              <label for="pContactPersonPhone">Contact Person Phone</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonAltPhone" name="contact_person_alt_phone" type="text" placeholder="Alternative phone"
                     value="<?= htmlspecialchars($formData['contact_person_alt_phone']) ?>" />
              <label for="pContactPersonAltPhone">Alternative Phone</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pContactPersonEmail" name="contact_person_email" type="email" placeholder="contact@example.com"
                     value="<?= htmlspecialchars($formData['contact_person_email']) ?>" />
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
              <textarea class="form-control" id="pNotes" name="notes" placeholder="Notes" style="height: 58px;"><?= htmlspecialchars($formData['notes']) ?></textarea>
              <label for="pNotes">Notes</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pLogoIcon" name="logo_icon" type="file" accept="image/*" placeholder="Icon Logo (100x100 px)" />
              <label for="pLogoIcon">Icon Logo (100x100 px)</label>
            </div>
            <?php if ($formData['logo_icon'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_icon'], '/')) ?>" alt="Icon logo" style="width: 100px; height: 100px; object-fit: contain;">
              </div>
            <?php endif; ?>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="pLogoMain" name="logo_main" type="file" accept="image/*" placeholder="Main Logo (300x100 px)" />
              <label for="pLogoMain">Main Logo (300x100 px)</label>
            </div>
            <?php if ($formData['logo_main'] !== ''): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($appBasePath . '/' . ltrim($formData['logo_main'], '/')) ?>" alt="Main logo" style="max-width: 300px; height: auto;">
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
                  <option value="<?= (int) $role['role_id'] ?>"
                    <?= (int) $formData['roleId'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $role['role_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <label for="pRole">Role</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <select class="form-select" id="pParent" name="parentId" aria-label="Parent Partner">
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
              <label for="pParent">Parent Partner</label>
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
                <input class="form-check-input" type="checkbox" id="pEnabled" name="enabled" value="1"
                       <?= (int) $formData['enabled'] === 1 ? 'checked' : '' ?> />
                <label class="form-check-label" for="pEnabled">Active</label>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2 mt-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= htmlspecialchars($currentPath) ?>?tab=partners#partner-tab-form">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit">Add Partner</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($activeTab === 'branches'): ?>
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Branch List</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Branch Name</th>
                <th class="text-800">Partner</th>
                <th class="text-800">Email / Mobile</th>
                <th class="text-800">Address</th>
                <th class="text-800">Status</th>
                <th class="text-800">Type</th>
                <th class="text-800">Ratio</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <div class="form-check form-switch d-inline-flex ms-2 m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                    <input class="form-check-input" type="checkbox" id="branchStatusToggle1" name="status" value="1" checked>
                  </div>
                </td>
                <td>Head Office</td>
                <td>Partner A</td>
                <td>headoffice@isp360.com<br><small class="text-600">+8801700000001</small></td>
                <td>Dhaka, Bangladesh</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>Head Office</td>
                <td>50%</td>
              </tr>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <div class="form-check form-switch d-inline-flex ms-2 m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                    <input class="form-check-input" type="checkbox" id="branchStatusToggle2" name="status" value="1" checked>
                  </div>
                </td>
                <td>Chittagong Branch</td>
                <td>Partner B</td>
                <td>ctg@isp360.com<br><small class="text-600">+8801700000002</small></td>
                <td>Chittagong, Bangladesh</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>Regional</td>
                <td>30%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Add Branch</h6>
      </div>
      <div class="card-body">
        <form class="row g-2" action="<?= htmlspecialchars($currentPath) ?>?tab=branches" method="post">
          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="branchName" name="branch_name" type="text" placeholder="Enter branch name" required />
              <label for="branchName">Branch Name</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <select class="form-select" id="branchPartner" name="partner_id" aria-label="Partner">
                <option value="" disabled selected>Select Partner</option>
                <option value="1">Partner A</option>
                <option value="2">Partner B</option>
                <option value="3">Partner C</option>
              </select>
              <label for="branchPartner">Partner</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="branchEmail" name="email" type="email" placeholder="Enter email" required />
              <label for="branchEmail">Email</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="branchMobile" name="mobile" type="text" placeholder="Enter mobile number" required />
              <label for="branchMobile">Mobile</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <textarea class="form-control" id="branchAddress" name="address" placeholder="Enter address" style="height: 58px;" required></textarea>
              <label for="branchAddress">Address</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <select class="form-select" id="branchType" name="branch_type" aria-label="Type">
                <option value="" disabled selected>Select Type</option>
                <option value="head_office">Head Office</option>
                <option value="regional">Regional</option>
                <option value="local">Local</option>
                <option value="franchise">Franchise</option>
              </select>
              <label for="branchType">Type</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="form-floating">
              <input class="form-control" id="branchRatio" name="ratio" type="number" min="0" max="100" placeholder="e.g. 30" />
              <label for="branchRatio">Ratio</label>
            </div>
          </div>

          <div class="col-md-6 col-xl-4">
            <div class="border rounded px-3 py-2 h-100 d-flex align-items-center justify-content-between">
              <span class="text-700">Status</span>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="branchStatus" name="status" value="1" checked />
                <label class="form-check-label fs-10" for="branchStatus">Active</label>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2 mt-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= htmlspecialchars($currentPath) ?>?tab=branches">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit">
              Save Branch
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require '../../includes/footer.php'; ?>

