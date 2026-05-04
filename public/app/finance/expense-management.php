<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../includes/auth.php';

use App\Core\Database;

if (!function_exists('ispts_expense_trx_part')) {
  function ispts_expense_trx_part(string $label): string
  {
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($label)) ?? '');
    if ($clean === '') {
      $clean = 'XXX';
    }

    return str_pad(substr($clean, 0, 3), 3, 'X');
  }
}

if (!function_exists('ispts_generate_expense_trxid')) {
  function ispts_generate_expense_trxid(string $categoryName, string $subCategoryName, int $seed): string
  {
    $categoryPart = ispts_expense_trx_part($categoryName);
    $subCategoryPart = ispts_expense_trx_part($subCategoryName);
    $generationDate = date('YmdHis');
    $seedPart = str_pad((string) max(1, $seed), 6, '0', STR_PAD_LEFT);

    return 'EXP-' . $categoryPart . '-' . $subCategoryPart . '-' . $generationDate . '-' . $seedPart;
  }
}

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

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS expense_categories (
        category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(120) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_expense_categories_name (category_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS expense_sub_categories (
        sub_category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NOT NULL,
        sub_category_name VARCHAR(120) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_expense_sub_categories_name (category_id, sub_category_name),
        KEY idx_expense_sub_categories_category (category_id),
        CONSTRAINT fk_expense_sub_categories_category FOREIGN KEY (category_id)
            REFERENCES expense_categories(category_id)
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_vendors (
        vendor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_name VARCHAR(180) NOT NULL,
        contact_person VARCHAR(180) DEFAULT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        email VARCHAR(180) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_inventory_vendors_name (vendor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
if ($databaseName !== '') {
    $companyColumnExistsStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $companyColumnExistsStmt->bindValue(':schema', $databaseName);
    $companyColumnExistsStmt->bindValue(':table_name', 'inventory_vendors');
    $companyColumnExistsStmt->bindValue(':column_name', 'company_id');
    $companyColumnExistsStmt->execute();
    $companyColumnExists = (int) $companyColumnExistsStmt->fetchColumn() > 0;

    if (!$companyColumnExists) {
        $pdo->exec('ALTER TABLE inventory_vendors ADD COLUMN company_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER vendor_name');
    }
}

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

    if ($databaseName !== '') {
      $trxColumnExistsStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
      );
      $trxColumnExistsStmt->bindValue(':schema', $databaseName);
      $trxColumnExistsStmt->bindValue(':table_name', 'finance_expenses');
      $trxColumnExistsStmt->bindValue(':column_name', 'trx_id');
      $trxColumnExistsStmt->execute();
      $trxColumnExists = (int) $trxColumnExistsStmt->fetchColumn() > 0;

      if (!$trxColumnExists) {
        $pdo->exec('ALTER TABLE finance_expenses ADD COLUMN trx_id VARCHAR(80) NULL AFTER expense_id');
      }

      $trxUniqueIndexStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name'
      );
      $trxUniqueIndexStmt->bindValue(':schema', $databaseName);
      $trxUniqueIndexStmt->bindValue(':table_name', 'finance_expenses');
      $trxUniqueIndexStmt->bindValue(':index_name', 'uk_finance_expenses_trx_id');
      $trxUniqueIndexStmt->execute();
      $trxUniqueIndexExists = (int) $trxUniqueIndexStmt->fetchColumn() > 0;

      if (!$trxUniqueIndexExists) {
        $pdo->exec('ALTER TABLE finance_expenses ADD UNIQUE KEY uk_finance_expenses_trx_id (trx_id)');
      }

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

    $missingTrxRows = $pdo->query(
      'SELECT fe.expense_id,
          ec.category_name,
          esc.sub_category_name
       FROM finance_expenses fe
       LEFT JOIN expense_categories ec ON ec.category_id = fe.category_id
       LEFT JOIN expense_sub_categories esc ON esc.sub_category_id = fe.sub_category_id
       WHERE fe.trx_id IS NULL OR fe.trx_id = ""'
    )->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($missingTrxRows)) {
      $updateTrxStmt = $pdo->prepare('UPDATE finance_expenses SET trx_id = :trx_id WHERE expense_id = :expense_id');

      foreach ($missingTrxRows as $trxRow) {
        $expenseId = (int) ($trxRow['expense_id'] ?? 0);
        if ($expenseId <= 0) {
          continue;
        }

        $generatedTrxId = ispts_generate_expense_trxid(
          (string) ($trxRow['category_name'] ?? ''),
          (string) ($trxRow['sub_category_name'] ?? ''),
          $expenseId
        );

        $updateTrxStmt->bindValue(':trx_id', $generatedTrxId);
        $updateTrxStmt->bindValue(':expense_id', $expenseId, \PDO::PARAM_INT);
        $updateTrxStmt->execute();
      }
    }

$paymentMethods = ['Cash', 'Bkash', 'Nagad', 'Bank', 'Other'];

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/finance/expense-management.php';

$expenseForm = [
  'expense_id' => 0,
    'account_id' => 0,
    'category_id' => 0,
    'sub_category_id' => 0,
    'vendor_id' => 0,
    'expense_date' => date('Y-m-d'),
    'amount' => '',
    'payment_method' => 'Cash',
    'note' => '',
    'reference_no' => '',
    'status' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_vendor_inline') {
        $vendorCompanyId = isset($_POST['vendor_company_id']) ? (int) $_POST['vendor_company_id'] : 0;
        $vendorName = trim((string) ($_POST['vendor_name'] ?? ''));
        $contactPerson = trim((string) ($_POST['vendor_contact_person'] ?? ''));
        $phone = trim((string) ($_POST['vendor_phone'] ?? ''));
        $email = trim((string) ($_POST['vendor_email'] ?? ''));
        $address = trim((string) ($_POST['vendor_address'] ?? ''));

        if ($vendorCompanyId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select an account first to detect company for vendor.'];
        } elseif ($vendorName === '') {
            $alert = ['type' => 'danger', 'message' => 'Vendor name is required.'];
        } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $alert = ['type' => 'danger', 'message' => 'Please enter a valid vendor email address.'];
        } else {
            $vendorInsert = $pdo->prepare(
                'INSERT INTO inventory_vendors (company_id, vendor_name, contact_person, phone, email, address, sort_order, status)
                 VALUES (:company_id, :vendor_name, :contact_person, :phone, :email, :address, :sort_order, :status)'
            );

            try {
                $vendorInsert->bindValue(':company_id', $vendorCompanyId, \PDO::PARAM_INT);
                $vendorInsert->bindValue(':vendor_name', $vendorName);
                $vendorInsert->bindValue(':contact_person', $contactPerson !== '' ? $contactPerson : null, \PDO::PARAM_STR);
                $vendorInsert->bindValue(':phone', $phone !== '' ? $phone : null, \PDO::PARAM_STR);
                $vendorInsert->bindValue(':email', $email !== '' ? $email : null, \PDO::PARAM_STR);
                $vendorInsert->bindValue(':address', $address !== '' ? $address : null, \PDO::PARAM_STR);
                $vendorInsert->bindValue(':sort_order', 0, \PDO::PARAM_INT);
                $vendorInsert->bindValue(':status', 1, \PDO::PARAM_INT);
                $vendorInsert->execute();

                header('Location: ' . $currentPath . '?vendor_saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to add vendor. Please use a different vendor name.'];
            }
        }
    }

    if ($action === 'save_expense') {
        $expenseForm = [
        'expense_id' => isset($_POST['expense_id']) ? (int) $_POST['expense_id'] : 0,
            'account_id' => isset($_POST['account_id']) ? (int) $_POST['account_id'] : 0,
            'category_id' => isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0,
            'sub_category_id' => isset($_POST['sub_category_id']) ? (int) $_POST['sub_category_id'] : 0,
            'vendor_id' => isset($_POST['vendor_id']) ? (int) $_POST['vendor_id'] : 0,
            'expense_date' => (string) ($_POST['expense_date'] ?? date('Y-m-d')),
            'amount' => trim((string) ($_POST['amount'] ?? '')),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
            'reference_no' => trim((string) ($_POST['reference_no'] ?? '')),
            'status' => isset($_POST['status']) ? 1 : 0,
        ];

        $expenseId = (int) $expenseForm['expense_id'];
        $accountId = (int) $expenseForm['account_id'];
        $categoryId = (int) $expenseForm['category_id'];
        $subCategoryId = (int) $expenseForm['sub_category_id'];
        $vendorId = (int) $expenseForm['vendor_id'];
        $expenseDate = (string) $expenseForm['expense_date'];
        $amount = (string) $expenseForm['amount'];
        $paymentMethod = (string) $expenseForm['payment_method'];

        $accountCompanyId = 0;
        if ($accountId > 0) {
            $accountStmt = $pdo->prepare('SELECT company_id FROM finance_accounts WHERE account_id = :account_id LIMIT 1');
            $accountStmt->bindValue(':account_id', $accountId, \PDO::PARAM_INT);
            $accountStmt->execute();
            $accountCompanyId = (int) $accountStmt->fetchColumn();
        }

        $subCategoryName = '';
        $subCategoryStmt = null;
        if ($subCategoryId > 0) {
            $subCategoryStmt = $pdo->prepare(
                'SELECT sub_category_name, category_id
                 FROM expense_sub_categories
                 WHERE sub_category_id = :sub_category_id
                 LIMIT 1'
            );
            $subCategoryStmt->bindValue(':sub_category_id', $subCategoryId, \PDO::PARAM_INT);
            $subCategoryStmt->execute();
            $subCategoryRow = $subCategoryStmt->fetch(\PDO::FETCH_ASSOC);
            if ($subCategoryRow) {
                $subCategoryName = strtolower(trim((string) ($subCategoryRow['sub_category_name'] ?? '')));
                if ($categoryId <= 0) {
                    $categoryId = (int) ($subCategoryRow['category_id'] ?? 0);
                    $expenseForm['category_id'] = $categoryId;
                }
            }
        }

        $isVendorPaymentSubCategory = $subCategoryName !== '' && strpos($subCategoryName, 'vendor payment') !== false;

        if ($accountId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select an account.'];
        } elseif ($accountCompanyId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Selected account does not have a valid company.'];
        } elseif ($categoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select expense category.'];
        } elseif ($subCategoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select expense sub category.'];
        } elseif ($expenseDate === '' || strtotime($expenseDate) === false) {
            $alert = ['type' => 'danger', 'message' => 'Please select a valid expense date.'];
        } elseif (!is_numeric($amount) || (float) $amount <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please enter a valid expense amount.'];
        } elseif (!in_array($paymentMethod, $paymentMethods, true)) {
            $alert = ['type' => 'danger', 'message' => 'Please select a valid payment method.'];
        } elseif ($isVendorPaymentSubCategory && $vendorId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Vendor is required for Vendor Payment sub category.'];
        } else {
            if ($vendorId > 0) {
                $vendorCheck = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM inventory_vendors
                     WHERE vendor_id = :vendor_id
                       AND company_id = :company_id'
                );
                $vendorCheck->bindValue(':vendor_id', $vendorId, \PDO::PARAM_INT);
                $vendorCheck->bindValue(':company_id', $accountCompanyId, \PDO::PARAM_INT);
                $vendorCheck->execute();
                $vendorBelongsToCompany = (int) $vendorCheck->fetchColumn() > 0;

                if (!$vendorBelongsToCompany) {
                    $alert = ['type' => 'danger', 'message' => 'Selected vendor does not belong to the selected account company.'];
                }
            }

            if ($alert === null) {
              $createdByUserId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
              $createdByName = trim((string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? ''));
              if ($createdByName === '') {
                $createdByName = 'Unknown User';
              }

              if ($expenseId > 0) {
                $updateStmt = $pdo->prepare(
                  'UPDATE finance_expenses
                   SET account_id = :account_id,
                     company_id = :company_id,
                     category_id = :category_id,
                     sub_category_id = :sub_category_id,
                     vendor_id = :vendor_id,
                     expense_date = :expense_date,
                     amount = :amount,
                     payment_method = :payment_method,
                     note = :note,
                     reference_no = :reference_no,
                     update_count = update_count + 1
                   WHERE expense_id = :expense_id'
                );

                $updateStmt->bindValue(':account_id', $accountId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':company_id', $accountCompanyId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':sub_category_id', $subCategoryId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':vendor_id', $vendorId > 0 ? $vendorId : null, \PDO::PARAM_INT);
                $updateStmt->bindValue(':expense_date', $expenseDate);
                $updateStmt->bindValue(':amount', number_format((float) $amount, 2, '.', ''));
                $updateStmt->bindValue(':payment_method', $paymentMethod);
                $updateStmt->bindValue(':note', $expenseForm['note'] !== '' ? $expenseForm['note'] : null, \PDO::PARAM_STR);
                $updateStmt->bindValue(':reference_no', $expenseForm['reference_no'] !== '' ? $expenseForm['reference_no'] : null, \PDO::PARAM_STR);
                $updateStmt->bindValue(':expense_id', $expenseId, \PDO::PARAM_INT);
                $updateStmt->execute();
              } else {
                $insertStmt = $pdo->prepare(
                  'INSERT INTO finance_expenses (
                    trx_id,
                    account_id,
                    company_id,
                    category_id,
                    sub_category_id,
                    vendor_id,
                    expense_date,
                    amount,
                    payment_method,
                    note,
                    reference_no,
                    created_by_user_id,
                    created_by_name
                  ) VALUES (
                    :trx_id,
                    :account_id,
                    :company_id,
                    :category_id,
                    :sub_category_id,
                    :vendor_id,
                    :expense_date,
                    :amount,
                    :payment_method,
                    :note,
                    :reference_no,
                    :created_by_user_id,
                    :created_by_name
                  )'
                );

                $categoryNameForTrx = '';
                if ($categoryId > 0) {
                  $categoryNameStmt = $pdo->prepare('SELECT category_name FROM expense_categories WHERE category_id = :category_id LIMIT 1');
                  $categoryNameStmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
                  $categoryNameStmt->execute();
                  $categoryNameForTrx = (string) $categoryNameStmt->fetchColumn();
                }

                $trxSeed = random_int(100000, 999999);
                $generatedTrxId = ispts_generate_expense_trxid($categoryNameForTrx, $subCategoryName, $trxSeed);

                $insertStmt->bindValue(':trx_id', $generatedTrxId);
                $insertStmt->bindValue(':account_id', $accountId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':company_id', $accountCompanyId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':sub_category_id', $subCategoryId, \PDO::PARAM_INT);
                $insertStmt->bindValue(':vendor_id', $vendorId > 0 ? $vendorId : null, \PDO::PARAM_INT);
                $insertStmt->bindValue(':expense_date', $expenseDate);
                $insertStmt->bindValue(':amount', number_format((float) $amount, 2, '.', ''));
                $insertStmt->bindValue(':payment_method', $paymentMethod);
                $insertStmt->bindValue(':note', $expenseForm['note'] !== '' ? $expenseForm['note'] : null, \PDO::PARAM_STR);
                $insertStmt->bindValue(':reference_no', $expenseForm['reference_no'] !== '' ? $expenseForm['reference_no'] : null, \PDO::PARAM_STR);
                $insertStmt->bindValue(':created_by_user_id', $createdByUserId > 0 ? $createdByUserId : null, \PDO::PARAM_INT);
                $insertStmt->bindValue(':created_by_name', $createdByName, \PDO::PARAM_STR);
                $insertStmt->execute();
              }

                header('Location: ' . $currentPath . '?saved=1');
                exit;
            }
        }
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Expense saved successfully.'];
}
if ($alert === null && isset($_GET['vendor_saved'])) {
    $alert = ['type' => 'success', 'message' => 'Vendor added successfully. You can now select it from the vendor dropdown.'];
}

