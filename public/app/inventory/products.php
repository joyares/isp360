<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_units (
        unit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        unit_name VARCHAR(120) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_inventory_units_name (unit_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_categories (
        category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(120) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_inventory_categories_name (category_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_sub_categories (
        sub_category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NOT NULL,
    unit_id BIGINT UNSIGNED DEFAULT NULL,
        sub_category_name VARCHAR(120) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_inventory_sub_categories_name (category_id, sub_category_name),
        KEY idx_inventory_sub_categories_category (category_id),
    KEY idx_inventory_sub_categories_unit (unit_id),
        CONSTRAINT fk_inventory_sub_categories_category FOREIGN KEY (category_id)
            REFERENCES inventory_categories(category_id)
      ON UPDATE CASCADE,
    CONSTRAINT fk_inventory_sub_categories_unit FOREIGN KEY (unit_id)
      REFERENCES inventory_units(unit_id)
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$ispts_has_column = static function (\PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
  );
  $stmt->bindValue(':table', $table);
  $stmt->bindValue(':column', $column);
  $stmt->execute();

  return (int) $stmt->fetchColumn() > 0;
};

if (!$ispts_has_column($pdo, 'inventory_sub_categories', 'unit_id')) {
  $pdo->exec('ALTER TABLE inventory_sub_categories ADD COLUMN unit_id BIGINT UNSIGNED DEFAULT NULL AFTER category_id');
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_products (
        product_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(180) NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        sub_category_id BIGINT UNSIGNED NOT NULL,
        unit_id BIGINT UNSIGNED NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_inventory_products_category (category_id),
        KEY idx_inventory_products_sub_category (sub_category_id),
        KEY idx_inventory_products_unit (unit_id),
        CONSTRAINT fk_inventory_products_category FOREIGN KEY (category_id)
            REFERENCES inventory_categories(category_id)
            ON UPDATE CASCADE,
        CONSTRAINT fk_inventory_products_sub_category FOREIGN KEY (sub_category_id)
            REFERENCES inventory_sub_categories(sub_category_id)
            ON UPDATE CASCADE,
        CONSTRAINT fk_inventory_products_unit FOREIGN KEY (unit_id)
            REFERENCES inventory_units(unit_id)
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/inventory/products.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_product') {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $productName = trim((string) ($_POST['product_name'] ?? ''));
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $subCategoryId = isset($_POST['sub_category_id']) ? (int) $_POST['sub_category_id'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($productName === '') {
            $alert = ['type' => 'danger', 'message' => 'Product name is required.'];
        } elseif ($categoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select a category.'];
        } elseif ($subCategoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select a sub category.'];
        } else {
          // Ensure selected sub category belongs to selected category and fetch mapped unit.
          $checkStmt = $pdo->prepare(
            'SELECT unit_id FROM inventory_sub_categories
             WHERE sub_category_id = :sub_category_id AND category_id = :category_id
             LIMIT 1'
          );
            $checkStmt->bindValue(':sub_category_id', $subCategoryId, \PDO::PARAM_INT);
            $checkStmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
            $checkStmt->execute();
          $mappedUnitId = (int) $checkStmt->fetchColumn();
          $isValidPair = $mappedUnitId > 0;

            if (!$isValidPair) {
            $alert = ['type' => 'danger', 'message' => 'Selected sub category must belong to selected category and have a unit.'];
            } else {
                if ($productId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE inventory_products
                         SET product_name = :product_name,
                             category_id = :category_id,
                             sub_category_id = :sub_category_id,
                             unit_id = :unit_id,
                             status = :status
                         WHERE product_id = :product_id'
                    );
                    $stmt->bindValue(':product_id', $productId, \PDO::PARAM_INT);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO inventory_products (product_name, category_id, sub_category_id, unit_id, status)
                         VALUES (:product_name, :category_id, :sub_category_id, :unit_id, :status)'
                    );
                }

                $stmt->bindValue(':product_name', $productName);
                $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
                $stmt->bindValue(':sub_category_id', $subCategoryId, \PDO::PARAM_INT);
                $stmt->bindValue(':unit_id', $mappedUnitId, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?saved=1');
                exit;
            }
        }
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Saved successfully.'];
}

$editProductId = isset($_GET['edit_product']) ? (int) $_GET['edit_product'] : 0;

$productForm = [
    'product_id' => 0,
    'product_name' => '',
    'category_id' => 0,
    'sub_category_id' => 0,
    'unit_id' => 0,
    'status' => 1,
];

if ($editProductId > 0) {
    $stmt = $pdo->prepare(
        'SELECT product_id, product_name, category_id, sub_category_id, unit_id, unit_price, status
         FROM inventory_products
         WHERE product_id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $editProductId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $productForm = [
            'product_id' => (int) $row['product_id'],
            'product_name' => (string) $row['product_name'],
            'category_id' => (int) $row['category_id'],
            'sub_category_id' => (int) $row['sub_category_id'],
            'unit_id' => (int) $row['unit_id'],
            'status' => (int) $row['status'],
        ];
    }
}

$categories = $pdo->query('SELECT category_id, category_name, status FROM inventory_categories ORDER BY category_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$subCategories = $pdo->query(
  'SELECT sc.sub_category_id, sc.category_id, sc.unit_id, sc.sub_category_name, sc.status, u.unit_name
   FROM inventory_sub_categories sc
   LEFT JOIN inventory_units u ON u.unit_id = sc.unit_id
   ORDER BY sc.sub_category_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$products = $pdo->query(
    'SELECT p.product_id, p.product_name, p.unit_price, p.status,
            c.category_name, sc.sub_category_name, u.unit_name
     FROM inventory_products p
     LEFT JOIN inventory_categories c ON c.category_id = p.category_id
     LEFT JOIN inventory_sub_categories sc ON sc.sub_category_id = p.sub_category_id
     LEFT JOIN inventory_units u ON u.unit_id = p.unit_id
     ORDER BY p.product_id DESC'
)->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/inventory/product-categories.php">Inventory</a></li>
    <li class="breadcrumb-item active">Products</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Products</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage products dynamically using Unit, Category, and Sub Category masters.</p>
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

  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0">Product List</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Product</th>
                <th class="text-800">Category</th>
                <th class="text-800">Sub Category</th>
                <th class="text-800">Unit</th>
                <th class="text-800">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center py-3 text-600">No products found.</td></tr>
              <?php else: ?>
                <?php foreach ($products as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/inventory/products.php?edit_product=<?= (int) $row['product_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                    </td>
                    <td><?= htmlspecialchars((string) $row['product_name']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['sub_category_name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['unit_name'] ?? '-')) ?></td>
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

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $productForm['product_id'] > 0 ? 'Update Product' : 'Add Product' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/inventory/products.php">
            <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_product">
          <input type="hidden" name="product_id" value="<?= (int) $productForm['product_id'] ?>">

          <div class="col-12">
            <label class="form-label" for="product-name">Product Name</label>
            <input class="form-control" id="product-name" name="product_name" type="text" value="<?= htmlspecialchars((string) $productForm['product_name']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label" for="product-category">Category</label>
            <select class="form-select" id="product-category" name="category_id" required>
              <option value="" disabled <?= (int) $productForm['category_id'] <= 0 ? 'selected' : '' ?>>Select Category</option>
              <?php foreach ($categories as $option): ?>
                <option value="<?= (int) $option['category_id'] ?>" <?= (int) $productForm['category_id'] === (int) $option['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $option['category_name']) ?><?= (int) $option['status'] === 0 ? ' (Off)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="product-sub-category">Sub Category</label>
            <select class="form-select" id="product-sub-category" name="sub_category_id" required>
              <option value="" disabled selected>Select Sub Category</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="product-unit">Unit</label>
            <input class="form-control" id="product-unit" type="text" value="" disabled readonly>
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label mb-0" for="product-status">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" id="product-status" type="checkbox" name="status" value="1" <?= (int) $productForm['status'] === 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/inventory/products.php">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit"><?= $productForm['product_id'] > 0 ? 'Update' : 'Add' ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var categorySelect = document.getElementById('product-category');
    var subCategorySelect = document.getElementById('product-sub-category');
    if (!categorySelect || !subCategorySelect) return;

    var subCategories = <?= json_encode($subCategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var selectedSubCategoryId = <?= (int) $productForm['sub_category_id'] ?>;
    var unitDisplayInput = document.getElementById('product-unit');

    function rebuildSubCategories() {
      var currentCategoryId = parseInt(categorySelect.value || '0', 10);
      var foundSelected = false;

      subCategorySelect.innerHTML = '';

      var placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.disabled = true;
      placeholder.textContent = 'Select Sub Category';
      subCategorySelect.appendChild(placeholder);

      if (unitDisplayInput) {
        unitDisplayInput.value = '';
      }

      subCategories.forEach(function (item) {
        if (parseInt(item.category_id, 10) !== currentCategoryId) return;

        var id = parseInt(item.sub_category_id, 10);
        var label = String(item.sub_category_name || '');
        if (parseInt(item.status, 10) === 0) {
          label += ' (Off)';
        }

        var option = document.createElement('option');
        option.value = String(id);
        option.textContent = label;
        option.setAttribute('data-unit-name', String(item.unit_name || ''));

        var isSelected = id === selectedSubCategoryId;
        if (isSelected) {
          option.selected = true;
          foundSelected = true;
        }

        subCategorySelect.appendChild(option);
      });

      if (!foundSelected) {
        subCategorySelect.value = '';
      } else {
        updateUnitFromSubCategory();
      }
    }

    function updateUnitFromSubCategory() {
      if (!unitDisplayInput) return;

      var selectedOption = subCategorySelect.options[subCategorySelect.selectedIndex] || null;
      if (!selectedOption || !selectedOption.value) {
        unitDisplayInput.value = '';
        return;
      }

      unitDisplayInput.value = selectedOption.getAttribute('data-unit-name') || '';
    }

    categorySelect.addEventListener('change', function () {
      selectedSubCategoryId = 0;
      rebuildSubCategories();
    });

    subCategorySelect.addEventListener('change', function () {
      updateUnitFromSubCategory();
    });

    rebuildSubCategories();
    updateUnitFromSubCategory();
  })();
</script>

<?php
require '../../includes/footer.php';
?>

