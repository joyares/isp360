<?php
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Dashboard</a></li>
    <li class="breadcrumb-item">Location Management</li>
    <li class="breadcrumb-item active">Country</li>
  </ol>
</nav>

<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Country</h1>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary btn-sm" type="button">
        <span class="fas fa-cog me-1"></span>Actions
      </button>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-2 col-lg-3">
    <div class="card h-100">
      <div class="card-body p-0">
        <div class="px-3 pt-3 pb-2 text-uppercase fw-semi-bold text-600 fs-11">Menu</div>
        <div class="list-group list-group-flush rounded-0">
          <a class="list-group-item list-group-item-action active" href="#">Country</a>
          <a class="list-group-item list-group-item-action" href="#">Division</a>
          <a class="list-group-item list-group-item-action" href="#">District</a>
          <a class="list-group-item list-group-item-action" href="#">Upazila/Thana</a>
          <a class="list-group-item list-group-item-action" href="#">Area</a>
          <a class="list-group-item list-group-item-action" href="#">Block</a>
          <a class="list-group-item list-group-item-action" href="#">Road</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-10 col-lg-9">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Country List</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-falcon-default btn-sm" type="button">
            <span class="fas fa-sync-alt"></span>
          </button>
          <button class="btn btn-primary btn-sm" type="button">
            <span class="fas fa-plus me-1"></span>Add
          </button>
          <button class="btn btn-info btn-sm text-white" type="button">
            <span class="fas fa-edit me-1"></span>Edit
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th style="width: 36px;"></th>
                <th>ID</th>
                <th>Country Name</th>
                <th>Description</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input class="form-check-input" type="checkbox" /></td>
                <td>2</td>
                <td>Nepal</td>
                <td>Nepal</td>
                <td class="text-center"><span class="badge badge-subtle-primary">Enable</span></td>
              </tr>
              <tr>
                <td><input class="form-check-input" type="checkbox" /></td>
                <td>1</td>
                <td>Bangladesh</td>
                <td>Dhaka</td>
                <td class="text-center"><span class="badge badge-subtle-primary">Enable</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>

