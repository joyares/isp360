<?php
// General Settings View
?>
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header bg-body-tertiary">
        <h6 class="mb-0">General Settings</h6>
      </div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?= ispts_csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="site_title" class="form-label">Site Title</label>
              <input type="text" class="form-control" id="site_title" name="site_title" value="<?= htmlspecialchars($general_map['site_title'] ?? '') ?>" placeholder="Enter Site Title">
            </div>
            <div class="col-md-6">
              <label for="system_currency" class="form-label">System Currency</label>
              <select class="form-select" id="system_currency" name="system_currency">
                <option disabled value="">Select Currency</option>
                <option value="BDT" <?= ($general_map['system_currency'] ?? '') === 'BDT' ? 'selected' : '' ?>>BDT</option>
                <option value="USD" <?= ($general_map['system_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="site_logo" class="form-label">Site Logo</label>
              <input type="file" class="form-control" id="site_logo" name="site_logo">
              <?php if (!empty($general_map['site_logo'])): ?>
                <div class="mt-2 p-2 border rounded bg-light d-inline-block">
                  <img src="<?= $appBasePath ?>/assets/uploads/<?= htmlspecialchars($general_map['site_logo']) ?>" alt="Logo" style="max-height: 50px;">
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label for="site_favicon" class="form-label">Site Favicon</label>
              <input type="file" class="form-control" id="site_favicon" name="site_favicon">
              <?php if (!empty($general_map['site_favicon'])): ?>
                <div class="mt-2 p-2 border rounded bg-light d-inline-block">
                  <img src="<?= $appBasePath ?>/assets/uploads/<?= htmlspecialchars($general_map['site_favicon']) ?>" alt="Favicon" style="max-height: 32px;">
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="mt-4 border-top pt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <span class="fas fa-save me-1"></span>Save General Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
