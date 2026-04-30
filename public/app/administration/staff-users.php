<?php declare(strict_types=1);
require_once __DIR__ . '/../../../app/Core/Database.php';
use App\Core\Database;
$pdo = Database::getConnection();

// Edit mode support for staff user
$editStaffUser = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  $stmt = $pdo->prepare('SELECT * FROM staff_users WHERE staff_user_id = :id LIMIT 1');
  $stmt->execute([':id' => $editId]);
  $editStaffUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


$pdo->exec(
  "CREATE TABLE IF NOT EXISTS staff_users (
    staff_user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    department VARCHAR(120) NULL,
    designation VARCHAR(120) NULL,
    username VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(190) NOT NULL,
    mobile VARCHAR(30) NOT NULL,

    status TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_staff_users_username (username),
    UNIQUE KEY uk_staff_users_email (email),
    KEY idx_staff_users_role_id (role_id),
    CONSTRAINT fk_staff_users_role_id
      FOREIGN KEY (role_id)
      REFERENCES roles (role_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// Fetch active roles for the dropdown
$activeRoles = [];
try {
  $rolesStmt = $pdo->query("SELECT role_id, role_name FROM roles WHERE status = 1 ORDER BY role_name ASC");
  $activeRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $activeRoles = [];
}

// Ensure columns exist
try {
    $pdo->exec("ALTER TABLE staff_users ADD COLUMN company_id BIGINT UNSIGNED AFTER role_id");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE staff_users ADD COLUMN branch_id BIGINT UNSIGNED AFTER company_id");
} catch (Exception $e) {}

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/staff-users.php';
$savedFlag = $_GET['saved'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_staff_user') {
        $editStaffUserId = isset($_POST['edit_staff_user_id']) ? (int)$_POST['edit_staff_user_id'] : 0;
        $roleId       = isset($_POST['role_id'])    ? (int) $_POST['role_id']    : 0;
        $fullName     = trim((string) ($_POST['full_name']    ?? ''));
        $companyId    = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
        $branchId     = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
        $department   = trim((string) ($_POST['department']   ?? ''));
        $designation  = trim((string) ($_POST['designation']  ?? ''));
        $username     = trim((string) ($_POST['username']     ?? ''));
        $password     = (string) ($_POST['password']          ?? '');
        $email        = trim((string) ($_POST['email']        ?? ''));
        $mobile       = trim((string) ($_POST['mobile']       ?? ''));
        $status       = isset($_POST['status']) ? 1 : 0;

        if ($roleId <= 0 || $fullName === '' || $username === '' || ($editStaffUserId <= 0 && $password === '') || $email === '' || $mobile === '') {
            $alert = ['type' => 'danger', 'message' => 'Role, Name, Username, ' . ($editStaffUserId <= 0 ? 'Password, ' : '') . 'Email and Mobile are required.'];
        } else {
            if ($editStaffUserId > 0) {
                // Update existing
                $updateFields = [
                    'role_id'     => $roleId,
                    'full_name'   => $fullName,
                    'company_id'  => $companyId,
                    'branch_id'   => $branchId,
                    'department'  => $department !== '' ? $department : null,
                    'designation' => $designation !== '' ? $designation : null,
                    'username'    => $username,
                    'email'       => $email,
                    'mobile'      => $mobile,
                    'status'      => $status
                ];
                $setSql = '';
                foreach ($updateFields as $field => $val) {
                    $setSql .= "$field = :$field, ";
                }
                if ($password !== '') {
                    $setSql .= "password_hash = :password_hash, ";
                }
                $setSql = rtrim($setSql, ', ');
                $updateStmt = $pdo->prepare("UPDATE staff_users SET $setSql WHERE staff_user_id = :staff_user_id");
                foreach ($updateFields as $field => $val) {
                    $updateStmt->bindValue(":$field", $val);
                }
                if ($password !== '') {
                    $updateStmt->bindValue(":password_hash", password_hash($password, PASSWORD_DEFAULT));
                }
                $updateStmt->bindValue(":staff_user_id", $editStaffUserId, PDO::PARAM_INT);
                $updateStmt->execute();
                header('Location: ' . $currentPath . '?saved=updated');
                exit;
            } else {
                // Insert new
                $insertStmt = $pdo->prepare(
                    'INSERT INTO staff_users (
                        role_id, full_name, company_id, branch_id, department, designation, username, password_hash,
                        email, mobile, status
                    ) VALUES (
                        :role_id, :full_name, :company_id, :branch_id, :department, :designation, :username, :password_hash,
                        :email, :mobile, :status
                    )'
                );
                $insertStmt->bindValue(':role_id',       $roleId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':full_name',     $fullName);
                $insertStmt->bindValue(':company_id',    $companyId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':branch_id',     $branchId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':department',    $department !== '' ? $department : null);
                $insertStmt->bindValue(':designation',   $designation !== '' ? $designation : null);
                $insertStmt->bindValue(':username',      $username);
                $insertStmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT));
                $insertStmt->bindValue(':email',         $email);
                $insertStmt->bindValue(':mobile',        $mobile);
                $insertStmt->bindValue(':status',        $status, \PDO::PARAM_INT);
                $insertStmt->execute();
                header('Location: ' . $currentPath . '?saved=created');
                exit;
            }
        }
    }
}

    $staffUsersStmt = $pdo->query('SELECT su.*, r.role_name FROM staff_users su LEFT JOIN roles r ON r.role_id = su.role_id ORDER BY su.staff_user_id DESC');
    $staffUsers = $staffUsersStmt->fetchAll(PDO::FETCH_ASSOC);

    require '../../includes/header.php';
    ?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Staff Users</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Staff Users</h1>
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
        <h5 class="mb-0">Staff User List</h5>
        <a href="#staff-user-form" class="btn btn-primary btn-sm">
          <span class="fas fa-plus me-1"></span>Add Staff User
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
                <th class="text-800">Department</th>
                <th class="text-800">Designation</th>
                <th class="text-800">Joining Date</th>
                <th class="text-800">Email / Mobile</th>
                <th class="text-800">Last Login Date</th>
                <th class="text-800">Last Login IP</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($staffUsers)): ?>
                <tr>
                  <td colspan="11" class="text-center py-3 text-600">No staff users found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($staffUsers as $user): ?>
                  <tr>
                    <td>
                      <a href="?edit=<?= (int)$user['staff_user_id'] ?>#staff-user-form" class="btn btn-link p-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                        <span class="fas fa-edit text-500"></span>
                      </a>
                      <form method="post" class="d-inline ms-2 align-middle">
                        <input type="hidden" name="action" value="toggle_staff_user_status" />
                        <input type="hidden" name="staff_user_id" value="<?= (int) $user['staff_user_id'] ?>" />
                        <input type="hidden" name="target_status" value="<?= (int) $user['status'] === 1 ? '0' : '1' ?>" />
                        <div class="form-check form-switch d-inline-flex m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                          <input class="form-check-input" type="checkbox" id="staffUserStatusToggle<?= (int) $user['staff_user_id'] ?>" name="status" value="1" <?= (int) $user['status'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
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
                    <td><?= htmlspecialchars((string) ($user['department'] ?: '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($user['designation'] ?: '-')) ?></td>
                    <td><?= $user['created_at'] ? htmlspecialchars(date('Y-m-d', strtotime((string) $user['created_at']))) : '-' ?></td>
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

  <div class="col-xl-3" id="staff-user-form">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Add Staff User</h6>
      </div>
      <div class="card-body">

        <form class="row g-3" action="" method="post">
          <input type="hidden" name="action" value="save_staff_user" />
          <?php if ($editStaffUser): ?>
            <input type="hidden" name="edit_staff_user_id" value="<?= (int)$editStaffUser['staff_user_id'] ?>" />
          <?php endif; ?>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffFullName">Name</label>
              <input class="form-control form-control-sm" id="staffFullName" name="full_name" type="text" placeholder="Enter full name" required value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['full_name']) : '' ?>" />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffRole">Role</label>
              <select class="form-select form-select-sm" id="staffRole" name="role_id" required>
                <option value="" disabled <?= !$editStaffUser ? 'selected' : '' ?>>Select Role</option>
                <?php
                $roleTypeStmt = $pdo->prepare('SELECT role_type FROM roles WHERE role_id = :role_id LIMIT 1');
                foreach ($activeRoles as $role):
                  $roleType = '';
                  if (!empty($role['role_id'])) {
                    $roleTypeStmt->execute([':role_id' => $role['role_id']]);
                    $roleType = (string)($roleTypeStmt->fetchColumn() ?: '');
                  }
                  if (in_array(strtolower($roleType), ['admin', 'super_admin'], true)) continue;
                  $selected = $editStaffUser && $editStaffUser['role_id'] == $role['role_id'] ? 'selected' : '';
                ?>
                  <option value="<?= (int) $role['role_id'] ?>" <?= $selected ?>><?= htmlspecialchars((string) $role['role_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>


          <?php
          // Fetch companies (mycompany)
          $companies = [];
          try {
            $companiesStmt = $pdo->query("SELECT id, company FROM companies WHERE status = 1");
            $companies = $companiesStmt ? $companiesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
          } catch (Throwable $e) {
            $companies = [];
          }

          // Fetch branches with partner_id
          $branches = [];
          try {
            $branchesStmt = $pdo->query("SELECT branch_id, branch_name, partner_id FROM branches WHERE status = 1");
            $branches = $branchesStmt ? $branchesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
          } catch (Throwable $e) {
            $branches = [];
          }
          ?>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffCompany">Select Company</label>
              <select class="form-select form-select-sm" id="staffCompany" name="company_id" required>
                <option value="" disabled <?= !$editStaffUser ? 'selected' : '' ?>>Select Company</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?= (int)$company['id'] ?>" <?= ($editStaffUser && isset($editStaffUser['company_id']) && $editStaffUser['company_id'] == $company['id']) ? 'selected' : '' ?>><?= htmlspecialchars($company['company']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffBranch">Select Branch</label>
              <select class="form-select form-select-sm" id="staffBranch" name="branch_id" required>
                <option value="" disabled <?= !$editStaffUser ? 'selected' : '' ?>>Select Branch</option>
                <?php foreach ($branches as $branch): ?>
                  <option value="<?= (int)$branch['branch_id'] ?>" data-company="<?= (int)$branch['partner_id'] ?>" <?= $editStaffUser && $editStaffUser['branch_id'] == $branch['branch_id'] ? 'selected' : '' ?>><?= htmlspecialchars($branch['branch_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffDept">Department</label>
              <input class="form-control form-control-sm" id="staffDept" name="department" type="text" placeholder="Enter department" value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['department'] ?? '') : '' ?>" />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffDesig">Designation</label>
              <input class="form-control form-control-sm" id="staffDesig" name="designation" type="text" placeholder="Enter designation" value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['designation'] ?? '') : '' ?>" />
            </div>
          </div>

          <script>
          document.addEventListener('DOMContentLoaded', function() {
            var companySelect = document.getElementById('staffCompany');
            var branchSelect = document.getElementById('staffBranch');
            function filterBranches() {
              var companyId = companySelect.value;
              var foundMatch = false;
              Array.from(branchSelect.options).forEach(function(opt) {
                if (!opt.value) return; // skip placeholder
                var optCompanyId = opt.getAttribute('data-company');
                if (!companyId || optCompanyId === companyId) {
                  opt.hidden = false;
                  opt.disabled = false;
                  if (opt.selected) foundMatch = true;
                } else {
                  opt.hidden = true;
                  opt.disabled = true;
                  if (opt.selected) opt.selected = false;
                }
              });
              // If current selected branch was hidden, reset to placeholder
              if (!foundMatch && branchSelect.value && companyId) {
                branchSelect.selectedIndex = 0;
              }
            }
            companySelect.addEventListener('change', filterBranches);
            // Initial filter on page load (for edit mode)
            filterBranches();
          });
          </script>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffUsername">Username</label>
              <input class="form-control form-control-sm" id="staffUsername" name="username" type="text" placeholder="Enter username" required value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['username']) : '' ?>" />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffPassword">Password</label>
              <input class="form-control form-control-sm" id="staffPassword" name="password" type="password" placeholder="<?= $editStaffUser ? 'Leave blank to keep current password' : 'Enter password' ?>" <?= $editStaffUser ? '' : 'required' ?> />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffEmail">Email</label>
              <input class="form-control form-control-sm" id="staffEmail" name="email" type="email" placeholder="Enter email" required value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['email']) : '' ?>" />
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0 text-nowrap" style="min-width: 100px;" for="staffMobile">Mobile</label>
              <input class="form-control form-control-sm" id="staffMobile" name="mobile" type="text" placeholder="Enter mobile number" required value="<?= $editStaffUser ? htmlspecialchars($editStaffUser['mobile']) : '' ?>" />
            </div>
          </div>



          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <label class="form-label d-block mb-0">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="staffStatus" name="status" value="1" <?= !$editStaffUser || (isset($editStaffUser['status']) && $editStaffUser['status']) ? 'checked' : '' ?> />
                <label class="form-check-label" for="staffStatus">Active</label>
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
