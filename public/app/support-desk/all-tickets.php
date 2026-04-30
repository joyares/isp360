<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$ispts_has_column = static function (PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
  );
  $stmt->bindValue(':table', $table);
  $stmt->bindValue(':column', $column);
  $stmt->execute();
  return (int) $stmt->fetchColumn() > 0;
};

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


if (!$ispts_has_column($pdo, 'support_tickets', 'company_id')) {
  $pdo->exec('ALTER TABLE support_tickets ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER customer_id');
}
if (!$ispts_has_column($pdo, 'support_tickets', 'branch_id')) {
  $pdo->exec('ALTER TABLE support_tickets ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER company_id');
}

$selectedTicketId = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/support-desk/all-tickets.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_ticket') {
        $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
        $issueDetails = trim((string) ($_POST['issue_details'] ?? ''));
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $priorityId = isset($_POST['priority_id']) ? (int) $_POST['priority_id'] : 0;
        $ticketStatusId = isset($_POST['ticket_status_id']) ? (int) $_POST['ticket_status_id'] : 0;
        $assignedEmployeeId = isset($_POST['assigned_employee_id']) ? (int) $_POST['assigned_employee_id'] : 0;
        $activeStatus = isset($_POST['status']) ? 1 : 0;
        $updatedBy = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

        if ($ticketId <= 0 || $issueDetails === '') {
            $alert = ['type' => 'danger', 'message' => 'Ticket and issue details are required.'];
        } else {
            $updateStmt = $pdo->prepare(
                'UPDATE support_tickets
                 SET issue_details = :issue_details,
                     category_id = :category_id,
                     priority_id = :priority_id,
                     ticket_status_id = :ticket_status_id,
                     assigned_employee_id = :assigned_employee_id,
                     status = :status,
                     updated_by = :updated_by
                 WHERE ticket_id = :ticket_id'
            );

            $updateStmt->bindValue(':issue_details', $issueDetails);
            $updateStmt->bindValue(':category_id', $categoryId > 0 ? $categoryId : null, $categoryId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $updateStmt->bindValue(':priority_id', $priorityId > 0 ? $priorityId : null, $priorityId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $updateStmt->bindValue(':ticket_status_id', $ticketStatusId > 0 ? $ticketStatusId : null, $ticketStatusId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $updateStmt->bindValue(':assigned_employee_id', $assignedEmployeeId > 0 ? $assignedEmployeeId : null, $assignedEmployeeId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $updateStmt->bindValue(':status', $activeStatus, PDO::PARAM_INT);
            $updateStmt->bindValue(':updated_by', $updatedBy, $updatedBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $updateStmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
            $updateStmt->execute();

            header('Location: ' . $currentPath . '?ticket_id=' . $ticketId . '&saved=1');
            exit;
        }
    }

    if ($action === 'quick_update_ticket') {
        $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
        $priorityId = isset($_POST['priority_id']) ? (int) $_POST['priority_id'] : 0;
        $assignedEmployeeId = isset($_POST['assigned_employee_id']) ? (int) $_POST['assigned_employee_id'] : 0;
        $updatedBy = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

        if ($ticketId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Invalid ticket selected.'];
        } else {
            $quickUpdateStmt = $pdo->prepare(
                'UPDATE support_tickets
                 SET priority_id = :priority_id,
                     assigned_employee_id = :assigned_employee_id,
                     updated_by = :updated_by
                 WHERE ticket_id = :ticket_id'
            );

            $quickUpdateStmt->bindValue(':priority_id', $priorityId > 0 ? $priorityId : null, $priorityId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $quickUpdateStmt->bindValue(':assigned_employee_id', $assignedEmployeeId > 0 ? $assignedEmployeeId : null, $assignedEmployeeId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $quickUpdateStmt->bindValue(':updated_by', $updatedBy, $updatedBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $quickUpdateStmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
            $quickUpdateStmt->execute();

            header('Location: ' . $currentPath . '?ticket_id=' . $ticketId . '&saved=1');
            exit;
        }
    }

    if ($action === 'add_ticket_note') {
        $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
        $noteText = trim((string) ($_POST['note_text'] ?? ''));
        $createdBy = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;

        if ($ticketId <= 0 || $noteText === '') {
            $alert = ['type' => 'danger', 'message' => 'Ticket note cannot be empty.'];
        } else {
            $noteStmt = $pdo->prepare(
                'INSERT INTO support_ticket_notes (ticket_id, note_text, created_by, status)
                 VALUES (:ticket_id, :note_text, :created_by, 1)'
            );

            $noteStmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
            $noteStmt->bindValue(':note_text', $noteText);
            $noteStmt->bindValue(':created_by', $createdBy, $createdBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $noteStmt->execute();

            header('Location: ' . $currentPath . '?ticket_id=' . $ticketId . '&note_saved=1#selected-ticket-details');
            exit;
        }
    }
}

if ($alert === null && isset($_GET['note_saved'])) {
    $alert = ['type' => 'success', 'message' => 'Ticket note added successfully.'];
} elseif ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Ticket updated successfully.'];
}

$search = trim((string) ($_GET['q'] ?? ''));
$todayDate = date('Y-m-d');
$oneMonthAgoDate = date('Y-m-d', strtotime('-1 month'));

$legacyFilterDate = trim((string) ($_GET['filter_date'] ?? ''));

$filterFromDate = trim((string) ($_GET['filter_from_date'] ?? ''));
$filterToDate = trim((string) ($_GET['filter_to_date'] ?? ''));

// Default: show only today's tickets if no date filter is set
if ($filterFromDate === '' && $filterToDate === '') {
  $filterFromDate = $todayDate;
  $filterToDate = $todayDate;
}

if ($filterFromDate === '' && $legacyFilterDate !== '') {
  $filterFromDate = $legacyFilterDate;
}
if ($filterToDate === '' && $legacyFilterDate !== '') {
  $filterToDate = $legacyFilterDate;
}

if ($filterFromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFromDate)) {
  $filterFromDate = '';
}
if ($filterToDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterToDate)) {
  $filterToDate = '';
}

$rawFilterStatus = isset($_GET['filter_status']) ? trim((string) $_GET['filter_status']) : '';
$showAllStatuses = strcasecmp($rawFilterStatus, 'all') === 0;
$filterStatusId = !$showAllStatuses && $rawFilterStatus !== '' ? (int) $rawFilterStatus : 0;

if ($showAllStatuses) {
  if ($filterFromDate === '') {
    $filterFromDate = $todayDate;
  }
  if ($filterToDate === '') {
    $filterToDate = $todayDate;
  }
} else {
  if ($filterFromDate === '' && $filterToDate === '') {
    $filterFromDate = $todayDate;
    $filterToDate = $todayDate;
  } elseif ($filterFromDate === '') {
    $filterFromDate = $filterToDate;
  } elseif ($filterToDate === '') {
    $filterToDate = $filterFromDate;
  }
}

if ($filterFromDate !== '' && $filterToDate !== '' && strtotime($filterFromDate) > strtotime($filterToDate)) {
  $swapDate = $filterFromDate;
  $filterFromDate = $filterToDate;
  $filterToDate = $swapDate;
}

$filterPriorityId = isset($_GET['filter_priority']) ? (int) $_GET['filter_priority'] : 0;
$filterAssigned = isset($_GET['filter_assigned']) ? (int) $_GET['filter_assigned'] : 0;
$filterBranchId = isset($_GET['filter_branch']) ? (int) $_GET['filter_branch'] : 0;

$categories = [];
$priorities = [];
$ticketStatuses = [];
$employees = [];
$allBranches = [];

try {
    $allBranches = $pdo->query('SELECT branch_id, branch_name FROM branches WHERE status = 1 ORDER BY branch_name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $allBranches = [];
}

try {
    $categories = $pdo->query(
        'SELECT category_id, category_name
         FROM support_ticket_categories
         WHERE status = 1
         ORDER BY sort_order ASC, category_name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

try {
    $priorities = $pdo->query(
        'SELECT priority_id, priority_name
         FROM support_ticket_priorities
         WHERE status = 1
         ORDER BY sort_order ASC, priority_name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $priorities = [];
}

try {
    $ticketStatuses = $pdo->query(
        'SELECT ticket_status_id, status_name
         FROM support_ticket_statuses
         WHERE status = 1
         ORDER BY sort_order ASC, status_name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $ticketStatuses = [];
}

if (!$showAllStatuses && $filterStatusId <= 0) {
  foreach ($ticketStatuses as $ticketStatus) {
    $statusName = trim((string) ($ticketStatus['status_name'] ?? ''));
    if (stripos($statusName, 'open') !== false) {
      $filterStatusId = (int) ($ticketStatus['ticket_status_id'] ?? 0);
      break;
    }
  }
}

try {
    $employees = $pdo->query(
        'SELECT admin_user_id, full_name, username
         FROM admin_users
         WHERE status = 1
         ORDER BY full_name ASC, username ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $employees = [];
}

$where = ['t.status = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(t.ticket_no LIKE :search OR c.username LIKE :search OR c.phone_no LIKE :search OR t.issue_details LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if (!$showAllStatuses && $filterStatusId > 0) {
    $where[] = 't.ticket_status_id = :filter_status';
    $params[':filter_status'] = $filterStatusId;
}

if ($filterPriorityId > 0) {
    $where[] = 't.priority_id = :filter_priority';
    $params[':filter_priority'] = $filterPriorityId;
}

if ($filterAssigned > 0) {
    $where[] = 't.assigned_employee_id = :filter_assigned';
    $params[':filter_assigned'] = $filterAssigned;
}

if ($filterBranchId > 0) {
    $where[] = 't.branch_id = :filter_branch';
    $params[':filter_branch'] = $filterBranchId;
}

if ($filterFromDate !== '' && $filterToDate !== '') {
  $where[] = 'DATE(t.created_at) BETWEEN :filter_from_date AND :filter_to_date';
  $params[':filter_from_date'] = $filterFromDate;
  $params[':filter_to_date'] = $filterToDate;
}

$whereSql = implode(' AND ', $where);
if ($selectedTicketId > 0) {
    $whereSql = "(($whereSql) OR t.ticket_id = :selected_ticket_id_filter)";
    $params[':selected_ticket_id_filter'] = $selectedTicketId;
}

$sql =
    'SELECT t.ticket_id,
            t.customer_id,
            t.ticket_no,
            t.ticket_for,
            t.issue_details,
            t.category_id,
            t.priority_id,
            t.ticket_status_id,
            t.assigned_employee_id,
            t.created_at,
            co.company_name,
            br.branch_name,
            (
                SELECT mc.logo_icon
                FROM companies mc
                WHERE mc.status = 1
                  AND mc.company = co.company_name
                  AND mc.logo_icon IS NOT NULL
                  AND mc.logo_icon <> \'\'
                ORDER BY mc.id DESC
                LIMIT 1
            ) AS company_logo_icon,
            (
                SELECT mc.logo_main
                FROM companies mc
                WHERE mc.status = 1
                  AND mc.company = co.company_name
                  AND mc.logo_main IS NOT NULL
                  AND mc.logo_main <> \'\'
                ORDER BY mc.id DESC
                LIMIT 1
            ) AS company_logo_main,
            (
                SELECT mc.phone
                FROM companies mc
                WHERE mc.status = 1
                  AND mc.company = co.company_name
                  AND mc.phone IS NOT NULL
                  AND mc.phone <> \'\'
                ORDER BY mc.id DESC
                LIMIT 1
            ) AS company_phone,
            (
                SELECT mc.email
                FROM companies mc
                WHERE mc.status = 1
                  AND mc.company = co.company_name
                  AND mc.email IS NOT NULL
                  AND mc.email <> \'\'
                ORDER BY mc.id DESC
                LIMIT 1
            ) AS company_email,
            (
                SELECT mc.address
                FROM companies mc
                WHERE mc.status = 1
                  AND mc.company = co.company_name
                  AND mc.address IS NOT NULL
                  AND mc.address <> \'\'
                ORDER BY mc.id DESC
                LIMIT 1
            ) AS company_address,
            c.username AS customer_username,
            c.phone_no AS customer_phone,
            c.address AS customer_address,
            cat.category_name,
            pri.priority_name,
            pri.color AS priority_color,
            st.status_name,
            au.full_name AS assigned_employee_name,
            au.username AS assigned_employee_username
     FROM support_tickets t
     LEFT JOIN customers c ON c.customer_id = t.customer_id
     LEFT JOIN support_ticket_categories cat ON cat.category_id = t.category_id
     LEFT JOIN support_ticket_priorities pri ON pri.priority_id = t.priority_id
     LEFT JOIN support_ticket_statuses st ON st.ticket_status_id = t.ticket_status_id
     LEFT JOIN admin_users au ON au.admin_user_id = t.assigned_employee_id
    LEFT JOIN support_ticket_companies co ON co.company_id = t.company_id
    LEFT JOIN support_ticket_branches br ON br.branch_id = t.branch_id
     WHERE ' . $whereSql . '
     ORDER BY t.ticket_id DESC';

$listStmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->execute();
$tickets = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedTicketId = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;

// Ticket status summary counts (always unfiltered)
$statusSummary = [];
try {
    $summaryRows = $pdo->query(
        'SELECT COALESCE(st.status_name, "Unassigned") AS status_name,
                COUNT(*) AS cnt
         FROM support_tickets t
         LEFT JOIN support_ticket_statuses st ON st.ticket_status_id = t.ticket_status_id
         WHERE t.status = 1
         GROUP BY st.status_name
         ORDER BY cnt DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $totalTickets = 0;
    foreach ($summaryRows as $sr) {
        $totalTickets += (int) $sr['cnt'];
    }
    $statusSummary = $summaryRows;
} catch (Throwable $e) {
    $totalTickets = 0;
    $statusSummary = [];
}

$selectedTicket = null;
if ($selectedTicketId > 0) {
    foreach ($tickets as $row) {
        if ((int) $row['ticket_id'] === $selectedTicketId) {
            $selectedTicket = $row;
            break;
        }
    }
}

$selectedTicketContextQuery = $_GET;
unset($selectedTicketContextQuery['saved'], $selectedTicketContextQuery['note_saved']);

$ispts_build_ticket_details_query = static function (int $ticketId) use ($selectedTicketContextQuery): string {
  $query = $selectedTicketContextQuery;
  $query['ticket_id'] = $ticketId;
  return '?' . http_build_query($query) . '#selected-ticket-details';
};

$selectedTicketActionQuery = $selectedTicketContextQuery;
if ($selectedTicketId > 0) {
  $selectedTicketActionQuery['ticket_id'] = $selectedTicketId;
}

$selectedTicketActionQueryString = !empty($selectedTicketActionQuery)
  ? ('?' . http_build_query($selectedTicketActionQuery))
  : '';

$ticketNotes = [];
if ($selectedTicket !== null) {
    try {
        $notesStmt = $pdo->prepare(
            'SELECT n.ticket_note_id,
                    n.note_text,
                    n.created_at,
                    n.created_by AS created_by_id,
                    NULLIF(TRIM(au.full_name), \'\') AS created_by_name,
                    NULLIF(TRIM(au.username), \'\') AS created_by_username
             FROM support_ticket_notes n
             LEFT JOIN admin_users au ON au.admin_user_id = n.created_by
             WHERE n.ticket_id = :ticket_id
               AND n.status = 1
             ORDER BY n.ticket_note_id DESC'
        );
        $notesStmt->bindValue(':ticket_id', (int) $selectedTicket['ticket_id'], PDO::PARAM_INT);
        $notesStmt->execute();
        $ticketNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $ticketNotes = [];
    }
}

require '../../includes/header.php';
?>

<style>
  .table-row-hover {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    padding: 8px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
  }
  .table-row-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    cursor: pointer;
  }
  .table-row-hover.active-row {
    background-color: var(--falcon-soft-primary, rgba(44, 123, 229, 0.08));
    box-shadow: 0 0 0 2px rgba(44, 123, 229, 0.3);
  }
  .table-row-hover td {
    padding: 10px 12px !important;
  }
  .ticket-row-action,
  .ticket-row-action * {
    position: relative;
    z-index: 2;
  }
  .ticket-row-action .btn,
  .ticket-row-action a {
    transform: none !important;
  }
</style>

<div class="row gx-3">
  <div class="col-xl-9 col-lg-9 col-12">
    <?php if ($alert): ?>
      <div class="alert alert-<?= htmlspecialchars((string) $alert['type']) ?> py-2" role="alert">
        <?= htmlspecialchars((string) $alert['message']) ?>
      </div>
    <?php endif; ?>

    <div class="card" id="ticketsTable">
      <div class="px-2 py-2 pb-2 pt-2">
        <!-- Row 1: Title, New Button -->
        <div class="row align-items-center gy-2 mb-3">
          <div class="col-auto">
            <h6 class="mb-0">All tickets</h6>
          </div>
          <div class="col-auto ms-auto text-end">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/support-desk/add-ticket.php"><span class="fas fa-plus" data-fa-transform="shrink-3"></span><span class="d-none d-sm-inline-block ms-1">New</span></a>
          </div>
        </div>

        <!-- Row 2: Filters -->
        <form id="filterForm" method="get" action="<?= $appBasePath ?>/app/support-desk/all-tickets.php" class="row align-items-center g-2 mb-3">
          <div class="col">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-sm shadow-none" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by ticket, customer, phone or issue" aria-label="search" />
              <button class="btn btn-sm btn-outline-secondary border-300 hover-border-secondary" type="submit"><span class="fa fa-search fs-10"></span></button>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center justify-content-end gap-2">
          <input type="hidden" id="filterFromDate" name="filter_from_date" value="<?= htmlspecialchars($filterFromDate) ?>">
          <input type="hidden" id="filterToDate" name="filter_to_date" value="<?= htmlspecialchars($filterToDate) ?>">
          <input class="form-control mb-5 js-flatpickr-range" id="supportDeskDateRangePicker" type="text" placeholder="dd/mm/yyyy to dd/mm/yyyy"
            value="<?= (new DateTime($filterFromDate))->format('d/m/Y') ?> to <?= (new DateTime($filterToDate))->format('d/m/Y') ?>"
            data-options='{"mode":"range","dateFormat":"d/m/Y","disableMobile":true,"position":"below"}' />
          <link rel="stylesheet" href="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.css">
          <script src="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.js"></script>
          <script>
            (function() {
              window.addEventListener('DOMContentLoaded', function () {
                var picker = document.getElementById('supportDeskDateRangePicker');
                var from = document.getElementById('filterFromDate');
                var to = document.getElementById('filterToDate');
                var opts = picker.getAttribute('data-options');
                var options = opts ? JSON.parse(opts) : {};

                var serverToday = '<?= $todayDate ?>';
                var defaultDates = (from.value && to.value) ? [from.value, to.value] : [serverToday, serverToday];

                function supportDeskFormatDisplay(date) {
                  if (!date) return '';
                  var d = date.getDate().toString().padStart(2, '0');
                  var m = (date.getMonth() + 1).toString().padStart(2, '0');
                  var y = date.getFullYear();
                  return d + '/' + m + '/' + y;
                }

                function supportDeskFormatISO(date) {
                  if (!date) return '';
                  var y = date.getFullYear();
                  var m = (date.getMonth() + 1).toString().padStart(2, '0');
                  var d = date.getDate().toString().padStart(2, '0');
                  return y + '-' + m + '-' + d;
                }

                flatpickr(picker, Object.assign(options, {
                  defaultDate: defaultDates,
                  formatDate: function(date) {
                    return supportDeskFormatDisplay(date);
                  },
                  onReady: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                      picker.value = supportDeskFormatDisplay(selectedDates[0]) + ' to ' + supportDeskFormatDisplay(selectedDates[1]);
                    }
                  },
                  onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                      from.value = supportDeskFormatISO(selectedDates[0]);
                      to.value = supportDeskFormatISO(selectedDates[1]);
                      picker.value = supportDeskFormatDisplay(selectedDates[0]) + ' to ' + supportDeskFormatDisplay(selectedDates[1]);
                      document.getElementById('filterForm').submit();
                    }
                  }
                }));
              });
            })();
          </script>
          <?php
            $statusLabel = $showAllStatuses ? 'All' : 'Status';
            if (!$showAllStatuses) {
              foreach ($ticketStatuses as $_s) {
                if ((int) $_s['ticket_status_id'] === $filterStatusId) {
                  $statusLabel = htmlspecialchars((string) $_s['status_name']);
                  break;
                }
              }
            }
          ?>
          <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle py-1 px-2" type="button" id="filterStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.75rem;">
              <?= $statusLabel ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="filterStatusDropdown">
              <?php
                $allStatusQuery = $_GET;
                unset($allStatusQuery['filter_date']);
                $allStatusQuery['filter_status'] = 'all';
                $allStatusQuery['filter_from_date'] = $todayDate;
                $allStatusQuery['filter_to_date'] = $todayDate;
              ?>
              <li>
                <a class="dropdown-item <?= $showAllStatuses ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query($allStatusQuery) ?>">All</a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <?php foreach ($ticketStatuses as $item): ?>
                <?php
                  $statusItemQuery = $_GET;
                  unset($statusItemQuery['filter_date']);
                  $statusItemQuery['filter_status'] = (int) $item['ticket_status_id'];
                  $statusItemQuery['filter_from_date'] = $filterFromDate;
                  $statusItemQuery['filter_to_date'] = $filterToDate;
                ?>
                <li>
                  <a class="dropdown-item <?= !$showAllStatuses && $filterStatusId === (int) $item['ticket_status_id'] ? 'active' : '' ?>"
                    href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query($statusItemQuery) ?>">
                    <?= htmlspecialchars((string) $item['status_name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php
            $priorityLabel = 'Priority';
            foreach ($priorities as $_p) {
              if ((int) $_p['priority_id'] === $filterPriorityId) {
                $priorityLabel = htmlspecialchars((string) $_p['priority_name']);
                break;
              }
            }
          ?>
          <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle py-1 px-2" type="button" id="filterPriorityDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.75rem;">
              <?= $priorityLabel ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="filterPriorityDropdown">
              <li>
                <a class="dropdown-item <?= $filterPriorityId === 0 ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_priority' => 0])) ?>">Priority</a>
              </li>
              <?php foreach ($priorities as $item): ?>
                <li>
                  <a class="dropdown-item <?= $filterPriorityId === (int) $item['priority_id'] ? 'active' : '' ?>"
                    href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_priority' => (int) $item['priority_id']])) ?>">
                    <?= htmlspecialchars((string) $item['priority_name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php
            $assignedLabel = 'Agent';
            foreach ($employees as $_e) {
              $employeeLabel = trim((string) ($_e['full_name'] ?? ''));
              if ($employeeLabel === '') {
                $employeeLabel = (string) ($_e['username'] ?? 'Employee');
              }
              if ((int) $_e['admin_user_id'] === $filterAssigned) {
                $assignedLabel = htmlspecialchars($employeeLabel);
                break;
              }
            }
          ?>
          <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle py-1 px-2" type="button" id="filterAssignedDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.75rem;">
              <?= $assignedLabel ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="filterAssignedDropdown">
              <li>
                <a class="dropdown-item <?= $filterAssigned === 0 ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_assigned' => 0])) ?>">Agent</a>
              </li>
              <?php foreach ($employees as $item): ?>
                <?php
                  $label = trim((string) ($item['full_name'] ?? ''));
                  if ($label === '') {
                    $label = (string) ($item['username'] ?? 'Employee');
                  }
                ?>
                <li>
                  <a class="dropdown-item <?= $filterAssigned === (int) $item['admin_user_id'] ? 'active' : '' ?>"
                    href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_assigned' => (int) $item['admin_user_id']])) ?>">
                    <?= htmlspecialchars($label) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php
            $branchLabel = 'Branch';
            foreach ($allBranches as $_b) {
              if ((int) $_b['branch_id'] === $filterBranchId) {
                $branchLabel = htmlspecialchars((string) $_b['branch_name']);
                break;
              }
            }
          ?>
          <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle py-1 px-2" type="button" id="filterBranchDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.75rem;">
              <?= $branchLabel ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="filterBranchDropdown">
              <li>
                <a class="dropdown-item <?= $filterBranchId === 0 ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_branch' => 0])) ?>">Branch</a>
              </li>
              <?php foreach ($allBranches as $item): ?>
                <li>
                  <a class="dropdown-item <?= $filterBranchId === (int) $item['branch_id'] ? 'active' : '' ?>"
                    href="<?= $appBasePath ?>/app/support-desk/all-tickets.php?<?= http_build_query(array_merge($_GET, ['filter_branch' => (int) $item['branch_id']])) ?>">
                    <?= htmlspecialchars((string) $item['branch_name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <a class="btn btn-link p-0 text-primary" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset Filters">
            <span class="fas fa-redo fs-9"></span>
          </a>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm mb-0 fs-10 table-view-tickets">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800 align-middle ps-2">Action</th>
                <th class="text-800 align-middle ps-2" style="min-width:12rem">Ticket</th>
                <th class="text-800 align-middle ps-2">Client</th>
                <th class="text-800 align-middle" style="min-width:15.625rem">Issue</th>
                <th class="text-800 align-middle">Priority</th>
                <th class="text-800 align-middle">Company</th>
                <th class="text-800 align-middle text-end">Agent</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tickets)): ?>
                <tr>
                  <td colspan="7" class="text-center py-3 text-600">No tickets found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                  <?php
                    $ticketDetailsQuery = $ispts_build_ticket_details_query((int) $ticket['ticket_id']);
                    $statusLabel = (string) ($ticket['status_name'] ?: 'Unspecified');
                    $priorityLabel = (string) ($ticket['priority_name'] ?: 'Normal');
                    $clientLabel = (string) ($ticket['customer_username'] ?: 'General');
                    $companyLabel = (string) (($ticket['company_name'] ?? '') !== '' ? $ticket['company_name'] : '-');
                    $branchLabel = (string) (($ticket['branch_name'] ?? '') !== '' ? $ticket['branch_name'] : '-');
                    $companyLogo = trim((string) ($ticket['company_logo_icon'] ?? ''));
                    $companyLogoUrl = $companyLogo !== '' ? ($appBasePath . '/' . ltrim($companyLogo, '/')) : '';
                    $agentLabel = (string) ($ticket['assigned_employee_name'] ?: $ticket['assigned_employee_username'] ?: '-');

                    $statusClass = 'secondary';
                    if (stripos($statusLabel, 'open') !== false || stripos($statusLabel, 'recent') !== false) {
                      $statusClass = 'success';
                    } elseif (stripos($statusLabel, 'progress') !== false || stripos($statusLabel, 'responded') !== false) {
                      $statusClass = 'info';
                    } elseif (stripos($statusLabel, 'pending') !== false) {
                      $statusClass = 'warning';
                    }

                    $falconColorHex = [
                      'primary'   => '#2c7be5',
                      'success'   => '#00d27a',
                      'warning'   => '#f5803e',
                      'danger'    => '#e63757',
                      'info'      => '#27bcfd',
                      'secondary' => '#748194',
                      'dark'      => '#0b1727',
                    ];
                    $rawPriorityColor = strtolower(trim((string) ($ticket['priority_color'] ?? 'primary')));
                    $priorityStroke = $falconColorHex[$rawPriorityColor] ?? '#2c7be5';
                  ?>
                  <?php
                    $createdTime = new DateTime($ticket['created_at'] ?? 'now');
                    $updatedTime = new DateTime($ticket['updated_at'] ?? 'now');
                    $now = new DateTime();
                    $duration = $now->diff($createdTime);
                    $durationText = '';
                    if ($duration->d > 0) {
                      $durationText = $duration->d . 'd';
                    } elseif ($duration->h > 0) {
                      $durationText = $duration->h . 'h';
                    } elseif ($duration->i > 0) {
                      $durationText = $duration->i . 'm';
                    } else {
                      $durationText = 'now';
                    }
                  ?>
                  <tr class="table-row-hover <?= $selectedTicketId === (int) $ticket['ticket_id'] ? 'active-row' : '' ?>" style="cursor:pointer;" onclick="window.location='<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($ticketDetailsQuery, ENT_QUOTES, 'UTF-8') ?>'">
                    <td class="align-middle ps-2 ticket-row-action" onclick="event.stopPropagation();">
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($ticketDetailsQuery) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" onclick="event.stopPropagation();">
                        <span class="fas fa-edit text-500"></span>
                      </a>
                      <?php if ((int) ($ticket['customer_id'] ?? 0) > 0): ?>
                        <a class="btn btn-link p-0 ms-2 text-primary" href="<?= $appBasePath ?>/app/support-desk/customer-details.php?id=<?= (int) $ticket['customer_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View Customer" onclick="event.stopPropagation();">
                          <span class="fas fa-eye fs-9"></span>
                        </a>
                      <?php endif; ?>
                    </td>
                    <td class="align-middle ps-2">
                      <div class="d-flex flex-column gap-1">
                        <div class="fw-semi-bold fs-10"><?= htmlspecialchars((string) $ticket['ticket_no']) ?></div>
                        <small class="badge rounded badge-subtle-<?= $statusClass ?> align-self-start"><?= htmlspecialchars($statusLabel) ?></small>
                        <div class="d-flex align-items-center gap-1">
                          <small class="badge badge-subtle-success rounded-pill" style="font-size: 0.7rem;"><span class="fas fa-hourglass-end me-1" style="font-size: 0.65rem;"></span><?= $durationText ?></small>
                          <small class="badge badge-subtle-secondary rounded-pill" style="font-size: 0.7rem;"><span class="fas fa-sync me-1" style="font-size: 0.65rem;"></span><?= $updatedTime->format('H:i') ?></small>
                        </div>
                      </div>
                    </td>
                    <td class="align-middle client white-space-nowrap pe-3 pe-xxl-4 ps-2">
                      <div class="d-flex align-items-center gap-2 position-relative">
                        <div class="avatar avatar-xl">
                          <?php if ($companyLogoUrl !== ''): ?>
                            <img class="rounded-circle" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="<?= htmlspecialchars($companyLabel) ?> logo" style="object-fit: cover; width: 100%; height: 100%;">
                          <?php else: ?>
                            <div class="avatar-name rounded-circle"><span><?= htmlspecialchars(strtoupper(substr($companyLabel !== '-' ? $companyLabel : $clientLabel, 0, 1))) ?></span></div>
                          <?php endif; ?>
                        </div>
                        <div class="d-flex flex-column gap-1">
                          <h6 class="mb-0"><a class="stretched-link text-900" href="<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($ticketDetailsQuery) ?>"><?= htmlspecialchars($clientLabel) ?></a></h6>
                          <small class="text-600"><?= htmlspecialchars((string) (($ticket['customer_phone'] ?? '') !== '' ? $ticket['customer_phone'] : '-')) ?></small>
                        </div>
                      </div>
                    </td>
                    <td class="align-middle subject py-2 pe-4">
                      <div class="d-flex flex-column gap-1">
                        <small class="badge badge-subtle-primary rounded-pill text-start align-self-start"><?= htmlspecialchars((string) ($ticket['category_name'] ?: 'Uncategorized')) ?></small>
                        <span><?= htmlspecialchars(mb_strimwidth((string) $ticket['issue_details'], 0, 80, '...')) ?></span>
                      </div>
                    </td>
                    <td class="align-middle priority pe-4">
                      <div class="d-flex align-items-center gap-2 ms-md-4 ms-xl-0" style="width:7.5rem;">
                        <div style="--falcon-circle-progress-bar:100"><svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                            <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke-width="12"></circle>
                            <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round" stroke="<?= htmlspecialchars($priorityStroke) ?>" stroke-width="12"></circle>
                          </svg></div>
                        <h6 class="mb-0 text-700"><?= htmlspecialchars($priorityLabel) ?></h6>
                      </div>
                    </td>
                    <td class="align-middle company">
                      <div class="d-flex flex-column gap-1">
                        <span class="fw-semi-bold text-900"><?= htmlspecialchars($companyLabel) ?></span>
                        <small class="text-600"><?= htmlspecialchars($branchLabel) ?></small>
                      </div>
                    </td>
                    <td class="align-middle agent text-end"><?= htmlspecialchars($agentLabel) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-lg-3 col-12" id="selected-ticket-details">
    <!-- Summary Stats Card (always visible) -->
    <?php
      $statusBadgeMap = [
        'open'       => 'success',
        'new'        => 'success',
        'progress'   => 'info',
        'responded'  => 'info',
        'pending'    => 'warning',
        'hold'       => 'warning',
        'closed'     => 'secondary',
        'resolved'   => 'secondary',
        'unassigned' => 'danger',
      ];
      $statusColorHexMap = [
        'primary'   => '#2c7be5',
        'success'   => '#00d27a',
        'info'      => '#27bcfd',
        'warning'   => '#f5803e',
        'danger'    => '#e63757',
        'secondary' => '#748194',
      ];
    ?>
    <div class="card h-md-100 mb-3" style="max-height: calc(106px + (var(--falcon-card-spacer-y, 1.25rem) * 2));">
      <div class="card-body">
        <div class="row h-100 justify-content-between g-0">
          <div class="col-7 col-sm-8 pe-2">
            <div class="fs-11 mt-2">
              <?php foreach ($statusSummary as $sr):
                $sLabel = (string) $sr['status_name'];
                $sCount = (int) $sr['cnt'];
                $sBadge = 'secondary';
                foreach ($statusBadgeMap as $keyword => $cls) {
                  if (stripos($sLabel, $keyword) !== false) {
                    $sBadge = $cls;
                    break;
                  }
                }
                $segmentColor = $statusColorHexMap[$sBadge] ?? '#748194';
              ?>
              <div class="d-flex align-items-center gap-1 mb-1">
                <div class="d-flex align-items-center gap-2 min-w-0">
                  <span class="dot bg-<?= $sBadge ?>"></span>
                  <span class="fw-semi-bold text-truncate" style="max-width:8.5rem;"><?= htmlspecialchars($sLabel) ?></span>
                </div>
                <span class="badge badge-subtle-<?= $sBadge ?> rounded-pill"><?= $sCount ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-auto position-relative d-flex align-items-center align-self-start" style="top: 0;">
            <div class="position-relative" style="width:106px; height:106px;">
              <canvas id="ticketsSummaryCanvas" width="106" height="106" style="position: absolute; left: 0px; top: 0px; width: 106px; height: 106px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;"
                data-values="<?= htmlspecialchars(implode(',', array_map(static fn($sr) => (int) $sr['cnt'], $statusSummary)), ENT_QUOTES, 'UTF-8') ?>"
                data-colors="<?= htmlspecialchars(implode(',', array_map(static function($sr) use ($statusBadgeMap, $statusColorHexMap) {
                  $label = (string) $sr['status_name'];
                  $badge = 'secondary';
                  foreach ($statusBadgeMap as $keyword => $cls) {
                    if (stripos($label, $keyword) !== false) {
                      $badge = $cls;
                      break;
                    }
                  }
                  return $statusColorHexMap[$badge] ?? '#748194';
                }, $statusSummary)), ENT_QUOTES, 'UTF-8') ?>"></canvas>
              <div class="position-absolute top-50 start-50 translate-middle text-1100 fs-7 fw-semi-bold"><?= $totalTickets ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($selectedTicket !== null): ?>
      <!-- Ticket Details Card -->
      <div class="card mb-3" style="background-image: url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-1.png); background-position: right bottom; background-repeat: no-repeat; background-size: auto 100%;">
        <div class="card-body position-relative">
          <?php
            $createdAt = $selectedTicket['created_at'] ?? '';
            $createdTime = new DateTime($createdAt);
            $updatedAt = $selectedTicket['updated_at'] ?? '';
            $updatedTime = new DateTime($updatedAt);
            $now = new DateTime();
            $duration = $now->diff($createdTime);
            
            $durationText = '';
            if ($duration->d > 0) {
              $durationText = $duration->d . ' day' . ($duration->d > 1 ? 's' : '');
            } elseif ($duration->h > 0) {
              $durationText = $duration->h . ' hour' . ($duration->h > 1 ? 's' : '');
            } elseif ($duration->i > 0) {
              $durationText = $duration->i . ' min' . ($duration->i > 1 ? 's' : '');
            } else {
              $durationText = 'Just now';
            }
            
            $updatedDuration = $now->diff($updatedTime);
            $updatedDurationText = '';
            if ($updatedDuration->d > 0) {
              $updatedDurationText = $updatedDuration->d . ' day' . ($updatedDuration->d > 1 ? 's' : '') . ' ago';
            } elseif ($updatedDuration->h > 0) {
              $updatedDurationText = $updatedDuration->h . ' hour' . ($updatedDuration->h > 1 ? 's' : '') . ' ago';
            } elseif ($updatedDuration->i > 0) {
              $updatedDurationText = $updatedDuration->i . ' min' . ($updatedDuration->i > 1 ? 's' : '') . ' ago';
            }
          ?>
          
          <!-- Row 1: Icon + Status Badge + Duration Badge + Edit -->
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
              <span class="fas fa-info-circle text-500 fs-9"></span>
              <span class="badge badge-subtle-<?= $statusClass ?> rounded-pill"><?= htmlspecialchars($statusLabel) ?></span>
              <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-calendar-plus me-1"></span><?= $durationText ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-link p-0 text-primary" type="button" title="Print 58mm" aria-label="Print 58mm" onclick="printThermalTicket()">
                <span class="fas fa-print fs-9"></span>
              </button>
              <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="modal" data-bs-target="#editTicketModal" title="Edit Details" aria-label="Edit Details">
                <span class="fas fa-edit fs-9"></span>
              </button>
            </div>
          </div>

          <!-- Row 2: Ticket ID + Copy Button -->
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="fw-semi-bold fs-10"><?= htmlspecialchars((string) $selectedTicket['ticket_no']) ?></span>
            <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Ticket ID" onclick="copyToClipboard('<?= htmlspecialchars((string) $selectedTicket['ticket_no']) ?>')">
              <span class="fas fa-copy fs-9"></span>
            </button>
          </div>

          <!-- Row 3: Created Date + Last Update Date -->
          <div class="d-flex align-items-center gap-2 mb-3">
            <small class="badge badge-subtle-info rounded-pill"><span class="fas fa-clock me-1"></span><?= $createdTime->format('M d, Y H:i') ?></small>
            <small class="badge badge-subtle-secondary rounded-pill"><span class="fas fa-sync me-1"></span><?= $updatedTime->format('M d, Y H:i') ?></small>
          </div>

          <form method="post" action="<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($selectedTicketActionQueryString) ?>#selected-ticket-details" class="mb-3">
            <input type="hidden" name="action" value="quick_update_ticket">
            <input type="hidden" name="ticket_id" value="<?= (int) $selectedTicket['ticket_id'] ?>">
            <div class="d-flex align-items-center gap-2">
              <div class="flex-fill min-w-0">
                <select class="form-select form-select-sm" name="priority_id" aria-label="Update priority">
                  <option value="0" disabled <?= (int) ($selectedTicket['priority_id'] ?? 0) <= 0 ? 'selected' : '' ?>>Priority</option>
                  <?php foreach ($priorities as $item): ?>
                    <option value="<?= (int) $item['priority_id'] ?>" <?= (int) $selectedTicket['priority_id'] === (int) $item['priority_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $item['priority_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="flex-fill min-w-0">
                <select class="form-select form-select-sm" name="assigned_employee_id" aria-label="Update assigned agent">
                  <option value="0" <?= (int) ($selectedTicket['assigned_employee_id'] ?? 0) <= 0 ? 'selected' : '' ?>>Agent</option>
                  <?php foreach ($employees as $item): ?>
                    <?php
                      $employeeLabel = trim((string) ($item['full_name'] ?? ''));
                      if ($employeeLabel === '') {
                        $employeeLabel = (string) ($item['username'] ?? 'Employee');
                      }
                    ?>
                    <option value="<?= (int) $item['admin_user_id'] ?>" <?= (int) $selectedTicket['assigned_employee_id'] === (int) $item['admin_user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employeeLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="flex-shrink-0">
                <button class="btn btn-falcon-default btn-sm" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Update ticket">
                  <span class="fas fa-check fs-10"></span>
                </button>
              </div>
            </div>
          </form>

        </div>
      </div>

      <!-- Customer Details Card -->
      <div class="card mb-3" style="background-image: url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-2.png); background-position: right bottom; background-repeat: no-repeat; background-size: auto 100%;">
        <div class="card-body position-relative">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <h6 class="mb-0">Customer<span class="badge badge-subtle-info rounded-pill ms-2">Active</span></h6>
            <a class="btn btn-link p-0 text-primary" href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="View Customer" aria-label="View Customer">
              <span class="fas fa-eye fs-9"></span>
            </a>
          </div>

          <?php $customerName = (string) ($selectedTicket['customer_username'] ?: '-'); ?>
          <?php $customerPhone = (string) ($selectedTicket['customer_phone'] ?? ''); ?>
          <?php $customerAddress = (string) ($selectedTicket['customer_address'] ?? ''); ?>
          <?php $customerAddressLabel = $customerAddress !== '' ? $customerAddress : 'No address on record'; ?>

          <!-- Inline Row: Name + Phone -->
          <?php if ($customerPhone !== ''): ?>
          <div class="d-flex align-items-center flex-wrap gap-3 mb-1">
            <div class="d-flex align-items-center gap-2 min-w-0">
              <span class="fas fa-user text-500 fs-9"></span>
              <span class="fw-semi-bold fs-10 text-truncate"><?= htmlspecialchars($customerName) ?></span>
              <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Name" onclick="copyToClipboard('<?= htmlspecialchars($customerName) ?>')">
                <span class="fas fa-copy fs-9"></span>
              </button>
            </div>
            <div class="d-flex align-items-center gap-2 min-w-0">
              <span class="fas fa-phone text-500 fs-9"></span>
              <span class="fw-semi-bold fs-10 text-truncate"><?= htmlspecialchars($customerPhone) ?></span>
              <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Phone" onclick="copyToClipboard('<?= htmlspecialchars($customerPhone) ?>')">
                <span class="fas fa-copy fs-9"></span>
              </button>
            </div>
          </div>
          <div class="d-flex align-items-start gap-2 mt-1">
            <span class="fas fa-map-marker-alt text-500 fs-9 mt-1"></span>
            <span class="fw-semi-bold fs-10 text-700"><?= htmlspecialchars($customerAddressLabel) ?></span>
          </div>
          <?php else: ?>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fas fa-user text-500 fs-9"></span>
            <span class="fw-semi-bold fs-10"><?= htmlspecialchars($customerName) ?></span>
            <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy Name" onclick="copyToClipboard('<?= htmlspecialchars($customerName) ?>')">
              <span class="fas fa-copy fs-9"></span>
            </button>
          </div>
          <div class="mb-1"><small class="text-500 fs-11">No phone on record</small></div>
          <div class="d-flex align-items-start gap-2 mt-1">
            <span class="fas fa-map-marker-alt text-500 fs-9 mt-1"></span>
            <span class="fw-semi-bold fs-10 text-700"><?= htmlspecialchars($customerAddressLabel) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Ticket Notes Card -->
      <div class="card mb-3">
        <div class="card-body position-relative">
          <?php if (empty($ticketNotes)): ?>
          <div class="mb-3"><small class="text-500 fs-11">No previous notes for this ticket.</small></div>
          <?php else: ?>
          <div class="mb-3">
            <?php foreach ($ticketNotes as $note):
              $sessionName = trim((string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? ''));
              $noteAuthor = (string) ($note['created_by_name']
                  ?: $note['created_by_username']
                  ?: ($sessionName !== '' ? $sessionName : 'Unknown agent'));
              $noteTime = new DateTime((string) $note['created_at']);
              $noteDiff = (new DateTime())->diff($noteTime);
              if ($noteDiff->days > 0) {
                $noteAgo = $noteDiff->days . 'd ago';
              } elseif ($noteDiff->h > 0) {
                $noteAgo = $noteDiff->h . 'h ago';
              } elseif ($noteDiff->i > 0) {
                $noteAgo = $noteDiff->i . 'm ago';
              } else {
                $noteAgo = 'Just now';
              }
            ?>
              <div class="border rounded p-2 mb-2">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                  <small class="fw-semi-bold fs-11 text-800"><?= htmlspecialchars($noteAuthor) ?></small>
                  <span class="badge badge-subtle-secondary rounded-pill fs-11"><?= htmlspecialchars($noteAgo) ?></span>
                </div>
                <div class="fs-10 text-700"><?= nl2br(htmlspecialchars((string) $note['note_text'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <form method="post" action="<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($selectedTicketActionQueryString) ?>#selected-ticket-details">
            <input type="hidden" name="action" value="add_ticket_note">
            <input type="hidden" name="ticket_id" value="<?= (int) $selectedTicket['ticket_id'] ?>">
            <div class="d-flex align-items-start gap-2">
              <textarea class="form-control form-control-sm flex-fill" name="note_text" rows="2" placeholder="Add a note..." required></textarea>
              <button class="btn btn-falcon-default btn-sm" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Add note">
                <span class="fas fa-check"></span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Ticket Modal -->
      <div class="modal fade" id="editTicketModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Ticket</h5>
              <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= $appBasePath ?>/app/support-desk/all-tickets.php<?= htmlspecialchars($selectedTicketActionQueryString) ?>">
              <div class="modal-body">
                <input type="hidden" name="action" value="update_ticket">
                <input type="hidden" name="ticket_id" value="<?= (int) $selectedTicket['ticket_id'] ?>">
                <div class="mb-3">
                  <label class="form-label" for="categoryId">Category</label>
                  <select class="form-select" id="categoryId" name="category_id">
                    <option value="0" disabled>Select category</option>
                    <?php foreach ($categories as $item): ?>
                      <option value="<?= (int) $item['category_id'] ?>" <?= (int) $selectedTicket['category_id'] === (int) $item['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $item['category_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="priorityId">Priority</label>
                  <select class="form-select" id="priorityId" name="priority_id">
                    <option value="0" disabled>Select priority</option>
                    <?php foreach ($priorities as $item): ?>
                      <option value="<?= (int) $item['priority_id'] ?>" <?= (int) $selectedTicket['priority_id'] === (int) $item['priority_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $item['priority_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="statusId">Status</label>
                  <select class="form-select" id="statusId" name="ticket_status_id">
                    <option value="0" disabled>Select status</option>
                    <?php foreach ($ticketStatuses as $item): ?>
                      <option value="<?= (int) $item['ticket_status_id'] ?>" <?= (int) $selectedTicket['ticket_status_id'] === (int) $item['ticket_status_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $item['status_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="assignedEmployee">Assigned Employee</label>
                  <select class="form-select" id="assignedEmployee" name="assigned_employee_id">
                    <option value="0" disabled>Select employee</option>
                    <?php foreach ($employees as $item): ?>
                      <?php
                        $employeeLabel = trim((string) ($item['full_name'] ?? ''));
                        if ($employeeLabel === '') {
                          $employeeLabel = (string) ($item['username'] ?? 'Employee');
                        }
                      ?>
                      <option value="<?= (int) $item['admin_user_id'] ?>" <?= (int) $selectedTicket['assigned_employee_id'] === (int) $item['admin_user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employeeLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="issueDetails">Issue Details</label>
                  <textarea class="form-control" id="issueDetails" name="issue_details" rows="3" required><?= htmlspecialchars((string) $selectedTicket['issue_details']) ?></textarea>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" id="ticketActive" type="checkbox" name="status" value="1" <?= (int) $selectedTicket['status'] === 1 ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ticketActive">Active</label>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary btn-sm" type="submit">Update Ticket</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
const thermalTicketData = <?php if ($selectedTicket !== null): ?><?= json_encode([
  'ticket_no' => (string) ($selectedTicket['ticket_no'] ?? ''),
  'status_name' => (string) ($selectedTicket['status_name'] ?? ''),
  'priority_name' => (string) ($selectedTicket['priority_name'] ?? ''),
  'category_name' => (string) ($selectedTicket['category_name'] ?? ''),
  'company_name' => (string) ($selectedTicket['company_name'] ?? ''),
  'branch_name' => (string) ($selectedTicket['branch_name'] ?? ''),
  'company_logo_icon' => (string) ($selectedTicket['company_logo_icon'] ?? ''),
  'company_logo_main' => (string) ($selectedTicket['company_logo_main'] ?? ''),
  'company_phone' => (string) ($selectedTicket['company_phone'] ?? ''),
  'company_email' => (string) ($selectedTicket['company_email'] ?? ''),
  'company_address' => (string) ($selectedTicket['company_address'] ?? ''),
  'assigned_employee_name' => (string) ($selectedTicket['assigned_employee_name'] ?? ''),
  'assigned_employee_username' => (string) ($selectedTicket['assigned_employee_username'] ?? ''),
  'created_at' => (string) ($selectedTicket['created_at'] ?? ''),
  'issue_details' => (string) ($selectedTicket['issue_details'] ?? ''),
  'customer_username' => (string) ($selectedTicket['customer_username'] ?? ''),
  'customer_phone' => (string) ($selectedTicket['customer_phone'] ?? ''),
  'customer_address' => (string) ($selectedTicket['customer_address'] ?? ''),
  'printed_by_agent' => (string) trim((string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Current User')),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?><?php else: ?>null<?php endif; ?>;

function escapeThermalHtml(text) {
  return String(text ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function printThermalTicket() {
  if (!thermalTicketData) {
    return;
  }

  const d = thermalTicketData;
  const companyName = d.company_name || 'ISP360';
  const agent = d.assigned_employee_name || d.assigned_employee_username || '-';
  const printedByAgent = d.printed_by_agent || '-';
  const customerName = d.customer_username || '-';
  const customerPhone = d.customer_phone || '-';
  const customerAddress = d.customer_address || '-';
  const companyPhone = d.company_phone || '-';
  const companyEmail = d.company_email || '-';
  const companyAddress = d.company_address || '-';
  const complaintTime = d.created_at || '-';
  const companyLogo = d.company_logo_main || d.company_logo_icon || '';
  const companyLogoUrl = companyLogo ? `${window.location.origin}<?= $appBasePath ?>/${String(companyLogo).replace(/^\/+/, '')}` : '';
  const printedAt = new Date().toLocaleString();

  const companyHeaderHtml = `
    ${companyLogoUrl ? `<div class="logo-wrap"><img id="thermalCompanyLogo" src="${escapeThermalHtml(companyLogoUrl)}" alt="${escapeThermalHtml(companyName)} logo"></div>` : ''}
    <div class="company-title">${escapeThermalHtml(companyName)}</div>
    <div class="center row">${escapeThermalHtml(companyPhone)}</div>
    <div class="center row">${escapeThermalHtml(companyEmail)}</div>
    <div class="center row">${escapeThermalHtml(companyAddress)}</div>
  `;

  const feedbackRow = (label, options) => `
    <div class="feedback-block">
      <div class="row feedback-row"><span class="label">${escapeThermalHtml(label)}:</span></div>
      ${options.map((option) => `<div class="row feedback-option">[ ] ${escapeThermalHtml(option)}</div>`).join('')}
    </div>
  `;

  const receiptHtml = `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Ticket Print</title>
        <style>
          @page { size: 58mm auto; margin: 2mm; }
          body { width: 54mm; margin: 0; font-family: Arial, sans-serif; font-size: 11px; color: #000; }
          .center { text-align: center; }
          .line { border-top: 1px dashed #000; margin: 6px 0; }
          .row { margin: 2px 0; word-wrap: break-word; }
          .label { font-weight: bold; }
          .title { font-size: 12px; font-weight: bold; }
          .section { margin-top: 6px; }
          .logo-wrap { text-align: center; margin-bottom: 4px; }
          .logo-wrap img { max-width: 44mm; max-height: 16mm; width: auto; height: auto; filter: grayscale(100%) contrast(180%) brightness(0.92); }
          .company-title { font-size: 12px; font-weight: bold; text-align: center; }
          .feedback-row { margin: 4px 0; line-height: 1.5; }
          .feedback-block { margin: 4px 0; }
          .feedback-option { margin: 1px 0 1px 8px; }
          .signature-line { text-align: center; margin: 8px 0 2px; }
          .signature-label { text-align: center; font-weight: bold; }
        </style>
      </head>
      <body>
        ${companyHeaderHtml}
        <div class="line"></div>
        <div class="center title">Ticket Print</div>
        <div class="center row">Printed: ${escapeThermalHtml(printedAt)}</div>
        <div class="center row">Printed By: ${escapeThermalHtml(printedByAgent)}</div>
        <div class="line"></div>

        <div class="section">
          <div class="row"><span class="label">Ticket:</span> ${escapeThermalHtml(d.ticket_no || '-')}</div>
          <div class="row"><span class="label">Status:</span> ${escapeThermalHtml(d.status_name || '-')}</div>
          <div class="row"><span class="label">Priority:</span> ${escapeThermalHtml(d.priority_name || '-')}</div>
          <div class="row"><span class="label">Category:</span> ${escapeThermalHtml(d.category_name || '-')}</div>
          <div class="row"><span class="label">Company:</span> ${escapeThermalHtml(d.company_name || '-')}</div>
          <div class="row"><span class="label">Branch:</span> ${escapeThermalHtml(d.branch_name || '-')}</div>
          <div class="row"><span class="label">Agent:</span> ${escapeThermalHtml(agent)}</div>
          <div class="row"><span class="label">Complain Time:</span> ${escapeThermalHtml(complaintTime)}</div>
        </div>

        <div class="line"></div>
        <div class="section">
          <div class="row label">Issue Details</div>
          <div class="row">${escapeThermalHtml(d.issue_details || '-')}</div>
        </div>

        <div class="line"></div>
        <div class="section">
          <div class="row label">Customer Details</div>
          <div class="row"><span class="label">Name:</span> ${escapeThermalHtml(customerName)}</div>
          <div class="row"><span class="label">Phone:</span> ${escapeThermalHtml(customerPhone)}</div>
          <div class="row"><span class="label">Address:</span> ${escapeThermalHtml(customerAddress)}</div>
        </div>

        <div class="line"></div>
        <div class="section">
          <div class="row label">Feedback</div>
          ${feedbackRow('Technician Behaviour', ['Good', 'Average', 'Bad'])}
          ${feedbackRow('Service', ['Good', 'Average', 'Bad'])}
          ${feedbackRow('Customer Care', ['Good', 'Average', 'Bad'])}
          ${feedbackRow('Extra Payment', ['Yes', 'No'])}
        </div>

        <div class="line"></div>
        <div class="section">
          <div class="signature-line">--------------------------</div>
          <div class="signature-label">Customer Sign</div>
          <div class="signature-line">--------------------------</div>
        </div>

        <div class="line"></div>
        <div class="center row">Thank you</div>
      </body>
    </html>
  `;

  const printWindow = window.open('', '_blank', 'width=360,height=800');
  if (!printWindow) {
    return;
  }
  printWindow.document.open();
  printWindow.document.write(receiptHtml);
  printWindow.document.close();

  const doPrint = () => {
    printWindow.focus();
    printWindow.print();
  };

  printWindow.onload = () => {
    const logo = printWindow.document.getElementById('thermalCompanyLogo');
    if (logo && !logo.complete) {
      logo.onload = () => setTimeout(doPrint, 150);
      logo.onerror = () => setTimeout(doPrint, 150);
      return;
    }
    setTimeout(doPrint, 150);
  };
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    const btn = event.target.closest('button');
    const originalTitle = btn.getAttribute('data-bs-original-title') || 'Copy Ticket ID';
    btn.setAttribute('data-bs-original-title', 'Copied!');
    const tooltip = bootstrap.Tooltip.getInstance(btn);
    if (tooltip) {
      tooltip.update();
    }
    setTimeout(() => {
      btn.setAttribute('data-bs-original-title', originalTitle);
      if (tooltip) {
        tooltip.update();
      }
    }, 2000);
  }).catch(() => {
    alert('Failed to copy to clipboard');
  });
}

function drawTicketsSummaryCanvas() {
  const canvas = document.getElementById('ticketsSummaryCanvas');
  if (!canvas) {
    return;
  }

  const values = (canvas.dataset.values || '')
    .split(',')
    .map(v => parseInt(v, 10))
    .filter(v => !Number.isNaN(v) && v > 0);
  const colors = (canvas.dataset.colors || '').split(',').filter(Boolean);
  const total = values.reduce((sum, v) => sum + v, 0);

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return;
  }

  const width = canvas.width;
  const height = canvas.height;
  const cx = width / 2;
  const cy = height / 2;
  const radius = Math.min(width, height) / 2 - 4;
  const lineWidth = 18;

  ctx.clearRect(0, 0, width, height);

  if (total <= 0) {
    ctx.beginPath();
    ctx.strokeStyle = '#e3e6ed';
    ctx.lineWidth = lineWidth;
    ctx.arc(cx, cy, radius - lineWidth / 2, 0, Math.PI * 2);
    ctx.stroke();
    return;
  }

  let startAngle = -Math.PI / 2;
  values.forEach((value, index) => {
    const sliceAngle = (value / total) * Math.PI * 2;
    ctx.beginPath();
    ctx.strokeStyle = colors[index] || '#748194';
    ctx.lineWidth = lineWidth;
    ctx.lineCap = 'butt';
    ctx.arc(cx, cy, radius - lineWidth / 2, startAngle, startAngle + sliceAngle);
    ctx.stroke();
    startAngle += sliceAngle;
  });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
  drawTicketsSummaryCanvas();
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
</script>

<?php require '../../includes/footer.php'; ?>

