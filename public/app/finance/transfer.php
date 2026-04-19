<?php
require '../../includes/header.php';
?>
          <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
              <li class="breadcrumb-item active">Transfer</li>
            </ol>
          </nav>
          <div class="page-header mb-4">
            <div class="row align-items-center">
              <div class="col-sm">
                <h1 class="page-header-title">Transfer</h1>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Fund Transfer</h5>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">From Account</label>
                  <select class="form-control">
                    <option>Main Checking Account</option>
                    <option>Savings Account</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">To Account</label>
                  <select class="form-control">
                    <option>Savings Account</option>
                    <option>Main Checking Account</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Amount</label>
                  <input class="form-control" type="number" placeholder="Enter amount">
                </div>
                <button class="btn btn-primary" type="submit">Transfer</button>
              </form>
            </div>
<?php
require '../../includes/footer.php';
?>

