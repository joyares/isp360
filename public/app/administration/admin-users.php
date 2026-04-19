<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS admin_users (
        admin_user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_id BIGINT UNSIGNED NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        username VARCHAR(120) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(190) NOT NULL,
        mobile VARCHAR(30) NOT NULL,
        branch_name VARCHAR(120) NULL,
        partner_registration TINYINT(1) NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        last_login_ip VARCHAR(45) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_admin_users_username (username),
        UNIQUE KEY uk_admin_users_email (email),
        KEY idx_admin_users_role_id (role_id),
        CONSTRAINT fk_admin_users_role_id
            FOREIGN KEY (role_id)
            REFERENCES roles (role_id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/admin-users.php';
$savedFlag = $_GET['saved'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_admin_user') {
        $roleId = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $email = trim((string) ($_POST['email'] ?? ''));
        $mobile = trim((string) ($_POST['mobile'] ?? ''));
        $branchName = trim((string) ($_POST['branch_name'] ?? ''));
        $status = isset($_POST['status']) ? 1 : 0;
        $partnerRegistration = isset($_POST['partner_registration']) ? 1 : 0;

        if ($roleId <= 0 || $fullName === '' || $username === '' || $password === '' || $email === '' || $mobile === '') {
            $alert = ['type' => 'danger', 'message' => 'Role, Name, Username, Password, Email and Mobile are required.'];
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO admin_users (
                    role_id, full_name, username, password_hash, email, mobile, branch_name, partner_registration, status
                 ) VALUES (
                    :role_id, :full_name, :username, :password_hash, :email, :mobile, :branch_name, :partner_registration, :status
                 )'
            );
            $insertStmt->bindValue(':role_id', $roleId, \PDO::PARAM_INT);
            $insertStmt->bindValue(':full_name', $fullName);
            $insertStmt->bindValue(':username', $username);
            $insertStmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT));
            $insertStmt->bindValue(':email', $email);
            $insertStmt->bindValue(':mobile', $mobile);
            $insertStmt->bindValue(':branch_name', $branchName !== '' ? $branchName : null);
            $insertStmt->bindValue(':partner_registration', $partnerRegistration, \PDO::PARAM_INT);
            $insertStmt->bindValue(':status', $status, \PDO::PARAM_INT);
            $insertStmt->execute();

            header('Location: ' . $currentPath . '?saved=created');
            exit;
        }
    }

    if ($action === 'toggle_admin_user_status') {
        $adminUserId = isset($_POST['admin_user_id']) ? (int) $_POST['admin_user_id'] : 0;
        $targetStatus = isset($_POST['target_status']) ? (int) $_POST['target_status'] : 0;
        $targetStatus = $targetStatus === 1 ? 1 : 0;

        if ($adminUserId > 0) {
            $toggleStmt = $pdo->prepare('UPDATE admin_users SET status = :status WHERE admin_user_id = :admin_user_id');
            $toggleStmt->bindValue(':status', $targetStatus, \PDO::PARAM_INT);
            $toggleStmt->bindValue(':admin_user_id', $adminUserId, \PDO::PARAM_INT);
            $toggleStmt->execute();
        }

        header('Location: ' . $currentPath . '?saved=status');
        exit;
    }
}

if ($alert === null) {
    if ($savedFlag === 'created') {
        $alert = ['type' => 'success', 'message' => 'Admin user created successfully.'];
    } elseif ($savedFlag === 'status') {
        $alert = ['type' => 'success', 'message' => 'Admin user status updated successfully.'];
    }
}

$activeRolesStmt = $pdo->query(
    'SELECT role_id, role_name
     FROM roles
     WHERE status = 1
     ORDER BY role_name ASC'
);
$activeRoles = $activeRolesStmt->fetchAll(\PDO::FETCH_ASSOC);

