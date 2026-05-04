<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS finance_expenses (
        expense_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trx_id VARCHAR(80) NULL,
        account_id BIGINT UNSIGNED NOT NULL,
        company_id INT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        sub_category_id BIGINT UNSIGNED NOT NULL,
        vendor_id BIGINT UNSIGNED NULL,
        expense_date DATE NOT NULL,
        amount DECIMAL(14,2) NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        note TEXT NULL,
        reference_no VARCHAR(120) NULL,
        created_by_user_id BIGINT UNSIGNED NULL,
        created_by_name VARCHAR(180) NULL,
        update_count INT UNSIGNED NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_finance_expenses_account (account_id),
        KEY idx_finance_expenses_company (company_id),
        KEY idx_finance_expenses_category (category_id),
        KEY idx_finance_expenses_sub_category (sub_category_id),
        KEY idx_finance_expenses_vendor (vendor_id),
        KEY idx_finance_expenses_date (expense_date),
        KEY idx_finance_expenses_status (status),
        UNIQUE KEY uk_finance_expenses_trx_id (trx_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
if ($databaseName !== '') {
    $updateCountColumnExistsStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $updateCountColumnExistsStmt->bindValue(':schema', $databaseName);
    $updateCountColumnExistsStmt->bindValue(':table_name', 'finance_expenses');
    $updateCountColumnExistsStmt->bindValue(':column_name', 'update_count');
    $updateCountColumnExistsStmt->execute();
    $updateCountColumnExists = (int) $updateCountColumnExistsStmt->fetchColumn() > 0;

    if (!$updateCountColumnExists) {
        $pdo->exec('ALTER TABLE finance_expenses ADD COLUMN update_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER created_by_name');
    }
}

// ── Filter inputs ────────────────────────────────────────────────────────
$filterSearch      = trim((string) ($_GET['search'] ?? ''));
$filterCompanyId   = isset($_GET['filter_company']) ? (int) $_GET['filter_company'] : 0;
$filterAccountId   = isset($_GET['filter_account']) ? (int) $_GET['filter_account'] : 0;
$filterMethod      = trim((string) ($_GET['filter_method'] ?? ''));
$filterCreatedFrom = trim((string) ($_GET['filter_created_from'] ?? ''));
$filterCreatedTo   = trim((string) ($_GET['filter_created_to'] ?? ''));
$filterUpdatedFrom = trim((string) ($_GET['filter_updated_from'] ?? ''));
$filterUpdatedTo   = trim((string) ($_GET['filter_updated_to'] ?? ''));

// ── Dropdown data ─────────────────────────────────────────────────────────
$filterCompanies = $pdo->query(
    "SELECT id, company FROM companies WHERE status = 1 ORDER BY company ASC"
)->fetchAll(\PDO::FETCH_ASSOC);

$filterAccounts = $pdo->query(
    "SELECT fa.account_id, fa.account_name, c.company
     FROM finance_accounts fa
     LEFT JOIN companies c ON c.id = fa.company_id
     WHERE fa.status = 1
     ORDER BY c.company ASC, fa.account_name ASC"
)->fetchAll(\PDO::FETCH_ASSOC);

$paymentMethodOptions = ['Cash', 'Bkash', 'Nagad', 'Bank', 'Other'];

// ── Outer WHERE conditions ────────────────────────────────────────────────
$outerWhere  = [];
$outerParams = [];

if ($filterSearch !== '') {
    $outerWhere[]              = '(t.trx_id LIKE :search OR t.reference_no LIKE :search2 OR t.note LIKE :search3 OR t.party_name LIKE :search4 OR t.created_by_name LIKE :search5)';
    $outerParams[':search']    = '%' . $filterSearch . '%';
    $outerParams[':search2']   = '%' . $filterSearch . '%';
    $outerParams[':search3']   = '%' . $filterSearch . '%';
    $outerParams[':search4']   = '%' . $filterSearch . '%';
    $outerParams[':search5']   = '%' . $filterSearch . '%';
}
if ($filterCompanyId > 0) {
    $outerWhere[]                = 't.company_id = :company_id';
    $outerParams[':company_id']  = $filterCompanyId;
}
if ($filterAccountId > 0) {
    $outerWhere[]                = 't.account_id = :account_id';
    $outerParams[':account_id']  = $filterAccountId;
}
if ($filterMethod !== '') {
    $outerWhere[]            = 't.payment_method = :method';
    $outerParams[':method']  = $filterMethod;
}
if ($filterCreatedFrom !== '') {
    $outerWhere[]                 = 't.created_at >= :created_from';
    $outerParams[':created_from'] = $filterCreatedFrom . ' 00:00:00';
}
if ($filterCreatedTo !== '') {
    $outerWhere[]               = 't.created_at <= :created_to';
    $outerParams[':created_to'] = $filterCreatedTo . ' 23:59:59';
}
if ($filterUpdatedFrom !== '') {
    $outerWhere[]                 = 't.updated_at >= :updated_from';
    $outerParams[':updated_from'] = $filterUpdatedFrom . ' 00:00:00';
}
if ($filterUpdatedTo !== '') {
    $outerWhere[]               = 't.updated_at <= :updated_to';
    $outerParams[':updated_to'] = $filterUpdatedTo . ' 23:59:59';
}

$whereClause = !empty($outerWhere) ? 'WHERE ' . implode(' AND ', $outerWhere) : '';

$sql = "SELECT t.* FROM (
     SELECT 'expense'           AS source_type,
            fe.expense_id       AS source_id,
            fe.company_id,
            fe.account_id,
            fe.trx_id,
            fe.expense_date     AS txn_date,
            fe.amount,
            fe.payment_method,
            fe.note,
            fe.reference_no,
            fe.created_by_name,
            fe.update_count,
            fe.created_at,
            fe.updated_at,
            fa.account_name,
            c.company,
            ec.category_name,
            esc.sub_category_name,
            iv.vendor_name      AS party_name,
            'Debit'             AS trx_type
     FROM finance_expenses fe
     LEFT JOIN finance_accounts fa   ON fa.account_id         = fe.account_id
     LEFT JOIN companies c           ON c.id                  = fe.company_id
     LEFT JOIN expense_categories ec ON ec.category_id        = fe.category_id
     LEFT JOIN expense_sub_categories esc ON esc.sub_category_id = fe.sub_category_id
     LEFT JOIN inventory_vendors iv  ON iv.vendor_id          = fe.vendor_id
     WHERE fe.status = 1

     UNION ALL

     SELECT 'income'                                             AS source_type,
            fi.income_id                                         AS source_id,
            fi.company_id,
            fi.account_id,
            fi.trx_id,
            fi.income_date                                       AS txn_date,
            fi.grand_total                                       AS amount,
            fi.payment_method,
            fi.note,
            fi.reference_no,
            fi.created_by_name,
            fi.update_count,
            fi.created_at,
            fi.updated_at,
            fa.account_name,
            c.company,
            NULL                                                 AS category_name,
            NULL                                                 AS sub_category_name,
            COALESCE(NULLIF(cu.full_name, ''), cu.username)     AS party_name,
            'Credit'                                             AS trx_type
     FROM finance_incomes fi
     LEFT JOIN finance_accounts fa ON fa.account_id = fi.account_id
     LEFT JOIN companies c         ON c.id          = fi.company_id
     LEFT JOIN customers cu        ON cu.customer_id = fi.customer_id
     WHERE fi.status = 1
) AS t
{$whereClause}
ORDER BY t.created_at DESC, t.source_id DESC";

$stmt = $pdo->prepare($sql);
foreach ($outerParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Finance Transactions';
require '../../includes/header.php';
?>
<link rel="stylesheet" href="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.css">
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Transactions</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Finance Transactions</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">All transactions from Income and Expense Management are shown here. Debit = Expense, Credit = Income.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0">Transaction List <span class="text-600 fw-normal fs-11">(<?= count($transactions) ?> results)</span></h6>
      </div>
      <div class="card-body border-bottom py-2">
        <form method="get" action="<?= $appBasePath ?>/app/finance/transactions.php" class="row g-2 align-items-end" id="txn-filter-form">
          <div class="col-12 col-md-3 col-xl-2">
            <input type="text" class="form-control form-control-sm" name="search" placeholder="Search TRXID, party, note..." value="<?= htmlspecialchars($filterSearch) ?>">
          </div>
          <div class="col-6 col-md-2">
            <select class="form-select form-select-sm" name="filter_company">
              <option value="">All Companies</option>
              <?php foreach ($filterCompanies as $co): ?>
                <option value="<?= (int) $co['id'] ?>" <?= $filterCompanyId === (int) $co['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $co['company']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select class="form-select form-select-sm" name="filter_account">
              <option value="">All Accounts</option>
              <?php foreach ($filterAccounts as $ac): ?>
                <option value="<?= (int) $ac['account_id'] ?>" <?= $filterAccountId === (int) $ac['account_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $ac['account_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-1">
            <select class="form-select form-select-sm" name="filter_method">
              <option value="">All Methods</option>
              <?php foreach ($paymentMethodOptions as $pm): ?>
                <option value="<?= htmlspecialchars($pm) ?>" <?= $filterMethod === $pm ? 'selected' : '' ?>><?= htmlspecialchars($pm) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <input type="text" class="form-control form-control-sm" id="created-at-picker" placeholder="Created At range" autocomplete="off" readonly>
            <input type="hidden" name="filter_created_from" id="filter_created_from" value="<?= htmlspecialchars($filterCreatedFrom) ?>">
            <input type="hidden" name="filter_created_to" id="filter_created_to" value="<?= htmlspecialchars($filterCreatedTo) ?>">
          </div>
          <div class="col-6 col-md-2">
            <input type="text" class="form-control form-control-sm" id="updated-at-picker" placeholder="Updated At range" autocomplete="off" readonly>
            <input type="hidden" name="filter_updated_from" id="filter_updated_from" value="<?= htmlspecialchars($filterUpdatedFrom) ?>">
            <input type="hidden" name="filter_updated_to" id="filter_updated_to" value="<?= htmlspecialchars($filterUpdatedTo) ?>">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?= $appBasePath ?>/app/finance/transactions.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
          </div>
        </form>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Type</th>
                <th class="text-800">TRXID</th>
                <th class="text-800">Date</th>
                <th class="text-800">Company</th>
                <th class="text-800">Account</th>
                <th class="text-800">Category</th>
                <th class="text-800">Sub Category</th>
                <th class="text-800">Customer / Vendor</th>
                <th class="text-800">Amount</th>
                <th class="text-800">Method</th>
                <th class="text-800">Reference</th>
                <th class="text-800">Note</th>
                <th class="text-800">Created By</th>
                <th class="text-800">Created At</th>
                <th class="text-800">Updated At</th>
                <th class="text-800">Updated For</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($transactions)): ?>
                <tr><td colspan="17" class="text-center py-3 text-600">No transactions found.</td></tr>
              <?php else: ?>
                <?php foreach ($transactions as $row): ?>
                  <tr>
                    <td>
                      <?php if ($row['source_type'] === 'expense'): ?>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/expense-management.php?edit_expense=<?= (int) $row['source_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Expense"><span class="fas fa-edit text-500"></span></a>
                      <?php else: ?>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/income-management.php?edit_income=<?= (int) $row['source_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Income"><span class="fas fa-edit text-500"></span></a>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['trx_type'] === 'Debit'): ?>
                        <span class="badge badge-subtle-danger">Debit</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-success">Credit</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['trx_id'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['txn_date']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['company'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['account_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['sub_category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['party_name'] ?? '-')) ?></td>
                    <td><?= number_format((float) $row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars((string) $row['payment_method']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['reference_no'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['note'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['created_by_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                    <td><?= htmlspecialchars((string) $row['updated_at']) ?></td>
                    <td><?= (int) ($row['update_count'] ?? 0) ?> time(s)</td>
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

<script src="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.js"></script>
<script>
(function () {
  var createdFrom = <?= json_encode($filterCreatedFrom) ?>;
  var createdTo   = <?= json_encode($filterCreatedTo) ?>;
  var updatedFrom = <?= json_encode($filterUpdatedFrom) ?>;
  var updatedTo   = <?= json_encode($filterUpdatedTo) ?>;

  flatpickr('#created-at-picker', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    defaultDate: (createdFrom && createdTo) ? [createdFrom, createdTo] : (createdFrom ? [createdFrom] : null),
    onChange: function (dates) {
      document.getElementById('filter_created_from').value = dates[0] ? flatpickr.formatDate(dates[0], 'Y-m-d') : '';
      document.getElementById('filter_created_to').value   = dates[1] ? flatpickr.formatDate(dates[1], 'Y-m-d') : '';
    }
  });

  flatpickr('#updated-at-picker', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    defaultDate: (updatedFrom && updatedTo) ? [updatedFrom, updatedTo] : (updatedFrom ? [updatedFrom] : null),
    onChange: function (dates) {
      document.getElementById('filter_updated_from').value = dates[0] ? flatpickr.formatDate(dates[0], 'Y-m-d') : '';
      document.getElementById('filter_updated_to').value   = dates[1] ? flatpickr.formatDate(dates[1], 'Y-m-d') : '';
    }
  });
}());
</script>
<?php
require '../../includes/footer.php';
