<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$invoiceRows = $pdo->query(
  'SELECT si.invoice_id, si.invoice_number, si.invoice_date, si.invoice_image,
      si.notes, si.payment_mode, si.subtotal, si.total_discount,
      si.grand_total, si.due_days, si.emi_count, si.ref_employee,
      si.created_by, si.created_at, v.vendor_name
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
            <th class="text-end">Subtotal</th>
            <th class="text-end">Discount</th>
            <th class="text-end pe-3">Grand Total</th>
            <th>Due Days</th>
            <th>EMI Count</th>
            <th>Reference</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$invoiceRows): ?>
            <tr>
              <td colspan="15" class="text-center text-600 py-4">No stock invoices found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($invoiceRows as $row): ?>
              <?php $invoiceId = (int) ($row['invoice_id'] ?? 0); ?>
              <?php $invoiceImage = trim((string) ($row['invoice_image'] ?? '')); ?>
              <?php $invoiceImageUrl = $invoiceImage !== '' ? ($appBasePath . '/assets/uploads/' . rawurlencode($invoiceImage)) : ''; ?>
              <tr>
                <td class="ps-3">
                  <a class="btn btn-link p-0 me-2 text-info" href="<?= $appBasePath ?>/app/inventory/add-stock.php?print_invoice=<?= $invoiceId ?>" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Show Invoice Preview">
                    <span class="fas fa-eye"></span>
                  </a>
                  <?php if ($invoiceImage !== ''): ?>
                    <button type="button"
                            class="btn btn-link p-0 me-2 text-warning js-show-invoice-image"
                            data-image-url="<?= htmlspecialchars($invoiceImageUrl, ENT_QUOTES, 'UTF-8') ?>"
                            data-invoice-no="<?= htmlspecialchars((string) ($row['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="View Uploaded Invoice Image">
                      <span class="fas fa-file-image"></span>
                    </button>
                  <?php else: ?>
                    <button type="button"
                            class="btn btn-link p-0 me-2 text-500"
                            disabled
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="No Invoice Image Uploaded">
                      <span class="fas fa-file-image"></span>
                    </button>
                  <?php endif; ?>
                  <a class="btn btn-link p-0 text-primary" href="<?= $appBasePath ?>/app/inventory/add-stock.php?print_invoice=<?= $invoiceId ?>&auto_print=1" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Print Invoice (A4) - ID: <?= $invoiceId ?>">
                    <span class="fas fa-print"></span>
                  </a>
                </td>
                <td><?= $invoiceId ?></td>
                <td><?= htmlspecialchars((string) ($row['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['invoice_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['vendor_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(strtoupper((string) ($row['payment_mode'] ?? 'cash')), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end"><?= number_format((float) ($row['subtotal'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_discount'] ?? 0), 2) ?></td>
                <td class="text-end pe-3"><?= number_format((float) ($row['grand_total'] ?? 0), 2) ?></td>
                <td><?= (int) ($row['due_days'] ?? 0) > 0 ? (int) $row['due_days'] : '-' ?></td>
                <td><?= (int) ($row['emi_count'] ?? 0) > 0 ? (int) $row['emi_count'] : '-' ?></td>
                <td><?= htmlspecialchars((string) ($row['ref_employee'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) ($row['created_by'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['notes'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="invoiceImageModal" tabindex="-1" aria-labelledby="invoiceImageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="invoiceImageModalLabel">Invoice Image</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="invoiceImagePreview" src="" alt="Invoice Image" class="img-fluid rounded border" style="max-height: 70vh; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var imageModalElement = document.getElementById('invoiceImageModal');
  var imagePreview = document.getElementById('invoiceImagePreview');
  var titleElement = document.getElementById('invoiceImageModalLabel');

  if (!imageModalElement || !imagePreview || !titleElement || typeof bootstrap === 'undefined') {
    return;
  }

  var imageModal = new bootstrap.Modal(imageModalElement);
  var triggerButtons = document.querySelectorAll('.js-show-invoice-image');

  triggerButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      var imageUrl = button.getAttribute('data-image-url') || '';
      var invoiceNo = button.getAttribute('data-invoice-no') || '';

      if (imageUrl === '') {
        return;
      }

      imagePreview.setAttribute('src', imageUrl);
      titleElement.textContent = invoiceNo !== '' ? 'Invoice Image - ' + invoiceNo : 'Invoice Image';
      imageModal.show();
    });
  });

  imageModalElement.addEventListener('hidden.bs.modal', function () {
    imagePreview.setAttribute('src', '');
  });
});
</script>

<?php
require '../../includes/footer.php';
?>

