<?php
// SMS Settings View
?>
<div class="row g-3 mb-3">
  <div class="col-12 col-lg-10">
    <div class="card h-100">
      <div class="card-header bg-body-tertiary d-flex flex-between-center">
        <h6 class="mb-0">SMS API Settings</h6>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" id="sms_status" name="sms_status" form="smsForm" type="checkbox" value="1" <?= ($sms_map['sms_status'] ?? '0') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label mb-0" for="sms_status">Enable SMS</label>
        </div>
      </div>
      <div class="card-body">
        <form method="post" id="smsForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="sms_api_name" class="form-label">API Name</label>
              <input type="text" class="form-control" id="sms_api_name" name="sms_api_name" value="<?= htmlspecialchars($sms_map['sms_api_name'] ?? '') ?>" placeholder="e.g. My SMS Service">
            </div>
            <div class="col-md-6">
              <label for="sms_gateway" class="form-label">SMS Gateway</label>
              <select class="form-select" id="sms_gateway" name="sms_gateway">
                <option value="">Select Gateway</option>
                <option value="Mobireach SMS" <?= ($sms_map['sms_gateway'] ?? '') === 'Mobireach SMS' ? 'selected' : '' ?>>Mobireach SMS</option>
                <option value="Other" <?= ($sms_map['sms_gateway'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="sms_api_method" class="form-label">API Method</label>
              <select class="form-select" id="sms_api_method" name="sms_api_method">
                <option value="GET" <?= ($sms_map['sms_api_method'] ?? '') === 'GET' ? 'selected' : '' ?>>GET</option>
                <option value="POST" <?= ($sms_map['sms_api_method'] ?? '') === 'POST' ? 'selected' : '' ?>>POST</option>
              </select>
            </div>
            <div class="col-12">
              <label for="sms_api_url" class="form-label">API Url</label>
              <textarea class="form-control" id="sms_api_url" name="sms_api_url" rows="3" placeholder="https://api.gateway.com/send?user=xxx&pass=yyy&to=[TO]&msg=[MSG]"><?= htmlspecialchars($sms_map['sms_api_url'] ?? '') ?></textarea>
              <div class="fs-11 text-muted mt-1">Use placeholders like [TO] and [MSG] if required by your gateway.</div>
            </div>
          </div>

          <div class="mt-4 border-top pt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <span class="fas fa-save me-1"></span>Save SMS Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
