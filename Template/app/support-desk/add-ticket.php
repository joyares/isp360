<?php
require '../../includes/header.php';

$loggedInEmployee = !empty($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Logged in employee';
?>
<div class="row gx-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Add Ticket</h5>
      </div>
      <div class="card-body">
        <form class="row g-3" action="#" method="post">
          <div class="col-12">
            <label class="form-label" for="ticket-customer">Customer</label>
            <input class="form-control" id="ticket-customer" name="customer" list="customer-options" type="text" placeholder="Search or type customer name" required />
            <datalist id="customer-options">
              <option value="Emma Watson"></option>
              <option value="Luke"></option>
              <option value="Finley"></option>
              <option value="Peter Gill"></option>
              <option value="Morrison Banneker"></option>
            </datalist>
          </div>

          <div class="col-12">
            <label class="form-label" for="complaint-description">Complaint Description</label>
            <textarea class="form-control" id="complaint-description" name="complaint_description" rows="5" placeholder="Enter complaint details" required></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="complaint-category">Complaint Category</label>
            <select class="form-select" id="complaint-category" name="complaint_category" required>
              <option value="" selected disabled>Select complaint category</option>
              <option value="billing">Billing</option>
              <option value="technical">Technical Issue</option>
              <option value="service">Service Interruption</option>
              <option value="installation">Installation</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="priority">Priority</label>
            <select class="form-select" id="priority" name="priority" required>
              <option value="high">High</option>
              <option value="medium" selected>Medium</option>
              <option value="low">Low</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="opened" selected>Opened</option>
              <option value="in_progress">In Progress</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="assigned-employee">Assigned Employee</label>
            <select class="form-select" id="assigned-employee" name="assigned_employee" required>
              <option value="logged-in" selected><?php echo htmlspecialchars($loggedInEmployee, ENT_QUOTES, 'UTF-8'); ?></option>
              <option value="anindya">Anindya</option>
              <option value="nowrin">Nowrin</option>
              <option value="khalid">Khalid</option>
            </select>
          </div>

          <div class="col-12 d-flex gap-2 justify-content-end">
            <a class="btn btn-falcon-default" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php">Cancel</a>
            <button class="btn btn-primary" type="submit">Create Ticket</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>

