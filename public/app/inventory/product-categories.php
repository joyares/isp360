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

$unitCount = (int) $pdo->query('SELECT COUNT(*) FROM inventory_units')->fetchColumn();
if ($unitCount === 0) {
    $pdo->exec(
        "INSERT INTO inventory_units (unit_name, sort_order, status)
         VALUES
            ('Pcs', 10, 1),
            ('Box', 20, 1),
            ('Meter', 30, 1)"
    );
}

$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM inventory_categories')->fetchColumn();
if ($categoryCount === 0) {
    $pdo->exec(
        "INSERT INTO inventory_categories (category_name, sort_order, status)
         VALUES
            ('Networking', 10, 1),
            ('Fiber Equipment', 20, 1),
            ('Accessories', 30, 1)"
    );
}

$subCategoryCount = (int) $pdo->query('SELECT COUNT(*) FROM inventory_sub_categories')->fetchColumn();
if ($subCategoryCount === 0) {
    $networkingId = (int) $pdo->query("SELECT category_id FROM inventory_categories WHERE category_name = 'Networking' LIMIT 1")->fetchColumn();
  $defaultUnitId = (int) $pdo->query("SELECT unit_id FROM inventory_units WHERE unit_name = 'Pcs' LIMIT 1")->fetchColumn();
  if ($networkingId > 0 && $defaultUnitId > 0) {
        $stmt = $pdo->prepare(
      'INSERT INTO inventory_sub_categories (category_id, unit_id, sub_category_name, sort_order, status)
       VALUES (:category_id_one, :unit_id_one, :name_one, 10, 1), (:category_id_two, :unit_id_two, :name_two, 20, 1)'
        );
        $stmt->bindValue(':category_id_one', $networkingId, \PDO::PARAM_INT);
        $stmt->bindValue(':category_id_two', $networkingId, \PDO::PARAM_INT);
    $stmt->bindValue(':unit_id_one', $defaultUnitId, \PDO::PARAM_INT);
    $stmt->bindValue(':unit_id_two', $defaultUnitId, \PDO::PARAM_INT);
        $stmt->bindValue(':name_one', 'Router');
        $stmt->bindValue(':name_two', 'Switch');
        $stmt->execute();
    }
}

$alert = null;
$activeTab = (string) ($_GET['tab'] ?? 'unit');
if (!in_array($activeTab, ['unit', 'category', 'subcategory'], true)) {
    $activeTab = 'unit';
}

$currentPath = $_SERVER['PHP_SELF'] ?? '/app/inventory/product-categories.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_unit') {
        $unitId = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : 0;
        $name = trim((string) ($_POST['unit_name'] ?? ''));
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Unit name is required.'];
        } else {
            if ($unitId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE inventory_units
                     SET unit_name = :name, sort_order = :sort_order, status = :status
                     WHERE unit_id = :id'
                );
                $stmt->bindValue(':id', $unitId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO inventory_units (unit_name, sort_order, status)
                     VALUES (:name, :sort_order, :status)'
                );
            }

            try {
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?tab=unit&saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to save unit. Please use a unique name.'];
            }
        }

        $activeTab = 'unit';
    }

    if ($action === 'save_category') {
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $name = trim((string) ($_POST['category_name'] ?? ''));
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Category name is required.'];
        } else {
            if ($categoryId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE inventory_categories
                     SET category_name = :name, sort_order = :sort_order, status = :status
                     WHERE category_id = :id'
                );
                $stmt->bindValue(':id', $categoryId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO inventory_categories (category_name, sort_order, status)
                     VALUES (:name, :sort_order, :status)'
                );
            }

            try {
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?tab=category&saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to save category. Please use a unique name.'];
            }
        }

        $activeTab = 'category';
    }

    if ($action === 'save_subcategory') {
        $subCategoryId = isset($_POST['sub_category_id']) ? (int) $_POST['sub_category_id'] : 0;
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
      $unitId = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : 0;
        $name = trim((string) ($_POST['sub_category_name'] ?? ''));
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($categoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select a category for sub category.'];
      } elseif ($unitId <= 0) {
        $alert = ['type' => 'danger', 'message' => 'Please select a unit for sub category.'];
        } elseif ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Sub category name is required.'];
        } else {
            if ($subCategoryId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE inventory_sub_categories
             SET category_id = :category_id, unit_id = :unit_id, sub_category_name = :name, sort_order = :sort_order, status = :status
                     WHERE sub_category_id = :id'
                );
                $stmt->bindValue(':id', $subCategoryId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
            'INSERT INTO inventory_sub_categories (category_id, unit_id, sub_category_name, sort_order, status)
             VALUES (:category_id, :unit_id, :name, :sort_order, :status)'
                );
            }

            try {
                $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
          $stmt->bindValue(':unit_id', $unitId, \PDO::PARAM_INT);
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?tab=subcategory&saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to save sub category. Use a unique name within the selected category.'];
            }
        }

        $activeTab = 'subcategory';
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Saved successfully.'];
}

