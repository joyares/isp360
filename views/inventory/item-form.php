<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'rbac_helper.php';

$item = $item ?? [];
$isEdit = isset($item['item_id']);
$canEditPurchasePrice = has_permission('inventory_price_edit');
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="/app/inventory/products.php">Inventory</a></li>
    <li class="breadcrumb-item active"><?= $isEdit ? 'Edit Item' : 'Add Item' ?></li>
  </ol>
</nav>

<div class="page-header mb-4">
  <div class="row align-items-center">
    <div class="col-sm">
      <h1 class="page-header-title"><?= $isEdit ? 'Edit Inventory Item' : 'Add Inventory Item' ?></h1>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Inventory Item Form</h5>

    <form method="post" action="/app/inventory/save-item.php" enctype="multipart/form-data">
            <?= ispts_csrf_field() ?>
      <input type="hidden" name="item_id" value="<?= htmlspecialchars((string) ($item['item_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="item_name">Item Name</label>
          <input
            class="form-control"
            id="item_name"
            name="item_name"
            type="text"
            value="<?= htmlspecialchars((string) ($item['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label" for="sku">SKU</label>
          <input
            class="form-control"
            id="sku"
            name="sku"
            type="text"
            value="<?= htmlspecialchars((string) ($item['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label" for="purchase_price">Purchase Price</label>
          <input
            class="form-control"
            id="purchase_price"
            name="purchase_price"
            type="number"
            step="0.01"
            min="0"
            value="<?= htmlspecialchars((string) ($item['purchase_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            <?= $canEditPurchasePrice ? '' : 'readonly' ?>
          >
          <?php if (!$canEditPurchasePrice): ?>
            <small class="text-muted">You do not have permission to edit purchase price.</small>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="sale_price">Sale Price</label>
          <input
            class="form-control"
            id="sale_price"
            name="sale_price"
            type="number"
            step="0.01"
            min="0"
            value="<?= htmlspecialchars((string) ($item['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label" for="stock_qty">Stock Quantity</label>
          <input
            class="form-control"
            id="stock_qty"
            name="stock_qty"
            type="number"
            min="0"
            value="<?= htmlspecialchars((string) ($item['stock_qty'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
            required
          >
        </div>
      </div>

      <div class="mt-4">
        <button class="btn btn-primary" type="submit">
          <?= $isEdit ? 'Update Item' : 'Save Item' ?>
        </button>
      </div>
    </form>
  </div>
</div>
