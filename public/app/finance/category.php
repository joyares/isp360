<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();

// Create Tables if not exists
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

// Optional: initial seed data if table is empty
$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_categories')->fetchColumn();
if ($categoryCount === 0) {
    $pdo->exec(
        "INSERT INTO expense_categories (category_name, sort_order, status)
         VALUES
            ('Office Setup', 10, 1),
            ('Marketing', 20, 1),
            ('Operational', 30, 1)"
    );
}

$alert = null;
$activeTab = (string) ($_GET['tab'] ?? 'category');
if (!in_array($activeTab, ['category', 'subcategory'], true)) {
    $activeTab = 'category';
}

$currentPath = $_SERVER['PHP_SELF'] ?? '/app/finance/category.php';

// Form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

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
                    'UPDATE expense_categories
                     SET category_name = :name, sort_order = :sort_order, status = :status
                     WHERE category_id = :id'
                );
                $stmt->bindValue(':id', $categoryId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO expense_categories (category_name, sort_order, status)
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
        $name = trim((string) ($_POST['sub_category_name'] ?? ''));
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($categoryId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Please select a category for sub category.'];
        } elseif ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Sub category name is required.'];
        } else {
            if ($subCategoryId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE expense_sub_categories
                     SET category_id = :category_id, sub_category_name = :name, sort_order = :sort_order, status = :status
                     WHERE sub_category_id = :id'
                );
                $stmt->bindValue(':id', $subCategoryId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO expense_sub_categories (category_id, sub_category_name, sort_order, status)
                     VALUES (:category_id, :name, :sort_order, :status)'
                );
            }

            try {
                $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
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

$editCategoryId = isset($_GET['edit_category']) ? (int) $_GET['edit_category'] : 0;
$editSubCategoryId = isset($_GET['edit_subcategory']) ? (int) $_GET['edit_subcategory'] : 0;

$categoryForm = ['category_id' => 0, 'category_name' => '', 'sort_order' => 0, 'status' => 1];
$subCategoryForm = ['sub_category_id' => 0, 'category_id' => 0, 'sub_category_name' => '', 'sort_order' => 0, 'status' => 1];

if ($editCategoryId > 0) {
    $stmt = $pdo->prepare('SELECT category_id, category_name, sort_order, status FROM expense_categories WHERE category_id = :id LIMIT 1');
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
    $stmt = $pdo->prepare('SELECT sub_category_id, category_id, sub_category_name, sort_order, status FROM expense_sub_categories WHERE sub_category_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editSubCategoryId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $subCategoryForm = [
            'sub_category_id' => (int) $row['sub_category_id'],
            'category_id' => (int) $row['category_id'],
            'sub_category_name' => (string) $row['sub_category_name'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'subcategory';
    }
}

$categories = $pdo->query('SELECT category_id, category_name, sort_order, status FROM expense_categories ORDER BY sort_order ASC, category_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

$subCategories = $pdo->query(
    'SELECT sc.sub_category_id, sc.sub_category_name, sc.category_id, sc.sort_order, sc.status, c.category_name
     FROM expense_sub_categories sc
     LEFT JOIN expense_categories c ON c.category_id = sc.category_id
     ORDER BY sc.sort_order ASC, sc.sub_category_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

$pageTitle = 'Expense Categories';
require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Finance</a></li>
    <li class="breadcrumb-item active">Categories</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Finance Master</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage Expense Categories and Sub Categories from one screen.</p>
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
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'category' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/finance/category.php?tab=category">Expense Categories</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'subcategory' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/finance/category.php?tab=subcategory">Expense Sub-Cat</a></li>
    </ul>
  </div>

  <?php if ($activeTab === 'category'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Expense Categories</h6></div>
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
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/category.php?tab=category&edit_category=<?= (int) $row['category_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
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
          <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/category.php?tab=category">
            <?= ispts_csrf_field() ?>
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
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/category.php?tab=category">Reset</a>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Expense Sub-Cat</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Sub-Category Name</th>
                  <th class="text-800">Category</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($subCategories)): ?>
                  <tr><td colspan="5" class="text-center py-3 text-600">No sub categories found.</td></tr>
                <?php else: ?>
                  <?php foreach ($subCategories as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/finance/category.php?tab=subcategory&edit_subcategory=<?= (int) $row['sub_category_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['sub_category_name']) ?></td>
                      <td><?= htmlspecialchars((string) ($row['category_name'] ?? '-')) ?></td>
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
          <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/finance/category.php?tab=subcategory">
            <?= ispts_csrf_field() ?>
            <input type="hidden" name="action" value="save_subcategory">
            <input type="hidden" name="sub_category_id" value="<?= (int) $subCategoryForm['sub_category_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="sub-category-parent">Category</label>
              <select class="form-select" id="sub-category-parent" name="category_id" required>
                <option value="" disabled <?= (int) $subCategoryForm['category_id'] <= 0 ? 'selected' : '' ?>>Select Category</option>
                <?php foreach ($categories as $option): ?>
                  <option value="<?= (int) $option['category_id'] ?>" <?= (int) $subCategoryForm['category_id'] === (int) $option['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $option['category_name']) ?><?= (int) $option['status'] === 0 ? ' (Off)' : '' ?>
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
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/finance/category.php?tab=subcategory">Reset</a>
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
