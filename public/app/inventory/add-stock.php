<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Add Stock</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Add Stock</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Add New Stock</h5>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">Product</label>
                  <input class="form-control" type="text" placeholder="Enter product name">
                </div>
                <div class="mb-3">
                  <label class="form-label">Quantity</label>
                  <input class="form-control" type="number" placeholder="Enter quantity">
                </div>
                <div class="mb-3">
                  <label class="form-label">Unit Price</label>
                  <input class="form-control" type="number" placeholder="Enter unit price">
                </div>
                <button class="btn btn-primary" type="submit">Add Stock</button>
              </form>
            </div>
<?php
require '../../includes/footer.php';
?>