$editUnitId = isset($_GET['edit_unit']) ? (int) $_GET['edit_unit'] : 0;
$editCategoryId = isset($_GET['edit_category']) ? (int) $_GET['edit_category'] : 0;
$editSubCategoryId = isset($_GET['edit_subcategory']) ? (int) $_GET['edit_subcategory'] : 0;

$unitForm = ['unit_id' => 0, 'unit_name' => '', 'sort_order' => 0, 'status' => 1];
$categoryForm = ['category_id' => 0, 'category_name' => '', 'sort_order' => 0, 'status' => 1];
$subCategoryForm = ['sub_category_id' => 0, 'category_id' => 0, 'unit_id' => 0, 'sub_category_name' => '', 'sort_order' => 0, 'status' => 1];

if ($editUnitId > 0) {
    $stmt = $pdo->prepare('SELECT unit_id, unit_name, sort_order, status FROM inventory_units WHERE unit_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editUnitId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $unitForm = [
            'unit_id' => (int) $row['unit_id'],
            'unit_name' => (string) $row['unit_name'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'unit';
    }
}

if ($editCategoryId > 0) {
    $stmt = $pdo->prepare('SELECT category_id, category_name, sort_order, status FROM inventory_categories WHERE category_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editCategoryId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $categoryForm = [
            'category_id' => (int) $row['category_id'],
            'category_name' => (string) $row['category_name'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'category';
    }
}

if ($editSubCategoryId > 0) {
  $stmt = $pdo->prepare('SELECT sub_category_id, category_id, unit_id, sub_category_name, sort_order, status FROM inventory_sub_categories WHERE sub_category_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editSubCategoryId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $subCategoryForm = [
            'sub_category_id' => (int) $row['sub_category_id'],
            'category_id' => (int) $row['category_id'],
      'unit_id' => (int) ($row['unit_id'] ?? 0),
            'sub_category_name' => (string) $row['sub_category_name'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'subcategory';
    }
}

$units = $pdo->query('SELECT unit_id, unit_name, sort_order, status FROM inventory_units ORDER BY sort_order ASC, unit_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$categories = $pdo->query('SELECT category_id, category_name, sort_order, status FROM inventory_categories ORDER BY sort_order ASC, category_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

$subCategories = $pdo->query(
  'SELECT sc.sub_category_id, sc.sub_category_name, sc.category_id, sc.unit_id, sc.sort_order, sc.status, c.category_name, u.unit_name
     FROM inventory_sub_categories sc
     LEFT JOIN inventory_categories c ON c.category_id = sc.category_id
   LEFT JOIN inventory_units u ON u.unit_id = sc.unit_id
     ORDER BY sc.sort_order ASC, sc.sub_category_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$categoryOptions = $pdo->query('SELECT category_id, category_name, status FROM inventory_categories ORDER BY category_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/inventory/product-categories.php">Inventory</a></li>
    <li class="breadcrumb-item active">Product Categories</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Inventory Master</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage Units, Category, and Sub Category from one screen.</p>
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

  <div class="col-12">
    <ul class="nav nav-pills mb-3">
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'unit' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=unit">Units</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'category' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=category">Category</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'subcategory' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=subcategory">Sub Category</a></li>
    </ul>
  </div>

  <?php if ($activeTab === 'unit'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Units</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Unit Name</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($units)): ?>
                  <tr><td colspan="4" class="text-center py-3 text-600">No units found.</td></tr>
                <?php else: ?>
                  <?php foreach ($units as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=unit&edit_unit=<?= (int) $row['unit_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['unit_name']) ?></td>
                      <td><?= (int) $row['sort_order'] ?></td>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $unitForm['unit_id'] > 0 ? 'Update Unit' : 'Add Unit' ?></h6></div>
        <div class="card-body">
          <form class="row g-2" method="post" action="<?= $appBasePath ?>
            <?= ispts_csrf_field() ?>/app/inventory/product-categories.php?tab=unit">
            <input type="hidden" name="action" value="save_unit">
            <input type="hidden" name="unit_id" value="<?= (int) $unitForm['unit_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="unit-name">Unit Name</label>
              <input class="form-control" id="unit-name" name="unit_name" type="text" value="<?= htmlspecialchars((string) $unitForm['unit_name']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="unit-sort">Sort Order</label>
              <input class="form-control" id="unit-sort" name="sort_order" type="number" value="<?= (int) $unitForm['sort_order'] ?>">
            </div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0" for="unit-status">Status</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" id="unit-status" type="checkbox" name="status" value="1" <?= (int) $unitForm['status'] === 1 ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=unit">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $unitForm['unit_id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($activeTab === 'category'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Category</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Category Name</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                  <tr><td colspan="4" class="text-center py-3 text-600">No categories found.</td></tr>
                <?php else: ?>
                  <?php foreach ($categories as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=category&edit_category=<?= (int) $row['category_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['category_name']) ?></td>
                      <td><?= (int) $row['sort_order'] ?></td>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $categoryForm['category_id'] > 0 ? 'Update Category' : 'Add Category' ?></h6></div>
        <div class="card-body">
          <form class="row g-2" method="post" action="<?= $appBasePath ?>
            <?= ispts_csrf_field() ?>/app/inventory/product-categories.php?tab=category">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="category_id" value="<?= (int) $categoryForm['category_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="category-name">Category Name</label>
              <input class="form-control" id="category-name" name="category_name" type="text" value="<?= htmlspecialchars((string) $categoryForm['category_name']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="category-sort">Sort Order</label>
              <input class="form-control" id="category-sort" name="sort_order" type="number" value="<?= (int) $categoryForm['sort_order'] ?>">
            </div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0" for="category-status">Status</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" id="category-status" type="checkbox" name="status" value="1" <?= (int) $categoryForm['status'] === 1 ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=category">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $categoryForm['category_id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($activeTab === 'subcategory'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Sub Category</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Sub Category</th>
                  <th class="text-800">Category</th>
                  <th class="text-800">Unit</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($subCategories)): ?>
                  <tr><td colspan="6" class="text-center py-3 text-600">No sub categories found.</td></tr>
                <?php else: ?>
                  <?php foreach ($subCategories as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=subcategory&edit_subcategory=<?= (int) $row['sub_category_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['sub_category_name']) ?></td>
                      <td><?= htmlspecialchars((string) ($row['category_name'] ?? '-')) ?></td>
                      <td><?= htmlspecialchars((string) ($row['unit_name'] ?? '-')) ?></td>
                      <td><?= (int) $row['sort_order'] ?></td>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $subCategoryForm['sub_category_id'] > 0 ? 'Update Sub Category' : 'Add Sub Category' ?></h6></div>
        <div class="card-body">
          <form class="row g-2" method="post" action="<?= $appBasePath ?>
            <?= ispts_csrf_field() ?>/app/inventory/product-categories.php?tab=subcategory">
            <input type="hidden" name="action" value="save_subcategory">
            <input type="hidden" name="sub_category_id" value="<?= (int) $subCategoryForm['sub_category_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="sub-category-parent">Category</label>
              <select class="form-select" id="sub-category-parent" name="category_id" required>
                <option value="" disabled <?= (int) $subCategoryForm['category_id'] <= 0 ? 'selected' : '' ?>>Select Category</option>
                <?php foreach ($categoryOptions as $option): ?>
                  <option value="<?= (int) $option['category_id'] ?>" <?= (int) $subCategoryForm['category_id'] === (int) $option['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $option['category_name']) ?><?= (int) $option['status'] === 0 ? ' (Off)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="sub-category-unit">Unit</label>
              <select class="form-select" id="sub-category-unit" name="unit_id" required>
                <option value="" disabled <?= (int) $subCategoryForm['unit_id'] <= 0 ? 'selected' : '' ?>>Select Unit</option>
                <?php foreach ($units as $option): ?>
                  <option value="<?= (int) $option['unit_id'] ?>" <?= (int) $subCategoryForm['unit_id'] === (int) $option['unit_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $option['unit_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="sub-category-name">Sub Category Name</label>
              <input class="form-control" id="sub-category-name" name="sub_category_name" type="text" value="<?= htmlspecialchars((string) $subCategoryForm['sub_category_name']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="sub-category-sort">Sort Order</label>
              <input class="form-control" id="sub-category-sort" name="sort_order" type="number" value="<?= (int) $subCategoryForm['sort_order'] ?>">
            </div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0" for="sub-category-status">Status</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" id="sub-category-status" type="checkbox" name="status" value="1" <?= (int) $subCategoryForm['status'] === 1 ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/inventory/product-categories.php?tab=subcategory">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $subCategoryForm['sub_category_id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php
require '../../includes/footer.php';
?>

