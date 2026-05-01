<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS finance_accounts (
        account_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id INT UNSIGNED NOT NULL,
        account_name VARCHAR(180) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_finance_accounts_company_name (company_id, account_name),
        KEY idx_finance_accounts_company (company_id),
        KEY idx_finance_accounts_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/finance/account-list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_account') {
        $accountId = isset($_POST['account_id']) ? (int) $_POST['account_id'] : 0;
        $companyId = isset($_POST['company_id']) ? (int) $_POST['company_id'] : 0;
        $accountName = trim((string) ($_POST['account_name'] ?? ''));
        $status = isset($_POST['status']) ? 1 : 0;

        if ($companyId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select a company.'];
        } elseif ($accountName === '') {
            $alert = ['type' => 'danger', 'message' => 'Account name is required.'];
        } else {
            if ($accountId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE finance_accounts
                     SET company_id = :company_id,
                         account_name = :account_name,
                         status = :status
                     WHERE account_id = :account_id'
                );
                $stmt->bindValue(':account_id', $accountId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO finance_accounts (company_id, account_name, status)
                     VALUES (:company_id, :account_name, :status)'
                );
            }

            try {
                $stmt->bindValue(':company_id', $companyId, \PDO::PARAM_INT);
                $stmt->bindValue(':account_name', $accountName);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to save account. Use a unique account name per company.'];
            }
        }
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Saved successfully.'];
}

$accountForm = [
    'account_id' => 0,
    'company_id' => 0,
    'account_name' => '',
    'status' => 1,
];

$editAccountId = isset($_GET['edit_account']) ? (int) $_GET['edit_account'] : 0;
if ($editAccountId > 0) {
    $stmt = $pdo->prepare(
        'SELECT account_id, company_id, account_name, status
         FROM finance_accounts
         WHERE account_id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $editAccountId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($row) {
        $accountForm = [
            'account_id' => (int) $row['account_id'],
            'company_id' => (int) $row['company_id'],
            'account_name' => (string) $row['account_name'],
            'status' => (int) $row['status'],
        ];
    }
}

$companies = $pdo->query(
    "SELECT id, company
     FROM companies
     WHERE status = 1
       AND enabled = 1
       AND deleted_at IS NULL
       AND company IS NOT NULL
       AND company <> ''
     ORDER BY company ASC"
)->fetchAll(\PDO::FETCH_ASSOC);

$accounts = $pdo->query(
    'SELECT fa.account_id, fa.company_id, fa.account_name, fa.status, c.company
     FROM finance_accounts fa
     LEFT JOIN companies c ON c.id = fa.company_id
     ORDER BY c.company ASC, fa.account_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Finance Account List';
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Account List</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Finance Account List</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage account names by company.</p>
      </div>
    </div>
  </div>

  <?php if ($alert): ?>
    <div class="col-12">
      <div class="alert alert-<?= htmlspecialchars((string) $alert['type']) ?> py-2" role="alert">
        <?= htmlspecialchars((string) $alert['message']) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12 col-md-8 col-xxl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0">Account List</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Company</th>
                <th class="text-800">Account Name</th>
                <th class="text-800">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($accounts)): ?>
                <tr><td colspan="4" class="text-center py-3 text-600">No accounts found.</td></tr>
              <?php else: ?>
                <?php foreach ($accounts as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/account-list.php?edit_account=<?= (int) $row['account_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><span class="fas fa-edit text-500"></span></a>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['company'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['account_name']) ?></td>
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

  <div class="col-12 col-md-4 col-xxl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $accountForm['account_id'] > 0 ? 'Update Account' : 'Add Account' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/account-list.php">
          <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_account">
          <input type="hidden" name="account_id" value="<?= (int) $accountForm['account_id'] ?>">

          <div class="col-12">
            <label class="form-label" for="account-company">Select company</label>
            <select class="form-select" id="account-company" name="company_id" required>
              <option value="" disabled <?= (int) $accountForm['company_id'] <= 0 ? 'selected' : '' ?>>Select company</option>
              <?php foreach ($companies as $company): ?>
                <option value="<?= (int) $company['id'] ?>" <?= (int) $accountForm['company_id'] === (int) $company['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $company['company']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="account-name">Account name</label>
            <input class="form-control" id="account-name" name="account_name" type="text" value="<?= htmlspecialchars((string) $accountForm['account_name']) ?>" required>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label mb-0" for="account-status">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" id="account-status" type="checkbox" name="status" value="1" <?= (int) $accountForm['status'] === 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/account-list.php">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit"><?= $accountForm['account_id'] > 0 ? 'Update' : 'Add' ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
require '../../includes/footer.php';
