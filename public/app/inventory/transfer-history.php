<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Transfer History</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Transfer History</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Transfer Records</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Transfer ID</th>
                    <th>From Location</th>
                    <th>To Location</th>
                    <th>Date</th>
                    <th>Items Count</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>TRF001</td>
                    <td>Main Warehouse</td>
                    <td>Secondary Storage</td>
                    <td>2024-04-10</td>
                    <td>25</td>
                    <td><span class="badge bg-success">Completed</span></td>
                  </tr>
                  <tr>
                    <td>TRF002</td>
                    <td>Secondary Storage</td>
                    <td>Main Warehouse</td>
                    <td>2024-04-12</td>
                    <td>15</td>
                    <td><span class="badge bg-success">Completed</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

