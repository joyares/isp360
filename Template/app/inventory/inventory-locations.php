<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Inventory Locations</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Inventory Locations</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Storage Locations</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Location ID</th>
                    <th>Location Name</th>
                    <th>Capacity</th>
                    <th>Current Items</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>LOC001</td>
                    <td>Main Warehouse</td>
                    <td>5000</td>
                    <td>2450</td>
                    <td><span class="badge bg-success">Active</span></td>
                  </tr>
                  <tr>
                    <td>LOC002</td>
                    <td>Secondary Storage</td>
                    <td>2000</td>
                    <td>890</td>
                    <td><span class="badge bg-success">Active</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

