<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Roles</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Roles</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">User Roles</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Role ID</th>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>ROLE001</td>
                    <td>Administrator</td>
                    <td>Full system access</td>
                    <td>All</td>
                    <td><span class="badge bg-success">Active</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

