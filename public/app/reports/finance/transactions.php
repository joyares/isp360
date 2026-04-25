<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Transactions</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Transactions</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Transaction History</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Transaction ID</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Type</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>TXN001</td>
                    <td>2024-04-15</td>
                    <td>Deposit</td>
                    <td>$1,000.00</td>
                    <td><span class="badge bg-success">Credit</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