$editExpenseId = isset($_GET['edit_expense']) ? (int) $_GET['edit_expense'] : 0;
if ($editExpenseId > 0) {
  $editStmt = $pdo->prepare(
    'SELECT expense_id,
        account_id,
        category_id,
        sub_category_id,
        vendor_id,
        expense_date,
        amount,
        payment_method,
        note,
        reference_no,
        status
     FROM finance_expenses
     WHERE expense_id = :expense_id
     LIMIT 1'
  );
  $editStmt->bindValue(':expense_id', $editExpenseId, \PDO::PARAM_INT);
  $editStmt->execute();
  $editRow = $editStmt->fetch(\PDO::FETCH_ASSOC);

  if ($editRow) {
    $expenseForm = [
      'expense_id' => (int) $editRow['expense_id'],
      'account_id' => (int) $editRow['account_id'],
      'category_id' => (int) $editRow['category_id'],
      'sub_category_id' => (int) $editRow['sub_category_id'],
      'vendor_id' => (int) ($editRow['vendor_id'] ?? 0),
      'expense_date' => (string) $editRow['expense_date'],
      'amount' => (string) $editRow['amount'],
      'payment_method' => (string) $editRow['payment_method'],
      'note' => (string) ($editRow['note'] ?? ''),
      'reference_no' => (string) ($editRow['reference_no'] ?? ''),
    ];
  }
}

