<?php
// Notifications Settings View
?>
<div class="row g-3 mb-3">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-body-tertiary">
        <h6 class="mb-0">Notification Settings</h6>
      </div>
      <div class="card-body">
        <form method="post">
          <div class="mb-4">
            <h5 class="fs-9">Create Ticket Notifications</h5>
            <p class="text-500 fs-10">Select which notification methods to trigger when a new ticket is created.</p>
            <div class="form-check custom-checkbox mb-2">
              <input class="form-check-input" id="create_ticket_sms" name="create_ticket_sms" type="checkbox" value="1" <?= ($notification_map['create_ticket_sms'] ?? '0') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label fw-bold" for="create_ticket_sms">SMS Notification</label>
              <div class="fs-11 text-muted">Send an SMS alert to the admin/customer.</div>
            </div>
            <div class="form-check custom-checkbox mb-2">
              <input class="form-check-input" id="create_ticket_email" name="create_ticket_email" type="checkbox" value="1" <?= ($notification_map['create_ticket_email'] ?? '0') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label fw-bold" for="create_ticket_email">Email Notification</label>
              <div class="fs-11 text-muted">Send an email notification to the admin/customer.</div>
            </div>
          </div>

          <div class="mt-4 border-top pt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <span class="fas fa-save me-1"></span>Save Notifications
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
