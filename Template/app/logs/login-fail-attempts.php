<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Login Fail Attempts</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Login Fail Attempts</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Failed Login Attempts</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Attempt ID</th>
                    <th>Username</th>
                    <th>IP Address</th>
                    <th>Time</th>
                    <th>Reason</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>FAIL001</td>
                    <td>testuser</td>
                    <td>192.168.1.150</td>
                    <td>2024-04-15 08:45 AM</td>
                    <td>Invalid Password</td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