$accounts = $pdo->query(
    "SELECT fa.account_id, fa.company_id, fa.account_name, c.company
     FROM finance_accounts fa
     LEFT JOIN companies c ON c.id = fa.company_id
     WHERE fa.status = 1
     ORDER BY c.company ASC, fa.account_name ASC"
)->fetchAll(\PDO::FETCH_ASSOC);

// Default account for new expenses: FO Main Account
if ($editExpenseId <= 0 && $expenseForm['account_id'] <= 0) {
  foreach ($accounts as $acc) {
    $companyName = (string)($acc['company'] ?? '');
    $accName = (string)($acc['account_name'] ?? '');
    if ((stripos($companyName, 'Friendsonline') !== false || stripos($companyName, 'FO') !== false) 
        && stripos($accName, 'Main') !== false) {
      $expenseForm['account_id'] = (int)$acc['account_id'];
      break;
    }
  }
}

$categories = $pdo->query(
    'SELECT category_id, category_name
     FROM expense_categories
     WHERE status = 1
     ORDER BY sort_order ASC, category_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$subCategories = $pdo->query(
    'SELECT sub_category_id, category_id, sub_category_name
     FROM expense_sub_categories
     WHERE status = 1
     ORDER BY sort_order ASC, sub_category_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$vendors = $pdo->query(
    'SELECT vendor_id, company_id, vendor_name
     FROM inventory_vendors
     WHERE status = 1
     ORDER BY vendor_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$perPageOptions = [10, 20, 50];
$perPageRaw = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$viewAll = (isset($_GET['view_all']) && $_GET['view_all'] === '1');
$filterDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$filterDateTo   = trim((string) ($_GET['date_to']   ?? ''));

if (!$viewAll && $filterDateFrom === '' && $filterDateTo === '') {
  $filterDateFrom = date('Y-m-d');
  $filterDateTo = date('Y-m-d');
}

$filterDateFrom = (strlen($filterDateFrom) === 10 && strtotime($filterDateFrom) !== false) ? $filterDateFrom : '';
$filterDateTo   = (strlen($filterDateTo)   === 10 && strtotime($filterDateTo)   !== false) ? $filterDateTo   : '';

$dateWhere      = '';
$countDateWhere = '';
if (!$viewAll) {
  if ($filterDateFrom !== '' && $filterDateTo !== '') {
    $dateWhere      = ' AND fe.expense_date BETWEEN :date_from AND :date_to';
    $countDateWhere = ' AND expense_date BETWEEN :date_from AND :date_to';
  } elseif ($filterDateFrom !== '') {
    $dateWhere      = ' AND fe.expense_date >= :date_from';
    $countDateWhere = ' AND expense_date >= :date_from';
  } elseif ($filterDateTo !== '') {
    $dateWhere      = ' AND fe.expense_date <= :date_to';
    $countDateWhere = ' AND expense_date <= :date_to';
  }
}

$expCountStmt = $pdo->prepare('SELECT COUNT(*) FROM finance_expenses WHERE 1=1' . $countDateWhere);
if (!$viewAll && $filterDateFrom !== '') { $expCountStmt->bindValue(':date_from', $filterDateFrom); }
if (!$viewAll && $filterDateTo   !== '') { $expCountStmt->bindValue(':date_to',   $filterDateTo); }
$expCountStmt->execute();
$totalExpenseCount = (int) $expCountStmt->fetchColumn();

$expStatsRow = $pdo->query(
  "SELECT
     COUNT(*) AS total_count,
     SUM(amount) AS total_amount,
     SUM(CASE WHEN expense_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN 1 ELSE 0 END) AS month_count,
     SUM(CASE WHEN expense_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) AS month_amount,
     SUM(CASE WHEN expense_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS yesterday_count,
     SUM(CASE WHEN expense_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) THEN amount ELSE 0 END) AS yesterday_amount,
     SUM(CASE WHEN expense_date = CURRENT_DATE THEN 1 ELSE 0 END) AS today_count,
     SUM(CASE WHEN expense_date = CURRENT_DATE THEN amount ELSE 0 END) AS today_amount,
     SUM(CASE WHEN expense_date = CURRENT_DATE AND payment_method = 'Cash'  THEN amount ELSE 0 END) AS today_cash,
     SUM(CASE WHEN expense_date = CURRENT_DATE AND payment_method = 'Bkash' THEN amount ELSE 0 END) AS today_bkash,
     SUM(CASE WHEN expense_date = CURRENT_DATE AND payment_method = 'Nagad' THEN amount ELSE 0 END) AS today_nagad,
     SUM(CASE WHEN expense_date = CURRENT_DATE AND payment_method = 'Bank'  THEN amount ELSE 0 END) AS today_bank,
     SUM(CASE WHEN expense_date = CURRENT_DATE AND payment_method NOT IN ('Cash','Bkash','Nagad','Bank') THEN amount ELSE 0 END) AS today_other,
     SUM(CASE WHEN payment_method = 'Cash'  THEN amount ELSE 0 END) AS total_cash,
     SUM(CASE WHEN payment_method = 'Bkash' THEN amount ELSE 0 END) AS total_bkash,
     SUM(CASE WHEN payment_method = 'Nagad' THEN amount ELSE 0 END) AS total_nagad,
     SUM(CASE WHEN payment_method = 'Bank'  THEN amount ELSE 0 END) AS total_bank,
     SUM(CASE WHEN payment_method NOT IN ('Cash','Bkash','Nagad','Bank') THEN amount ELSE 0 END) AS total_other
   FROM finance_expenses"
)->fetch(\PDO::FETCH_ASSOC);

$statExpTotalCount     = (int)   ($expStatsRow['total_count']       ?? 0);
$statExpTotalAmount    = (float) ($expStatsRow['total_amount']      ?? 0);
$statExpMonthCount     = (int)   ($expStatsRow['month_count']       ?? 0);
$statExpMonthAmount    = (float) ($expStatsRow['month_amount']      ?? 0);
$statExpYesterdayCount = (int)   ($expStatsRow['yesterday_count']   ?? 0);
$statExpYesterdayAmount= (float) ($expStatsRow['yesterday_amount']  ?? 0);
$statExpTodayCount     = (int)   ($expStatsRow['today_count']       ?? 0);
$statExpTodayAmount    = (float) ($expStatsRow['today_amount']      ?? 0);
$statExpTodayCash      = (float) ($expStatsRow['today_cash']        ?? 0);
$statExpTodayBkash     = (float) ($expStatsRow['today_bkash']       ?? 0);
$statExpTodayNagad     = (float) ($expStatsRow['today_nagad']       ?? 0);
$statExpTodayBank      = (float) ($expStatsRow['today_bank']        ?? 0);
$statExpTodayOther     = (float) ($expStatsRow['today_other']       ?? 0);
$statExpCash           = (float) ($expStatsRow['total_cash']        ?? 0);
$statExpBkash          = (float) ($expStatsRow['total_bkash']       ?? 0);
$statExpNagad          = (float) ($expStatsRow['total_nagad']       ?? 0);
$statExpBank           = (float) ($expStatsRow['total_bank']        ?? 0);
$statExpOther          = (float) ($expStatsRow['total_other']       ?? 0);

$totalPages = $totalExpenseCount > 0 ? (int) ceil($totalExpenseCount / $perPage) : 1;
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$expListStmt = $pdo->prepare(
    'SELECT fe.expense_id,
      fe.trx_id,
            fe.expense_date,
            fe.amount,
            fe.payment_method,
            fe.reference_no,
            fe.created_by_name,
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
     WHERE 1=1' . $dateWhere . '
     ORDER BY fe.expense_date DESC, fe.expense_id DESC
     LIMIT :limit OFFSET :offset'
);
$expListStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$expListStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!$viewAll && $filterDateFrom !== '') { $expListStmt->bindValue(':date_from', $filterDateFrom); }
if (!$viewAll && $filterDateTo   !== '') { $expListStmt->bindValue(':date_to',   $filterDateTo); }
$expListStmt->execute();
$expenses = $expListStmt->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Expense Management';
require '../../includes/header.php';
?>
<link rel="stylesheet" href="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.css">
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Expense Management</li>
  </ol>
</nav>

<div class="mb-1 mt-1">
  <h5 class="mb-0 text-primary position-relative">
    <span class="bg-200 dark__bg-1100 pe-3"><?= htmlspecialchars($pageTitle) ?></span>
    <span class="border position-absolute top-50 translate-middle-y w-100 start-0 z-n1"></span>
  </h5>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars((string) $alert['type']) ?> py-2 mb-3" role="alert">
    <?= htmlspecialchars((string) $alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <!-- Total Expenses -->
  <div class="col-sm-12 col-md-2">
    <div class="card h-100" data-no-auto-view="true">
      <div class="bg-holder bg-card"
        style="background-image:url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-1.png);"></div>
      <div class="card-body position-relative">
        <h6>Total Expenses</h6>
        <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-warning"><?= number_format($statExpTotalCount) ?></div>
        <div class="d-flex flex-wrap gap-2">
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-info"></div>
            <h6 class="lh-base text-700 mb-0">Yesterday: <?= number_format($statExpYesterdayCount) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-success"></div>
            <h6 class="lh-base text-700 mb-0">Today: <?= number_format($statExpTodayCount) ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Expense Amount -->
  <div class="col-sm-12 col-md-5">
    <div class="card h-100" data-no-auto-view="true">
      <div class="bg-holder bg-card"
        style="background-image:url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-2.png);"></div>
      <div class="card-body position-relative">
        <h6>Total Expense Amount</h6>
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 mb-3">
          <div class="display-4 fs-5 mb-0 fw-normal font-sans-serif text-warning" style="margin-right: 20px;">
            <?= number_format($statExpTotalAmount, 2) ?>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center">
              <div class="vr rounded ps-1 bg-warning"></div>
              <h6 class="lh-base text-700 mb-0">This Month: <?= number_format($statExpMonthAmount, 2) ?></h6>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <div class="vr rounded ps-1 bg-warning"></div>
              <h6 class="lh-base text-700 mb-0">Yesterday: <?= number_format($statExpYesterdayAmount, 2) ?></h6>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <div class="vr rounded ps-1 bg-warning"></div>
              <h6 class="lh-base text-700 mb-0">Today: <?= number_format($statExpTodayAmount, 2) ?></h6>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-3">
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-success"></div>
            <h6 class="lh-base text-700 mb-0">Cash: <?= number_format($statExpCash, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-info"></div>
            <h6 class="lh-base text-700 mb-0">Bkash: <?= number_format($statExpBkash, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-warning"></div>
            <h6 class="lh-base text-700 mb-0">Nagad: <?= number_format($statExpNagad, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-primary"></div>
            <h6 class="lh-base text-700 mb-0">Bank: <?= number_format($statExpBank, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-secondary"></div>
            <h6 class="lh-base text-700 mb-0">Others: <?= number_format($statExpOther, 2) ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Today's Expenses -->
  <div class="col-sm-12 col-md-5">
    <div class="card h-100" data-no-auto-view="true">
      <div class="bg-holder bg-card"
        style="background-image:url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-3.png);"></div>
      <div class="card-body position-relative">
        <h6>Today's Expenses</h6>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
          <div class="display-4 fs-5 mb-0 fw-normal font-sans-serif text-success">
            <?= number_format($statExpTodayAmount, 2) ?>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-3">
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-success"></div>
            <h6 class="lh-base text-700 mb-0">Cash: <?= number_format($statExpTodayCash, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-info"></div>
            <h6 class="lh-base text-700 mb-0">Bkash: <?= number_format($statExpTodayBkash, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-warning"></div>
            <h6 class="lh-base text-700 mb-0">Nagad: <?= number_format($statExpTodayNagad, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-primary"></div>
            <h6 class="lh-base text-700 mb-0">Bank: <?= number_format($statExpTodayBank, 2) ?></h6>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="vr rounded ps-1 bg-secondary"></div>
            <h6 class="lh-base text-700 mb-0">Others: <?= number_format($statExpTodayOther, 2) ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row gx-3 gy-3">

  <div class="col-12 col-md-8 col-xxl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0">Expense List <span class="text-600 fw-normal fs-11">(<?= $totalExpenseCount ?> total)</span></h6>
        <form method="get" action="<?= $appBasePath ?>/app/finance/expense-management.php" class="d-flex align-items-center flex-wrap gap-2" id="expense-filter-form">
          <div class="form-check form-check-inline mb-0 ms-2">
            <input class="form-check-input" type="radio" name="view_all" id="viewAllFalse" value="0" <?= !$viewAll ? 'checked' : '' ?> onchange="this.form.submit()">
            <label class="form-check-label fs-11" for="viewAllFalse">Filter</label>
          </div>
          <div class="form-check form-check-inline mb-0 me-2">
            <input class="form-check-input" type="radio" name="view_all" id="viewAllTrue" value="1" <?= $viewAll ? 'checked' : '' ?> onchange="this.form.submit()">
            <label class="form-check-label fs-11" for="viewAllTrue">All</label>
          </div>
          <input type="text" class="form-control form-control-sm datetimepicker" id="expense-date-range" name="_date_range" placeholder="Date range" style="width:200px;"
            data-options='{"mode":"range","dateFormat":"Y-m-d","disableMobile":true,"position":"below"}'
            value="<?= $filterDateFrom !== '' ? htmlspecialchars($filterDateFrom . ($filterDateTo !== '' ? ' to ' . $filterDateTo : '')) : '' ?>"
            <?= $viewAll ? 'disabled' : '' ?>>
          <input type="hidden" name="date_from" id="expense-date-from" value="<?= htmlspecialchars($filterDateFrom) ?>">
          <input type="hidden" name="date_to"   id="expense-date-to"   value="<?= htmlspecialchars($filterDateTo) ?>">
          <?php if ($filterDateFrom !== '' || $filterDateTo !== ''): ?>
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/expense-management.php?per_page=<?= $perPage ?>">Clear</a>
          <?php endif; ?>
          <label class="form-label fs-11 mb-0 text-600 ms-1">Show</label>
          <select class="form-select form-select-sm" name="per_page" onchange="this.form.submit()" style="width:70px;">
            <?php foreach ($perPageOptions as $opt): ?>
              <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </form>
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
                <th class="text-800">Created By</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($expenses)): ?>
                <tr><td colspan="12" class="text-center py-3 text-600">No expense records found.</td></tr>
              <?php else: ?>
                <?php foreach ($expenses as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/expense-management.php?edit_expense=<?= (int) $row['expense_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><span class="fas fa-edit text-500"></span></a>
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
                    <td><?= htmlspecialchars((string) ($row['created_by_name'] ?? '-')) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php if ($totalPages > 1): ?>
      <div class="card-footer d-flex align-items-center justify-content-between">
        <small class="text-600">Page <?= $currentPage ?> of <?= $totalPages ?></small>
        <ul class="pagination pagination-sm mb-0">
          <?php if ($currentPage > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?>&per_page=<?= $perPage ?><?= $filterDateFrom !== '' ? '&date_from=' . urlencode($filterDateFrom) : '' ?><?= $filterDateTo !== '' ? '&date_to=' . urlencode($filterDateTo) : '' ?>">&#8249;</a></li>
          <?php endif; ?>
          <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>&per_page=<?= $perPage ?><?= $filterDateFrom !== '' ? '&date_from=' . urlencode($filterDateFrom) : '' ?><?= $filterDateTo !== '' ? '&date_to=' . urlencode($filterDateTo) : '' ?>"><?= $p ?></a></li>
          <?php endfor; ?>
          <?php if ($currentPage < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?>&per_page=<?= $perPage ?><?= $filterDateFrom !== '' ? '&date_from=' . urlencode($filterDateFrom) : '' ?><?= $filterDateTo !== '' ? '&date_to=' . urlencode($filterDateTo) : '' ?>">&#8250;</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 col-md-4 col-xxl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= (int) $expenseForm['expense_id'] > 0 ? 'Update Expense' : 'Add Expense' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/expense-management.php" id="expense-form">
          <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_expense">
          <input type="hidden" name="expense_id" value="<?= (int) $expenseForm['expense_id'] ?>">

          <div class="col-md-6">
           
            <select class="form-select" id="expense-account" name="account_id" required>
              <option value="" disabled <?= (int) $expenseForm['account_id'] <= 0 ? 'selected' : '' ?>>Select account</option>
              <?php foreach ($accounts as $account): ?>
                <option value="<?= (int) $account['account_id'] ?>" data-company-id="<?= (int) $account['company_id'] ?>" <?= (int) $expenseForm['account_id'] === (int) $account['account_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) ($account['company'] ?? 'Unknown Company')) ?> - <?= htmlspecialchars((string) $account['account_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
           
            <input class="form-control" id="expense-date" name="expense_date" type="date" value="<?= htmlspecialchars((string) $expenseForm['expense_date']) ?>" required>
          </div>


          <div class="col-md-6">
            
            <select class="form-select" id="expense-category" name="category_id" required>
              <option value="" disabled <?= (int) $expenseForm['category_id'] <= 0 ? 'selected' : '' ?>>Select category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>" <?= (int) $expenseForm['category_id'] === (int) $category['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $category['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            
            <select class="form-select" id="expense-sub-category" name="sub_category_id" required>
              <option value="" disabled <?= (int) $expenseForm['sub_category_id'] <= 0 ? 'selected' : '' ?>>Select sub category</option>
              <?php foreach ($subCategories as $subCategory): ?>
                <?php $isVendorPaymentOption = stripos((string) $subCategory['sub_category_name'], 'vendor payment') !== false; ?>
                <option
                  value="<?= (int) $subCategory['sub_category_id'] ?>"
                  data-category-id="<?= (int) $subCategory['category_id'] ?>"
                  data-vendor-payment="<?= $isVendorPaymentOption ? '1' : '0' ?>"
                  <?= (int) $expenseForm['sub_category_id'] === (int) $subCategory['sub_category_id'] ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars((string) $subCategory['sub_category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 d-none" id="vendor-field-wrap">
            <label class="form-label" for="expense-vendor">Select vendor</label>
            <select class="form-select" id="expense-vendor" name="vendor_id">
              <option value="" disabled <?= (int) $expenseForm['vendor_id'] <= 0 ? 'selected' : '' ?>>Select vendor</option>
              <?php foreach ($vendors as $vendor): ?>
                <option value="<?= (int) $vendor['vendor_id'] ?>" data-company-id="<?= (int) $vendor['company_id'] ?>" <?= (int) $expenseForm['vendor_id'] === (int) $vendor['vendor_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $vendor['vendor_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="mt-2">
              <button class="btn btn-falcon-default btn-sm" type="button" id="toggle-vendor-quick-add">Add New Vendor</button>
            </div>
          </div>

          
          <div class="col-12">
            <input class="form-control" id="expense-amount" name="amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $expenseForm['amount']) ?>" placeholder="0.00" required>
          </div>

          <div class="col-12">
            <select class="form-select" id="expense-payment-method" name="payment_method" required>
              <option value="" disabled <?= $expenseForm['payment_method'] === '' ? 'selected' : '' ?>>Select payment method</option>
              <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= htmlspecialchars($method) ?>" <?= (string) $expenseForm['payment_method'] === $method ? 'selected' : '' ?>>
                  <?= htmlspecialchars($method) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            
            <input class="form-control" id="expense-reference" name="reference_no" type="text" value="<?= htmlspecialchars((string) $expenseForm['reference_no']) ?>" placeholder="Reference/Employee">
          </div>

          <div class="col-md-6">
          
            <textarea class="form-control" id="expense-note" name="note" rows="1" placeholder="Notes"><?= htmlspecialchars((string) $expenseForm['note']) ?></textarea>
          </div>

          

          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/expense-management.php">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit"><?= (int) $expenseForm['expense_id'] > 0 ? 'Update Expense' : 'Save Expense' ?></button>
          </div>
        </form>

        <form method="post" action="<?= $appBasePath ?>/app/finance/expense-management.php" class="row g-2 mt-2 d-none" id="vendor-quick-add-wrap">
          <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_vendor_inline">
          <input type="hidden" name="vendor_company_id" id="vendor-company-id" value="0">

          <div class="col-12">
            <div class="border rounded p-2 bg-body-tertiary">
              <h6 class="mb-2 fs-10">Quick Add Vendor</h6>

              <div class="mb-2">
                <label class="form-label mb-1" for="vendor-name">Vendor Name</label>
                <input class="form-control form-control-sm" id="vendor-name" name="vendor_name" type="text">
              </div>
              <div class="mb-2">
                <label class="form-label mb-1" for="vendor-contact-person">Contact Person</label>
                <input class="form-control form-control-sm" id="vendor-contact-person" name="vendor_contact_person" type="text">
              </div>
              <div class="mb-2">
                <label class="form-label mb-1" for="vendor-phone">Phone</label>
                <input class="form-control form-control-sm" id="vendor-phone" name="vendor_phone" type="text">
              </div>
              <div class="mb-2">
                <label class="form-label mb-1" for="vendor-email">Email</label>
                <input class="form-control form-control-sm" id="vendor-email" name="vendor_email" type="email">
              </div>
              <div class="mb-2">
                <label class="form-label mb-1" for="vendor-address">Address</label>
                <textarea class="form-control form-control-sm" id="vendor-address" name="vendor_address" rows="2"></textarea>
              </div>
              <div class="d-flex justify-content-end">
                <button class="btn btn-primary btn-sm" type="submit">Save Vendor</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var accountSelect = document.getElementById('expense-account');
  var categorySelect = document.getElementById('expense-category');
  var subCategorySelect = document.getElementById('expense-sub-category');
  var vendorWrap = document.getElementById('vendor-field-wrap');
  var vendorSelect = document.getElementById('expense-vendor');
  var vendorQuickAddWrap = document.getElementById('vendor-quick-add-wrap');
  var toggleVendorQuickAddBtn = document.getElementById('toggle-vendor-quick-add');
  var vendorCompanyIdInput = document.getElementById('vendor-company-id');

  if (!accountSelect || !categorySelect || !subCategorySelect || !vendorWrap || !vendorSelect) {
    return;
  }

  var allSubCategoryOptions = Array.prototype.slice.call(subCategorySelect.querySelectorAll('option[value]'));
  var allVendorOptions = Array.prototype.slice.call(vendorSelect.querySelectorAll('option[value]'));

  var setVendorCompanyId = function () {
    var selectedAccount = accountSelect.options[accountSelect.selectedIndex];
    var companyId = selectedAccount ? (selectedAccount.getAttribute('data-company-id') || '0') : '0';
    if (vendorCompanyIdInput) {
      vendorCompanyIdInput.value = companyId;
    }
    return companyId;
  };

  var filterSubCategories = function () {
    var selectedCategoryId = categorySelect.value;
    var currentSelectedSubCategory = subCategorySelect.value;

    allSubCategoryOptions.forEach(function (option) {
      var optionCategoryId = option.getAttribute('data-category-id');
      var shouldShow = selectedCategoryId !== '' && optionCategoryId === selectedCategoryId;
      option.hidden = !shouldShow;
      if (!shouldShow && option.selected) {
        option.selected = false;
      }
    });

    if (currentSelectedSubCategory !== subCategorySelect.value) {
      subCategorySelect.value = '';
    }

    toggleVendorField();
  };

  var filterVendorsByAccountCompany = function () {
    var selectedCompanyId = setVendorCompanyId();

    allVendorOptions.forEach(function (option) {
      var optionCompanyId = option.getAttribute('data-company-id');
      var shouldShow = selectedCompanyId !== '0' && optionCompanyId === selectedCompanyId;
      option.hidden = !shouldShow;
      if (!shouldShow && option.selected) {
        option.selected = false;
      }
    });
  };

  var toggleVendorField = function () {
    var selectedSubCategory = subCategorySelect.options[subCategorySelect.selectedIndex];
    var isVendorPayment = selectedSubCategory && selectedSubCategory.getAttribute('data-vendor-payment') === '1';

    if (isVendorPayment) {
      vendorWrap.classList.remove('d-none');
      vendorSelect.setAttribute('required', 'required');
      filterVendorsByAccountCompany();
    } else {
      vendorWrap.classList.add('d-none');
      vendorSelect.removeAttribute('required');
      vendorSelect.value = '';
      if (vendorQuickAddWrap) {
        vendorQuickAddWrap.classList.add('d-none');
      }
    }
  };

  accountSelect.addEventListener('change', function () {
    filterVendorsByAccountCompany();
  });

  categorySelect.addEventListener('change', filterSubCategories);
  subCategorySelect.addEventListener('change', toggleVendorField);

  if (toggleVendorQuickAddBtn && vendorQuickAddWrap) {
    toggleVendorQuickAddBtn.addEventListener('click', function () {
      setVendorCompanyId();
      vendorQuickAddWrap.classList.toggle('d-none');
    });
  }

  setVendorCompanyId();
  filterSubCategories();
  filterVendorsByAccountCompany();
  toggleVendorField();
})();
</script>

<script src="<?= $appBasePath ?>/vendors/flatpickr/flatpickr.min.js"></script>
<script>
(function () {
  var rangeInput = document.getElementById('expense-date-range');
  var hiddenFrom = document.getElementById('expense-date-from');
  var hiddenTo   = document.getElementById('expense-date-to');
  var filterForm = document.getElementById('expense-filter-form');

  if (!rangeInput || !hiddenFrom || !hiddenTo || typeof flatpickr === 'undefined') { return; }

  flatpickr(rangeInput, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    disableMobile: true,
    defaultDate: [hiddenFrom.value || null, hiddenTo.value || null].filter(Boolean),
    onChange: function (selectedDates) {
      if (selectedDates.length === 2) {
        var fmt = function (d) {
          return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
        };
        hiddenFrom.value = fmt(selectedDates[0]);
        hiddenTo.value   = fmt(selectedDates[1]);
        filterForm.submit();
      } else if (selectedDates.length === 0) {
        hiddenFrom.value = '';
        hiddenTo.value   = '';
      }
    },
  });
})();
</script>
<?php
require '../../includes/footer.php';
