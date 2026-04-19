<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Tickets Logs</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Tickets Logs</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Support Ticket Activity Logs</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Ticket ID</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Time</th>
                    <th>Details</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>TICKET001</td>
                    <td>Created</td>
                    <td>customer@company.com</td>
                    <td>2024-04-15 10:15 AM</td>
                    <td>Issue reported</td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

