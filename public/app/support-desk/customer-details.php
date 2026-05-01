<?php
declare(strict_types=1);
require_once '../../includes/auth.php';
$appBasePath = ispts_resolve_app_base_path();
ispts_require_authentication($appBasePath);

use App\Core\Database;

$pdo = Database::getConnection();
$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($customerId <= 0) {
  header('Location: customers.php');
  exit;
}

// ── ASSET AJAX HANDLERS ──────────────────────────────────────────────────────
if (isset($_GET['ajax_get_serials_checkout'])) {
  $pid = (int) $_GET['pid'];
  $bid = (int) ($_GET['bid'] ?? 0);
  $stmt = $pdo->prepare('
        SELECT sn.serial_id, sn.serial_ref 
        FROM inventory_serial_numbers sn
        JOIN inventory_stock_invoices si ON sn.invoice_id = si.invoice_id
        WHERE sn.product_id = :p AND sn.status = 1 AND si.branch_id = :b
    ');
  $stmt->execute(['p' => $pid, 'b' => $bid]);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}
if (isset($_GET['ajax_get_serials_checkin'])) {
  $pid = (int) $_GET['pid'];
  $stmt = $pdo->prepare('
        SELECT DISTINCT sn.serial_id, sn.serial_ref 
        FROM inventory_serial_numbers sn
        JOIN inventory_operation_serials ios ON sn.serial_id = ios.serial_id
        JOIN inventory_operation_items ioi ON ios.op_item_id = ioi.op_item_id
        JOIN inventory_operations io ON ioi.op_id = io.op_id
        WHERE sn.product_id = :p AND sn.status = 2 AND io.customer_id = :cid
    ');
  $stmt->execute(['p' => $pid, 'cid' => $customerId]);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

// ── HANDLE ASSET OPERATIONS ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asset_action'])) {
  $opType = $_POST['asset_action'];
  $productId = (int) ($_POST['product_id'] ?? 0);
  $serials = $_POST['serials'] ?? [];
  $purpose = $_POST['purpose'] ?? ($opType === 'checkout' ? 'Issue to Customer' : 'Return from Customer');
  $notes = $_POST['notes'] ?? '';
  $branchId = (int) ($_POST['branch_id'] ?? 0);

  if ($productId > 0 && $branchId > 0) {
    try {
      $pdo->beginTransaction();
      $stmt = $pdo->prepare('INSERT INTO inventory_operations (op_type, purpose, branch_id, customer_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$opType, $purpose, $branchId, $customerId, $notes, $_SESSION['admin_user_id']]);
      $opId = $pdo->lastInsertId();

      $stmt = $pdo->prepare('INSERT INTO inventory_operation_items (op_id, product_id, quantity) VALUES (?, ?, ?)');
      $stmt->execute([$opId, $productId, count($serials) ?: 1]);
      $opItemId = $pdo->lastInsertId();

      foreach ($serials as $sId) {
        $stmt = $pdo->prepare('INSERT INTO inventory_operation_serials (op_item_id, serial_id) VALUES (?, ?)');
        $stmt->execute([$opItemId, $sId]);
        $newStatus = ($opType === 'checkout') ? 2 : 1;
        $stmt = $pdo->prepare('UPDATE inventory_serial_numbers SET status = ? WHERE serial_id = ?');
        $stmt->execute([$newStatus, $sId]);
      }
      $pdo->commit();
      header("Location: customer-details.php?id=$customerId&asset_saved=1");
      exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction())
        $pdo->rollBack();
      $assetError = $e->getMessage();
    }
  }
}

// ── FETCH ASSET DATA ─────────────────────────────────────────────────────────
$stmt = $pdo->prepare('
    SELECT io.op_id, io.op_type, io.purpose, io.created_at, io.notes,
           ip.product_name, ioi.quantity, ioi.op_item_id,
           au.full_name as agent_name,
           (SELECT GROUP_CONCAT(isn.serial_ref SEPARATOR ", ") 
            FROM inventory_operation_serials ios 
            JOIN inventory_serial_numbers isn ON ios.serial_id = isn.serial_id
            WHERE ios.op_item_id = ioi.op_item_id) as serials
    FROM inventory_operations io
    JOIN inventory_operation_items ioi ON io.op_id = ioi.op_id
    JOIN inventory_products ip ON ioi.product_id = ip.product_id
    LEFT JOIN admin_users au ON io.created_by = au.admin_user_id
    WHERE io.customer_id = :cid
    ORDER BY io.created_at DESC
');
$stmt->execute(['cid' => $customerId]);
$assetHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = :id LIMIT 1');
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
  header('Location: customers.php');
  exit;
}

$packageAmount = 0.0;
$packageRaw = trim((string) ($customer['package_id'] ?? ''));
if ($packageRaw !== '') {
  $packageAmount = (float) preg_replace('/[^\d.]+/', '', $packageRaw);
}

$billingStartSource = $customer['package_activate_date'] ?? null;
if (empty($billingStartSource)) {
  $billingStartSource = $customer['registered_date'] ?? null;
}
if (empty($billingStartSource)) {
  $billingStartSource = $customer['created_at'] ?? null;
}

$billingStart = new DateTime('first day of this month');

$billingEnd = new DateTime('first day of this month');

$billingText = trim((string) (($customer['payment'] ?? '') . ' ' . ($customer['invoices'] ?? '')));
$paidMonths = [];
if ($billingText !== '') {
  preg_match_all('/\b(20\d{2})[-\/.](0[1-9]|1[0-2])(?:[-\/.](0[1-9]|[12]\d|3[01]))?\b/', $billingText, $matches, PREG_SET_ORDER);
  foreach ($matches as $m) {
    $paidMonths[$m[1] . '-' . $m[2]] = true;
  }
}

$paidAmountByMonth = [];
$monthlyItemPaidStmt = $pdo->prepare(
  "SELECT fii.description, fii.amount
   FROM finance_incomes fi
   INNER JOIN finance_income_items fii ON fii.income_id = fi.income_id
   WHERE fi.status = 1
     AND fi.customer_id = :customer_id
     AND fi.payment_status = 'paid'
     AND fii.status = 1
     AND fii.item_type = 'monthly_bill'"
);
$monthlyItemPaidStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
$monthlyItemPaidStmt->execute();
$monthlyItemPaidRows = $monthlyItemPaidStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($monthlyItemPaidRows as $monthlyItemPaidRow) {
  $itemDescription = (string) ($monthlyItemPaidRow['description'] ?? '');
  $itemAmount = (float) ($monthlyItemPaidRow['amount'] ?? 0);
  if ($itemAmount <= 0) {
    continue;
  }

  preg_match_all('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+20\d{2}\b/i', $itemDescription, $monthLabelMatches);
  $monthLabels = array_values(array_unique(array_map(static function ($label): string {
    return trim((string) $label);
  }, $monthLabelMatches[0] ?? [])));

  if (empty($monthLabels)) {
    continue;
  }

  $perMonthAmount = $itemAmount / count($monthLabels);
  foreach ($monthLabels as $monthLabel) {
    $monthDate = DateTime::createFromFormat('!M Y', $monthLabel);
    if (!$monthDate instanceof DateTime) {
      continue;
    }
    $monthKey = $monthDate->format('Y-m');
    if (!isset($paidAmountByMonth[$monthKey])) {
      $paidAmountByMonth[$monthKey] = 0.0;
    }
    $paidAmountByMonth[$monthKey] += $perMonthAmount;
  }
}

$billingRows = [];
$cursor = clone $billingStart;
$guard = 0;
while ($cursor <= $billingEnd && $guard < 240) {
  $monthKey = $cursor->format('Y-m');
  $monthPaidAmount = (float) ($paidAmountByMonth[$monthKey] ?? 0);
  $rowStatus = isset($paidMonths[$monthKey]) ? 'paid' : 'unpaid';
  $rowAmount = $packageAmount;

  if ($monthPaidAmount > 0) {
    if ($packageAmount > 0 && $monthPaidAmount + 0.01 >= $packageAmount) {
      $rowStatus = 'paid';
      $rowAmount = $packageAmount;
    } else {
      $rowStatus = 'partial_paid';
      $rowAmount = $monthPaidAmount;
    }
  }

  $billingRows[] = [
    'bill_date' => $cursor->format('Y-m-01'),
    'month_label' => $cursor->format('M Y'),
    'amount' => $rowAmount,
    'status' => $rowStatus,
  ];
  $cursor->modify('+1 month');
  $guard++;
}
$billingRows = array_reverse($billingRows);

$billingPaidCount = count(array_filter($billingRows, static fn($r) => $r['status'] === 'paid'));
$billingPartialPaidCount = count(array_filter($billingRows, static fn($r) => $r['status'] === 'partial_paid'));
$billingUnpaidCount = count($billingRows) - $billingPaidCount - $billingPartialPaidCount;

$customerInvoiceStmt = $pdo->prepare(
  'SELECT fi.income_id,
    fi.trx_id,
    fi.income_date,
    fi.grand_total,
    fi.payment_method,
    fi.reference_no,
    fi.payment_status,
    fi.created_by_name,
    fa.account_name,
    c.company,
    GROUP_CONCAT(fii.description ORDER BY fii.income_item_id SEPARATOR ", ") AS item_descriptions
  FROM finance_incomes fi
  LEFT JOIN finance_accounts fa ON fa.account_id = fi.account_id
  LEFT JOIN companies c ON c.id = fi.company_id
  LEFT JOIN finance_income_items fii ON fii.income_id = fi.income_id AND fii.status = 1
  WHERE fi.status = 1 AND fi.customer_id = :customer_id
  GROUP BY fi.income_id,
    fi.trx_id,
    fi.income_date,
    fi.grand_total,
    fi.payment_method,
    fi.reference_no,
    fi.payment_status,
    fi.created_by_name,
    fa.account_name,
    c.company
  ORDER BY fi.income_date DESC, fi.income_id DESC'
);
$customerInvoiceStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
$customerInvoiceStmt->execute();
$invoiceRows = $customerInvoiceStmt->fetchAll(PDO::FETCH_ASSOC);
$invoiceCount = count($invoiceRows);

$branches = $pdo->query('SELECT branch_id, branch_name FROM branches WHERE status = 1 ORDER BY branch_name ASC')->fetchAll(PDO::FETCH_ASSOC);

// Resolve customer's branch ID
$customerBranchId = (int) ($customer['branch_id'] ?? 0);

// Products for Checkout (Status 1 in Branch)
$checkoutProducts = [];
if ($customerBranchId > 0) {
  $stmt = $pdo->prepare('
        SELECT DISTINCT p.product_id, p.product_name 
        FROM inventory_products p
        JOIN inventory_serial_numbers sn ON p.product_id = sn.product_id
        JOIN inventory_stock_invoices si ON sn.invoice_id = si.invoice_id
        WHERE sn.status = 1 AND si.branch_id = :bid AND p.status = 1
        ORDER BY p.product_name ASC
    ');
  $stmt->execute(['bid' => $customerBranchId]);
  $checkoutProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Products for Checkin (Status 2 held by this Customer)
$checkinProductsStmt = $pdo->prepare('
    SELECT DISTINCT p.product_id, p.product_name 
    FROM inventory_products p
    JOIN inventory_serial_numbers sn ON p.product_id = sn.product_id
    JOIN inventory_operation_serials ios ON sn.serial_id = ios.serial_id
    JOIN inventory_operation_items ioi ON ios.op_item_id = ioi.op_item_id
    JOIN inventory_operations io ON ioi.op_id = io.op_id
    WHERE sn.status = 2 AND io.customer_id = :cid AND p.status = 1
    ORDER BY p.product_name ASC
');
$checkinProductsStmt->execute(['cid' => $customerId]);
$checkinProducts = $checkinProductsStmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch Tickets
$ticketsStmt = $pdo->prepare('
    SELECT t.*, st.status_name, pri.priority_name, pri.color as priority_color, au.full_name as assigned_to
    FROM support_tickets t
    LEFT JOIN support_ticket_statuses st ON t.ticket_status_id = st.ticket_status_id
    LEFT JOIN support_ticket_priorities pri ON t.priority_id = pri.priority_id
    LEFT JOIN admin_users au ON t.assigned_employee_id = au.admin_user_id
    WHERE t.customer_id = :cid
    ORDER BY t.created_at DESC
');
$ticketsStmt->execute(['cid' => $customerId]);
$customerTickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Priorities & Agents for the quick-edit form
$priorities = $pdo->query("SELECT priority_id, priority_name FROM support_ticket_priorities WHERE status = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT admin_user_id, full_name FROM admin_users WHERE status = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Selected Ticket details if ticket_id is provided
$selectedTicket = null;
if (isset($_GET['ticket_id'])) {
  $stStmt = $pdo->prepare('
        SELECT t.*, st.status_name, pri.priority_name, pri.color as priority_color, au.full_name as assigned_to,
               u.full_name as updated_by_name
        FROM support_tickets t
        LEFT JOIN support_ticket_statuses st ON t.ticket_status_id = st.ticket_status_id
        LEFT JOIN support_ticket_priorities pri ON t.priority_id = pri.priority_id
        LEFT JOIN admin_users au ON t.assigned_employee_id = au.admin_user_id
        LEFT JOIN admin_users u ON t.updated_by = u.admin_user_id
        WHERE t.ticket_id = :tid AND t.customer_id = :cid
    ');
  $stStmt->execute(['tid' => $_GET['ticket_id'], 'cid' => $customerId]);
  $selectedTicket = $stStmt->fetch(PDO::FETCH_ASSOC);

  // Fetch Notes for the selected ticket
  $ticketNotes = [];
  if ($selectedTicket) {
    $notesStmt = $pdo->prepare('
            SELECT n.*, au.full_name as created_by_name, au.username as created_by_username
            FROM support_ticket_notes n
            LEFT JOIN admin_users au ON au.admin_user_id = n.created_by
            WHERE n.ticket_id = :tid
            ORDER BY n.created_at DESC
        ');
    $notesStmt->execute(['tid' => $selectedTicket['ticket_id']]);
    $ticketNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

// Fetch All Notes for all tickets of this customer
$allCustomerNotesStmt = $pdo->prepare('
    SELECT n.*, t.ticket_no, au.full_name as created_by_name, au.username as created_by_username
    FROM support_ticket_notes n
    JOIN support_tickets t ON n.ticket_id = t.ticket_id
    LEFT JOIN admin_users au ON n.created_by = au.admin_user_id
    WHERE t.customer_id = :cid
    ORDER BY n.created_at DESC
');
$allCustomerNotesStmt->execute(['cid' => $customerId]);
$allCustomerNotesRaw = $allCustomerNotesStmt->fetchAll(PDO::FETCH_ASSOC);

// Group notes by ticket_id
$allCustomerNotes = [];
foreach ($allCustomerNotesRaw as $note) {
  $tid = $note['ticket_id'];
  if (!isset($allCustomerNotes[$tid])) {
    $allCustomerNotes[$tid] = [
      'ticket_no' => $note['ticket_no'],
      'ticket_id' => $note['ticket_id'],
      'notes' => []
    ];
  }
  $allCustomerNotes[$tid]['notes'][] = $note;
}

// Merge events for Timeline
$timelineEvents = [];

// 1. Add Tickets
foreach ($customerTickets as $t) {
  $timelineEvents[] = [
    'type' => 'ticket',
    'title' => 'Created Ticket: ' . $t['ticket_no'],
    'description' => $t['issue_details'] ?? '',
    'date' => $t['created_at'],
    'status' => $t['status_name'],
    'icon' => 'fa-ticket-alt',
    'color' => 'primary',
    'badge' => $t['status_name'],
    'badge_color' => 'info',
    'agent' => $t['assigned_to'] ?: 'Unassigned'
  ];
}

// 2. Add Assets
foreach ($assetHistory as $a) {
  $isCheckout = $a['op_type'] === 'checkout';
  $timelineEvents[] = [
    'type' => 'asset',
    'title' => ($isCheckout ? 'Checked out' : 'Returned') . ' Asset: ' . $a['product_name'],
    'description' => 'Quantity: ' . $a['quantity'] . (!empty($a['serials']) ? ' (Serials: ' . $a['serials'] . ')' : ''),
    'date' => $a['created_at'],
    'status' => ucfirst($a['op_type']),
    'icon' => $isCheckout ? 'fa-box' : 'fa-undo',
    'color' => $isCheckout ? 'warning' : 'success',
    'badge' => ucfirst($a['op_type']),
    'badge_color' => $isCheckout ? 'primary' : 'success',
    'agent' => $a['agent_name'] ?: 'System'
  ];
}

// 3. Add Notes
foreach ($allCustomerNotesRaw as $n) {
  $author = $n['created_by_name'] ?: $n['created_by_username'] ?: 'System';
  $timelineEvents[] = [
    'type' => 'note',
    'title' => 'Note added by ' . $author . ' on Ticket: ' . $n['ticket_no'],
    'description' => $n['note_text'],
    'date' => $n['created_at'],
    'color' => 'info',
    'agent' => $author
  ];
}

// Sort by date descending
usort($timelineEvents, function ($a, $b) {
  return strtotime($b['date']) <=> strtotime($a['date']);
});

require '../../includes/header.php';
?>
<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between">
    <a class="btn btn-falcon-default btn-sm" href="customers.php"><span class="fas fa-arrow-left"></span></a>
    <div class="d-flex"><button class="btn btn-sm btn-falcon-default d-xl-none" type="button" data-bs-toggle="offcanvas"
        data-bs-target="#contactDetailsOffcanvas" aria-controls="contactDetailsOffcanvas"><span class="fas fa-tasks"
          data-fa-transform="shrink-2"></span><span class="ms-1">To-do</span></button>
      <div class="bg-300 mx-3 d-xl-none" style="width:1px; height:29px"></div>
      <a class="btn btn-falcon-default btn-sm me-2" href="customer-registration.php?id=<?= $customerId ?>"><span
          class="fas fa-edit"></span><span class="d-none d-xl-inline-block ms-1">Edit</span></a>
      <button class="btn btn-falcon-default btn-sm d-none d-sm-block" type="button"><span
          class="fas fa-sync-alt"></span><span class="d-none d-xl-inline-block ms-1">Convert to Agent</span></button>
      <button class="btn btn-falcon-default btn-sm btn-sm d-none d-sm-block mx-2" type="button"><span
          class="fas fa-lock"></span><span class="d-none d-xl-inline-block ms-1">Send Activation Email</span></button>
      <button class="btn btn-falcon-default btn-sm d-none d-sm-block me-2" type="button"><span
          class="fas fa-trash-alt text-danger"></span><span
          class="d-none d-xl-inline-block ms-1 text-danger">Delete</span></button>
      <button class="btn btn-falcon-default btn-sm d-none d-sm-block me-2" type="button"><span
          class="fas fa-key"></span><span class="d-none d-xl-inline-block ms-1">Change Password</span></button>
      <div class="dropdown font-sans-serif"><button
          class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none" type="button"
          id="preview-dropdown" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true"
          aria-expanded="false"><span class="fas fa-ellipsis-v fs-11"></span></button>
        <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="preview-dropdown"><a
            class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a><a
            class="dropdown-item d-sm-none" href="#!">Convert to Agent</a><a class="dropdown-item d-sm-none"
            href="#!">Send Activation Email</a><a class="dropdown-item d-sm-none" href="#!">Delete</a><a
            class="dropdown-item d-sm-none" href="#!">Change Password</a>
          <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row g-3">
  <div class="col-xxl-3 col-xl-3 order-xl-1">
    <div class="position-xl-sticky top-0">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <h6 class="mb-0">Contact Information</h6>
          <div class="dropdown font-sans-serif btn-reveal-trigger"><button
              class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button"
              id="dropdown-contact-information" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true"
              aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-contact-information"><a
                class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
              <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="px-3 py-2">
            <?php
            $d = fn($v) => htmlspecialchars((string) ($v ?? ''));

            // Resolve branch name for display
            $displayBranchName = '-';
            if ($customerBranchId > 0) {
              foreach ($branches as $b) {
                if ((int) $b['branch_id'] === $customerBranchId) {
                  $displayBranchName = $b['branch_name'];
                  break;
                }
              }
            }

            $rows = [
              ['icon' => 'fa-user', 'label' => 'Full Name', 'value' => $customer['full_name'] ?? $customer['username']],
              ['icon' => 'fa-id-badge', 'label' => 'Radious ID', 'value' => $customer['radious_id'] ?? null],
              ['icon' => 'fa-address-card', 'label' => 'NID', 'value' => $customer['nid'] ?? null],
              ['icon' => 'fa-envelope', 'label' => 'Email', 'value' => $customer['email'] ?? null, 'mailto' => true],
              ['icon' => 'fa-phone', 'label' => 'Phone No', 'value' => $customer['phone_no'] ?? null, 'tel' => true],
              ['icon' => 'fa-map-marker-alt', 'label' => 'Address', 'value' => $customer['address'] ?? null],
              ['icon' => 'fa-layer-group', 'label' => 'Area', 'value' => $customer['area'] ?? null],
              ['icon' => 'fa-map-pin', 'label' => 'Sub Area', 'value' => $customer['sub_area'] ?? null],
              ['icon' => 'fa-code-branch', 'label' => 'Branch', 'value' => $displayBranchName],
              ['icon' => 'fa-box', 'label' => 'Package', 'value' => $customer['package_id'] ?? null],
              ['icon' => 'fa-calendar-check', 'label' => 'Activate Date', 'value' => $customer['package_activate_date'] ?? null],
              ['icon' => 'fa-calendar-times', 'label' => 'Expire Date', 'value' => $customer['package_expire_date'] ?? null],
              ['icon' => 'fa-calendar-alt', 'label' => 'Registered Date', 'value' => $customer['registered_date'] ?? null],
              ['icon' => 'fa-piggy-bank', 'label' => 'Deposit Money', 'value' => isset($customer['deposit_money']) ? 'Tk ' . number_format((float) $customer['deposit_money'], 2) : null],
              ['icon' => 'fa-plug', 'label' => 'Connection Charge', 'value' => isset($customer['connection_charge']) ? 'Tk ' . number_format((float) $customer['connection_charge'], 2) : null],
              ['icon' => 'fa-sticky-note', 'label' => 'Notes', 'value' => $customer['notes'] ?? null],
              ['icon' => 'fa-clock', 'label' => 'Created At', 'value' => !empty($customer['created_at']) ? date('M d, Y g:i A', strtotime($customer['created_at'])) : null],
              ['icon' => 'fa-sync-alt', 'label' => 'Updated At', 'value' => !empty($customer['updated_at']) ? date('M d, Y g:i A', strtotime($customer['updated_at'])) : null],
            ];
            foreach ($rows as $r):
              $val = $r['value'];
              if ($val === null || $val === '')
                continue;
              ?>
              <div class="d-flex align-items-start py-2 border-bottom border-200">
                <span class="fas <?= $r['icon'] ?> text-400 me-2 mt-1 fs-10" style="width:14px;text-align:center;"></span>
                <div class="flex-1" style="min-width:0;">
                  <div class="fs-11 text-500 mb-0"><?= $r['label'] ?></div>
                  <div class="fs-10 text-700 fw-medium text-break">
                    <?php if (!empty($r['mailto'])): ?>
                      <a href="mailto:<?= $d($val) ?>"><?= $d($val) ?></a>
                    <?php elseif (!empty($r['tel'])): ?>
                      <a href="tel:<?= $d($val) ?>"><?= $d($val) ?></a>
                    <?php else: ?>
                      <?= $d($val) ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="offcanvas offcanvas-end offcanvas-contact-info" tabindex="-1" id="contactDetailsOffcanvas"
        aria-labelledby="contactDetailsOffcanvasLabelCard">
        <div class="offcanvas-header d-xl-none d-flex flex-between-center d-xl-none bg-body-tertiary">
          <h6 class="fs-9 mb-0 fw-semi-bold">To-do List</h6><button class="btn-close text-reset d-xl-none shadow-none"
            id="contactDetailsOffcanvasLabelCard" type="button" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body scrollbar scrollbar-none-xl p-0"><button
            class="btn btn-falcon-default btn-sm d-flex align-items-center mb-x1 d-xl-none ms-x1 mt-x1"
            type="button"><span class="fas fa-plus" data-fa-transform="shrink-3"></span><span
              class="ms-1">Add</span></button>
          <div class="border-bottom border-xl-0 border-200"></div>
          <div class="card shadow-none shadow-show-xl mt-xl-3">
            <div class="card-header d-flex flex-between-center bg-body-tertiary d-none d-xl-flex">
              <h6 class="mb-0">To-do List</h6><button class="btn btn-falcon-default btn-sm d-flex align-items-center"
                type="button"><span class="fas fa-plus" data-fa-transform="shrink-3"></span><span
                  class="ms-1">Add</span></button>
            </div>
            <div class="card-body ticket-todo-list scrollbar-overlay h-auto">
              <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 border-bottom mb-3">
                <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox"
                    id="ticket-checkbox-todo-0" /><label class="form-check-label w-100 pe-3"
                    for="ticket-checkbox-todo-0"><span class="mb-1 text-700 d-block">Sidenav text cutoff rendering
                      issue</span><span class="fs-11 text-600 lh-base font-base fw-normal d-block mb-2">Problem with
                      Falcon theme</span></label></div>
                <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span
                      class="fas fa-trash text-danger"></span></button></div>
              </div>
              <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 border-bottom mb-3">
                <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox"
                    id="ticket-checkbox-todo-1" /><label class="form-check-label w-100 pe-3"
                    for="ticket-checkbox-todo-1"><span class="mb-1 text-700 d-block">Notify when the WebPack release is
                      ready</span><span class="fs-11 text-600 lh-base font-base fw-normal d-block mb-2">Falcon Bootstarp
                      5</span></label></div>
                <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span
                      class="fas fa-trash text-danger"></span></button></div>
              </div>
              <div class="d-flex hover-actions-trigger btn-reveal-trigger gap-3 border-200 mb-0">
                <div class="form-check mb-0"><input class="form-check-input form-check-line-through" type="checkbox"
                    id="ticket-checkbox-todo-2" /><label class="form-check-label w-100 pe-3 mb-0"
                    for="ticket-checkbox-todo-2"><span class="mb-1 text-700 d-block">File Attachments</span><span
                      class="fs-11 text-600 lh-base font-base fw-normal d-block mb-0">Sending attachments automatically
                      attaches them to the notification email that the client receives as well as making them accessible
                      through.</span></label></div>
                <div class="hover-actions end-0"><button class="btn fs-11 icon-item-sm btn-link px-0 text-600"><span
                      class="fas fa-trash text-danger"></span></button></div>
              </div>
            </div>
            <div class="card-footer border-top border-200 text-xl-center p-0"><a
                class="btn btn-link btn-sm fw-medium py-x1 py-xl-2 px-x1" href="#!">View all<span
                  class="fas fa-chevron-right ms-1 fs-11"></span></a></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xxl-6 col-xl-6">
    <div class="card overflow-hidden">
      <div class="card-header p-0 scrollbar-overlay border-bottom">
        <ul class="nav nav-tabs border-0 tab-contact-details flex-nowrap" id="contact-details-tab" role="tablist">
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1 active" id="contact-timeline-tab"
              data-bs-toggle="tab" href="#timeline" role="tab" aria-controls="timeline" aria-selected="true"><span
                class="fas fa-stream icon"></span>
              <h6 class="mb-0 text-600">Timeline</h6>
            </a></li>
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-tickets-tab"
              data-bs-toggle="tab" href="#tickets" role="tab" aria-controls="tickets" aria-selected="false"><span
                class="fas fa-ticket-alt"></span>
              <h6 class="mb-0 text-600">Tickets</h6>
            </a></li>
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-notes-tab"
              data-bs-toggle="tab" href="#notes" role="tab" aria-controls="notes" aria-selected="false"><span
                class="fas fa-file-alt icon"></span>
              <h6 class="mb-0 text-600">Notes</h6>
            </a></li>
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-assets-tab"
              data-bs-toggle="tab" href="#assets" role="tab" aria-controls="assets" aria-selected="false"><span
                class="fas fa-boxes icon"></span>
              <h6 class="mb-0 text-600">Assets</h6>
            </a></li>
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-billing-tab"
              data-bs-toggle="tab" href="#billing" role="tab" aria-controls="billing" aria-selected="false"><span
                class="fas fa-file-invoice-dollar icon"></span>
              <h6 class="mb-0 text-600">Billing</h6>
            </a></li>
          <li class="nav-item text-nowrap" role="presentation"><a
              class="nav-link mb-0 d-flex align-items-center gap-2 py-3 px-x1" id="contact-invoices-tab"
              data-bs-toggle="tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="false"><span
                class="fas fa-receipt icon"></span>
              <h6 class="mb-0 text-600">Invoices</h6>
            </a></li>
        </ul>
      </div>
      <div class="tab-content">
        <div class="card-body bg-body-tertiary tab-pane active" id="timeline" role="tabpanel"
          aria-labelledby="contact-timeline-tab">
          <div id="timelineList" data-list='{"valueNames":["event-title", "event-date"],"page":10,"pagination":true}'>
            <div class="d-flex align-items-center justify-content-end mb-3">
              <label class="mb-0 me-2 fs-10">Rows per page:</label>
              <select class="form-select form-select-sm w-auto" onchange="const list = window.List.getInstance(document.getElementById('timelineList')); if(list) { list.page = parseInt(this.value); list.update(); }">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
              </select>
            </div>
            
            <ul class="list-unstyled mt-3 scrollbar management-calendar-events list" id="management-calendar-events">
              <?php if (empty($timelineEvents)): ?>
                <li class="text-center py-4 text-500">No activity recorded yet.</li>
              <?php else: ?>
                <?php foreach ($timelineEvents as $event):
                  $eventDate = new DateTime($event['date']);
                  ?>
                  <li class="border-top pt-3 mb-3 pb-1 cursor-pointer" data-calendar-events="">
                    <div class="d-flex">
                      <div class="pe-3 text-end" style="width: 140px; flex-shrink: 0;">
                        <div class="fs-11 fw-semi-bold text-700 mb-1 event-date">
                          <?= $eventDate->format('d M, Y') ?> <span class="fw-normal text-500"><?= $eventDate->format('h:i A') ?></span>
                        </div>
                        <?php if (!empty($event['agent'])): ?>
                          <div class="fs-11 text-600 text-truncate" title="<?= htmlspecialchars($event['agent']) ?>">
                            <span class="fas fa-user me-1"></span><?= htmlspecialchars($event['agent']) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="border-start border-3 border-<?= $event['color'] ?> ps-3 mt-1 flex-1 min-w-0">
                        <div class="d-flex align-items-center mb-1">
                          <h6 class="mb-0 fw-semi-bold text-700 hover-primary event-title text-truncate me-2">
                            <?= htmlspecialchars((string) ($event['title'] ?? '')) ?>
                          </h6>
                          <?php if (!empty($event['badge'])): ?>
                            <span class="badge rounded badge-subtle-<?= $event['badge_color'] ?> flex-shrink-0"><?= htmlspecialchars($event['badge']) ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                          <p class="fs-10 text-600 mb-0" style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= strip_tags((string)$event['description']) ?>
                          </p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
            
            <div class="d-flex justify-content-center mt-3">
              <button class="btn btn-sm btn-falcon-default me-1" type="button" title="Previous" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
              <ul class="pagination mb-0"></ul>
              <button class="btn btn-sm btn-falcon-default ms-1" type="button" title="Next" data-list-pagination="next"><span class="fas fa-chevron-right"></span></button>
            </div>
          </div>
        </div>
        <div class="card-body tab-pane p-0" id="tickets" role="tabpanel" aria-labelledby="contact-tickets-tab">
          <div class="bg-body-tertiary d-flex flex-column gap-3 p-x1">
            <?php if (empty($customerTickets)): ?>
              <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm text-center">
                <p class="text-500 mb-0 py-3">No tickets found for this customer.</p>
              </div>
            <?php else: ?>
              <?php foreach ($customerTickets as $t):
                $statusLabel = $t['status_name'] ?: 'Open';
                $statusClass = 'secondary';
                if (stripos($statusLabel, 'open') !== false || stripos($statusLabel, 'recent') !== false) {
                  $statusClass = 'success';
                } elseif (stripos($statusLabel, 'progress') !== false || stripos($statusLabel, 'responded') !== false) {
                  $statusClass = 'info';
                } elseif (stripos($statusLabel, 'pending') !== false) {
                  $statusClass = 'warning';
                }

                $priorityColor = strtolower(trim((string) ($t['priority_color'] ?? 'primary')));
                $falconColors = [
                  'primary' => '#2c7be5',
                  'success' => '#00d27a',
                  'warning' => '#f5803e',
                  'danger' => '#e63757',
                  'info' => '#27bcfd',
                  'secondary' => '#748194'
                ];
                $strokeColor = $falconColors[$priorityColor] ?? '#2c7be5';
                ?>
                <?php
                $isSelected = isset($_GET['ticket_id']) && (int) $_GET['ticket_id'] === (int) $t['ticket_id'];
                $ticketUrl = 'customer-details.php?' . http_build_query(array_merge($_GET, ['ticket_id' => $t['ticket_id']]));
                ?>
                <div
                  class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm d-md-flex d-xl-inline-block d-xxl-flex align-items-center <?= $isSelected ? 'border border-primary border-2' : '' ?>"
                  style="cursor: pointer;" onclick="window.location='<?= $ticketUrl ?>'">
                  <div class="flex-1">
                    <p class="fw-semi-bold mb-1">
                      <a href="<?= $ticketUrl ?>" class="<?= $isSelected ? 'text-primary' : '' ?>">
                        <?= htmlspecialchars((string) $t['ticket_no']) ?> |
                        <?= htmlspecialchars(mb_strimwidth((string) $t['issue_details'], 0, 80, '...')) ?>
                      </a>
                    </p>
                    <div class="d-flex align-items-center">
                      <h6 class="mb-0 me-3 text-800 fs-11"><?= date('d M, Y', strtotime($t['created_at'])) ?></h6>
                      <small
                        class="badge rounded badge-subtle-<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></small>
                    </div>
                  </div>
                  <div class="border-bottom d-md-none mt-3 mb-3"></div>
                  <div class="d-flex justify-content-between align-items-center ms-md-auto">
                    <div class="d-flex align-items-center gap-2 me-4" style="width:7.5rem;">
                      <div style="--falcon-circle-progress-bar:100">
                        <svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                          <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" stroke-width="12"></circle>
                          <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" stroke-linecap="round"
                            stroke="<?= $strokeColor ?>" stroke-width="12"></circle>
                        </svg>
                      </div>
                      <h6 class="mb-0 text-700 fs-11"><?= htmlspecialchars((string) ($t['priority_name'] ?: 'Normal')) ?>
                      </h6>
                    </div>
                    <div class="text-end">
                      <div class="fs-11 text-600">Agent</div>
                      <div class="fw-semi-bold text-900 fs-11">
                        <?= htmlspecialchars((string) ($t['assigned_to'] ?: 'Unassigned')) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <!-- END OF TICKETS TAB -->
        <div class="card-body tab-pane p-0" id="notes" role="tabpanel" aria-labelledby="contact-notes-tab">
          <div class="bg-body-tertiary d-flex flex-column gap-3 p-x1">
            <?php if (empty($allCustomerNotes)): ?>
              <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm text-center">
                <p class="text-500 mb-0 py-3">No ticket notes found for this customer.</p>
              </div>
            <?php else: ?>
              <?php foreach ($allCustomerNotes as $ticketGroup): ?>
                <div class="bg-white dark__bg-1100 p-x1 rounded-3 shadow-sm">
                  <div class="row flex-between-center mb-2">
                    <div class="col-12">
                      <h5 class="mb-1 border-bottom pb-2">
                        <a
                          href="customer-details.php?id=<?= $customerId ?>&ticket_id=<?= (int) $ticketGroup['ticket_id'] ?>#tickets">
                          Ticket: <?= htmlspecialchars((string) $ticketGroup['ticket_no']) ?>
                        </a>
                      </h5>
                    </div>
                  </div>
                  <div class="d-flex flex-column gap-2">
                    <?php foreach ($ticketGroup['notes'] as $note):
                      $noteAuthor = $note['created_by_name'] ?: $note['created_by_username'] ?: 'System';
                      $noteTime = new DateTime($note['created_at']);
                      ?>
                      <div class="p-2 border rounded-3 bg-body-tertiary">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                          <small class="badge badge-subtle-info rounded-pill fs-11">Agent:
                            <?= htmlspecialchars($noteAuthor) ?></small>
                          <small class="text-600 fs-11">
                            <span class="fas fa-clock me-1"></span><?= $noteTime->format('d M, Y h:i A') ?>
                          </small>
                        </div>
                        <p class="mb-0 fs-10 text-700">
                          <?= nl2br(htmlspecialchars((string) $note['note_text'])) ?>
                        </p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body tab-pane p-0" id="assets" role="tabpanel" aria-labelledby="contact-assets-tab">
          <div class="bg-body-tertiary p-x1">
            <?php if (isset($_GET['asset_saved'])): ?>
              <div class="alert alert-success py-2 mb-3 fs-10">Asset operation saved successfully.</div>
            <?php endif; ?>
            <?php if (isset($assetError)): ?>
              <div class="alert alert-danger py-2 mb-3 fs-10"><?= htmlspecialchars($assetError) ?></div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-lg-8">
                <div class="card shadow-none">
                  <div class="card-header bg-white border-bottom border-200 py-2">
                    <h6 class="mb-0">Asset History</h6>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-sm table-striped fs-11 mb-0">
                        <thead class="bg-200">
                          <tr>
                            <th class="ps-x1">Date</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Serials</th>
                            <th class="pe-x1 text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($assetHistory)): ?>
                            <tr>
                              <td colspan="6" class="text-center py-3 text-500">No asset records found for this customer.
                              </td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($assetHistory as $asset): ?>
                              <tr>
                                <td class="ps-x1 text-nowrap"><?= date('M d, Y', strtotime($asset['created_at'])) ?></td>
                                <td>
                                  <span
                                    class="badge badge-subtle-<?= $asset['op_type'] === 'checkout' ? 'primary' : 'success' ?> fs-11">
                                    <?= ucfirst($asset['op_type']) ?>
                                  </span>
                                </td>
                                <td class="fw-semi-bold"><?= htmlspecialchars($asset['product_name']) ?></td>
                                <td><?= $asset['quantity'] ?></td>
                                <td class="text-600"><?= htmlspecialchars((string) $asset['serials']) ?></td>
                                <td class="pe-x1 text-end">
                                  <a href="../inventory/operation-print.php?op_id=<?= $asset['op_id'] ?>" target="_blank"
                                    class="btn btn-link p-0 text-600"><span class="fas fa-print"></span></a>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-4">
                <!-- Compact Checkout -->
                <div class="card shadow-none mb-3">
                  <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0 text-white"><span class="fas fa-sign-out-alt me-2"></span>Quick Checkout</h6>
                  </div>
                  <div class="card-body p-2">
                    <form method="post" action="customer-details.php?id=<?= $customerId ?>">
            <?= ispts_csrf_field() ?>
                      <input type="hidden" name="asset_action" value="checkout">
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Branch</label>
                        <input type="hidden" name="branch_id" value="<?= $customerBranchId ?>">
                        <select class="form-select form-select-sm" disabled>
                          <?php if ($customerBranchId > 0): ?>
                            <?php foreach ($branches as $b): ?>
                              <?php if ((int) $b['branch_id'] === $customerBranchId): ?>
                                <option value="<?= $b['branch_id'] ?>" selected><?= htmlspecialchars($b['branch_name']) ?>
                                </option>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <option value="0" selected>No Branch Assigned</option>
                          <?php endif; ?>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Product</label>
                        <select class="form-select form-select-sm js-asset-prod" name="product_id" required
                          data-type="checkout">
                          <option value="" disabled selected>Select Product</option>
                          <?php foreach ($checkoutProducts as $p): ?>
                            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Serials</label>
                        <select class="form-select form-select-sm js-asset-serials" name="serials[]" multiple
                          data-placeholder="Select Serials">
                        </select>
                      </div>
                      <button class="btn btn-primary btn-sm w-100" type="submit">Issue Asset</button>
                    </form>
                  </div>
                </div>

                <!-- Compact Checkin -->
                <div class="card shadow-none">
                  <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0 text-white"><span class="fas fa-sign-in-alt me-2"></span>Quick Return</h6>
                  </div>
                  <div class="card-body p-2">
                    <form method="post" action="customer-details.php?id=<?= $customerId ?>">
            <?= ispts_csrf_field() ?>
                      <input type="hidden" name="asset_action" value="checkin">
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Branch</label>
                        <input type="hidden" name="branch_id" value="<?= $customerBranchId ?>">
                        <select class="form-select form-select-sm" disabled>
                          <?php if ($customerBranchId > 0): ?>
                            <?php foreach ($branches as $b): ?>
                              <?php if ((int) $b['branch_id'] === $customerBranchId): ?>
                                <option value="<?= $b['branch_id'] ?>" selected><?= htmlspecialchars($b['branch_name']) ?>
                                </option>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <option value="0" selected>No Branch Assigned</option>
                          <?php endif; ?>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Product</label>
                        <select class="form-select form-select-sm js-asset-prod" name="product_id" required
                          data-type="checkin">
                          <option value="" disabled selected>Select Product</option>
                          <?php foreach ($checkinProducts as $p): ?>
                            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label fs-11 mb-1">Serials</label>
                        <select class="form-select form-select-sm js-asset-serials" name="serials[]" multiple
                          data-placeholder="Select Serials">
                        </select>
                      </div>
                      <button class="btn btn-success btn-sm w-100 text-white" type="submit">Return Asset</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body tab-pane p-0" id="billing" role="tabpanel" aria-labelledby="contact-billing-tab">
          <div class="bg-body-tertiary p-x1">
            <div class="row g-3">
              <div class="col-lg-8">
                <div class="card shadow-none">
                  <div class="card-header bg-white border-bottom border-200 py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Billing Schedule</h6>
                    <div class="d-flex gap-2">
                      <span class="badge badge-subtle-success fs-11">Paid: <?= $billingPaidCount ?></span>
                      <span class="badge badge-subtle-warning fs-11">Partial Paid: <?= $billingPartialPaidCount ?></span>
                      <span class="badge badge-subtle-danger fs-11">Unpaid: <?= $billingUnpaidCount ?></span>
                    </div>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-sm table-striped fs-11 mb-0">
                        <thead class="bg-200">
                          <tr>
                            <th class="ps-x1">Bill Date</th>
                            <th>Billing Month</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th class="pe-x1 text-end">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($billingRows)): ?>
                            <tr>
                              <td colspan="5" class="text-center py-3 text-500">No billing schedule available.</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($billingRows as $bill): ?>
                              <tr>
                                <td class="ps-x1 text-nowrap"><?= date('M d, Y', strtotime($bill['bill_date'])) ?></td>
                                <td><?= htmlspecialchars($bill['month_label']) ?></td>
                                <td><?= htmlspecialchars($packageRaw !== '' ? $packageRaw : '-') ?></td>
                                <td class="fw-semi-bold">Tk <?= number_format((float) $bill['amount'], 2) ?></td>
                                <td class="pe-x1 text-end">
                                  <?php
                                  $statusValue = (string) ($bill['status'] ?? 'unpaid');
                                  $statusBadgeClass = $statusValue === 'paid' ? 'success' : ($statusValue === 'partial_paid' ? 'warning' : 'danger');
                                  $statusLabel = $statusValue === 'partial_paid' ? 'PARTIAL PAID' : strtoupper($statusValue);
                                  ?>
                                  <span class="badge badge-subtle-<?= $statusBadgeClass ?> fs-11"><?= $statusLabel ?></span>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-4">
              </div>
            </div>
          </div>
        </div>

        <div class="card-body tab-pane p-0" id="invoices" role="tabpanel" aria-labelledby="contact-invoices-tab">
          <div class="bg-body-tertiary p-x1">
            <div class="card shadow-none">
              <div class="card-header bg-white border-bottom border-200 py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Customer Invoices</h6>
                <span class="badge badge-subtle-primary fs-11">Total: <?= $invoiceCount ?></span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-striped fs-11 mb-0">
                    <thead class="bg-200">
                      <tr>
                        <th class="ps-x1">Action</th>
                        <th>Invoice ID</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Account</th>
                        <th>Details</th>
                        <th>Total</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="pe-x1 text-end">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($invoiceRows)): ?>
                        <tr>
                          <td colspan="10" class="text-center py-3 text-500">No invoices found for this customer.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($invoiceRows as $invoice): ?>
                          <tr>
                            <td class="ps-x1">
                              <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/income-management.php?edit_income=<?= (int) $invoice['income_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View invoice">
                                <span class="fas fa-eye text-primary"></span>
                              </a>
                            </td>
                            <td><?= htmlspecialchars((string) ($invoice['trx_id'] ?? '-')) ?></td>
                            <td class="text-nowrap"><?= date('M d, Y', strtotime((string) $invoice['income_date'])) ?></td>
                            <td><?= htmlspecialchars((string) ($invoice['company'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($invoice['account_name'] ?? '-')) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars((string) ($invoice['item_descriptions'] ?? '-')) ?></td>
                            <td class="fw-semi-bold">Tk <?= number_format((float) ($invoice['grand_total'] ?? 0), 2) ?></td>
                            <td><?= htmlspecialchars((string) ($invoice['payment_method'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) (($invoice['reference_no'] ?? '') !== '' ? $invoice['reference_no'] : '-')) ?></td>
                            <td class="pe-x1 text-end">
                              <?php if ((string) ($invoice['payment_status'] ?? 'unpaid') === 'paid'): ?>
                                <span class="badge badge-subtle-success fs-11">PAID</span>
                              <?php else: ?>
                                <span class="badge badge-subtle-warning fs-11">UNPAID</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Ticket Details -->
  <div class="col-xxl-3 col-xl-3">
    <?php if ($selectedTicket):
      $t = $selectedTicket;
      $statusLabel = $t['status_name'] ?: 'Open';
      $statusClass = 'secondary';
      if (stripos($statusLabel, 'open') !== false || stripos($statusLabel, 'recent') !== false)
        $statusClass = 'success';
      elseif (stripos($statusLabel, 'progress') !== false || stripos($statusLabel, 'responded') !== false)
        $statusClass = 'info';
      elseif (stripos($statusLabel, 'pending') !== false)
        $statusClass = 'warning';

      $created = new DateTime($t['created_at']);
      $updated = new DateTime($t['updated_at']);
      $diff = $created->diff(new DateTime());
      $duration = $diff->days > 0 ? $diff->days . ' days' : $diff->h . ' hours';

      $currentUrl = 'customer-details.php?' . http_build_query($_GET);
      ?>
      <div class="card mb-3"
        style="background-image: url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-1.png); background-position: right bottom; background-repeat: no-repeat; background-size: auto 100%;">
        <div class="card-body position-relative">
          <!-- Row 1: Status + Duration + Action -->
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
              <span class="fas fa-info-circle text-500 fs-9"></span>
              <span
                class="badge badge-subtle-<?= $statusClass ?> rounded-pill"><?= htmlspecialchars($statusLabel) ?></span>
              <span class="badge badge-subtle-success rounded-pill"><span
                  class="fas fa-calendar-plus me-1"></span><?= $duration ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-link p-0 text-primary" type="button" title="Print 58mm"
                onclick="printThermalTicket(<?= (int) $t['ticket_id'] ?>)">
                <span class="fas fa-print fs-9"></span>
              </button>
              <a class="btn btn-link p-0 text-primary" href="all-tickets.php?ticket_id=<?= (int) $t['ticket_id'] ?>"
                title="Edit in All Tickets">
                <span class="fas fa-edit fs-9"></span>
              </a>
            </div>
          </div>

          <!-- Row 2: Ticket ID -->
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="fw-semi-bold fs-10"><?= htmlspecialchars((string) $t['ticket_no']) ?></span>
            <button class="btn btn-link p-0 text-primary" type="button"
              onclick="copyToClipboard('<?= htmlspecialchars((string) $t['ticket_no']) ?>')" title="Copy Ticket ID">
              <span class="fas fa-copy fs-9"></span>
            </button>
          </div>

          <!-- Row 3: Timestamps -->
          <div class="d-flex flex-column gap-1 mb-3">
            <small class="badge badge-subtle-info rounded-pill w-100 text-start">
              <span class="fas fa-clock me-1"></span>Created: <?= $created->format('M d, Y H:i') ?>
            </small>
            <small class="badge badge-subtle-secondary rounded-pill w-100 text-start">
              <span class="fas fa-sync me-1"></span>Updated: <?= $updated->format('M d, Y H:i') ?>
            </small>
          </div>

          <!-- Quick Update Form -->
          <form method="post" action="all-tickets.php?<?= http_build_query($_GET) ?>#selected-ticket-details"
            class="mb-0">
            <?= ispts_csrf_field() ?>
            <input type="hidden" name="action" value="quick_update_ticket">
            <input type="hidden" name="ticket_id" value="<?= (int) $t['ticket_id'] ?>">
            <div class="d-flex flex-column gap-2">
              <select class="form-select form-select-sm" name="priority_id">
                <option value="0">Priority</option>
                <?php foreach ($priorities as $p): ?>
                  <option value="<?= (int) $p['priority_id'] ?>" <?= (int) $t['priority_id'] === (int) $p['priority_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['priority_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select class="form-select form-select-sm" name="assigned_employee_id">
                <option value="0">Agent</option>
                <?php foreach ($employees as $e): ?>
                  <option value="<?= (int) $e['admin_user_id'] ?>"
                    <?= (int) $t['assigned_employee_id'] === (int) $e['admin_user_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['full_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary btn-sm w-100" type="submit">
                <span class="fas fa-check me-1"></span>Update
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Ticket Notes Section -->
      <div class="card mb-3">
        <div class="card-header bg-body-tertiary">
          <h6 class="mb-0">Ticket History & Notes</h6>
        </div>
        <div class="card-body p-x1">
          <?php if (empty($ticketNotes)): ?>
            <p class="text-500 fs-11 mb-3">No previous notes for this ticket.</p>
          <?php else: ?>
            <div class="scrollbar-overlay mb-3" style="max-height: 300px;">
              <?php foreach ($ticketNotes as $note):
                $noteAuthor = $note['created_by_name'] ?: $note['created_by_username'] ?: 'System';
                $noteTime = new DateTime($note['created_at']);
                $now = new DateTime();
                $diff = $now->diff($noteTime);
                if ($diff->days > 0)
                  $ago = $diff->days . 'd ago';
                elseif ($diff->h > 0)
                  $ago = $diff->h . 'h ago';
                elseif ($diff->i > 0)
                  $ago = $diff->i . 'm ago';
                else
                  $ago = 'Just now';
                ?>
                <div class="border rounded p-2 mb-2 bg-body-tertiary">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <small class="fw-semi-bold fs-11 text-800"><?= htmlspecialchars($noteAuthor) ?></small>
                    <span class="badge badge-subtle-secondary rounded-pill fs-11"><?= $ago ?></span>
                  </div>
                  <div class="fs-10 text-700"><?= nl2br(htmlspecialchars((string) $note['note_text'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" action="all-tickets.php?<?= http_build_query($_GET) ?>#selected-ticket-details"
            class="mb-0">
            <?= ispts_csrf_field() ?>
            <input type="hidden" name="action" value="add_ticket_note">
            <input type="hidden" name="ticket_id" value="<?= (int) $t['ticket_id'] ?>">
            <div class="d-flex align-items-start gap-2">
              <textarea class="form-control form-control-sm flex-fill" name="note_text" rows="2"
                placeholder="Add a note..." required></textarea>
              <button class="btn btn-primary btn-sm" type="submit" title="Add note">
                <span class="fas fa-paper-plane"></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="card bg-body-tertiary">
        <div class="card-body text-center py-4">
          <span class="fas fa-ticket-alt fs-2 text-300 mb-2"></span>
          <p class="fs-10 text-600 mb-0">Select a ticket from the list to view details.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const prodSelects = document.querySelectorAll('.js-asset-prod');
    prodSelects.forEach(sel => {
      sel.addEventListener('change', function () {
        const type = this.dataset.type;
        const pid = this.value;
        const serialSel = this.closest('form').querySelector('.js-asset-serials');

        const bid = this.closest('form').querySelector('[name="branch_id"]').value;
        fetch(`customer-details.php?id=<?= $customerId ?>&ajax_get_serials_${type}=1&pid=${pid}&bid=${bid}`)
          .then(r => r.json())
          .then(data => {
            serialSel.innerHTML = data.map(s => `<option value="${s.serial_id}">${s.serial_ref}</option>`).join('');
            if (window.Choices) {
              if (serialSel._choices) serialSel._choices.destroy();
              serialSel._choices = new window.Choices(serialSel, { removeItemButton: true, itemSelectText: '' });
            }
          });
      });

      if (window.Choices) new window.Choices(sel, { searchEnabled: true, itemSelectText: '' });
    });
  });

  function printThermalTicket(ticketId) {
    if (!ticketId) return;
    const printUrl = `<?= $appBasePath ?>/app/support-desk/print-ticket.php?ticket_id=${ticketId}`;
    const printWindow = window.open(printUrl, 'Print Ticket', 'width=400,height=600');
    if (printWindow) {
      printWindow.onload = function () {
        printWindow.print();
      };
    }
  }

  function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
      // Optional: Show a toast or tooltip
    });
  }
</script>
<?php
require '../../includes/footer.php';
?>