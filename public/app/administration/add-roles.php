<?php
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/administration/roles.php">Roles</a></li>
    <li class="breadcrumb-item active">Add Role</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Add Role</h1>
    </div>
    <div class="col-auto">
      <a href="<?= $appBasePath ?>/app/administration/roles.php" class="btn btn-falcon-default btn-sm">
        <span class="fas fa-arrow-left me-1"></span>Back to Roles
      </a>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-5">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Role Details</h6>
      </div>
      <div class="card-body">
        <form class="row g-3" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="roleGroup">Role Group</label>
            <select class="form-select" id="roleGroup" name="role_group">
              <option value="" disabled selected>Select Role Group</option>
              <option value="management">Management</option>
              <option value="staff">Staff</option>
              <option value="technical">Technical</option>
              <option value="finance">Finance</option>
              <option value="support">Support</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="roleName">Role Name</label>
            <input class="form-control" id="roleName" name="role_name" type="text" placeholder="Enter role name" required />
          </div>
          <div class="col-12">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="roleStatus" name="status" value="1" checked />
              <label class="form-check-label" for="roleStatus">Active</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="roleDescription">Description</label>
            <textarea class="form-control" id="roleDescription" name="description" rows="3" placeholder="Enter role description"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= $appBasePath ?>/app/administration/roles.php" class="btn btn-falcon-default btn-sm">Cancel</a>
            <button class="btn btn-primary btn-sm" type="submit">Save Role</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Select Menus / Permissions</h6>
      </div>
      <div class="card-body">
        <p class="text-700 fs-11 mb-3">Select the menus and features this role can access.</p>
        <div class="row g-3">

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">Support Desk</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_support_view" name="permissions[]" value="support_view" />
                <label class="form-check-label fs-10" for="perm_support_view">View Tickets</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_support_add" name="permissions[]" value="support_add" />
                <label class="form-check-label fs-10" for="perm_support_add">Add Ticket</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_support_edit" name="permissions[]" value="support_edit" />
                <label class="form-check-label fs-10" for="perm_support_edit">Edit Ticket</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_support_mgmt" name="permissions[]" value="support_mgmt" />
                <label class="form-check-label fs-10" for="perm_support_mgmt">Ticket Mgmt</label>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">Inventory</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_inventory_view" name="permissions[]" value="inventory_view" />
                <label class="form-check-label fs-10" for="perm_inventory_view">View Products</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_inventory_add" name="permissions[]" value="inventory_add" />
                <label class="form-check-label fs-10" for="perm_inventory_add">Add Product</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_inventory_edit" name="permissions[]" value="inventory_edit" />
                <label class="form-check-label fs-10" for="perm_inventory_edit">Edit Product</label>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">Finance</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_finance_view" name="permissions[]" value="finance_view" />
                <label class="form-check-label fs-10" for="perm_finance_view">View Finance</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_finance_add" name="permissions[]" value="finance_add" />
                <label class="form-check-label fs-10" for="perm_finance_add">Add Invoice</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_finance_edit" name="permissions[]" value="finance_edit" />
                <label class="form-check-label fs-10" for="perm_finance_edit">Edit Invoice</label>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">HR &amp; Payroll</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_hr_view" name="permissions[]" value="hr_view" />
                <label class="form-check-label fs-10" for="perm_hr_view">View HR</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_hr_payroll" name="permissions[]" value="hr_payroll" />
                <label class="form-check-label fs-10" for="perm_hr_payroll">Process Payroll</label>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">Reports</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_reports_view" name="permissions[]" value="reports_view" />
                <label class="form-check-label fs-10" for="perm_reports_view">View Reports</label>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fs-10 text-uppercase text-600 mb-2">Administration</h6>
            <div class="d-flex flex-column gap-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_admin_roles" name="permissions[]" value="admin_roles" />
                <label class="form-check-label fs-10" for="perm_admin_roles">Manage Roles</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_admin_users" name="permissions[]" value="admin_users" />
                <label class="form-check-label fs-10" for="perm_admin_users">Manage Admin Users</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="perm_admin_branches" name="permissions[]" value="admin_branches" />
                <label class="form-check-label fs-10" for="perm_admin_branches">Manage Branches</label>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>
