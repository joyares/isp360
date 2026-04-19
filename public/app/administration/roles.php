<?php
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Roles</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Roles</h1>
    </div>
  </div>
</div>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Role List</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">#</th>
                <th class="text-800">Name</th>
                <th class="text-800">Role Type</th>
                <th class="text-800">Status</th>
                <th class="text-800">Added Date</th>
                <th class="text-800">Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <button class="btn btn-link p-0 ms-2" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Off" aria-label="Off">
                    <span class="fas fa-power-off text-danger"></span>
                  </button>
                </td>
                <td>1</td>
                <td>Administrator</td>
                <td>Super Admin</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>2026-01-01</td>
                <td>Full system access</td>
              </tr>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <button class="btn btn-link p-0 ms-2" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Off" aria-label="Off">
                    <span class="fas fa-power-off text-danger"></span>
                  </button>
                </td>
                <td>2</td>
                <td>Support Agent</td>
                <td>Staff</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>2026-01-15</td>
                <td>Handle support tickets</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Create Role Form</h5>
      </div>
      <div class="card-body">
        <form class="row g-3" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="roleName">Role Name</label>
            <input class="form-control form-control-sm" id="roleName" name="role_name" type="text" placeholder="Enter role name" required />
          </div>

          <div class="col-12">
            <label class="form-label" for="roleType">Role Type</label>
            <select class="form-select form-select-sm" id="roleType" name="role_type">
              <option value="" disabled selected>Select Role Type</option>
              <option value="super_admin">Super Admin</option>
              <option value="staff">Staff</option>
              <option value="manager">Manager</option>
              <option value="partner">Partner</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="roleStatus" name="status" value="1" />
              <label class="form-check-label fs-10" for="roleStatus">Active</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="roleMenus">Menus</label>
            <select class="form-select form-select-sm" id="roleMenus" name="menu_access">
              <option value="" disabled selected>Select Menus</option>
              <option value="dashboard">Dashboard</option>
              <option value="support_desk">Support Desk</option>
              <option value="inventory">Inventory</option>
              <option value="finance">Finance</option>
              <option value="administration">Administration</option>
              <option value="reports">Reports</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="roleDescription">Description</label>
            <textarea class="form-control form-control-sm" id="roleDescription" name="description" rows="3" placeholder="Enter description"></textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <button class="btn btn-falcon-default btn-sm" type="reset">Reset</button>
            <button class="btn btn-primary btn-sm" type="submit">
              <span class="fas fa-save me-1"></span>Save Role
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

