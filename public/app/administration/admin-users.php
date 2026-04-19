<?php
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
    <div class="col-auto">
      <a href="<?= $appBasePath ?>/app/administration/add-admin-user.php" class="btn btn-primary btn-sm">
        <span class="fas fa-plus me-1"></span>Add Admin User
      </a>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header border-bottom border-200">
    <h5 class="mb-0">Admin User List</h5>
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
            <th class="text-800">User Type</th>
            <th class="text-800">Department</th>
            <th class="text-800">SuperAdmin</th>
            <th class="text-800">Roles</th>
            <th class="text-800">Email</th>
            <th class="text-800">Mobile</th>
            <th class="text-800">Last Login Date</th>
            <th class="text-800">Last Login IP</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit" onclick="window.location.href='<?= $appBasePath ?>/app/administration/add-admin-user.php?id=1'">
                <span class="fas fa-edit text-500"></span>
              </button>
              <button class="btn btn-link p-0 ms-2" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Off" aria-label="Off">
                <span class="fas fa-power-off text-danger"></span>
              </button>
            </td>
            <td>Mostafa Joy</td>
            <td>mostafa.joy</td>
            <td><span class="badge badge-subtle-success">Active</span></td>
            <td>Internal</td>
            <td>Administration</td>
            <td><span class="badge badge-subtle-primary">Yes</span></td>
            <td>Administrator</td>
            <td>admin@isp360.com</td>
            <td>+8801700000000</td>
            <td>2026-04-18 09:20 AM</td>
            <td>192.168.0.12</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>

