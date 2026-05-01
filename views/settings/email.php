<?php
// Email Settings View
?>
<div class="row g-3 mb-3">
  <div class="col-12 col-lg-10">
    <div class="card h-100">
      <div class="card-header bg-body-tertiary d-flex flex-between-center">
        <h6 class="mb-0">Email (SMTP) Settings</h6>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" id="email_status" name="email_status" form="emailForm" type="checkbox" value="1" <?= ($email_map['email_status'] ?? '0') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label mb-0" for="email_status">Enable Email</label>
        </div>
      </div>
      <div class="card-body">
        <form method="post" id="emailForm">
            <?= ispts_csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="email_form_name" class="form-label">Form Name</label>
              <input type="text" class="form-control" id="email_form_name" name="email_form_name" value="<?= htmlspecialchars($email_map['email_form_name'] ?? '') ?>" placeholder="e.g. isp360 Support">
            </div>
            <div class="col-md-6">
              <label for="email_form_email" class="form-label">Form Email</label>
              <input type="email" class="form-control" id="email_form_email" name="email_form_email" value="<?= htmlspecialchars($email_map['email_form_email'] ?? '') ?>" placeholder="noreply@isp360.com">
            </div>
            <div class="col-md-6">
              <label for="email_smtp_host" class="form-label">SMTP Host</label>
              <input type="text" class="form-control" id="email_smtp_host" name="email_smtp_host" value="<?= htmlspecialchars($email_map['email_smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-3">
              <label for="email_smtp_port" class="form-label">SMTP Port</label>
              <input type="text" class="form-control" id="email_smtp_port" name="email_smtp_port" value="<?= htmlspecialchars($email_map['email_smtp_port'] ?? '') ?>" placeholder="587">
            </div>
            <div class="col-md-3">
              <label for="email_encryption" class="form-label">Email Encryption</label>
              <select class="form-select" id="email_encryption" name="email_encryption">
                <option value="NO" <?= ($email_map['email_encryption'] ?? '') === 'NO' ? 'selected' : '' ?>>NO</option>
                <option value="SSL" <?= ($email_map['email_encryption'] ?? '') === 'SSL' ? 'selected' : '' ?>>SSL</option>
                <option value="TLS" <?= ($email_map['email_encryption'] ?? '') === 'TLS' ? 'selected' : '' ?>>TLS</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="email_smtp_username" class="form-label">SMTP Username</label>
              <input type="text" class="form-control" id="email_smtp_username" name="email_smtp_username" value="<?= htmlspecialchars($email_map['email_smtp_username'] ?? '') ?>" placeholder="user@domain.com">
            </div>
            <div class="col-md-6">
              <label for="email_smtp_password" class="form-label">SMTP Password</label>
              <input type="password" class="form-control" id="email_smtp_password" name="email_smtp_password" value="<?= htmlspecialchars($email_map['email_smtp_password'] ?? '') ?>" placeholder="Password">
            </div>
            <div class="col-12">
              <label for="email_signature" class="form-label">Email Signature</label>
              <textarea class="form-control" id="email_signature" name="email_signature" rows="3" placeholder="Regards, Team isp360"><?= htmlspecialchars($email_map['email_signature'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="mt-4 border-top pt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <span class="fas fa-save me-1"></span>Save Email Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
