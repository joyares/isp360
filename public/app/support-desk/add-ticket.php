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
      company_id BIGINT UNSIGNED NULL,
      branch_id BIGINT UNSIGNED NULL,
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

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_companies (
        company_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(190) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_companies_name (company_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_branches (
        branch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id BIGINT UNSIGNED NOT NULL,
        branch_name VARCHAR(190) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_support_ticket_branches_company (company_id),
        UNIQUE KEY uk_support_ticket_branches_company_name (company_id, branch_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$ispts_has_column = static function (PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
  );
  $stmt->bindValue(':table', $table);
  $stmt->bindValue(':column', $column);
  $stmt->execute();
  return (int) $stmt->fetchColumn() > 0;
};

if (!$ispts_has_column($pdo, 'support_tickets', 'company_id')) {
  $pdo->exec('ALTER TABLE support_tickets ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER customer_id');
}
if (!$ispts_has_column($pdo, 'support_tickets', 'branch_id')) {
  $pdo->exec('ALTER TABLE support_tickets ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER company_id');
}

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

$companyCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_companies')->fetchColumn();
if ($companyCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_companies (company_name, sort_order, status)
         VALUES
            ('Friendsonline BD', 10, 1)"
    );
}

$friendsonlineCompanyStmt = $pdo->prepare(
  'SELECT company_id
   FROM support_ticket_companies
   WHERE status = 1 AND company_name = :company_name
   ORDER BY sort_order ASC, company_id ASC
   LIMIT 1'
);
$friendsonlineCompanyStmt->execute(['company_name' => 'Friendsonline BD']);
$friendsonlineCompanyId = (int) $friendsonlineCompanyStmt->fetchColumn();

if ($friendsonlineCompanyId > 0) {
  $mainBranchExistsStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM support_ticket_branches
     WHERE company_id = :company_id
       AND branch_name = :branch_name'
  );
  $mainBranchExistsStmt->execute([
    'company_id' => $friendsonlineCompanyId,
    'branch_name' => 'Main Branch',
  ]);

  if ((int) $mainBranchExistsStmt->fetchColumn() === 0) {
    $mainBranchInsertStmt = $pdo->prepare(
      'INSERT INTO support_ticket_branches (company_id, branch_name, sort_order, status)
       VALUES (:company_id, :branch_name, 10, 1)'
    );
    $mainBranchInsertStmt->execute([
      'company_id' => $friendsonlineCompanyId,
      'branch_name' => 'Main Branch',
    ]);
  }
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

$companiesStmt = $pdo->query(
    'SELECT company_id, company_name
     FROM support_ticket_companies
     WHERE status = 1
     ORDER BY sort_order ASC, company_name ASC'
);
$companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);

$branchesStmt = $pdo->query(
    'SELECT branch_id, company_id, branch_name
     FROM support_ticket_branches
     WHERE status = 1
     ORDER BY sort_order ASC, branch_name ASC'
);
$branches = $branchesStmt->fetchAll(PDO::FETCH_ASSOC);

$branchesByCompany = [];
foreach ($branches as $branch) {
  $companyKey = (int) ($branch['company_id'] ?? 0);
  if ($companyKey <= 0) {
    continue;
  }
  if (!isset($branchesByCompany[$companyKey])) {
    $branchesByCompany[$companyKey] = [];
  }
  $branchesByCompany[$companyKey][] = $branch;
}

$defaultCompanyId = 0;
foreach ($companies as $company) {
  if (strcasecmp(trim((string) ($company['company_name'] ?? '')), 'Friendsonline BD') === 0) {
    $defaultCompanyId = (int) ($company['company_id'] ?? 0);
    break;
  }
}
if ($defaultCompanyId <= 0 && !empty($companies)) {
  $defaultCompanyId = (int) ($companies[0]['company_id'] ?? 0);
}

$defaultBranchId = 0;
if ($defaultCompanyId > 0 && !empty($branchesByCompany[$defaultCompanyId])) {
  foreach ($branchesByCompany[$defaultCompanyId] as $branch) {
    if (strcasecmp(trim((string) ($branch['branch_name'] ?? '')), 'Main Branch') === 0) {
      $defaultBranchId = (int) ($branch['branch_id'] ?? 0);
      break;
    }
  }
  if ($defaultBranchId <= 0) {
    $defaultBranchId = (int) ($branchesByCompany[$defaultCompanyId][0]['branch_id'] ?? 0);
  }
}

$openedStatusStmt = $pdo->prepare(
  'SELECT ticket_status_id
   FROM support_ticket_statuses
   WHERE status = 1 AND status_name = :status_name
   ORDER BY sort_order ASC, ticket_status_id ASC
   LIMIT 1'
);
$openedStatusStmt->execute(['status_name' => 'Opened']);
$openedStatusId = (int) $openedStatusStmt->fetchColumn();
if ($openedStatusId <= 0 && !empty($ticketStatuses)) {
  $openedStatusId = (int) ($ticketStatuses[0]['ticket_status_id'] ?? 0);
}

$openedStatusLabel = 'Opened';
foreach ($ticketStatuses as $ticketStatus) {
  if ((int) ($ticketStatus['ticket_status_id'] ?? 0) === $openedStatusId) {
    $openedStatusLabel = (string) ($ticketStatus['status_name'] ?? 'Opened');
    break;
  }
}

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

$currentUserId = 0;
foreach (['admin_user_id', 'user_id', 'id'] as $sessionUserIdKey) {
  if (isset($_SESSION[$sessionUserIdKey]) && (int) $_SESSION[$sessionUserIdKey] > 0) {
    $currentUserId = (int) $_SESSION[$sessionUserIdKey];
    break;
  }
}

$currentUserName = '';
foreach (['admin_full_name', 'full_name', 'admin_username', 'username'] as $sessionNameKey) {
  $candidateName = trim((string) ($_SESSION[$sessionNameKey] ?? ''));
  if ($candidateName !== '') {
    $currentUserName = $candidateName;
    break;
  }
}

if ($currentUserId <= 0 && $currentUserName !== '') {
  $findByNameStmt = $pdo->prepare(
    'SELECT admin_user_id, full_name, username
     FROM admin_users
     WHERE status = 1 AND (full_name = :name OR username = :name)
     ORDER BY admin_user_id ASC
     LIMIT 1'
  );
  $findByNameStmt->execute(['name' => $currentUserName]);
  $resolvedUser = $findByNameStmt->fetch(PDO::FETCH_ASSOC);
  if (is_array($resolvedUser)) {
    $currentUserId = (int) ($resolvedUser['admin_user_id'] ?? 0);
    $resolvedFullName = trim((string) ($resolvedUser['full_name'] ?? ''));
    $resolvedUsername = trim((string) ($resolvedUser['username'] ?? ''));
    $currentUserName = $resolvedFullName !== '' ? $resolvedFullName : $resolvedUsername;
  }
}

if ($currentUserId <= 0) {
  $fallbackUserStmt = $pdo->prepare(
    'SELECT admin_user_id, full_name, username
     FROM admin_users
     WHERE status = 1 AND full_name = :full_name
     ORDER BY admin_user_id ASC
     LIMIT 1'
  );
  $fallbackUserStmt->execute(['full_name' => 'Mostafa Joy']);
  $fallbackUser = $fallbackUserStmt->fetch(PDO::FETCH_ASSOC);
  if (is_array($fallbackUser)) {
    $currentUserId = (int) ($fallbackUser['admin_user_id'] ?? 0);
    $resolvedFullName = trim((string) ($fallbackUser['full_name'] ?? ''));
    $resolvedUsername = trim((string) ($fallbackUser['username'] ?? ''));
    $currentUserName = $resolvedFullName !== '' ? $resolvedFullName : $resolvedUsername;
  }
}

if ($currentUserId > 0 && $currentUserName === '') {
  $findByIdStmt = $pdo->prepare(
    'SELECT full_name, username
     FROM admin_users
     WHERE status = 1 AND admin_user_id = :admin_user_id
     LIMIT 1'
  );
  $findByIdStmt->bindValue(':admin_user_id', $currentUserId, PDO::PARAM_INT);
  $findByIdStmt->execute();
  $resolvedById = $findByIdStmt->fetch(PDO::FETCH_ASSOC);
  if (is_array($resolvedById)) {
    $resolvedFullName = trim((string) ($resolvedById['full_name'] ?? ''));
    $resolvedUsername = trim((string) ($resolvedById['username'] ?? ''));
    $currentUserName = $resolvedFullName !== '' ? $resolvedFullName : $resolvedUsername;
  }
}

if ($currentUserName === '') {
  $currentUserName = 'Current User';
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ticketFor = (string) ($_POST['ticket_for'] ?? 'existing_customer');
  if (!in_array($ticketFor, ['existing_customer', 'general', 'new_connection'], true)) {
    $ticketFor = 'existing_customer';
  }

  $customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
  $companyId = isset($_POST['company_id']) ? (int) $_POST['company_id'] : $defaultCompanyId;
  $branchId = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : $defaultBranchId;
  $issueDetails = trim((string) ($_POST['issue_details'] ?? ''));
  $categoryId = isset($_POST['ticket_category']) ? (int) $_POST['ticket_category'] : 0;
  $priorityId = isset($_POST['priority']) ? (int) $_POST['priority'] : 0;
  $ticketStatusId = $openedStatusId;
  $assignedEmployeeId = $currentUserId > 0 ? $currentUserId : 0;
  $createdBy = $currentUserId > 0 ? $currentUserId : null;

  if ($ticketFor === 'existing_customer' && $customerId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Please select a customer for Existing Customer ticket.'];
  } elseif ($companyId <= 0 || $branchId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Company and Branch are required.'];
  } elseif ($currentUserId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Current login user not found. Please login again.'];
  } elseif ($ticketStatusId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Opened status is not configured. Please contact administrator.'];
  } elseif ($issueDetails === '' || $categoryId <= 0 || $priorityId <= 0) {
    $alert = ['type' => 'danger', 'message' => 'Category, Priority and Issue Details are required.'];
  } else {
    $ticketNo = 'TKT-' . date('YmdHis') . '-' . random_int(100, 999);

    $insertStmt = $pdo->prepare(
      'INSERT INTO support_tickets (
        ticket_no, ticket_for, customer_id, issue_details,
        company_id, branch_id,
        category_id, priority_id, ticket_status_id, assigned_employee_id,
        status, created_by
       ) VALUES (
        :ticket_no, :ticket_for, :customer_id, :issue_details,
        :company_id, :branch_id,
        :category_id, :priority_id, :ticket_status_id, :assigned_employee_id,
        :status, :created_by
       )'
    );

    $insertStmt->bindValue(':ticket_no', $ticketNo);
    $insertStmt->bindValue(':ticket_for', $ticketFor);
    $insertStmt->bindValue(':customer_id', $customerId > 0 ? $customerId : null, $customerId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $insertStmt->bindValue(':issue_details', $issueDetails);
    $insertStmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
    $insertStmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
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
<?php
$selectedCompanyId = $_SERVER['REQUEST_METHOD'] === 'POST'
  ? (isset($_POST['company_id']) ? (int) $_POST['company_id'] : $defaultCompanyId)
  : $defaultCompanyId;
$selectedBranchId = $_SERVER['REQUEST_METHOD'] === 'POST'
  ? (isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : $defaultBranchId)
  : $defaultBranchId;
?>
<div class="row gx-3">
  <div class="col-6">
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
          <div class="col-md-6">
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
                <label class="form-check-label" for="ticketForNewConnection">Connection Request</label>
              </div>
            </div>
          </div>

          <div class="col-md-6" id="customerFieldWrap">
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
                  <a class="btn btn-link p-0 text-primary" href="#" id="view-customer-link" data-bs-toggle="tooltip" data-bs-placement="top" title="View Customer" aria-label="View Customer">
                    <span class="fas fa-eye fs-9"></span>
                  </a>
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

          <div class="col-md-6">
            <label class="form-label" for="issue-details">Issue Details</label>
            <textarea class="form-control" id="issue-details" name="issue_details" rows="5" placeholder="Enter issue details" required></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="ticket-company">Company</label>
            <select class="form-select" id="ticket-company" name="company_id" required>
              <option value="" disabled <?= $selectedCompanyId <= 0 ? 'selected' : '' ?>>Select company</option>
              <?php foreach ($companies as $company): ?>
                <?php $companyId = (int) ($company['company_id'] ?? 0); ?>
                <option value="<?= $companyId ?>" <?= $selectedCompanyId === $companyId ? 'selected' : '' ?>><?= htmlspecialchars((string) ($company['company_name'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="ticket-branch">Branch</label>
            <select class="form-select" id="ticket-branch" name="branch_id" required>
              <option value="" disabled <?= $selectedBranchId <= 0 ? 'selected' : '' ?>>Select branch</option>
              <?php foreach ($branches as $branch): ?>
                <?php
                  $branchIdOpt = (int) ($branch['branch_id'] ?? 0);
                  $branchCompanyId = (int) ($branch['company_id'] ?? 0);
                ?>
                <option
                  value="<?= $branchIdOpt ?>"
                  data-company-id="<?= $branchCompanyId ?>"
                  <?= $selectedBranchId === $branchIdOpt ? 'selected' : '' ?>
                ><?= htmlspecialchars((string) ($branch['branch_name'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
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
            <select class="form-select" id="status" name="status" disabled>
              <option value="<?= $openedStatusId > 0 ? $openedStatusId : '' ?>" selected><?= htmlspecialchars($openedStatusLabel) ?></option>
            </select>
            <small class="text-600">Status is fixed to <?= htmlspecialchars($openedStatusLabel) ?> while creating ticket.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="assigned-employee">Assigned Employee</label>
            <select class="form-select" id="assigned-employee" name="assigned_employee" disabled>
              <?php if ($currentUserId > 0): ?>
                <option value="<?= $currentUserId ?>" selected><?= htmlspecialchars($currentUserName) ?> (Current User)</option>
              <?php else: ?>
                <option value="" selected>No current user found</option>
              <?php endif; ?>
            </select>
            <small class="text-600">Assigned Employee is fixed to current login user while creating ticket.</small>
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
    var companySelect = document.getElementById('ticket-company');
    var branchSelect = document.getElementById('ticket-branch');

    function syncBranchesByCompany() {
      if (!companySelect || !branchSelect) {
        return;
      }

      var selectedCompany = companySelect.value || '';
      var hasVisibleSelected = false;

      Array.prototype.forEach.call(branchSelect.options, function (option, index) {
        if (index === 0) {
          return;
        }
        var optionCompany = option.getAttribute('data-company-id') || '';
        var visible = selectedCompany !== '' && optionCompany === selectedCompany;
        option.hidden = !visible;
        if (visible && option.selected) {
          hasVisibleSelected = true;
        }
      });

      if (!hasVisibleSelected) {
        branchSelect.value = '';
        Array.prototype.some.call(branchSelect.options, function (option, index) {
          if (index === 0 || option.hidden) {
            return false;
          }
          option.selected = true;
          return true;
        });
      }
    }

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
    if (companySelect) {
      companySelect.addEventListener('change', syncBranchesByCompany);
    }

    updateCustomerFieldVisibility();
    updateCustomerPreview();
    syncBranchesByCompany();
  })();
</script>
<?php
require '../../includes/footer.php';
?>

