<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Renewal Report</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Renewal Report</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Partnership Renewals</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Partner</th>
                    <th>Contract End Date</th>
                    <th>Renewal Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Tech Partners Inc</td>
                    <td>2024-12-31</td>
                    <td>2024-10-01</td>
                    <td><span class="badge bg-warning">Pending</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