$adminUsersStmt = $pdo->query(
    'SELECT au.admin_user_id,
            au.full_name,
            au.username,
            au.status,
            r.role_name,
            au.email,
            au.mobile,
            au.last_login_at,
            au.last_login_ip,
            au.created_at
     FROM admin_users au
     LEFT JOIN roles r ON r.role_id = au.role_id
     ORDER BY au.admin_user_id DESC'
);
$adminUsers = $adminUsersStmt->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Admin Users</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Admin Users</h1>
    </div>
  </div>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-xl-9">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Admin User List</h5>
        <a href="#admin-user-form" class="btn btn-primary btn-sm">
          <span class="fas fa-plus me-1"></span>Add Admin User
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Name</th>
                <th class="text-800">Username</th>
                <th class="text-800">Status</th>
                <th class="text-800">Role</th>
                <th class="text-800">Email / Mobile</th>
                <th class="text-800">Last Login Date</th>
                <th class="text-800">Last Login IP</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($adminUsers)): ?>
                <tr>
                  <td colspan="8" class="text-center py-3 text-600">No admin users found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($adminUsers as $user): ?>
                  <tr>
                    <td>
                      <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit" onclick="window.location.hash='admin-user-form'">
                        <span class="fas fa-edit text-500"></span>
                      </button>
                      <form method="post" class="d-inline ms-2 align-middle">
                        <input type="hidden" name="action" value="toggle_admin_user_status" />
                        <input type="hidden" name="admin_user_id" value="<?= (int) $user['admin_user_id'] ?>" />
                        <input type="hidden" name="target_status" value="<?= (int) $user['status'] === 1 ? '0' : '1' ?>" />
                        <div class="form-check form-switch d-inline-flex m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                          <input class="form-check-input" type="checkbox" id="adminUserStatusToggle<?= (int) $user['admin_user_id'] ?>" name="status" value="1" <?= (int) $user['status'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                        </div>
                      </form>
                    </td>
                    <td><?= htmlspecialchars((string) $user['full_name']) ?></td>
                    <td><?= htmlspecialchars((string) $user['username']) ?></td>
                    <td>
                      <?php if ((int) $user['status'] === 1): ?>
                        <span class="badge badge-subtle-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-danger">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($user['role_name'] ?: '-')) ?></td>
                    <td><?= htmlspecialchars((string) $user['email']) ?><br><small class="text-600"><?= htmlspecialchars((string) $user['mobile']) ?></small></td>
                    <td><?= $user['last_login_at'] ? htmlspecialchars(date('Y-m-d h:i A', strtotime((string) $user['last_login_at']))) : '-' ?></td>
                    <td><?= htmlspecialchars((string) ($user['last_login_ip'] ?: '-')) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3" id="admin-user-form">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Add Admin User</h6>
      </div>
      <div class="card-body">
        <form class="row g-3" action="" method="post">
          <input type="hidden" name="action" value="save_admin_user" />

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="userName">Name</label>
              <input class="form-control form-control-sm" id="userName" name="full_name" type="text" placeholder="Enter full name" required />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="userRole">Role</label>
              <select class="form-select form-select-sm" id="userRole" name="role_id" required>
                <option value="" disabled selected>Select Role</option>
                <?php foreach ($activeRoles as $role): ?>
                  <option value="<?= (int) $role['role_id'] ?>"><?= htmlspecialchars((string) $role['role_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="username">Username</label>
              <input class="form-control form-control-sm" id="username" name="username" type="text" placeholder="Enter username" required />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="userPassword">Password</label>
              <input class="form-control form-control-sm" id="userPassword" name="password" type="password" placeholder="Enter password" required />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="userEmail">Email</label>
              <input class="form-control form-control-sm" id="userEmail" name="email" type="email" placeholder="Enter email" required />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="userMobile">Mobile</label>
              <input class="form-control form-control-sm" id="userMobile" name="mobile" type="text" placeholder="Enter mobile number" required />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 92px;" for="branchName">Branch</label>
              <input class="form-control form-control-sm" id="branchName" name="branch_name" type="text" placeholder="Enter branch" />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <label class="form-label d-block mb-0">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="userStatus" name="status" value="1" checked />
                <label class="form-check-label" for="userStatus">Active</label>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <label class="form-label d-block mb-0">Partner Registration</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="partnerReg" name="partner_registration" value="1" />
                <label class="form-check-label" for="partnerReg">Enable</label>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3 mt-1">
            <button class="btn btn-falcon-default btn-sm" type="reset">Reset</button>
            <button class="btn btn-primary btn-sm" type="submit">
              <span class="fas fa-save me-1"></span>Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>
