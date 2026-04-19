<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Add Partner</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Add Partner</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Add New Partner</h5>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">Partner Name</label>
                  <input class="form-control" type="text" placeholder="Enter partner name">
                </div>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input class="form-control" type="email" placeholder="Enter email">
                </div>
                <div class="mb-3">
                  <label class="form-label">Phone</label>
                  <input class="form-control" type="tel" placeholder="Enter phone number">
                </div>
                <button class="btn btn-primary" type="submit">Add Partner</button>
              </form>
            </div>
<?php
require '../../includes/footer.php';
?>

