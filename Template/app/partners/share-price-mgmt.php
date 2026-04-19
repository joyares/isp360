<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Share &amp; Price Management</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Share &amp; Price Management</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Share Pricing</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Partner</th>
                    <th>Share Price</th>
                    <th>Shares Held</th>
                    <th>Total Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Tech Partners Inc</td>
                    <td>$100.00</td>
                    <td>500</td>
                    <td>$50,000.00</td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

