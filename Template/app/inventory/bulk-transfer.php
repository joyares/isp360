<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Bulk Transfer</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Bulk Transfer</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Transfer Multiple Items</h5>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">From Location</label>
                  <select class="form-control">
                    <option>Main Warehouse</option>
                    <option>Secondary Storage</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">To Location</label>
                  <select class="form-control">
                    <option>Main Warehouse</option>
                    <option>Secondary Storage</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Items to Transfer</label>
                  <textarea class="form-control" rows="4" placeholder="Enter items separated by comma"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Transfer</button>
              </form>
            </div>
<?php
require '../../includes/footer.php';
?>

