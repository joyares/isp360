<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Manage Stock</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Manage Stock</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Stock Management</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Current Stock</th>
                    <th>Min Level</th>
                    <th>Max Level</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Laptop Computer</td>
                    <td>45</td>
                    <td>10</td>
                    <td>100</td>
                    <td><span class="badge bg-success">Normal</span></td>
                  </tr>
                  <tr>
                    <td>Office Chair</td>
                    <td>8</td>
                    <td>10</td>
                    <td>50</td>
                    <td><span class="badge bg-warning">Low</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

