<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Stock Items</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Stock Items</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">All Stock Items</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Reorder Level</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>STK001</td>
                    <td>Item Alpha</td>
                    <td>100</td>
                    <td>20</td>
                    <td><button class="btn btn-sm btn-primary">Edit</button></td>
                  </tr>
                  <tr>
                    <td>STK002</td>
                    <td>Item Beta</td>
                    <td>75</td>
                    <td>15</td>
                    <td><button class="btn btn-sm btn-primary">Edit</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

