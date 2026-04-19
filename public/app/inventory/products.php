<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Products</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Products</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Product Inventory</h5>
              <table class="table">
                <thead>
                  <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>PROD001</td>
                    <td>Laptop Computer</td>
                    <td>Electronics</td>
                    <td>$899.99</td>
                    <td><span class="badge bg-success">In Stock</span></td>
                  </tr>
                  <tr>
                    <td>PROD002</td>
                    <td>Office Chair</td>
                    <td>Furniture</td>
                    <td>$249.99</td>
                    <td><span class="badge bg-success">In Stock</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
<?php
require '../../includes/footer.php';
?>

