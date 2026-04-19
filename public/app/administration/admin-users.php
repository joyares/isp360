<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Admin Users</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Admin Users</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Administrator Users</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Action</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>User Type</th>
                    <th>Department</th>
                    <th>SuperAdmin</th>
                    <th>Roles</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Last Login Date</th>
                    <th>Last Login IP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><button type="button" class="btn btn-falcon-default btn-sm">Edit</button></td>
                    <td>Admin User</td>
                    <td>admin.user</td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>Internal</td>
                    <td>Administration</td>
                    <td>Yes</td>
                    <td>Administrator</td>
                    <td>admin@company.com</td>
                    <td>+8801700000000</td>
                    <td>2026-04-18 09:20 AM</td>
                    <td>192.168.0.12</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal fade" id="addAdminUserModal" tabindex="-1" aria-labelledby="addAdminUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="addAdminUserModalLabel">Add Admin User</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label" for="adminUserName">Name</label>
                        <input class="form-control" id="adminUserName" type="text" placeholder="Enter full name" />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="adminUsername">Username</label>
                        <input class="form-control" id="adminUsername" type="text" placeholder="Enter username" />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="adminEmail">Email</label>
                        <input class="form-control" id="adminEmail" type="email" placeholder="Enter email" />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="adminMobile">Mobile</label>
                        <input class="form-control" id="adminMobile" type="text" placeholder="Enter mobile number" />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="adminStatus">Status</label>
                        <select class="form-select" id="adminStatus">
                          <option selected>Active</option>
                          <option>Inactive</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="adminUserType">User Type</label>
                        <select class="form-select" id="adminUserType">
                          <option selected>Internal</option>
                          <option>External</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="adminSuperAdmin">SuperAdmin</label>
                        <select class="form-select" id="adminSuperAdmin">
                          <option selected>No</option>
                          <option>Yes</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="adminDepartment">Department</label>
                        <input class="form-control" id="adminDepartment" type="text" placeholder="Enter department" />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="adminRoles">Roles</label>
                        <input class="form-control" id="adminRoles" type="text" placeholder="Enter roles" />
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Admin User</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <script>
            (function () {
              var openHash = '#add-admin-user';
              var initModalHashOpener = function () {
                var modalElement = document.getElementById('addAdminUserModal');
                if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

                var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

                var openFromHash = function () {
                  if (window.location.hash === openHash) {
                    modalInstance.show();
                  }
                };

                modalElement.addEventListener('hidden.bs.modal', function () {
                  if (window.location.hash === openHash) {
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                  }
                });

                window.addEventListener('hashchange', openFromHash);
                openFromHash();
              };

              window.addEventListener('load', initModalHashOpener);
            })();
          </script>
<?php
require '../../includes/footer.php';
?>

