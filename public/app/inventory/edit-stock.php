<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../../app/Helpers/ispts_ImageHelper.php';

use App\Core\Database;
use App\Helpers\ispts_ImageHelper;

$pdo = Database::getConnection();

$ispts_has_table = static function (\PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
  );
  $stmt->bindValue(':table', $table);
  $stmt->execute();

  return (int) $stmt->fetchColumn() > 0;
};

$ispts_has_column = static function (\PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
  );
  $stmt->bindValue(':table', $table);
  $stmt->bindValue(':column', $column);
  $stmt->execute();

  return (int) $stmt->fetchColumn() > 0;
};

$branchesTableExists = $ispts_has_table($pdo, 'branches');
$partnersTableExists = $ispts_has_table($pdo, 'partners');

// ── SESSION ───────────────────────────────────────────────────────────────────

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$loggedInUserId = (int) ($_SESSION['admin_user_id'] ?? 0);
$loggedInName   = (string) ($_SESSION['admin_full_name'] ?? 'Unknown');

$alert          = null;
$currentPath    = $_SERVER['PHP_SELF'] ?? '/app/inventory/edit-stock.php';

// ── POST HANDLER ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');
    
    // Debug: Log what we received
    error_log("POST request received. Action: $action");
    error_log("POST data keys: " . implode(', ', array_keys($_POST)));
    
    
    if ($action === 'save_stock_invoice') {
        $invoiceId   = isset($_POST['invoice_id'])   ? (int) $_POST['invoice_id']   : 0;
        $branchId    = isset($_POST['branch_id'])    ? (int) $_POST['branch_id']    : 0;
        $vendorId    = isset($_POST['vendor_id'])    ? (int) $_POST['vendor_id']    : 0;
        $invoiceDate = trim((string) ($_POST['invoice_date'] ?? ''));
        $paymentMode = in_array($_POST['payment_mode'] ?? '', ['cash', 'partial', 'emi'], true)
                       ? (string) $_POST['payment_mode'] : 'cash';
        $dueDays     = isset($_POST['due_days'])  ? max(1, (int) $_POST['due_days'])  : 30;
        $emiCount    = isset($_POST['emi_count']) ? max(1, (int) $_POST['emi_count']) : 1;
        $manualTotalDiscount = isset($_POST['manual_total_discount'])
          ? max(0.0, (float) $_POST['manual_total_discount'])
          : 0.0;
        $refEmployee = trim((string) ($_POST['ref_employee'] ?? ''));
        $notes       = trim((string) ($_POST['notes']        ?? ''));
        $rawItems    = isset($_POST['items'])        && is_array($_POST['items'])        ? $_POST['items']        : [];
        $rawEmi      = isset($_POST['emi_schedule']) && is_array($_POST['emi_schedule']) ? $_POST['emi_schedule'] : [];
        $rawPartial  = isset($_POST['partial_schedule']) && is_array($_POST['partial_schedule']) ? $_POST['partial_schedule'] : [];
        
        // New partial payment fields
        $cashPayment = isset($_POST['cash_payment']) ? max(0.0, (float) $_POST['cash_payment']) : 0.0;
        $nextInstallmentQty = isset($_POST['next_installment_qty']) ? max(1, (int) $_POST['next_installment_qty']) : 1;
        $dueMax = isset($_POST['due_max']) ? max(1, (int) $_POST['due_max']) : 30;
        $dueInterval = in_array($_POST['due_interval'] ?? '', ['days', 'months'], true)
                       ? (string) $_POST['due_interval'] : 'days';
        
        $errors = [];
        if ($invoiceId <= 0)    $errors[] = 'Invalid invoice ID.';
        if ($branchId <= 0)     $errors[] = 'Please select a branch.';
        if ($vendorId <= 0)     $errors[] = 'Please select a vendor.';
        if ($invoiceDate === '') $errors[] = 'Invoice date is required.';
        if (empty($rawItems))   $errors[] = 'Please add at least one product item.';
        
        error_log("Validation errors: " . (empty($errors) ? 'None' : implode(', ', $errors)));

        $cleanItems = [];
        foreach ($rawItems as $rawItem) {
            if (!is_array($rawItem)) continue;
            $productId      = isset($rawItem['product_id'])       ? (int) $rawItem['product_id']                  : 0;
            $qty            = isset($rawItem['quantity'])          ? max(1, (int) $rawItem['quantity'])             : 1;
            $unitPrice      = isset($rawItem['unit_price'])        ? max(0.0, (float) $rawItem['unit_price'])       : 0.0;
            $discPerUnit    = isset($rawItem['discount_per_unit']) ? max(0.0, (float) $rawItem['discount_per_unit']) : 0.0;
            $brand          = trim((string) ($rawItem['brand']           ?? ''));
            $warrantyPeriod = trim((string) ($rawItem['warranty_period'] ?? ''));
            $warrantyUnit   = trim((string) ($rawItem['warranty_unit']   ?? ''));
            $serialsRaw     = isset($rawItem['serials']) && is_array($rawItem['serials']) ? $rawItem['serials'] : [];

            if ($productId <= 0) continue;

            if ($warrantyPeriod !== '' && in_array($warrantyUnit, ['Days', 'Months', 'Years'], true)) {
              $warrantyPeriod .= ' ' . $warrantyUnit;
            }

            $lineSubtotal = round($qty * $unitPrice, 2);
            $lineDiscount = round($qty * $discPerUnit, 2);
            $lineTotal    = round($lineSubtotal - $lineDiscount, 2);
            if ($lineTotal < 0) $lineTotal = 0.0;

            $serials = [];
            foreach ($serialsRaw as $sr) {
                $sr = trim((string) $sr);
                if ($sr !== '') $serials[] = $sr;
            }

            $cleanItems[] = [
                'product_id'        => $productId,
                'brand'             => $brand           !== '' ? $brand           : null,
                'quantity'          => $qty,
                'unit_price'        => $unitPrice,
                'discount_per_unit' => $discPerUnit,
                'warranty_period'   => $warrantyPeriod  !== '' ? $warrantyPeriod  : null,
                'line_subtotal'     => $lineSubtotal,
                'line_discount'     => $lineDiscount,
                'line_total'        => $lineTotal,
                'serials'           => $serials,
            ];
        }

        if (empty($cleanItems)) $errors[] = 'Please add at least one valid product item.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Debug logging
                error_log("Starting invoice update for ID: $invoiceId");
                error_log("Items count: " . count($cleanItems));

                $subtotal      = 0.0;
                foreach ($cleanItems as $ci) {
                    $subtotal += $ci['line_subtotal'];
                }
                $totalDiscount = $manualTotalDiscount;
                $grandTotal    = round($subtotal - $totalDiscount, 2);
                if ($grandTotal < 0) $grandTotal = 0.0;

                // Handle image upload
                $imageFileName = null;
                if (isset($_FILES['invoice_image']) && (int) $_FILES['invoice_image']['error'] === UPLOAD_ERR_OK) {
                    $imageHelper   = new ispts_ImageHelper();
                    $imageFileName = $imageHelper->ispts_compress($_FILES['invoice_image']);
                }

                $imageSql = $imageFileName !== null ? 'invoice_image = :invoice_image, ' : '';
                $invStmt = $pdo->prepare(
                    "UPDATE inventory_stock_invoices SET 
                       branch_id = :branch_id, vendor_id = :vendor_id, invoice_date = :invoice_date, 
                       $imageSql notes = :notes, payment_mode = :payment_mode, subtotal = :subtotal, 
                       total_discount = :total_discount, grand_total = :grand_total, due_days = :due_days, 
                       emi_count = :emi_count, ref_employee = :ref_employee 
                     WHERE invoice_id = :invoice_id"
                );
                $invStmt->bindValue(':invoice_id', $invoiceId, \PDO::PARAM_INT);
                $invStmt->bindValue(':branch_id',      $branchId,       \PDO::PARAM_INT);
                $invStmt->bindValue(':vendor_id',      $vendorId,       \PDO::PARAM_INT);
                $invStmt->bindValue(':invoice_date',   $invoiceDate);
                $invStmt->bindValue(':notes',          $notes !== '' ? $notes : null, \PDO::PARAM_STR);
                $invStmt->bindValue(':payment_mode',   $paymentMode);
                $invStmt->bindValue(':subtotal',       $subtotal);
                $invStmt->bindValue(':total_discount', $totalDiscount);
                $invStmt->bindValue(':grand_total',    $grandTotal);
                $invStmt->bindValue(':due_days',   $paymentMode === 'partial' ? $dueDays  : null,
                                                   $paymentMode === 'partial' ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
                $invStmt->bindValue(':emi_count',  $paymentMode === 'emi'     ? $emiCount : null,
                                                   $paymentMode === 'emi'     ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
                $invStmt->bindValue(':ref_employee',  $refEmployee !== '' ? $refEmployee : null, \PDO::PARAM_STR);
                if ($imageFileName !== null) {
                    $invStmt->bindValue(':invoice_image', $imageFileName, \PDO::PARAM_STR);
                }
                
                error_log("Executing invoice update SQL...");
                $invStmt->execute();
                error_log("Invoice update affected rows: " . $invStmt->rowCount());

                // Clear and re-insert child records
                $pdo->prepare('DELETE FROM inventory_stock_invoice_items WHERE invoice_id = :id')->execute(['id' => $invoiceId]);
                $pdo->prepare('DELETE FROM inventory_serial_numbers WHERE invoice_id = :id')->execute(['id' => $invoiceId]);
                $pdo->prepare('DELETE FROM inventory_stock_payments WHERE invoice_id = :id')->execute(['id' => $invoiceId]);

                $itemInsert = $pdo->prepare(
                    'INSERT INTO inventory_stock_invoice_items
                     (invoice_id, product_id, brand, quantity, unit_price, discount_per_unit,
                      warranty_period, line_subtotal, line_discount, line_total, status)
                     VALUES
                     (:invoice_id, :product_id, :brand, :quantity, :unit_price, :discount_per_unit,
                      :warranty_period, :line_subtotal, :line_discount, :line_total, 1)'
                );
                $serialInsert = $pdo->prepare(
                    'INSERT INTO inventory_serial_numbers
                     (item_id, invoice_id, product_id, serial_ref, status)
                     VALUES (:item_id, :invoice_id, :product_id, :serial_ref, 1)'
                );

                foreach ($cleanItems as $ci) {
                    $itemInsert->bindValue(':invoice_id',        $invoiceId,           \PDO::PARAM_INT);
                    $itemInsert->bindValue(':product_id',        $ci['product_id'],    \PDO::PARAM_INT);
                    $itemInsert->bindValue(':brand',             $ci['brand'],         \PDO::PARAM_STR);
                    $itemInsert->bindValue(':quantity',          $ci['quantity'],      \PDO::PARAM_INT);
                    $itemInsert->bindValue(':unit_price',        $ci['unit_price']);
                    $itemInsert->bindValue(':discount_per_unit', $ci['discount_per_unit']);
                    $itemInsert->bindValue(':warranty_period',   $ci['warranty_period'], \PDO::PARAM_STR);
                    $itemInsert->bindValue(':line_subtotal',     $ci['line_subtotal']);
                    $itemInsert->bindValue(':line_discount',     $ci['line_discount']);
                    $itemInsert->bindValue(':line_total',        $ci['line_total']);
                    $itemInsert->execute();

                    $itemId = (int) $pdo->lastInsertId();
                    error_log("Inserted item ID: $itemId for product: " . $ci['product_id']);
                    
                    foreach ($ci['serials'] as $sr) {
                        $serialInsert->execute([
                            'item_id'    => $itemId,
                            'invoice_id' => $invoiceId,
                            'product_id' => $ci['product_id'],
                            'serial_ref' => $sr
                        ]);
                    }
                }

                if ($paymentMode === 'partial' && !empty($rawPartial)) {
                    $payInsert = $pdo->prepare('INSERT INTO inventory_stock_payments (invoice_id, due_date, amount, payment_note, status) VALUES (:id, :d, :a, :n, 1)');
                    foreach ($rawPartial as $p) {
                        $payInsert->execute([
                            'id' => $invoiceId,
                            'd'  => $p['due_date'],
                            'a'  => $p['amount'],
                            'n'  => 'Partial payment installment'
                        ]);
                    }
                } elseif ($paymentMode === 'emi' && !empty($rawEmi)) {
                    $payInsert = $pdo->prepare('INSERT INTO inventory_stock_payments (invoice_id, due_date, amount, payment_note, status) VALUES (:id, :d, :a, :n, 1)');
                    foreach ($rawEmi as $e) {
                        $payInsert->execute([
                            'id' => $invoiceId,
                            'd'  => $e['due_date'],
                            'a'  => $e['amount'],
                            'n'  => $e['note'] ?? ''
                        ]);
                    }
                }

                error_log("Committing transaction...");
                $pdo->commit();
                error_log("Transaction committed successfully");
                
                $alert = ['type' => 'success', 'message' => 'Invoice updated successfully.'];
                header('Location: manage-stock.php?updated=1');
                exit;

            } catch (\Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                    error_log("Transaction rolled back due to error: " . $ex->getMessage());
                }
                error_log("Update failed: " . $ex->getMessage());
                $alert = ['type' => 'danger', 'message' => 'Update failed: ' . $ex->getMessage()];
            }
        } else {
            $alert = ['type' => 'danger', 'message' => implode(' ', $errors)];
        }
    }
}

