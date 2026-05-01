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
                     update_count = update_count + 1,
                     status = :status
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
                $updateStmt->bindValue(':status', (int) $expenseForm['status'], \PDO::PARAM_INT);
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
                    created_by_name,
                    status
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
                    :created_by_name,
                    :status
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
                $insertStmt->bindValue(':status', (int) $expenseForm['status'], \PDO::PARAM_INT);
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
      'status' => (int) $editRow['status'],
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

$expenses = $pdo->query(
    'SELECT fe.expense_id,
      fe.trx_id,
            fe.expense_date,
            fe.amount,
            fe.payment_method,
            fe.reference_no,
            fe.status,
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
     ORDER BY fe.expense_date DESC, fe.expense_id DESC'
)->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Expense Management';
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Expense Management</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Expense Management</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Add expense entries and track who created each transaction.</p>
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
      <div class="card-header border-bottom border-200"><h6 class="mb-0">Expense List</h6></div>
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
                <th class="text-800">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($expenses)): ?>
                <tr><td colspan="13" class="text-center py-3 text-600">No expenses found.</td></tr>
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
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= (int) $expenseForm['expense_id'] > 0 ? 'Update Expense' : 'Add Expense' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/expense-management.php" id="expense-form">
          <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_expense">
          <input type="hidden" name="expense_id" value="<?= (int) $expenseForm['expense_id'] ?>">

          <div class="col-12">
            <label class="form-label" for="expense-account">Select account</label>
            <select class="form-select" id="expense-account" name="account_id" required>
              <option value="" disabled <?= (int) $expenseForm['account_id'] <= 0 ? 'selected' : '' ?>>Select account</option>
              <?php foreach ($accounts as $account): ?>
                <option value="<?= (int) $account['account_id'] ?>" data-company-id="<?= (int) $account['company_id'] ?>" <?= (int) $expenseForm['account_id'] === (int) $account['account_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) ($account['company'] ?? 'Unknown Company')) ?> - <?= htmlspecialchars((string) $account['account_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-category">Select expense category</label>
            <select class="form-select" id="expense-category" name="category_id" required>
              <option value="" disabled <?= (int) $expenseForm['category_id'] <= 0 ? 'selected' : '' ?>>Select category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>" <?= (int) $expenseForm['category_id'] === (int) $category['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $category['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-sub-category">Select expense sub category</label>
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
            <label class="form-label" for="expense-date">Date</label>
            <input class="form-control" id="expense-date" name="expense_date" type="date" value="<?= htmlspecialchars((string) $expenseForm['expense_date']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-amount">Amount</label>
            <input class="form-control" id="expense-amount" name="amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $expenseForm['amount']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-payment-method">Payment Method</label>
            <select class="form-select" id="expense-payment-method" name="payment_method" required>
              <option value="" disabled>Select payment method</option>
              <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= htmlspecialchars($method) ?>" <?= (string) $expenseForm['payment_method'] === $method ? 'selected' : '' ?>>
                  <?= htmlspecialchars($method) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-note">Note</label>
            <textarea class="form-control" id="expense-note" name="note" rows="2"><?= htmlspecialchars((string) $expenseForm['note']) ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label" for="expense-reference">Reference</label>
            <input class="form-control" id="expense-reference" name="reference_no" type="text" value="<?= htmlspecialchars((string) $expenseForm['reference_no']) ?>">
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label mb-0" for="expense-status">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" id="expense-status" type="checkbox" name="status" value="1" <?= (int) $expenseForm['status'] === 1 ? 'checked' : '' ?>>
              </div>
            </div>
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

<?php
require '../../includes/footer.php';
