require '../../includes/header.php';

require_once __DIR__ . '/../../../app/Core/Database.php';
use App\Core\Database;
$pdo = Database::getConnection();

$branchesStmt = $pdo->query('SELECT branch_id, branch_name FROM branches WHERE status = 1 ORDER BY branch_name ASC');
$branches = $branchesStmt->fetchAll(\PDO::FETCH_ASSOC);

$rolesStmt = $pdo->query('SELECT role_id, role_name FROM roles WHERE status = 1 ORDER BY role_name ASC');
$roles = $rolesStmt->fetchAll(\PDO::FETCH_ASSOC);
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/administration/admin-users.php">Admin Users</a></li>
    <li class="breadcrumb-item active">Add Admin User</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Add Admin User</h1>
    </div>
    <div class="col-auto">
      <a href="<?= $appBasePath ?>/app/administration/admin-users.php" class="btn btn-falcon-default btn-sm">
        <span class="fas fa-arrow-left me-1"></span>Back to Admin Users
      </a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom border-200">
    <h6 class="mb-0">Admin User Details</h6>
  </div>
  <div class="card-body">
    <form class="row g-3" action="#" method="post">
            <?= ispts_csrf_field() ?>

      <div class="col-md-4">
        <label class="form-label" for="userType">User Type</label>
        <select class="form-select" id="userType" name="user_type">
          <option value="" disabled selected>Select User Type</option>
          <option value="internal">Internal</option>
          <option value="external">External</option>
          <option value="partner">Partner</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userDepartment">Department</label>
        <select class="form-select" id="userDepartment" name="department_id">
          <option value="" disabled selected>Select Department</option>
          <option value="1">Administration</option>
          <option value="2">Finance</option>
          <option value="3">Technical Support</option>
          <option value="4">Sales</option>
          <option value="5">HR</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userRole">Role</label>
        <select class="form-select" id="userRole" name="role_id">
          <option value="" disabled selected>Select Role</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['role_id'] ?>"><?= htmlspecialchars((string) $role['role_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>


      <div class="col-md-4">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" type="text" placeholder="Enter username" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userPassword">Password</label>
        <input class="form-control" id="userPassword" name="password" type="password" placeholder="Enter password" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userBranch">Branch</label>
        <select class="form-select" id="userBranch" name="branch_id">
          <option value="" disabled selected>Select Branch</option>
          <?php foreach ($branches as $branch): ?>
            <option value="<?= (int) $branch['branch_id'] ?>"><?= htmlspecialchars((string) $branch['branch_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>


      <div class="col-md-4">
        <label class="form-label" for="firstName">First Name</label>
        <input class="form-control" id="firstName" name="first_name" type="text" placeholder="Enter first name" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="lastName">Last Name</label>
        <input class="form-control" id="lastName" name="last_name" type="text" placeholder="Enter last name" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userEmail">Email</label>
        <input class="form-control" id="userEmail" name="email" type="email" placeholder="Enter email" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="userMobile">Mobile <span class="text-danger">*</span></label>
        <input class="form-control" id="userMobile" name="mobile" type="text" placeholder="Enter mobile number" required />
      </div>

      <div class="col-md-4">
        <label class="form-label d-block">Status</label>
        <div class="form-check form-switch mt-1">
          <input class="form-check-input" type="checkbox" id="userStatus" name="status" value="1" checked />
          <label class="form-check-label" for="userStatus">Active</label>
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label d-block">Partner Registration</label>
        <div class="form-check form-switch mt-1">
          <input class="form-check-input" type="checkbox" id="partnerReg" name="partner_registration" value="1" />
          <label class="form-check-label" for="partnerReg">Enable</label>
        </div>
      </div>

      <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3 mt-1">
        <a href="<?= $appBasePath ?>/app/administration/admin-users.php" class="btn btn-falcon-default btn-sm">Cancel</a>
        <button class="btn btn-primary btn-sm" type="submit">
          <span class="fas fa-save me-1"></span>Save Admin User
        </button>
      </div>

    </form>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>
