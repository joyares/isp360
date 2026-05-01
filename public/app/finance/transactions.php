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

$transactions = $pdo->query(
    'SELECT fe.expense_id,
            fe.trx_id,
            fe.expense_date,
            fe.amount,
            fe.payment_method,
            fe.note,
            fe.reference_no,
            fe.created_by_name,
            fe.update_count,
            fe.status,
            fe.created_at,
            fe.updated_at,
            fa.account_name,
            c.company,
            ec.category_name,
            esc.sub_category_name,
            iv.vendor_name
     FROM finance_expenses fe
     LEFT JOIN finance_accounts fa ON fa.account_id = fe.account_id
     LEFT JOIN companies c ON c.id = fe.company_id
     LEFT JOIN expense_categories ec ON ec.category_id = fe.category_id
     LEFT JOIN expense_sub_categories esc ON esc.sub_category_id = fe.sub_category_id
     LEFT JOIN inventory_vendors iv ON iv.vendor_id = fe.vendor_id
     ORDER BY fe.created_at DESC, fe.expense_id DESC'
)->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Finance Transactions';
require '../../includes/header.php';
?>
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
        <p class="text-700 mb-0">All transactions created from Expense Management are shown here with full audit fields.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Transaction List</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">TRXID</th>
                <th class="text-800">Date</th>
                <th class="text-800">Company</th>
                <th class="text-800">Account</th>
                <th class="text-800">Category</th>
                <th class="text-800">Sub Category</th>
                <th class="text-800">Vendor</th>
                <th class="text-800">Amount</th>
                <th class="text-800">Method</th>
                <th class="text-800">Reference</th>
                <th class="text-800">Note</th>
                <th class="text-800">Created By</th>
                <th class="text-800">Created At</th>
                <th class="text-800">Updated At</th>
                <th class="text-800">Updated For</th>
                <th class="text-800">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($transactions)): ?>
                <tr><td colspan="17" class="text-center py-3 text-600">No transactions found.</td></tr>
              <?php else: ?>
                <?php foreach ($transactions as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/expense-management.php?edit_expense=<?= (int) $row['expense_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Expense"><span class="fas fa-edit text-500"></span></a>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['trx_id'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['expense_date']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['company'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['account_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['sub_category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['vendor_name'] ?? '-')) ?></td>
                    <td><?= number_format((float) $row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars((string) $row['payment_method']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['reference_no'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['note'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['created_by_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                    <td><?= htmlspecialchars((string) $row['updated_at']) ?></td>
                    <td><?= (int) ($row['update_count'] ?? 0) ?> time(s)</td>
                    <td><?= (int) $row['status'] === 1 ? '<span class="badge badge-subtle-success">On</span>' : '<span class="badge badge-subtle-danger">Off</span>' ?></td>
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

<?php
require '../../includes/footer.php';
