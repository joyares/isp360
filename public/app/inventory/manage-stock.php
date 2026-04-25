<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$invoiceRows = $pdo->query(
    'SELECT si.invoice_id, si.invoice_number, si.invoice_date, si.payment_mode,
            si.grand_total, si.created_at, v.vendor_name
     FROM inventory_stock_invoices si
     LEFT JOIN inventory_vendors v ON v.vendor_id = si.vendor_id
     WHERE si.status = 1
     ORDER BY si.invoice_id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/inventory/product-categories.php">Inventory</a></li>
    <li class="breadcrumb-item active">Manage Stock</li>
  </ol>
</nav>

<div class="page-header mb-4">
  <div class="row align-items-center">
    <div class="col-sm">
      <h1 class="page-header-title">Manage Stock</h1>
    </div>
    <div class="col-sm-auto">
      <a class="btn btn-primary btn-sm" href="<?= $appBasePath ?>/app/inventory/add-stock.php">
        <span class="fas fa-plus me-1"></span>Add Stock Invoice
      </a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom border-200">
    <h6 class="mb-0">Stock Invoice List</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm fs-10 mb-0">
        <thead class="bg-200 text-800">
          <tr>
            <th class="ps-3">Action</th>
            <th>Invoice ID</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Vendor</th>
            <th>Payment</th>
            <th class="text-end pe-3">Grand Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$invoiceRows): ?>
            <tr>
              <td colspan="7" class="text-center text-600 py-4">No stock invoices found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($invoiceRows as $row): ?>
              <?php $invoiceId = (int) ($row['invoice_id'] ?? 0); ?>
              <tr>
                <td class="ps-3">
                  <a class="btn btn-link p-0 me-2 text-info" href="<?= $appBasePath ?>/app/inventory/add-stock.php?print_invoice=<?= $invoiceId ?>" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Show Invoice Preview">
                    <span class="fas fa-eye"></span>
                  </a>
                  <a class="btn btn-link p-0 text-primary" href="<?= $appBasePath ?>/app/inventory/add-stock.php?print_invoice=<?= $invoiceId ?>&auto_print=1" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Print Invoice (A4) - ID: <?= $invoiceId ?>">
                    <span class="fas fa-print"></span>
                  </a>
                </td>
                <td><?= $invoiceId ?></td>
                <td><?= htmlspecialchars((string) ($row['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['invoice_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['vendor_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(strtoupper((string) ($row['payment_mode'] ?? 'cash')), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end pe-3"><?= number_format((float) ($row['grand_total'] ?? 0), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
require '../../includes/footer.php';
?>

