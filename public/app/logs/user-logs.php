<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">User Logs</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">User Logs</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">User Activity Logs</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Time</th>
                    <th>IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>ULOG001</td>
                    <td>user@company.com</td>
                    <td>Profile Updated</td>
                    <td>2024-04-15 11:30 AM</td>
                    <td>192.168.1.100</td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