// ── DATA LOADING ──────────────────────────────────────────────────────────────

$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
if ($editId <= 0) {
    header('Location: manage-stock.php');
    exit;
}

$editStmt = $pdo->prepare('SELECT * FROM inventory_stock_invoices WHERE invoice_id = :id LIMIT 1');
$editStmt->execute(['id' => $editId]);
$editInvoice = $editStmt->fetch(\PDO::FETCH_ASSOC);

if (!$editInvoice) {
    header('Location: manage-stock.php');
    exit;
}

$vendors = $pdo->query('SELECT vendor_id, vendor_name FROM inventory_vendors WHERE status = 1 ORDER BY vendor_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$productsAll = $pdo->query('SELECT p.product_id, p.product_name, COALESCE(su.unit_name, pu.unit_name, \'\') AS sub_category_unit_name FROM inventory_products p LEFT JOIN inventory_sub_categories sc ON sc.sub_category_id = p.sub_category_id LEFT JOIN inventory_units su ON su.unit_id = sc.unit_id LEFT JOIN inventory_units pu ON pu.unit_id = p.unit_id WHERE p.status = 1 ORDER BY p.product_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

// Simplified branch loading
$branches = [];
if ($branchesTableExists) {
    $companiesTableExists = $ispts_has_table($pdo, 'companies');
    $branchJoin = '';
    $branchCompanySelect = ", '' AS company_name";
    if ($companiesTableExists) {
        $branchJoin = ' LEFT JOIN companies p ON p.id = b.partner_id';
        $branchCompanySelect = ", COALESCE(NULLIF(p.company, ''), p.firstname, p.username, '') AS company_name";
    }
    $branches = $pdo->query('SELECT b.branch_id, b.branch_name' . $branchCompanySelect . ' FROM branches b' . $branchJoin . ' ORDER BY b.branch_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
}

$itemStmt = $pdo->prepare('SELECT * FROM inventory_stock_invoice_items WHERE invoice_id = :id ORDER BY item_id ASC');
$itemStmt->execute(['id' => $editId]);
$editItems = $itemStmt->fetchAll(\PDO::FETCH_ASSOC);

$serialStmt = $pdo->prepare('SELECT item_id, serial_ref FROM inventory_serial_numbers WHERE invoice_id = :id');
$serialStmt->execute(['id' => $editId]);
$editSerials = [];
foreach ($serialStmt->fetchAll(\PDO::FETCH_ASSOC) as $s) {
    $editSerials[(int) $s['item_id']][] = $s['serial_ref'];
}

$payStmt = $pdo->prepare('SELECT due_date, amount, payment_note FROM inventory_stock_payments WHERE invoice_id = :id ORDER BY due_date ASC');
$payStmt->execute(['id' => $editId]);
$editPayments = $payStmt->fetchAll(\PDO::FETCH_ASSOC);

$jsProducts      = json_encode($productsAll,    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsVendors       = json_encode($vendors,        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsBranches      = json_encode($branches,       JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsEditInvoice   = json_encode($editInvoice,    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsEditItems     = json_encode($editItems,      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsEditSerials   = json_encode($editSerials,    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsEditPayments  = json_encode($editPayments,   JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

require '../../includes/header.php';
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/inventory/manage-stock.php">Inventory</a></li>
    <li class="breadcrumb-item active">Edit Stock Invoice</li>
  </ol>
</nav>

<div class="d-flex mb-4 align-items-center">
    <div class="me-3">
        <a class="btn btn-falcon-default btn-sm" href="manage-stock.php">
            <span class="fas fa-chevron-left me-1"></span>Back to List
        </a>
    </div>
    <h2 class="text-bold mb-0">Edit Stock Invoice: <?= htmlspecialchars((string)$editInvoice['invoice_number']) ?></h2>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2 mb-3" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] . '?edit_id=' . $editId) ?>
            <?= ispts_csrf_field() ?>" enctype="multipart/form-data" id="stockInvoiceForm">
  <input type="hidden" name="action" value="save_stock_invoice">
  <input type="hidden" name="invoice_id" value="<?= $editId ?>">

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card mb-3">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Product Items</h6>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" id="branch_id" name="branch_id" style="min-width: 280px;" required>
                <option value="" disabled <?= (int)$editInvoice['branch_id'] === 0 ? 'selected' : '' ?>>Select Branch</option>
                <?php foreach ($branches as $b): ?>
                    <?php $branchCompanyName = trim((string) ($b['company_name'] ?? '')); ?>
                    <option value="<?= (int)$b['branch_id'] ?>" <?= (int)$editInvoice['branch_id'] === (int)$b['branch_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $b['branch_name'], ENT_QUOTES, 'UTF-8') ?><?= $branchCompanyName !== '' ? ' | ' . htmlspecialchars($branchCompanyName, ENT_QUOTES, 'UTF-8') : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-sm btn-falcon-success" id="addItemBtn">
                <span class="fas fa-plus me-1"></span>Add Item
            </button>
        </div>
      </div>
      <div class="card-body p-0" id="itemRowsContainer">
        <div class="text-center text-600 py-4 fs-10" id="noItemsPlaceholder" style="display:none;">
          Click <strong>Add Item</strong> to begin adding products.
        </div>
      </div>
      <div class="card-footer border-top border-200 bg-body-tertiary">
        <div class="row justify-content-end">
          <div class="col-md-4">
            <table class="table table-sm mb-0 fs-10">
              <tr><td>Subtotal</td><td class="text-end fw-semibold" id="summarySubtotal">0.00</td></tr>
              <tr><td>Discount</td><td><input class="form-control form-control-sm text-end text-danger" type="number" step="0.01" value="<?= (float)$editInvoice['total_discount'] ?>" id="manualTotalDiscount" name="manual_total_discount"></td></tr>
              <tr class="border-top border-200"><th>Grand Total</th><th class="text-end text-primary fs-9" id="summaryGrandTotal">0.00</th></tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
    <div class="card-header border-bottom border-200">
      <h6 class="mb-0">Payment Mode</h6>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12">
          <div class="d-flex flex-wrap gap-4">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_mode" id="pm_cash" value="cash" <?= $editInvoice['payment_mode'] === 'cash' ? 'checked' : '' ?>>
              <label class="form-check-label" for="pm_cash">
                <span class="fas fa-money-bill-wave me-1 text-success"></span>Cash (Full)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_mode" id="pm_partial" value="partial" <?= $editInvoice['payment_mode'] === 'partial' ? 'checked' : '' ?>>
              <label class="form-check-label" for="pm_partial">
                <span class="fas fa-clock me-1 text-warning"></span>Partial / Due / EMI
              </label>
            </div>
          </div>
        </div>

        <!-- Partial / Due Section -->
        <div class="col-12" id="partialSection" style="display: <?= $editInvoice['payment_mode'] === 'partial' ? 'block' : 'none' ?>;">
          <div class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
              <label class="form-label" for="cash_payment">Cash Payment <span class="text-danger">*</span></label>
              <input class="form-control" type="number" name="cash_payment" id="cash_payment" min="0" step="0.01" value="0.00" placeholder="Enter cash amount" oninput="ispts_onPartialChange();">
              <small class="text-600">Amount paid in cash immediately.</small>
            </div>

            <div class="col-md-3">
              <label class="form-label" for="next_installment_qty">Number of EMIs <span class="text-danger">*</span></label>
              <input class="form-control" type="number" name="next_installment_qty" id="next_installment_qty" min="1" value="1" placeholder="e.g. 3">
              <small class="text-600">Number of remaining payment installments.</small>
            </div>

            <div class="col-md-2">
              <label class="form-label" for="due_max">Due Max <span class="text-danger">*</span></label>
              <input class="form-control" type="number" name="due_max" id="due_max" min="1" value="30" placeholder="e.g. 30">
            </div>

            <div class="col-md-2">
              <label class="form-label">Interval</label>
              <div class="d-flex gap-1">
                <div class="form-check form-check-inline m-0">
                  <input class="form-check-input" type="radio" name="due_interval" id="due_days_radio" value="days" checked>
                  <label class="form-check-label fs-10" for="due_days_radio">Days</label>
                </div>
                <div class="form-check form-check-inline m-0">
                  <input class="form-check-input" type="radio" name="due_interval" id="due_months_radio" value="months">
                  <label class="form-check-label fs-10" for="due_months_radio">Months</label>
                </div>
              </div>
            </div>

            <div class="col-md-2" style="display:none;">
              <button type="button" class="btn btn-falcon-info btn-sm" id="generatePartialBtn">
                <span class="fas fa-magic me-1"></span>Generate Schedule
              </button>
            </div>
          </div>

          <div id="partialScheduleContainer"></div>
        </div>

        <!-- EMI Section -->
        <div class="col-12" id="emiSection" style="display:none;">
          <div class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
              <label class="form-label" for="emi_count">Number of EMIs <span class="text-danger">*</span></label>
              <input class="form-control" type="number" name="emi_count" id="emi_count" min="1" max="60" value="<?= (int)$editInvoice['emi_count'] ?: 3 ?>" placeholder="e.g. 6">
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-falcon-info btn-sm" id="generateEmiBtn">
                <span class="fas fa-magic me-1"></span>Generate Schedule
              </button>
            </div>
          </div>
          <div id="emiScheduleContainer"></div>
        </div>
      </div>
    </div>
  </div>

    <div class="card mb-3">
      <div class="card-header border-bottom border-200"><h6 class="mb-0">Notes & Reference</h6></div>
      <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Reference Employee</label>
                <input class="form-control" name="ref_employee" value="<?= htmlspecialchars((string)$editInvoice['ref_employee']) ?>" type="text">
            </div>
            <div class="col-md-6">
                <label class="form-label">Invoice Image</label>
                <input class="form-control" type="file" name="invoice_image" accept="image/*">
                <?php if ($editInvoice['invoice_image']): ?>
                    <div class="mt-2"><small class="text-success">Current image exists</small></div>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars((string)$editInvoice['notes']) ?></textarea>
            </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a class="btn btn-falcon-default" href="manage-stock.php">Cancel</a>
        <button class="btn btn-primary" type="submit">Update Invoice</button>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card mb-3">
      <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">General Info</h6>
        <input class="form-control form-control-sm" style="width: 140px;" name="invoice_date" type="date" value="<?= $editInvoice['invoice_date'] ?>" required>
      </div>
      <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Vendor</label>
            <select class="form-select" name="vendor_id" id="vendor_id" required>
                <?php foreach ($vendors as $v): ?>
                    <option value="<?= (int)$v['vendor_id'] ?>" <?= (int)$editInvoice['vendor_id'] === (int)$v['vendor_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$v['vendor_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="border rounded-2 p-2" style="background: #f9f9f9;">
            <div class="fw-bold mb-1 fs-10">Invoice Summary</div>
            <div class="d-flex justify-content-between fs-10"><span>Branch:</span><span id="previewBranch">-</span></div>
            <div class="d-flex justify-content-between fs-10"><span>Grand Total:</span><span class="fw-bold text-primary" id="previewGrand">0.00</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
</form>

<script>
(function() {
    'use strict';
    const PRODUCTS = <?= $jsProducts ?>;
    const EDIT_ITEMS = <?= $jsEditItems ?>;
    const EDIT_SERIALS = <?= $jsEditSerials ?>;
    const EDIT_PAYMENTS = <?= $jsEditPayments ?>;
    const EDIT_INVOICE = <?= $jsEditInvoice ?>;
    
    let rowCounter = 0;

    function esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function buildItemRowHtml(idx) {
        let opt = PRODUCTS.map(p => `<option value="${p.product_id}" data-unit="${esc(p.sub_category_unit_name)}">${esc(p.product_name)}</option>`).join('');
        return `
        <div class="item-row border-bottom p-3" data-idx="${idx}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fs-11">Product</label>
                    <select class="form-select form-select-sm js-product-search" name="items[${idx}][product_id]" id="product_${idx}" onchange="onProductChange(${idx})" required>
                        <option value="" disabled selected>Search Product...</option>
                        ${opt}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11">Brand</label>
                    <input class="form-control form-control-sm" name="items[${idx}][brand]" id="brand_${idx}">
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11">Qty</label>
                    <input class="form-control form-control-sm text-end" type="number" name="items[${idx}][quantity]" id="qty_${idx}" value="1" min="1" oninput="calcLine(${idx})">
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11">Unit Price</label>
                    <input class="form-control form-control-sm text-end" type="number" step="0.01" name="items[${idx}][unit_price]" id="price_${idx}" value="0.00" oninput="calcLine(${idx})">
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11">Discount</label>
                    <input class="form-control form-control-sm text-end text-danger" type="number" step="0.01" name="items[${idx}][discount_per_unit]" id="disc_${idx}" value="0.00" oninput="calcLine(${idx})">
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11">Warranty</label>
                    <input class="form-control form-control-sm" type="text" name="items[${idx}][warranty_period]" id="warranty_p_${idx}" placeholder="e.g. 1">
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11">Unit</label>
                    <select class="form-select form-select-sm" name="items[${idx}][warranty_unit]" id="warranty_u_${idx}">
                        <option value="Days">Days</option>
                        <option value="Months">Months</option>
                        <option value="Years" selected>Years</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1 align-items-center">
                    <div class="flex-grow-1 text-end">
                        <label class="form-label fs-11">Total</label>
                        <input class="form-control form-control-sm text-end bg-200 fw-bold" id="total_${idx}" value="0.00" readonly>
                    </div>
                    <button type="button" class="btn btn-link text-danger p-0 mt-3" onclick="removeRow(this)"><span class="fas fa-times-circle"></span></button>
                </div>
            </div>
            <div class="mt-2" id="serials_${idx}"></div>
        </div>`;
    }

    window.onProductChange = function(idx) {
        renderSerials(idx);
        updatePreview();
    };

    window.calcLine = function(idx) {
        let q = parseFloat(document.getElementById(`qty_${idx}`).value) || 0;
        let p = parseFloat(document.getElementById(`price_${idx}`).value) || 0;
        let d = parseFloat(document.getElementById(`disc_${idx}`).value) || 0;
        let total = (q * p) - (q * d);
        document.getElementById(`total_${idx}`).value = Math.max(0, total).toFixed(2);
        calcTotals();
        renderSerials(idx);
    };

    function calcTotals() {
        let sub = 0;
        let totalLineDisc = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            let idx = row.dataset.idx;
            let q = parseFloat(document.getElementById(`qty_${idx}`).value) || 0;
            let p = parseFloat(document.getElementById(`price_${idx}`).value) || 0;
            let d = parseFloat(document.getElementById(`disc_${idx}`).value) || 0;
            sub += q * p;
            totalLineDisc += q * d;
        });
        document.getElementById('summarySubtotal').textContent = sub.toFixed(2);
        let manualDisc = parseFloat(document.getElementById('manualTotalDiscount').value) || 0;
        let finalGrand = sub - totalLineDisc - manualDisc;
        document.getElementById('summaryGrandTotal').textContent = Math.max(0, finalGrand).toFixed(2);
        document.getElementById('previewGrand').textContent = Math.max(0, finalGrand).toFixed(2);
    }

    function isMeterProduct(idx) {
        let sel = document.getElementById(`product_${idx}`);
        if (!sel || sel.selectedIndex < 0) return false;
        let opt = sel.options[sel.selectedIndex];
        if (!opt) return false;
        let unit = String(opt.dataset.unit || '').toLowerCase().trim();
        return unit === 'meter';
    }

    function renderSerials(idx) {
        let q = parseInt(document.getElementById(`qty_${idx}`).value) || 0;
        let cont = document.getElementById(`serials_${idx}`);
        
        if (isMeterProduct(idx)) {
            cont.innerHTML = `
                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fs-11">Start Serial</label>
                        <input class="form-control form-control-sm" name="items[${idx}][serials][]" id="serial_start_${idx}" placeholder="Start range" oninput="updateMeterEnd(${idx})">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-11">End Serial (Auto)</label>
                        <input class="form-control form-control-sm bg-200" id="serial_end_${idx}" readonly placeholder="End range">
                        <input type="hidden" name="items[${idx}][serials][]" id="serial_end_val_${idx}">
                    </div>
                </div>
            `;
            updateMeterEnd(idx);
            return;
        }

        let html = '<div class="d-flex flex-wrap gap-1 mt-1">';
        for(let i=0; i<q; i++) {
            html += `<input class="form-control form-control-sm" style="width:120px" name="items[${idx}][serials][]" id="serial_${idx}_${i}" placeholder="S/N ${i+1}">`;
        }
        html += '</div>';
        cont.innerHTML = html || '<small class="text-500">No serials needed</small>';
    }

    window.updateMeterEnd = function(idx) {
        let start = parseInt(document.getElementById(`serial_start_${idx}`)?.value) || 0;
        let q = parseInt(document.getElementById(`qty_${idx}`)?.value) || 0;
        if (start > 0) {
            let end = start + q;
            document.getElementById(`serial_end_${idx}`).value = end;
            document.getElementById(`serial_end_val_${idx}`).value = end;
        }
    };

    window.removeRow = function(btn) {
        btn.closest('.item-row').remove();
        calcTotals();
        if(!document.querySelector('.item-row')) document.getElementById('noItemsPlaceholder').style.display = 'block';
    };

    document.getElementById('addItemBtn').addEventListener('click', function() {
        document.getElementById('noItemsPlaceholder').style.display = 'none';
        let idx = rowCounter++;
        document.getElementById('itemRowsContainer').insertAdjacentHTML('beforeend', buildItemRowHtml(idx));
        let sel = document.getElementById(`product_${idx}`);
        if(window.Choices) sel._choicesInstance = new window.Choices(sel, { searchEnabled: true, itemSelectText: '' });
        updatePreview();
    });

    function updatePreview() {
        let b = document.getElementById('branch_id');
        document.getElementById('previewBranch').textContent = b.options[b.selectedIndex]?.text || '-';
    }

    document.getElementById('manualTotalDiscount').addEventListener('input', calcTotals);
    document.getElementById('branch_id').addEventListener('change', updatePreview);
    
    document.querySelectorAll('input[name="payment_mode"]').forEach(r => {
        r.addEventListener('change', function() {
            document.getElementById('partialSection').style.display = this.value === 'partial' ? 'block' : 'none';
            document.getElementById('emiSection').style.display = this.value === 'emi' ? 'block' : 'none';
            
            if (this.value === 'partial') {
                ispts_generatePartialSchedule();
            }
            if (this.value === 'emi') {
                generateEmiSchedule();
            }
            updatePreview();
        });
    });

    function generateEmiSchedule() {
        const count = parseInt(document.getElementById('emi_count').value) || 1;
        const container = document.getElementById('emiScheduleContainer');
        const grandTotal = parseFloat(document.getElementById('summaryGrandTotal').textContent) || 0;
        const perInst = (grandTotal / count).toFixed(2);
        
        let html = '<table class="table table-sm fs-11"><thead><tr><th>Due Date</th><th>Amount</th><th>Note</th></tr></thead><tbody>';
        let d = new Date();
        for (let i = 1; i <= count; i++) {
            d.setMonth(d.getMonth() + 1);
            let dateStr = d.toISOString().split('T')[0];
            html += `<tr>
                <td><input type="date" class="form-control form-control-sm" name="emi_schedule[${i}][due_date]" value="${dateStr}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="emi_schedule[${i}][amount]" value="${perInst}"></td>
                <td><input type="text" class="form-control form-control-sm" name="emi_schedule[${i}][note]" value="EMI #${i}"></td>
            </tr>`;
        }
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    // ── PARTIAL / DUE SCHEDULE GENERATOR ──────────────────────────────────────────
    window.ispts_generatePartialSchedule = function () {
        const cashPay = parseFloat(document.getElementById('cash_payment')?.value || '0') || 0;
        const qty = parseInt(document.getElementById('next_installment_qty').value || '1', 10);
        if (qty <= 0) qty = 1;
        const dueMax = parseInt(document.getElementById('due_max').value || '30', 10);
        const interval = document.querySelector('input[name="due_interval"]:checked')?.value || 'days';
        const grandTotal = parseFloat(document.getElementById('summaryGrandTotal').textContent) || 0;
        
        const remaining = Math.max(0, grandTotal - cashPay);
        const perInstallment = qty > 0 ? remaining / qty : remaining;

        let html = '<div class="table-responsive"><table class="table table-sm fs-10 mb-0 border"><thead class="bg-body-tertiary"><tr><th style="width:36px">#</th><th>Payment Amount</th><th>Due Date</th></tr></thead><tbody>';
        
        let d = new Date();
        for (let i = 0; i < qty; i++) {
            if (interval === 'days') {
                d.setDate(d.getDate() + dueMax);
            } else {
                d.setMonth(d.getMonth() + dueMax);
            }
            const dueDateStr = d.toISOString().split('T')[0];
            const amt = i === qty - 1 ? remaining - (perInstallment * (qty - 1)) : perInstallment;
            
            html += '<tr>'
               + '<td class="text-center text-600" style="width:40px;">' + (i + 1) + '</td>'
               + '<td><input class="form-control form-control-sm text-end" type="number" step="0.01"'
               +   ' name="partial_schedule[' + i + '][amount]" value="' + amt.toFixed(2) + '" oninput="ispts_onPartialChange();"></td>'
               + '<td><input class="form-control form-control-sm" type="date"'
               +   ' name="partial_schedule[' + i + '][due_date]" value="' + dueDateStr + '"></td>'
               + '</tr>';
        }
        html += '</tbody></table></div>';
        
        document.getElementById('partialScheduleContainer').innerHTML = html;
        updatePreview();
    };

    window.ispts_onPartialChange = function () {
        updatePreview();
    };

    document.getElementById('generateEmiBtn').addEventListener('click', generateEmiSchedule);

    // Event listeners for partial payment fields
    ['cash_payment', 'next_installment_qty', 'due_max', 'due_days_radio', 'due_months_radio'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            if (id === 'cash_payment' || id === 'next_installment_qty' || id === 'due_max') {
                ispts_generatePartialSchedule();
            }
            updatePreview();
        });
        el.addEventListener('change', function () {
            if (id === 'cash_payment' || id === 'next_installment_qty' || id === 'due_max') {
                ispts_generatePartialSchedule();
            }
            updatePreview();
        });
    });

    // Prefill logic
    document.addEventListener('DOMContentLoaded', function() {
        if (EDIT_ITEMS.length > 0) {
            EDIT_ITEMS.forEach(item => {
                document.getElementById('addItemBtn').click();
                let idx = rowCounter - 1;
                
                let sel = document.getElementById(`product_${idx}`);
                // Initialize Choices.js first if available, then set value
                if(window.Choices && !sel._choicesInstance) {
                    sel._choicesInstance = new window.Choices(sel, { searchEnabled: true, itemSelectText: '' });
                }
                if (sel._choicesInstance) {
                    sel._choicesInstance.setChoiceByValue(String(item.product_id));
                } else {
                    sel.value = item.product_id;
                }
                
                document.getElementById(`brand_${idx}`).value = item.brand || '';
                document.getElementById(`qty_${idx}`).value = item.quantity;
                document.getElementById(`price_${idx}`).value = item.unit_price;
                document.getElementById(`disc_${idx}`).value = item.discount_per_unit || 0;
                
                if (item.warranty_period) {
                    let parts = item.warranty_period.split(' ');
                    document.getElementById(`warranty_p_${idx}`).value = parts[0] || '';
                    if (parts[1]) {
                        document.getElementById(`warranty_u_${idx}`).value = parts[1];
                    }
                }
                
                calcLine(idx);
                
                if (isMeterProduct(idx)) {
                    let srs = EDIT_SERIALS[item.item_id] || [];
                    if (srs[0]) {
                        document.getElementById(`serial_start_${idx}`).value = srs[0];
                        updateMeterEnd(idx);
                    }
                } else {
                    let serialInputs = document.querySelectorAll(`[name="items[${idx}][serials][]"]`);
                    let srs = EDIT_SERIALS[item.item_id] || [];
                    srs.forEach((s, si) => {
                        if(serialInputs[si]) serialInputs[si].value = s;
                    });
                }
            });
        }
        
        let mode = EDIT_INVOICE.payment_mode;
        document.querySelector(`input[name="payment_mode"][value="${mode}"]`).checked = true;
        document.querySelector(`input[name="payment_mode"][value="${mode}"]`).dispatchEvent(new Event('change'));
        
        if (mode === 'emi') {
            generateEmiSchedule();
            let rows = document.querySelectorAll('#emiScheduleContainer tbody tr');
            EDIT_PAYMENTS.forEach((p, pi) => {
                if(rows[pi]) {
                    rows[pi].querySelector('input[type="date"]').value = p.due_date;
                    rows[pi].querySelector('input[type="number"]').value = p.amount;
                    rows[pi].querySelector('input[type="text"]').value = p.payment_note || '';
                }
            });
        } else if (mode === 'partial') {
            ispts_generatePartialSchedule();
            if (EDIT_PAYMENTS.length > 0) {
                document.querySelector('[name="partial_schedule[0][due_date]"]').value = EDIT_PAYMENTS[0].due_date;
                document.querySelector('[name="partial_schedule[0][amount]"]').value = EDIT_PAYMENTS[0].amount;
            }
        }
        calcTotals();
        updatePreview();
    });
    
    // Add form submission validation
    document.getElementById('stockInvoiceForm').addEventListener('submit', function(e) {
        // Check if we have at least one item
        const itemRows = document.querySelectorAll('.item-row');
        if (itemRows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product item.');
            return false;
        }
        
        // Validate each item has a product selected
        let validItems = 0;
        itemRows.forEach((row, idx) => {
            const productSelect = document.getElementById(`product_${row.dataset.idx}`);
            if (productSelect && productSelect.value) {
                validItems++;
            }
        });
        
        if (validItems === 0) {
            e.preventDefault();
            alert('Please select products for all items.');
            return false;
        }
        
        // Validate EMI count if EMI mode is selected
        const paymentMode = document.querySelector('input[name="payment_mode"]:checked')?.value;
        if (paymentMode === 'emi') {
            const emiCount = document.getElementById('emi_count');
            if (!emiCount.value || parseInt(emiCount.value) < 1) {
                e.preventDefault();
                alert('Please enter a valid EMI count (minimum 1).');
                emiCount.focus();
                return false;
            }
        }
        
        return true;
    });
})();
</script>

<?php require '../../includes/footer.php'; ?>
