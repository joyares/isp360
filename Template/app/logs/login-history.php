<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Login History</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Login History</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">User Login Records</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Login ID</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Login Time</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>LOGIN001</td>
                    <td>user@company.com</td>
                    <td>192.168.1.100</td>
                    <td>2024-04-15 09:00 AM</td>
                    <td><span class="badge bg-success">Success</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

