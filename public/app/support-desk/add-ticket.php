<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_categories (
        category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(120) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_categories_name (category_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_priorities (
        priority_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        priority_name VARCHAR(120) NOT NULL,
        color VARCHAR(30) NOT NULL DEFAULT 'secondary',
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_priorities_name (priority_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_statuses (
        ticket_status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        status_name VARCHAR(120) NOT NULL,
        color VARCHAR(30) NOT NULL DEFAULT 'secondary',
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_statuses_name (status_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_tickets (
      ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ticket_no VARCHAR(40) NOT NULL,
      ticket_for VARCHAR(40) NOT NULL DEFAULT 'existing_customer',
      customer_id BIGINT UNSIGNED NULL,
      issue_details TEXT NOT NULL,
      category_id BIGINT UNSIGNED NULL,
      priority_id BIGINT UNSIGNED NULL,
      ticket_status_id BIGINT UNSIGNED NULL,
      assigned_employee_id BIGINT UNSIGNED NULL,
      status TINYINT(1) NOT NULL DEFAULT 1,
      created_by BIGINT UNSIGNED NULL,
      updated_by BIGINT UNSIGNED NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uk_support_tickets_ticket_no (ticket_no),
      KEY idx_support_tickets_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );

$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_categories')->fetchColumn();
if ($categoryCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_categories (category_name, sort_order, status)
         VALUES
            ('Billing', 10, 1),
            ('Technical Issue', 20, 1),
            ('Service Interruption', 30, 1),
            ('Installation', 40, 1)"
    );
}

$priorityCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_priorities')->fetchColumn();
if ($priorityCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_priorities (priority_name, color, sort_order, status)
         VALUES
            ('Low', 'success', 10, 1),
            ('Medium', 'warning', 20, 1),
            ('High', 'danger', 30, 1),
            ('Critical', 'dark', 40, 1)"
    );
}

$statusCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_statuses')->fetchColumn();
if ($statusCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_statuses (status_name, color, sort_order, status)
         VALUES
            ('Opened', 'success', 10, 1),
            ('In Progress', 'primary', 20, 1),
            ('Pending', 'warning', 30, 1),
            ('Closed', 'secondary', 40, 1)"
    );
}

$categoriesStmt = $pdo->query(
    'SELECT category_id, category_name
     FROM support_ticket_categories
     WHERE status = 1
     ORDER BY sort_order ASC, category_name ASC'
);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$prioritiesStmt = $pdo->query(
    'SELECT priority_id, priority_name
     FROM support_ticket_priorities
     WHERE status = 1
     ORDER BY sort_order ASC, priority_name ASC'
);
$priorities = $prioritiesStmt->fetchAll(PDO::FETCH_ASSOC);

$ticketStatusesStmt = $pdo->query(
    'SELECT ticket_status_id, status_name
     FROM support_ticket_statuses
     WHERE status = 1
     ORDER BY sort_order ASC, status_name ASC'
);
$ticketStatuses = $ticketStatusesStmt->fetchAll(PDO::FETCH_ASSOC);

$employeesStmt = $pdo->query(
    'SELECT admin_user_id, full_name, username
     FROM admin_users
     WHERE status = 1
     ORDER BY full_name ASC, username ASC'
);
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

$customersStmt = $pdo->query(
  'SELECT customer_id, username, phone_no, address
     FROM customers
     WHERE status = 1
     ORDER BY username ASC, phone_no ASC'
);
$customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

$currentUserId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
$currentUserName = trim((string) ($_SESSION['admin_full_name'] ?? ''));
if ($currentUserName === '') {
    $currentUserName = trim((string) ($_SESSION['admin_username'] ?? 'Current User'));
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ticketFor = (string) ($_POST['ticket_for'] ?? 'existing_customer');
  if (!in_array($ticketFor, ['existing_customer', 'general', 'new_connection'], true)) {
    $ticketFor = 'existing_customer';
  }

  $customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
  $issueDetails = trim((string) ($_POST['issue_details'] ?? ''));
  $categoryId = isset($_POST['ticket_category']) ? (int) $_POST['ticket_category'] : 0;
  $priorityId = isset($_POST['priority']) ? (int) $_POST['priority'] : 0;
  $ticketStatusId = isset($_POST['status']) ? (int) $_POST['status'] : 0;
  $assignedEmployeeId = isset($_POST['assigned_employee']) ? (int) $_POST['assigned_employee'] : 0;
  $createdBy = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

  if ($ticketFor === 'existing_customer' && $customerId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Please select a customer for Existing Customer ticket.'];
  } elseif ($issueDetails === '' || $categoryId <= 0 || $priorityId <= 0 || $ticketStatusId <= 0 || $assignedEmployeeId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Category, Priority, Status, Assigned Employee and Issue Details are required.'];
  } else {
    $ticketNo = 'TKT-' . date('YmdHis') . '-' . random_int(100, 999);

    $insertStmt = $pdo->prepare(
      'INSERT INTO support_tickets (
        ticket_no, ticket_for, customer_id, issue_details,
        category_id, priority_id, ticket_status_id, assigned_employee_id,
        status, created_by
       ) VALUES (
        :ticket_no, :ticket_for, :customer_id, :issue_details,
        :category_id, :priority_id, :ticket_status_id, :assigned_employee_id,
        :status, :created_by
       )'
    );

    $insertStmt->bindValue(':ticket_no', $ticketNo);
    $insertStmt->bindValue(':ticket_for', $ticketFor);
    $insertStmt->bindValue(':customer_id', $customerId > 0 ? $customerId : null, $customerId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $insertStmt->bindValue(':issue_details', $issueDetails);
    $insertStmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $insertStmt->bindValue(':priority_id', $priorityId, PDO::PARAM_INT);
    $insertStmt->bindValue(':ticket_status_id', $ticketStatusId, PDO::PARAM_INT);
    $insertStmt->bindValue(':assigned_employee_id', $assignedEmployeeId, PDO::PARAM_INT);
    $insertStmt->bindValue(':status', 1, PDO::PARAM_INT);
    $insertStmt->bindValue(':created_by', $createdBy, $createdBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $insertStmt->execute();

    $newTicketId = (int) $pdo->lastInsertId();
    header('Location: all-tickets.php?ticket_id=' . $newTicketId . '&saved=1');
    exit;
  }
}

require '../../includes/header.php';
?>
<link rel="stylesheet" href="<?= $appBasePath ?>/vendors/choices/choices.min.css">
<div class="row gx-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Add Ticket</h5>
      </div>
      <div class="card-body">
        <?php if ($alert): ?>
          <div class="alert alert-<?= htmlspecialchars((string) $alert['type']) ?> py-2" role="alert">
            <?= htmlspecialchars((string) $alert['message']) ?>
          </div>
        <?php endif; ?>
        <form class="row g-3" action="" method="post">
          <div class="col-12">
            <label class="form-label d-block mb-2">Ticket For</label>
            <div class="d-flex flex-wrap gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="ticket_for" id="ticketForExisting" value="existing_customer" checked>
                <label class="form-check-label" for="ticketForExisting">Existing Customer</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="ticket_for" id="ticketForGeneral" value="general">
                <label class="form-check-label" for="ticketForGeneral">General</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="ticket_for" id="ticketForNewConnection" value="new_connection">
                <label class="form-check-label" for="ticketForNewConnection">New Connection</label>
              </div>
            </div>
          </div>

          <div class="col-12" id="customerFieldWrap">
            <label class="form-label" for="ticket-customer-id">Customer</label>
            <select class="form-select" id="ticket-customer-id" name="customer_id" required>
              <option value="" selected>Select customer</option>
              <?php foreach ($customers as $customer): ?>
                <?php
                  $customerId = (int) ($customer['customer_id'] ?? 0);
                  $username = trim((string) ($customer['username'] ?? ''));
                  $phoneNo = trim((string) ($customer['phone_no'] ?? ''));
                  $address = trim((string) ($customer['address'] ?? ''));
                  $optionLabel = $username;
                  if ($phoneNo !== '') {
                      $optionLabel .= ' | ' . $phoneNo;
                  }
                ?>
                <?php if ($customerId > 0): ?>
                  <option
                    value="<?= $customerId ?>"
                    data-username="<?= htmlspecialchars($username) ?>"
                    data-phone="<?= htmlspecialchars($phoneNo) ?>"
                    data-address="<?= htmlspecialchars($address) ?>"
                  ><?= htmlspecialchars($optionLabel) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <small class="text-600">Search by username or phone from the dropdown.</small>

            <div class="card mt-3">
              <div class="card-body position-relative">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                  <h6 class="mb-0">Customer<span class="badge badge-subtle-info rounded-pill ms-2">Active</span></h6>
                  <a class="fw-semi-bold fs-10 text-nowrap" href="#" id="view-customer-link">View Customer<span class="fas fa-angle-right ms-1"></span></a>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-3 mb-1" id="customer-preview-inline">
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="fas fa-user text-500 fs-9"></span>
                    <span class="fw-semi-bold fs-10 text-truncate" id="preview-customer-name">-</span>
                    <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Name" id="copy-customer-name" aria-label="Copy Name">
                      <span class="fas fa-copy fs-9"></span>
                    </button>
                  </div>
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="fas fa-phone text-500 fs-9"></span>
                    <span class="fw-semi-bold fs-10 text-truncate" id="preview-customer-phone">-</span>
                    <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Phone" id="copy-customer-phone" aria-label="Copy Phone">
                      <span class="fas fa-copy fs-9"></span>
                    </button>
                  </div>
                </div>

                <div class="d-flex align-items-start gap-2">
                  <span class="fas fa-map-marker-alt text-500 fs-9 mt-1"></span>
                  <span class="fw-semi-bold fs-10 text-700" id="preview-customer-address">No address on record</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="issue-details">Issue Details</label>
            <textarea class="form-control" id="issue-details" name="issue_details" rows="5" placeholder="Enter issue details" required></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="ticket-category">Ticket Category</label>
            <select class="form-select" id="ticket-category" name="ticket_category" required>
              <option value="" selected disabled>Select ticket category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars((string) $category['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="priority">Priority</label>
            <select class="form-select" id="priority" name="priority" required>
              <option value="" selected disabled>Select priority</option>
              <?php foreach ($priorities as $priority): ?>
                <option value="<?= (int) $priority['priority_id'] ?>"><?= htmlspecialchars((string) $priority['priority_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="" selected disabled>Select status</option>
              <?php foreach ($ticketStatuses as $ticketStatus): ?>
                <option value="<?= (int) $ticketStatus['ticket_status_id'] ?>"><?= htmlspecialchars((string) $ticketStatus['status_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="assigned-employee">Assigned Employee</label>
            <select class="form-select" id="assigned-employee" name="assigned_employee" required>
              <?php if ($currentUserId > 0): ?>
                <option value="<?= $currentUserId ?>" selected><?= htmlspecialchars($currentUserName) ?> (Current User)</option>
              <?php endif; ?>
              <?php foreach ($employees as $employee): ?>
                <?php $employeeId = (int) ($employee['admin_user_id'] ?? 0); ?>
                <?php if ($employeeId <= 0 || $employeeId === $currentUserId) { continue; } ?>
                <?php
                  $employeeLabel = trim((string) ($employee['full_name'] ?? ''));
                  if ($employeeLabel === '') {
                      $employeeLabel = (string) ($employee['username'] ?? 'Employee');
                  }
                ?>
                <option value="<?= $employeeId ?>"><?= htmlspecialchars($employeeLabel) ?></option>
              <?php endforeach; ?>
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

<script src="<?= $appBasePath ?>/vendors/choices/choices.min.js"></script>
<script>
  (function () {
    function copyToClipboard(text) {
      if (!text || !navigator.clipboard) {
        return;
      }
      navigator.clipboard.writeText(text).catch(function () {});
    }

    var customerFieldWrap = document.getElementById('customerFieldWrap');
    var customerSelect = document.getElementById('ticket-customer-id');
    var ticketForRadios = document.querySelectorAll('input[name="ticket_for"]');
    var previewName = document.getElementById('preview-customer-name');
    var previewPhone = document.getElementById('preview-customer-phone');
    var previewAddress = document.getElementById('preview-customer-address');
    var viewCustomerLink = document.getElementById('view-customer-link');
    var copyCustomerName = document.getElementById('copy-customer-name');
    var copyCustomerPhone = document.getElementById('copy-customer-phone');

    var customerChoices = null;
    if (customerSelect) {
      customerChoices = new Choices(customerSelect, {
        searchEnabled: true,
        itemSelectText: '',
        shouldSort: false,
        placeholder: true,
        searchPlaceholderValue: 'Search customer by username or phone'
      });
    }

    function updateCustomerFieldVisibility() {
      var selected = document.querySelector('input[name="ticket_for"]:checked');
      var isExistingCustomer = selected && selected.value === 'existing_customer';

      customerFieldWrap.style.display = isExistingCustomer ? '' : 'none';
      customerSelect.required = !!isExistingCustomer;

      if (!isExistingCustomer) {
        if (customerChoices) {
          customerChoices.removeActiveItems();
          customerChoices.setChoiceByValue('');
        }
      }

      updateCustomerPreview();
    }

    function updateCustomerPreview() {
      if (!customerSelect) {
        return;
      }

      var selectedOption = customerSelect.options[customerSelect.selectedIndex] || null;
      var name = selectedOption ? (selectedOption.getAttribute('data-username') || '') : '';
      var phone = selectedOption ? (selectedOption.getAttribute('data-phone') || '') : '';
      var address = selectedOption ? (selectedOption.getAttribute('data-address') || '') : '';
      var customerId = selectedOption ? (selectedOption.value || '') : '';

      previewName.textContent = name !== '' ? name : '-';
      previewPhone.textContent = phone !== '' ? phone : '-';
      previewAddress.textContent = address !== '' ? address : 'No address on record';

      if (viewCustomerLink) {
        viewCustomerLink.setAttribute('href', customerId !== '' ? 'customer-registration.php?customer_id=' + encodeURIComponent(customerId) : '#');
      }

      if (copyCustomerName) {
        copyCustomerName.onclick = function () {
          if (name !== '') {
            copyToClipboard(name);
          }
        };
      }

      if (copyCustomerPhone) {
        copyCustomerPhone.onclick = function () {
          if (phone !== '') {
            copyToClipboard(phone);
          }
        };
      }
    }

    ticketForRadios.forEach(function (radio) {
      radio.addEventListener('change', updateCustomerFieldVisibility);
    });

    if (customerSelect) {
      customerSelect.addEventListener('change', updateCustomerPreview);
    }

    updateCustomerFieldVisibility();
    updateCustomerPreview();
  })();
</script>
<?php
require '../../includes/footer.php';
?>

