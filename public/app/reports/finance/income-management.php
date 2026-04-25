<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Income Management</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Income Management</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Income Records</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Income ID</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>INC001</td>
                    <td>Sales</td>
                    <td>$5,000.00</td>
                    <td>2024-04-15</td>
                    <td><span class="badge bg-success">Received</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

