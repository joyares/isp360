<?php
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Branches</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Branches</h1>
    </div>
  </div>
</div>

<div class="row g-3">

  <!-- Branch List (left, col-xl-8) -->
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Branch List</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Branch Name</th>
                <th class="text-800">Partner</th>
                <th class="text-800">Email / Mobile</th>
                <th class="text-800">Address</th>
                <th class="text-800">Status</th>
                <th class="text-800">Type</th>
                <th class="text-800">Ratio</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <div class="form-check form-switch d-inline-flex ms-2 m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                    <input class="form-check-input" type="checkbox" id="branchStatusToggle1" name="status" value="1" checked>
                  </div>
                </td>
                <td>Head Office</td>
                <td>Partner A</td>
                <td>headoffice@isp360.com<br><small class="text-600">+8801700000001</small></td>
                <td>Dhaka, Bangladesh</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>Head Office</td>
                <td>50%</td>
              </tr>
              <tr>
                <td>
                  <button class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                    <span class="fas fa-edit text-500"></span>
                  </button>
                  <div class="form-check form-switch d-inline-flex ms-2 m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                    <input class="form-check-input" type="checkbox" id="branchStatusToggle2" name="status" value="1" checked>
                  </div>
                </td>
                <td>Chittagong Branch</td>
                <td>Partner B</td>
                <td>ctg@isp360.com<br><small class="text-600">+8801700000002</small></td>
                <td>Chittagong, Bangladesh</td>
                <td><span class="badge badge-subtle-success">Active</span></td>
                <td>Regional</td>
                <td>30%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add / Edit Branch Form (right, col-xl-4) -->
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Add Branch</h6>
      </div>
      <div class="card-body">
        <form class="row g-3" action="#" method="post">

          <div class="col-12">
            <label class="form-label" for="branchName">Branch Name</label>
            <input class="form-control form-control-sm" id="branchName" name="branch_name" type="text" placeholder="Enter branch name" required />
          </div>

          <div class="col-12">
            <label class="form-label" for="branchPartner">Partner</label>
            <select class="form-select form-select-sm" id="branchPartner" name="partner_id">
              <option value="" disabled selected>Select Partner</option>
              <option value="1">Partner A</option>
              <option value="2">Partner B</option>
              <option value="3">Partner C</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="branchEmail">Email <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" id="branchEmail" name="email" type="email" placeholder="Enter email" required />
          </div>

          <div class="col-12">
            <label class="form-label" for="branchMobile">Mobile <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" id="branchMobile" name="mobile" type="text" placeholder="Enter mobile number" required />
          </div>

          <div class="col-12">
            <label class="form-label" for="branchAddress">Address <span class="text-danger">*</span></label>
            <textarea class="form-control form-control-sm" id="branchAddress" name="address" rows="2" placeholder="Enter address" required></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="branchType">Type</label>
            <select class="form-select form-select-sm" id="branchType" name="branch_type">
              <option value="" disabled selected>Select Type</option>
              <option value="head_office">Head Office</option>
              <option value="regional">Regional</option>
              <option value="local">Local</option>
              <option value="franchise">Franchise</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="branchRatio">Ratio</label>
            <input class="form-control form-control-sm" id="branchRatio" name="ratio" type="number" min="0" max="100" placeholder="e.g. 30" />
          </div>

          <div class="col-12">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="branchStatus" name="status" value="1" checked />
              <label class="form-check-label fs-10" for="branchStatus">Active</label>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <button class="btn btn-falcon-default btn-sm" type="reset">Reset</button>
            <button class="btn btn-primary btn-sm" type="submit">
              <span class="fas fa-save me-1"></span>Save Branch
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

</div>
<?php
require '../../includes/footer.php';
?>

