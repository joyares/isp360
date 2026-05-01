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

if (!function_exists('ispts_income_trx_part')) {
  function ispts_income_trx_part(string $label): string
  {
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($label)) ?? '');
    if ($clean === '') {
      $clean = 'INC';
    }

    return str_pad(substr($clean, 0, 3), 3, 'X');
  }
}

if (!function_exists('ispts_generate_income_trxid')) {
  function ispts_generate_income_trxid(string $customerLabel, int $seed): string
  {
    $customerPart = ispts_income_trx_part($customerLabel);
    $generationDate = date('YmdHis');
    $seedPart = str_pad((string) max(1, $seed), 6, '0', STR_PAD_LEFT);

    return 'INC-' . $customerPart . '-' . $generationDate . '-' . $seedPart;
  }
}

$pdo->exec(
  "CREATE TABLE IF NOT EXISTS finance_incomes (
      income_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      trx_id VARCHAR(80) NOT NULL,
      account_id BIGINT UNSIGNED NOT NULL,
      company_id INT UNSIGNED NOT NULL,
      customer_id BIGINT UNSIGNED NOT NULL,
      income_date DATE NOT NULL,
      payment_method VARCHAR(20) NOT NULL,
      subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      note TEXT NULL,
      reference_no VARCHAR(120) NULL,
      payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
      paid_at DATETIME NULL,
      created_by_user_id BIGINT UNSIGNED NULL,
      created_by_name VARCHAR(180) NULL,
      update_count INT UNSIGNED NOT NULL DEFAULT 0,
      status TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uk_finance_incomes_trx_id (trx_id),
      KEY idx_finance_incomes_account (account_id),
      KEY idx_finance_incomes_company (company_id),
      KEY idx_finance_incomes_customer (customer_id),
      KEY idx_finance_incomes_date (income_date),
      KEY idx_finance_incomes_status (status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
  "CREATE TABLE IF NOT EXISTS finance_income_items (
      income_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      income_id BIGINT UNSIGNED NOT NULL,
      item_type VARCHAR(30) NOT NULL,
      product_id BIGINT UNSIGNED NULL,
      serial_id BIGINT UNSIGNED NULL,
      description VARCHAR(255) NULL,
      amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      status TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY idx_finance_income_items_income (income_id),
      KEY idx_finance_income_items_product (product_id),
      KEY idx_finance_income_items_serial (serial_id),
      KEY idx_finance_income_items_status (status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$paymentMethods = ['Cash', 'Bkash', 'Nagad', 'Bank', 'Other'];
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/finance/income-management.php';
$alert = null;

$form = [
  'income_id' => 0,
  'account_id' => 0,
  'customer_id' => 0,
  'income_date' => date('Y-m-d'),
  'payment_method' => 'Cash',
  'discount_amount' => '0',
  'reference_no' => '',
  'note' => '',
  'get_payment' => 0,
  'status' => 1,
];
$formItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ispts_csrf_validate();
  $action = (string) ($_POST['action'] ?? '');

  if ($action === 'off_income') {
    $incomeId = isset($_POST['income_id']) ? (int) $_POST['income_id'] : 0;
    if ($incomeId > 0) {
      $stmt = $pdo->prepare('UPDATE finance_incomes SET status = 0 WHERE income_id = :income_id');
      $stmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
      $stmt->execute();
      header('Location: ' . $currentPath . '?turned_off=1');
      exit;
    }
  }

  if ($action === 'mark_income_paid') {
    $incomeId = isset($_POST['income_id']) ? (int) $_POST['income_id'] : 0;
    if ($incomeId > 0) {
      $stmt = $pdo->prepare(
        "UPDATE finance_incomes
         SET payment_status = 'paid',
             paid_at = NOW()
         WHERE income_id = :income_id"
      );
      $stmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
      $stmt->execute();
      header('Location: ' . $currentPath . '?paid=1');
      exit;
    }
  }

  if ($action === 'save_income') {
    $form = [
      'income_id' => isset($_POST['income_id']) ? (int) $_POST['income_id'] : 0,
      'account_id' => isset($_POST['account_id']) ? (int) $_POST['account_id'] : 0,
      'customer_id' => isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0,
      'income_date' => (string) ($_POST['income_date'] ?? date('Y-m-d')),
      'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
      'discount_amount' => trim((string) ($_POST['discount_amount'] ?? '0')),
      'reference_no' => trim((string) ($_POST['reference_no'] ?? '')),
      'note' => trim((string) ($_POST['note'] ?? '')),
      'get_payment' => isset($_POST['get_payment']) ? 1 : 0,
      'status' => isset($_POST['status']) ? 1 : 0,
    ];

    $incomeId = (int) $form['income_id'];
    $accountId = (int) $form['account_id'];
    $customerId = (int) $form['customer_id'];
    $incomeDate = (string) $form['income_date'];
    $getPayment = (int) $form['get_payment'] === 1;
    $paymentMethod = $getPayment ? (string) $form['payment_method'] : '';
    $discountAmount = is_numeric($form['discount_amount']) ? (float) $form['discount_amount'] : -1;

    $accountCompanyId = 0;
    if ($accountId > 0) {
      $accountStmt = $pdo->prepare('SELECT company_id FROM finance_accounts WHERE account_id = :account_id LIMIT 1');
      $accountStmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
      $accountStmt->execute();
      $accountCompanyId = (int) $accountStmt->fetchColumn();
    }

    $customerNameForTrx = '';
    if ($customerId > 0) {
      $customerStmt = $pdo->prepare('SELECT COALESCE(NULLIF(full_name, ""), username) AS customer_name FROM customers WHERE customer_id = :customer_id LIMIT 1');
      $customerStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
      $customerStmt->execute();
      $customerNameForTrx = (string) $customerStmt->fetchColumn();
    }

    $rawItems = $_POST['items'] ?? [];
    $normalizedItems = [];
    $subtotal = 0.0;

    if (is_array($rawItems)) {
      foreach ($rawItems as $row) {
        if (!is_array($row)) {
          continue;
        }

        $itemType = trim((string) ($row['item_type'] ?? ''));
        $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
        $serialId = isset($row['serial_id']) ? (int) $row['serial_id'] : 0;
        $description = trim((string) ($row['description'] ?? ''));
        $amountRaw = trim((string) ($row['amount'] ?? '0'));
        $amount = is_numeric($amountRaw) ? (float) $amountRaw : -1;

        if (!in_array($itemType, ['monthly_bill', 'product', 'deposit', 'other_charge'], true)) {
          continue;
        }
        if ($amount <= 0) {
          continue;
        }

        if ($itemType === 'product' && $productId <= 0) {
          continue;
        }
        if ($itemType === 'other_charge' && $description === '') {
          continue;
        }

        if ($itemType === 'monthly_bill' && $description === '') {
          $description = 'Monthly bill due collection';
        }

        $normalizedItems[] = [
          'item_type' => $itemType,
          'product_id' => $productId > 0 ? $productId : null,
          'serial_id' => $serialId > 0 ? $serialId : null,
          'description' => $description !== '' ? $description : null,
          'amount' => $amount,
        ];
        $subtotal += $amount;
      }
    }

    if ($accountId <= 0) {
      $alert = ['type' => 'danger', 'message' => 'Please select an account.'];
    } elseif ($accountCompanyId <= 0) {
      $alert = ['type' => 'danger', 'message' => 'Selected account does not have a valid company.'];
    } elseif ($customerId <= 0) {
      $alert = ['type' => 'danger', 'message' => 'Please select a customer.'];
    } elseif ($incomeDate === '' || strtotime($incomeDate) === false) {
      $alert = ['type' => 'danger', 'message' => 'Please select a valid income date.'];
    } elseif ($getPayment && !in_array($paymentMethod, $paymentMethods, true)) {
      $alert = ['type' => 'danger', 'message' => 'Please select a valid payment method.'];
    } elseif ($discountAmount < 0) {
      $alert = ['type' => 'danger', 'message' => 'Discount must be zero or a positive number.'];
    } elseif (empty($normalizedItems)) {
      $alert = ['type' => 'danger', 'message' => 'Please add at least one income item.'];
    } else {
      $grandTotal = max(0, $subtotal - $discountAmount);
      $createdByUserId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
      $createdByName = trim((string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? ''));
      if ($createdByName === '') {
        $createdByName = 'Unknown User';
      }

      try {
        $pdo->beginTransaction();

        if ($incomeId > 0) {
          $currentPaymentStatusStmt = $pdo->prepare('SELECT payment_status, paid_at, trx_id FROM finance_incomes WHERE income_id = :income_id LIMIT 1');
          $currentPaymentStatusStmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
          $currentPaymentStatusStmt->execute();
          $currentIncome = $currentPaymentStatusStmt->fetch(PDO::FETCH_ASSOC);

          if (!$currentIncome) {
            throw new RuntimeException('Income not found for update.');
          }

          $updateStmt = $pdo->prepare(
            'UPDATE finance_incomes
             SET account_id = :account_id,
                 company_id = :company_id,
                 customer_id = :customer_id,
                 income_date = :income_date,
                 payment_method = :payment_method,
                 subtotal = :subtotal,
                 discount_amount = :discount_amount,
                 grand_total = :grand_total,
                 note = :note,
                 reference_no = :reference_no,
               payment_status = :payment_status,
               paid_at = :paid_at,
                 status = :status,
                 update_count = update_count + 1
             WHERE income_id = :income_id'
          );

          $updateStmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
          $updateStmt->bindValue(':company_id', $accountCompanyId, PDO::PARAM_INT);
          $updateStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
          $updateStmt->bindValue(':income_date', $incomeDate);
          $updateStmt->bindValue(':payment_method', $paymentMethod);
          $updateStmt->bindValue(':subtotal', number_format($subtotal, 2, '.', ''));
          $updateStmt->bindValue(':discount_amount', number_format($discountAmount, 2, '.', ''));
          $updateStmt->bindValue(':grand_total', number_format($grandTotal, 2, '.', ''));
          $updateStmt->bindValue(':note', $form['note'] !== '' ? $form['note'] : null, PDO::PARAM_STR);
          $updateStmt->bindValue(':reference_no', $form['reference_no'] !== '' ? $form['reference_no'] : null, PDO::PARAM_STR);
          $updateStmt->bindValue(':payment_status', $getPayment ? 'paid' : 'unpaid');
          if ($getPayment) {
            $paidAt = !empty($currentIncome['paid_at']) ? (string) $currentIncome['paid_at'] : date('Y-m-d H:i:s');
            $updateStmt->bindValue(':paid_at', $paidAt);
          } else {
            $updateStmt->bindValue(':paid_at', null, PDO::PARAM_NULL);
          }
          $updateStmt->bindValue(':status', 1, PDO::PARAM_INT);
          $updateStmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
          $updateStmt->execute();

          $softOffItemsStmt = $pdo->prepare('UPDATE finance_income_items SET status = 0 WHERE income_id = :income_id AND status = 1');
          $softOffItemsStmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
          $softOffItemsStmt->execute();
        } else {
          $trxSeed = random_int(100000, 999999);
          $generatedTrxId = ispts_generate_income_trxid($customerNameForTrx, $trxSeed);

          $insertStmt = $pdo->prepare(
            'INSERT INTO finance_incomes (
                trx_id,
                account_id,
                company_id,
                customer_id,
                income_date,
                payment_method,
                subtotal,
                discount_amount,
                grand_total,
                note,
                reference_no,
                payment_status,
                paid_at,
                created_by_user_id,
                created_by_name,
                status
             ) VALUES (
                :trx_id,
                :account_id,
                :company_id,
                :customer_id,
                :income_date,
                :payment_method,
                :subtotal,
                :discount_amount,
                :grand_total,
                :note,
                :reference_no,
                :payment_status,
                :paid_at,
                :created_by_user_id,
                :created_by_name,
                :status
             )'
          );

          $insertStmt->bindValue(':trx_id', $generatedTrxId);
          $insertStmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
          $insertStmt->bindValue(':company_id', $accountCompanyId, PDO::PARAM_INT);
          $insertStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
          $insertStmt->bindValue(':income_date', $incomeDate);
          $insertStmt->bindValue(':payment_method', $paymentMethod);
          $insertStmt->bindValue(':subtotal', number_format($subtotal, 2, '.', ''));
          $insertStmt->bindValue(':discount_amount', number_format($discountAmount, 2, '.', ''));
          $insertStmt->bindValue(':grand_total', number_format($grandTotal, 2, '.', ''));
          $insertStmt->bindValue(':note', $form['note'] !== '' ? $form['note'] : null, PDO::PARAM_STR);
          $insertStmt->bindValue(':reference_no', $form['reference_no'] !== '' ? $form['reference_no'] : null, PDO::PARAM_STR);
          $insertStmt->bindValue(':payment_status', $getPayment ? 'paid' : 'unpaid');
          if ($getPayment) {
            $insertStmt->bindValue(':paid_at', date('Y-m-d H:i:s'));
          } else {
            $insertStmt->bindValue(':paid_at', null, PDO::PARAM_NULL);
          }
          $insertStmt->bindValue(':created_by_user_id', $createdByUserId > 0 ? $createdByUserId : null, PDO::PARAM_INT);
          $insertStmt->bindValue(':created_by_name', $createdByName);
          $insertStmt->bindValue(':status', 1, PDO::PARAM_INT);
          $insertStmt->execute();

          $incomeId = (int) $pdo->lastInsertId();
        }

        $insertItemStmt = $pdo->prepare(
          'INSERT INTO finance_income_items (
              income_id,
              item_type,
              product_id,
              serial_id,
              description,
              amount,
              status
           ) VALUES (
              :income_id,
              :item_type,
              :product_id,
              :serial_id,
              :description,
              :amount,
              :status
           )'
        );

        foreach ($normalizedItems as $item) {
          $insertItemStmt->bindValue(':income_id', $incomeId, PDO::PARAM_INT);
          $insertItemStmt->bindValue(':item_type', $item['item_type']);
          $insertItemStmt->bindValue(':product_id', $item['product_id'], $item['product_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
          $insertItemStmt->bindValue(':serial_id', $item['serial_id'], $item['serial_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
          $insertItemStmt->bindValue(':description', $item['description'], $item['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
          $insertItemStmt->bindValue(':amount', number_format((float) $item['amount'], 2, '.', ''));
          $insertItemStmt->bindValue(':status', 1, PDO::PARAM_INT);
          $insertItemStmt->execute();
        }

        $pdo->commit();
        header('Location: ' . $currentPath . '?saved=1');
        exit;
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $alert = ['type' => 'danger', 'message' => 'Unable to save income. ' . $exception->getMessage()];
      }
    }

    $formItems = [];
    foreach ($normalizedItems as $item) {
      $formItems[] = [
        'item_type' => $item['item_type'],
        'product_id' => (int) ($item['product_id'] ?? 0),
        'serial_id' => (int) ($item['serial_id'] ?? 0),
        'description' => (string) ($item['description'] ?? ''),
        'amount' => (string) number_format((float) $item['amount'], 2, '.', ''),
      ];
    }
  }
}

if ($alert === null && isset($_GET['saved'])) {
  $alert = ['type' => 'success', 'message' => 'Bill generated successfully.'];
}
if ($alert === null && isset($_GET['paid'])) {
  $alert = ['type' => 'success', 'message' => 'Income marked as paid.'];
}
if ($alert === null && isset($_GET['turned_off'])) {
  $alert = ['type' => 'success', 'message' => 'Income record deactivated successfully.'];
}

$accounts = $pdo->query(
  "SELECT fa.account_id, fa.company_id, fa.account_name, c.company
   FROM finance_accounts fa
   LEFT JOIN companies c ON c.id = fa.company_id
   WHERE fa.status = 1
   ORDER BY c.company ASC, fa.account_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$customers = $pdo->query(
  "SELECT customer_id,
      COALESCE(NULLIF(full_name, ''), username) AS display_name,
      username,
  phone_no,
  address,
      package_id,
      package_activate_date,
      registered_date,
      created_at,
      payment,
      invoices
   FROM customers
   WHERE status = 1
   ORDER BY display_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$products = $pdo->query(
  'SELECT product_id, product_name, unit_price
   FROM inventory_products
   WHERE status = 1
   ORDER BY product_name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$serialRows = $pdo->query(
  'SELECT serial_id, product_id, serial_ref
   FROM inventory_serial_numbers
   WHERE status = 1
   ORDER BY product_id ASC, serial_ref ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$serialMap = [];
foreach ($serialRows as $sr) {
  $pid = (int) ($sr['product_id'] ?? 0);
  if (!isset($serialMap[$pid])) {
    $serialMap[$pid] = [];
  }
  $serialMap[$pid][] = [
    'serial_id' => (int) $sr['serial_id'],
    'serial_ref' => (string) $sr['serial_ref'],
  ];
}

$customerBillingMap = [];
$firstDayCurrentMonth = new DateTime('first day of this month');
foreach ($customers as $c) {
  $customerId = (int) ($c['customer_id'] ?? 0);
  $packageRaw = trim((string) ($c['package_id'] ?? ''));
  $monthlyAmount = $packageRaw !== '' ? (float) preg_replace('/[^\\d.]+/', '', $packageRaw) : 0.0;

  $startSource = $c['package_activate_date'] ?? null;
  if (empty($startSource)) {
    $startSource = $c['registered_date'] ?? null;
  }
  if (empty($startSource)) {
    $startSource = $c['created_at'] ?? null;
  }

  $start = !empty($startSource) ? new DateTime((string) $startSource) : new DateTime('now');
  $start->modify('first day of next month');

  $paidMonths = [];
  $billingText = trim((string) (($c['payment'] ?? '') . ' ' . ($c['invoices'] ?? '')));
  if ($billingText !== '') {
    preg_match_all('/\\b(20\\d{2})[-\\/.](0[1-9]|1[0-2])(?:[-\\/.](0[1-9]|[12]\\d|3[01]))?\\b/', $billingText, $m, PREG_SET_ORDER);
    foreach ($m as $one) {
      $paidMonths[$one[1] . '-' . $one[2]] = true;
    }
  }

  $totalMonths = 0;
  $paidCount = 0;
  $cursor = clone $start;
  $guard = 0;
  while ($cursor <= $firstDayCurrentMonth && $guard < 240) {
    $totalMonths++;
    if (isset($paidMonths[$cursor->format('Y-m')])) {
      $paidCount++;
    }
    $cursor->modify('+1 month');
    $guard++;
  }

  $unpaidCount = max(0, $totalMonths - $paidCount);
  $unpaidAmount = $monthlyAmount * $unpaidCount;

  $unpaidMonthNames = [];
  $cursor2 = clone $start;
  $guard2 = 0;
  while ($cursor2 <= $firstDayCurrentMonth && $guard2 < 240) {
    $ym = $cursor2->format('Y-m');
    if (!isset($paidMonths[$ym])) {
      $unpaidMonthNames[] = $cursor2->format('M Y');
    }
    $cursor2->modify('+1 month');
    $guard2++;
  }

  $customerBillingMap[$customerId] = [
    'monthly_amount' => $monthlyAmount,
    'unpaid_count' => $unpaidCount,
    'unpaid_amount' => $unpaidAmount,
    'unpaid_months' => $unpaidMonthNames,
  ];
}

$editIncomeId = isset($_GET['edit_income']) ? (int) $_GET['edit_income'] : 0;
if ($editIncomeId > 0) {
  $editStmt = $pdo->prepare(
    'SELECT income_id,
        account_id,
        customer_id,
        income_date,
        payment_method,
        payment_status,
        discount_amount,
        reference_no,
        note,
        status
     FROM finance_incomes
     WHERE income_id = :income_id
     LIMIT 1'
  );
  $editStmt->bindValue(':income_id', $editIncomeId, PDO::PARAM_INT);
  $editStmt->execute();
  $editRow = $editStmt->fetch(PDO::FETCH_ASSOC);

  if ($editRow) {
    $form = [
      'income_id' => (int) $editRow['income_id'],
      'account_id' => (int) $editRow['account_id'],
      'customer_id' => (int) $editRow['customer_id'],
      'income_date' => (string) $editRow['income_date'],
      'payment_method' => (string) $editRow['payment_method'],
      'discount_amount' => (string) $editRow['discount_amount'],
      'reference_no' => (string) ($editRow['reference_no'] ?? ''),
      'note' => (string) ($editRow['note'] ?? ''),
      'get_payment' => (string) ($editRow['payment_status'] ?? 'unpaid') === 'paid' ? 1 : 0,
    ];

    $itemStmt = $pdo->prepare(
      'SELECT item_type, product_id, serial_id, description, amount
       FROM finance_income_items
       WHERE income_id = :income_id AND status = 1
       ORDER BY income_item_id ASC'
    );
    $itemStmt->bindValue(':income_id', $editIncomeId, PDO::PARAM_INT);
    $itemStmt->execute();
    $formItems = [];
    foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
      $formItems[] = [
        'item_type' => (string) ($it['item_type'] ?? 'other_charge'),
        'product_id' => (int) ($it['product_id'] ?? 0),
        'serial_id' => (int) ($it['serial_id'] ?? 0),
        'description' => (string) ($it['description'] ?? ''),
        'amount' => (string) number_format((float) ($it['amount'] ?? 0), 2, '.', ''),
      ];
    }
  }
}

if (empty($formItems)) {
  $formItems[] = [
    'item_type' => 'monthly_bill',
    'product_id' => 0,
    'serial_id' => 0,
    'description' => 'Monthly bill due collection',
    'amount' => '0.00',
  ];
}

$perPageOptions = [10, 20, 50];
$perPageRaw = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$totalIncomeCount = (int) $pdo->query("SELECT COUNT(*) FROM finance_incomes WHERE status = 1")->fetchColumn();
$totalPages = $totalIncomeCount > 0 ? (int) ceil($totalIncomeCount / $perPage) : 1;
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$incomeListStmt = $pdo->prepare(
  "SELECT fi.income_id,
      fi.trx_id,
      fi.income_date,
      fi.subtotal,
      fi.discount_amount,
      fi.grand_total,
      fi.payment_method,
      fi.reference_no,
      fi.payment_status,
      fi.paid_at,
      fi.created_by_name,
      fi.status,
      fa.account_name,
      c.company,
      COALESCE(NULLIF(cu.full_name, ''), cu.username) AS customer_name
   FROM finance_incomes fi
   LEFT JOIN finance_accounts fa ON fa.account_id = fi.account_id
   LEFT JOIN companies c ON c.id = fi.company_id
   LEFT JOIN customers cu ON cu.customer_id = fi.customer_id
   WHERE fi.status = 1
   ORDER BY fi.income_date DESC, fi.income_id DESC
   LIMIT :limit OFFSET :offset"
);
$incomeListStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$incomeListStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$incomeListStmt->execute();
$incomeRows = $incomeListStmt->fetchAll(PDO::FETCH_ASSOC);

$incomeItemCountMap = [];
if (!empty($incomeRows)) {
  $incomeIds = array_map(static fn($r) => (int) $r['income_id'], $incomeRows);
  $incomeIds = array_values(array_filter($incomeIds, static fn($id) => $id > 0));
  if (!empty($incomeIds)) {
    $inList = implode(',', array_fill(0, count($incomeIds), '?'));
    $countStmt = $pdo->prepare(
      "SELECT income_id, COUNT(*) AS total_items
       FROM finance_income_items
       WHERE status = 1 AND income_id IN ($inList)
       GROUP BY income_id"
    );
    foreach ($incomeIds as $i => $id) {
      $countStmt->bindValue($i + 1, $id, PDO::PARAM_INT);
    }
    $countStmt->execute();
    foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $cr) {
      $incomeItemCountMap[(int) $cr['income_id']] = (int) $cr['total_items'];
    }
  }
}

$jsProducts = json_encode(array_map(static function (array $p): array {
  return [
    'product_id' => (int) $p['product_id'],
    'product_name' => (string) $p['product_name'],
    'unit_price' => (float) $p['unit_price'],
  ];
}, $products), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$jsSerialMap = json_encode($serialMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsCustomerBillingMap = json_encode($customerBillingMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsFormItems = json_encode($formItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$pageTitle = 'Income Management';
require '../../includes/header.php';
?>
<link rel="stylesheet" href="<?= $appBasePath ?>/vendors/choices/choices.min.css">
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Income Management</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Income Management</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Generate customer bills with item rows, discount, and paid status control.</p>
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
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0">Income List <span class="text-600 fw-normal fs-11">(<?= $totalIncomeCount ?> total)</span></h6>
        <form method="get" action="<?= $appBasePath ?>/app/finance/income-management.php" class="d-flex align-items-center gap-1">
          <label class="form-label fs-11 mb-0 text-600">Show</label>
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
                <th class="text-800">Customer</th>
                <th class="text-800">Items</th>
                <th class="text-800">Subtotal</th>
                <th class="text-800">Discount</th>
                <th class="text-800">Grand Total</th>
                <th class="text-800">Method</th>
                <th class="text-800">Reference</th>
                <th class="text-800">Created By</th>
                <th class="text-800">Payment</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($incomeRows)): ?>
                <tr><td colspan="14" class="text-center py-3 text-600">No incomes found.</td></tr>
              <?php else: ?>
                <?php foreach ($incomeRows as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0 me-1" href="<?= $appBasePath ?>/app/finance/income-management.php?edit_income=<?= (int) $row['income_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      <?php if ((string) ($row['payment_status'] ?? 'unpaid') !== 'paid'): ?>
                        <form method="post" action="<?= $appBasePath ?>/app/finance/income-management.php" class="d-inline">
                          <?= ispts_csrf_field() ?>
                          <input type="hidden" name="action" value="mark_income_paid">
                          <input type="hidden" name="income_id" value="<?= (int) $row['income_id'] ?>">
                          <button class="btn btn-link p-0 me-1" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark Paid">
                            <span class="fas fa-check-circle text-success"></span>
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="post" action="<?= $appBasePath ?>/app/finance/income-management.php" class="d-inline" onsubmit="return confirm('Deactivate this income record?');">
                        <?= ispts_csrf_field() ?>
                        <input type="hidden" name="action" value="off_income">
                        <input type="hidden" name="income_id" value="<?= (int) $row['income_id'] ?>">
                        <button class="btn btn-link p-0" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Off">
                          <span class="fas fa-ban text-warning"></span>
                        </button>
                      </form>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['trx_id'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) $row['income_date']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['company'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['account_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['customer_name'] ?? '-')) ?></td>
                    <td><?= (int) ($incomeItemCountMap[(int) $row['income_id']] ?? 0) ?></td>
                    <td><?= number_format((float) $row['subtotal'], 2) ?></td>
                    <td><?= number_format((float) $row['discount_amount'], 2) ?></td>
                    <td class="fw-semi-bold"><?= number_format((float) $row['grand_total'], 2) ?></td>
                    <td><?= htmlspecialchars((string) $row['payment_method']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['reference_no'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['created_by_name'] ?? '-')) ?></td>
                    <td>
                      <?php if ((string) ($row['payment_status'] ?? 'unpaid') === 'paid'): ?>
                        <span class="badge badge-subtle-success">Paid</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-warning">Unpaid</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
            <small class="text-600">Page <?= $currentPage ?> of <?= $totalPages ?></small>
            <nav aria-label="Income pagination">
              <ul class="pagination pagination-sm mb-0">
                <?php if ($currentPage > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?= $appBasePath ?>/app/finance/income-management.php?page=<?= $currentPage - 1 ?>&per_page=<?= $perPage ?>">&#8249;</a>
                  </li>
                <?php endif; ?>
                <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                  <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $appBasePath ?>/app/finance/income-management.php?page=<?= $p ?>&per_page=<?= $perPage ?>"><?= $p ?></a>
                  </li>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?= $appBasePath ?>/app/finance/income-management.php?page=<?= $currentPage + 1 ?>&per_page=<?= $perPage ?>">&#8250;</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4 col-xxl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= (int) $form['income_id'] > 0 ? 'Update Invoice' : 'Add Invoice' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/income-management.php" id="income-form">
          <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_income">
          <input type="hidden" name="income_id" value="<?= (int) $form['income_id'] ?>">

          <div class="col-12">
            <label class="form-label" for="income-account">Select account</label>
            <select class="form-select" id="income-account" name="account_id" required>
              <option value="" disabled <?= (int) $form['account_id'] <= 0 ? 'selected' : '' ?>>Select account</option>
              <?php foreach ($accounts as $account): ?>
                <option value="<?= (int) $account['account_id'] ?>" <?= (int) $form['account_id'] === (int) $account['account_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) ($account['company'] ?? 'Unknown Company')) ?> - <?= htmlspecialchars((string) $account['account_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="income-customer">Select customer (search)</label>
            <select class="form-select" id="income-customer" name="customer_id" required>
              <option value="" disabled <?= (int) $form['customer_id'] <= 0 ? 'selected' : '' ?>>Search and select customer</option>
              <?php foreach ($customers as $customer): ?>
                <?php
                  $customerUsername = trim((string) ($customer['username'] ?? ''));
                  $customerPhone = trim((string) ($customer['phone_no'] ?? ''));
                  $customerAddress = trim((string) ($customer['address'] ?? ''));
                  $optionLabel = $customerUsername !== '' ? $customerUsername : (string) ($customer['display_name'] ?? '');
                  if ($customerPhone !== '') {
                    $optionLabel .= ' | ' . $customerPhone;
                  }
                ?>
              <option value="<?= (int) $customer['customer_id'] ?>" <?= (int) $form['customer_id'] === (int) $customer['customer_id'] ? 'selected' : '' ?> data-username="<?= htmlspecialchars($customerUsername) ?>" data-phone="<?= htmlspecialchars($customerPhone) ?>" data-address="<?= htmlspecialchars($customerAddress) ?>">
                  <?= htmlspecialchars($optionLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-600">Search by username or phone from the dropdown.</small>

            <div class="card mt-3">
              <div class="card-body position-relative">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                  <h6 class="mb-0">Customer<span class="badge badge-subtle-info rounded-pill ms-2">Active</span></h6>
                  <a class="btn btn-link p-0 text-primary" href="#" id="view-customer-link" data-bs-toggle="tooltip" data-bs-placement="top" title="View Customer">
                    <span class="fas fa-eye fs-9"></span>
                  </a>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-3 mb-1" id="customer-preview-inline">
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="fas fa-user text-500 fs-9"></span>
                    <span class="fw-semi-bold fs-10 text-truncate" id="preview-customer-name">-</span>
                    <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" id="copy-customer-name" title="Copy Name">
                      <span class="fas fa-copy fs-9"></span>
                    </button>
                  </div>
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="fas fa-phone text-500 fs-9"></span>
                    <span class="fw-semi-bold fs-10 text-truncate" id="preview-customer-phone">-</span>
                    <button class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-placement="top" id="copy-customer-phone" title="Copy Phone">
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
            <label class="form-label" for="income-date">Date</label>
            <input class="form-control" id="income-date" name="income_date" type="date" value="<?= htmlspecialchars((string) $form['income_date']) ?>" required>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label mb-0">Income Items</label>
              <button class="btn btn-falcon-default btn-sm" type="button" id="add-income-item">Add Item</button>
            </div>
            <div class="table-responsive border rounded">
              <table class="table table-sm mb-0 fs-11" id="income-items-table">
                <thead class="bg-body-tertiary">
                  <tr>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Amount</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="income-items-body"></tbody>
              </table>
            </div>
            <div class="fs-11 text-600 mt-1">Type options: Monthly Bill, Product, Deposit, Other Charge.</div>
          </div>

          <div class="col-12">
            <label class="form-label" for="income-reference">Reference</label>
            <input class="form-control" id="income-reference" name="reference_no" type="text" value="<?= htmlspecialchars((string) $form['reference_no']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label" for="income-note">Note</label>
            <textarea class="form-control" id="income-note" name="note" rows="2"><?= htmlspecialchars((string) $form['note']) ?></textarea>
          </div>

          <div class="col-12">
            <div class="row g-2">
              <div class="col-12">
                <div class="border rounded p-2 bg-body-tertiary">
                  <div class="d-flex justify-content-between"><span>Summary Total</span><strong id="summary-total">0.00</strong></div>
                  <div class="mt-2">
                    <label class="form-label fs-11 mb-1" for="discount-amount">Discount</label>
                    <input class="form-control form-control-sm" id="discount-amount" name="discount_amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $form['discount_amount']) ?>">
                  </div>
                  <div class="d-flex justify-content-between mt-2 border-top pt-2"><span>Grand Total</span><strong id="grand-total">0.00</strong></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label mb-0" for="income-get-payment">Get Payment</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" id="income-get-payment" type="checkbox" name="get_payment" value="1" <?= (int) $form['get_payment'] === 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="col-12 d-none" id="payment-method-wrap">
            <label class="form-label" for="income-payment-method">Payment Method</label>
            <select class="form-select" id="income-payment-method" name="payment_method">
              <option value="" disabled <?= trim((string) $form['payment_method']) === '' ? 'selected' : '' ?>>Select payment method</option>
              <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= htmlspecialchars($method) ?>" <?= (string) $form['payment_method'] === $method ? 'selected' : '' ?>>
                  <?= htmlspecialchars($method) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/income-management.php">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit">Generate Invoice</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="<?= $appBasePath ?>/vendors/choices/choices.min.js"></script>
<script>
(function () {
  var PRODUCTS = <?= $jsProducts ?: '[]' ?>;
  var SERIAL_MAP = <?= $jsSerialMap ?: '{}' ?>;
  var CUSTOMER_BILLING = <?= $jsCustomerBillingMap ?: '{}' ?>;
  var INITIAL_ITEMS = <?= $jsFormItems ?: '[]' ?>;

  var form = document.getElementById('income-form');
  var customerSelect = document.getElementById('income-customer');
  var addItemBtn = document.getElementById('add-income-item');
  var itemsBody = document.getElementById('income-items-body');
  var discountInput = document.getElementById('discount-amount');
  var summaryTotalEl = document.getElementById('summary-total');
  var grandTotalEl = document.getElementById('grand-total');
  var previewName = document.getElementById('preview-customer-name');
  var previewPhone = document.getElementById('preview-customer-phone');
  var previewAddress = document.getElementById('preview-customer-address');
  var viewCustomerLink = document.getElementById('view-customer-link');
  var copyCustomerName = document.getElementById('copy-customer-name');
  var copyCustomerPhone = document.getElementById('copy-customer-phone');
  var getPaymentInput = document.getElementById('income-get-payment');
  var paymentMethodWrap = document.getElementById('payment-method-wrap');
  var paymentMethodSelect = document.getElementById('income-payment-method');

  if (!form || !customerSelect || !addItemBtn || !itemsBody || !discountInput || !summaryTotalEl || !grandTotalEl) {
    return;
  }

  function syncPaymentMethodState() {
    if (!getPaymentInput || !paymentMethodWrap || !paymentMethodSelect) {
      return;
    }

    var enabled = !!getPaymentInput.checked;
    paymentMethodWrap.classList.toggle('d-none', !enabled);
    paymentMethodSelect.required = enabled;

    if (!enabled) {
      paymentMethodSelect.value = '';
    } else if (!paymentMethodSelect.value && paymentMethodSelect.options.length > 1) {
      paymentMethodSelect.selectedIndex = 1;
    }
  }

  if (window.Choices) {
    new window.Choices(customerSelect, {
      searchEnabled: true,
      searchFields: ['label'],
      itemSelectText: '',
      shouldSort: false,
      placeholder: true,
      searchPlaceholderValue: 'Search customer by username or phone',
    });
  }

  var rowIndex = 0;

  function formatMoney(v) {
    return (Math.round(v * 100) / 100).toFixed(2);
  }

  function copyToClipboard(text) {
    if (!text || !navigator.clipboard) {
      return;
    }
    navigator.clipboard.writeText(text).catch(function () {});
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

    if (previewName) {
      previewName.textContent = name !== '' ? name : '-';
    }
    if (previewPhone) {
      previewPhone.textContent = phone !== '' ? phone : '-';
    }
    if (previewAddress) {
      previewAddress.textContent = address !== '' ? address : 'No address on record';
    }

    if (viewCustomerLink) {
      viewCustomerLink.setAttribute('href', customerId !== '' ? '<?= $appBasePath ?>/app/support-desk/customer-details.php?id=' + encodeURIComponent(customerId) : '#');
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

  function getCustomerBillingData() {
    var customerId = parseInt(customerSelect.value || '0', 10);
    if (!customerId || !CUSTOMER_BILLING[customerId]) {
      return { monthly_amount: 0, unpaid_count: 0, unpaid_amount: 0, unpaid_months: [] };
    }
    return CUSTOMER_BILLING[customerId];
  }

  function buildMonthOptions(unpaidMonths, preSelect) {
    var selectSet = {};
    (preSelect || []).forEach(function (m) { selectSet[m] = true; });
    var allMonths = (unpaidMonths || []).slice();
    (preSelect || []).forEach(function (m) {
      if (allMonths.indexOf(m) === -1) { allMonths.push(m); }
    });
    if (allMonths.length === 0) {
      return '<option disabled value="">No unpaid months</option>';
    }
    var html = '';
    allMonths.forEach(function (m) {
      html += '<option value="' + m + '"' + (selectSet[m] ? ' selected' : '') + '>' + m + '</option>';
    });
    return html;
  }

  function getSelectedMonths(row) {
    var sel = row.querySelector('.js-month-select');
    if (!sel) { return []; }
    var out = [];
    Array.prototype.forEach.call(sel.options, function (o) {
      if (o.selected && o.value) { out.push(o.value); }
    });
    return out;
  }

  function updateMonthlyBillFromSelect(row) {
    var billing = getCustomerBillingData();
    var months = getSelectedMonths(row);
    var descHidden = row.querySelector('.js-desc-hidden');
    var amountInput = row.querySelector('.js-income-item-amount');
    if (descHidden) {
      descHidden.value = months.length > 0 ? 'Monthly bill: ' + months.join(', ') : '';
    }
    if (amountInput) {
      var rate = parseFloat(billing.monthly_amount || 0);
      amountInput.value = formatMoney(rate * months.length);
    }
    recalcTotals();
  }

  function buildProductOptions(selectedProductId) {
    var html = '<option value="" disabled selected>Select product</option>';
    PRODUCTS.forEach(function (p) {
      var selected = parseInt(selectedProductId || '0', 10) === parseInt(p.product_id || '0', 10) ? ' selected' : '';
      html += '<option value="' + p.product_id + '"' + selected + '>' + (p.product_name || ('Product #' + p.product_id)) + '</option>';
    });
    return html;
  }

  function buildSerialOptions(productId, selectedSerialId) {
    var pid = parseInt(productId || '0', 10);
    var list = pid > 0 && SERIAL_MAP[pid] ? SERIAL_MAP[pid] : [];
    var html = '<option value="">No serial</option>';
    list.forEach(function (s) {
      var selected = parseInt(selectedSerialId || '0', 10) === parseInt(s.serial_id || '0', 10) ? ' selected' : '';
      html += '<option value="' + s.serial_id + '"' + selected + '>' + (s.serial_ref || ('Serial #' + s.serial_id)) + '</option>';
    });
    return html;
  }

  function recalcTotals() {
    var amountInputs = itemsBody.querySelectorAll('.js-income-item-amount');
    var subtotal = 0;
    amountInputs.forEach(function (input) {
      var v = parseFloat(input.value || '0');
      if (!isNaN(v) && v > 0) {
        subtotal += v;
      }
    });

    var discount = parseFloat(discountInput.value || '0');
    if (isNaN(discount) || discount < 0) {
      discount = 0;
    }

    var grand = subtotal - discount;
    if (grand < 0) {
      grand = 0;
    }

    summaryTotalEl.textContent = formatMoney(subtotal);
    grandTotalEl.textContent = formatMoney(grand);
  }

  function applyTypeState(row, preserveMonthSelection) {
    var typeSelect = row.querySelector('.js-item-type');
    var monthWrap = row.querySelector('.js-month-wrap');
    var productWrap = row.querySelector('.js-product-wrap');
    var otherWrap = row.querySelector('.js-other-wrap');
    var amountInput = row.querySelector('.js-income-item-amount');
    var monthSelect = row.querySelector('.js-month-select');
    var descHidden = row.querySelector('.js-desc-hidden');

    if (!typeSelect || !monthWrap || !productWrap || !otherWrap || !amountInput) {
      return;
    }

    var type = typeSelect.value;
    var billing = getCustomerBillingData();
    var unpaidMonths = Array.isArray(billing.unpaid_months) ? billing.unpaid_months : [];

    if (type === 'monthly_bill') {
      monthWrap.classList.remove('d-none');
      productWrap.classList.add('d-none');
      otherWrap.classList.add('d-none');
      amountInput.readOnly = true;

      var preSelect = [];
      if (preserveMonthSelection) {
        preSelect = getSelectedMonths(row);
      } else if (descHidden && descHidden.value.indexOf('Monthly bill:') === 0) {
        preSelect = descHidden.value.replace('Monthly bill:', '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
      } else {
        preSelect = unpaidMonths.slice();
      }

      if (monthSelect) {
        monthSelect.innerHTML = buildMonthOptions(unpaidMonths, preSelect);
      }
      updateMonthlyBillFromSelect(row);
    } else if (type === 'product') {
      monthWrap.classList.add('d-none');
      productWrap.classList.remove('d-none');
      otherWrap.classList.add('d-none');
      amountInput.readOnly = false;
      if (!amountInput.value || parseFloat(amountInput.value) === 0) {
        amountInput.value = '0.00';
      }
    } else {
      monthWrap.classList.add('d-none');
      productWrap.classList.add('d-none');
      otherWrap.classList.remove('d-none');
      amountInput.readOnly = false;
      if (!amountInput.value || parseFloat(amountInput.value) === 0) {
        amountInput.value = '0.00';
      }
    }

    recalcTotals();
  }

  function bindRowEvents(row) {
    var typeSelect = row.querySelector('.js-item-type');
    var removeBtn = row.querySelector('.js-remove-item');
    var amountInput = row.querySelector('.js-income-item-amount');
    var productSelect = row.querySelector('.js-item-product');
    var serialSelect = row.querySelector('.js-item-serial');
    var monthSelect = row.querySelector('.js-month-select');
    var otherInput = row.querySelector('.js-other-description');
    var descHidden = row.querySelector('.js-desc-hidden');

    if (typeSelect) {
      typeSelect.addEventListener('change', function () {
        applyTypeState(row, false);
      });
    }

    if (monthSelect) {
      monthSelect.addEventListener('change', function () {
        updateMonthlyBillFromSelect(row);
      });
    }

    if (otherInput && descHidden) {
      otherInput.addEventListener('input', function () {
        descHidden.value = otherInput.value;
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        row.remove();
        if (itemsBody.querySelectorAll('tr').length === 0) {
          addItemRow({ item_type: 'monthly_bill', product_id: 0, serial_id: 0, description: '', amount: '0.00' });
        }
        recalcTotals();
      });
    }

    if (amountInput) {
      amountInput.addEventListener('input', recalcTotals);
    }

    if (productSelect && serialSelect) {
      productSelect.addEventListener('change', function () {
        serialSelect.innerHTML = buildSerialOptions(productSelect.value, 0);
      });
    }
  }

  function addItemRow(data) {
    rowIndex += 1;
    var idx = rowIndex;

    var itemType = data && data.item_type ? data.item_type : 'monthly_bill';
    var productId = data && data.product_id ? parseInt(data.product_id, 10) : 0;
    var serialId = data && data.serial_id ? parseInt(data.serial_id, 10) : 0;
    var description = data && data.description ? String(data.description) : '';
    var amount = data && data.amount ? String(data.amount) : '0.00';

    var tr = document.createElement('tr');
    tr.innerHTML = '' +
      '<td style="min-width:120px;">' +
      '  <select class="form-select form-select-sm js-item-type" name="items[' + idx + '][item_type]" required>' +
      '    <option value="monthly_bill"' + (itemType === 'monthly_bill' ? ' selected' : '') + '>Monthly Bill</option>' +
      '    <option value="product"' + (itemType === 'product' ? ' selected' : '') + '>Product</option>' +
      '    <option value="deposit"' + (itemType === 'deposit' ? ' selected' : '') + '>Deposit</option>' +
      '    <option value="other_charge"' + (itemType === 'other_charge' ? ' selected' : '') + '>Other Charge</option>' +
      '  </select>' +
      '</td>' +
      '<td style="min-width:200px;">' +
      '  <div class="js-month-wrap d-none">' +
      '    <select class="form-select form-select-sm js-month-select" multiple size="3"></select>' +
      '    <div class="fs-11 text-500 mt-1">Hold Ctrl / Cmd to select multiple months</div>' +
      '  </div>' +
      '  <div class="js-product-wrap d-none">' +
      '    <select class="form-select form-select-sm js-item-product mb-1" name="items[' + idx + '][product_id]">' + buildProductOptions(productId) + '</select>' +
      '    <select class="form-select form-select-sm js-item-serial" name="items[' + idx + '][serial_id]">' + buildSerialOptions(productId, serialId) + '</select>' +
      '  </div>' +
      '  <div class="js-other-wrap d-none">' +
      '    <input class="form-control form-control-sm js-other-description" type="text" placeholder="Write charge description" value="' + (itemType === 'other_charge' ? description.replace(/"/g, '&quot;') : '') + '">' +
      '  </div>' +
      '  <input class="js-desc-hidden" name="items[' + idx + '][description]" type="hidden" value="' + description.replace(/"/g, '&quot;') + '">' +
      '</td>' +
      '<td style="min-width:110px;">' +
      '  <input class="form-control form-control-sm js-income-item-amount" name="items[' + idx + '][amount]" type="number" min="0" step="0.01" value="' + amount.replace(/"/g, '&quot;') + '" required>' +
      '</td>' +
      '<td class="text-end">' +
      '  <button class="btn btn-link p-0 js-remove-item" type="button" title="Remove"><span class="fas fa-times text-danger"></span></button>' +
      '</td>';

    itemsBody.appendChild(tr);
    bindRowEvents(tr);
    applyTypeState(tr, false);

    var psel = tr.querySelector('.js-item-product');
    var ssel = tr.querySelector('.js-item-serial');
    if (psel && productId > 0) {
      psel.value = String(productId);
      if (ssel) {
        ssel.innerHTML = buildSerialOptions(productId, serialId);
      }
    }

    // For product/other_charge, restore the saved amount
    if (itemType !== 'monthly_bill') {
      var amtInput = tr.querySelector('.js-income-item-amount');
      if (amtInput && amount !== '' && parseFloat(amount) > 0) {
        amtInput.value = amount;
      }
    }

    recalcTotals();
  }

  addItemBtn.addEventListener('click', function () {
    addItemRow({
      item_type: 'other_charge',
      product_id: 0,
      serial_id: 0,
      description: '',
      amount: '0.00',
    });
  });

  customerSelect.addEventListener('change', function () {
    updateCustomerPreview();
    var rows = itemsBody.querySelectorAll('tr');
    rows.forEach(function (row) {
      var typeSelect = row.querySelector('.js-item-type');
      if (typeSelect && typeSelect.value === 'monthly_bill') {
        applyTypeState(row, true);
      }
    });
    recalcTotals();
  });

  discountInput.addEventListener('input', recalcTotals);
  if (getPaymentInput) {
    getPaymentInput.addEventListener('change', syncPaymentMethodState);
  }

  if (Array.isArray(INITIAL_ITEMS) && INITIAL_ITEMS.length > 0) {
    INITIAL_ITEMS.forEach(function (item) {
      addItemRow(item || {});
    });
  } else {
    addItemRow({
      item_type: 'monthly_bill',
      product_id: 0,
      serial_id: 0,
      description: 'Monthly bill due collection',
      amount: '0.00',
    });
  }

  updateCustomerPreview();
  syncPaymentMethodState();
  recalcTotals();
})();
</script>

<?php
require '../../includes/footer.php';
?>
