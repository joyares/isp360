<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_categories (
        category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(120) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_categories_name (category_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_statuses (
        ticket_status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        status_name VARCHAR(120) NOT NULL,
        color VARCHAR(30) NOT NULL DEFAULT 'secondary',
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_statuses_name (status_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS support_ticket_priorities (
        priority_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        priority_name VARCHAR(120) NOT NULL,
        color VARCHAR(30) NOT NULL DEFAULT 'secondary',
        status TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_support_ticket_priorities_name (priority_name)
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

  if (!$ispts_has_column($pdo, 'support_ticket_statuses', 'color')) {
    $pdo->exec("ALTER TABLE support_ticket_statuses ADD COLUMN color VARCHAR(30) NOT NULL DEFAULT 'secondary' AFTER status_name");
  }

  if (!$ispts_has_column($pdo, 'support_ticket_priorities', 'color')) {
    $pdo->exec("ALTER TABLE support_ticket_priorities ADD COLUMN color VARCHAR(30) NOT NULL DEFAULT 'secondary' AFTER priority_name");
  }

$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_categories')->fetchColumn();
if ($categoryCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_categories (category_name, sort_order, status)
         VALUES
            ('Billing', 10, 1),
            ('Technical Issue', 20, 1),
            ('Service Interruption', 30, 1),
            ('Installation', 40, 1)"
    );
}

$statusCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_statuses')->fetchColumn();
if ($statusCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_statuses (status_name, color, sort_order, status)
         VALUES
            ('Opened', 'success', 10, 1),
            ('In Progress', 'primary', 20, 1),
            ('Pending', 'warning', 30, 1),
            ('Closed', 'secondary', 40, 1)"
    );
}

$priorityCount = (int) $pdo->query('SELECT COUNT(*) FROM support_ticket_priorities')->fetchColumn();
if ($priorityCount === 0) {
    $pdo->exec(
        "INSERT INTO support_ticket_priorities (priority_name, color, sort_order, status)
         VALUES
            ('Low', 'success', 10, 1),
            ('Medium', 'warning', 20, 1),
            ('High', 'danger', 30, 1),
            ('Critical', 'dark', 40, 1)"
    );
}

$colorOptions = [
    'success' => 'Green',
    'primary' => 'Blue',
    'warning' => 'Yellow',
    'danger' => 'Red',
    'info' => 'Teal',
    'secondary' => 'Gray',
    'dark' => 'Dark',
];

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/support-desk/ticket-mgmt.php';

$activeTab = (string) ($_GET['tab'] ?? 'category');
if (!in_array($activeTab, ['category', 'status', 'priority'], true)) {
    $activeTab = 'category';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    'UPDATE support_ticket_categories
                     SET category_name = :name, sort_order = :sort_order, status = :status
                     WHERE category_id = :id'
                );
                $stmt->bindValue(':id', $categoryId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO support_ticket_categories (category_name, sort_order, status)
                     VALUES (:name, :sort_order, :status)'
                );
            }

            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=category&saved=1');
            exit;
        }

        $activeTab = 'category';
    }

    if ($action === 'save_status') {
        $statusId = isset($_POST['ticket_status_id']) ? (int) $_POST['ticket_status_id'] : 0;
        $name = trim((string) ($_POST['status_name'] ?? ''));
        $color = (string) ($_POST['color'] ?? 'secondary');
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if (!isset($colorOptions[$color])) {
            $color = 'secondary';
        }

        if ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Status name is required.'];
        } else {
            if ($statusId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE support_ticket_statuses
                     SET status_name = :name, color = :color, sort_order = :sort_order, status = :status
                     WHERE ticket_status_id = :id'
                );
                $stmt->bindValue(':id', $statusId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO support_ticket_statuses (status_name, color, sort_order, status)
                     VALUES (:name, :color, :sort_order, :status)'
                );
            }

            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':color', $color);
            $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=status&saved=1');
            exit;
        }

        $activeTab = 'status';
    }

    if ($action === 'save_priority') {
        $priorityId = isset($_POST['priority_id']) ? (int) $_POST['priority_id'] : 0;
        $name = trim((string) ($_POST['priority_name'] ?? ''));
        $color = (string) ($_POST['color'] ?? 'secondary');
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if (!isset($colorOptions[$color])) {
            $color = 'secondary';
        }

        if ($name === '') {
            $alert = ['type' => 'danger', 'message' => 'Priority name is required.'];
        } else {
            if ($priorityId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE support_ticket_priorities
                     SET priority_name = :name, color = :color, sort_order = :sort_order, status = :status
                     WHERE priority_id = :id'
                );
                $stmt->bindValue(':id', $priorityId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO support_ticket_priorities (priority_name, color, sort_order, status)
                     VALUES (:name, :color, :sort_order, :status)'
                );
            }

            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':color', $color);
            $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
            $stmt->execute();

            header('Location: ' . $currentPath . '?tab=priority&saved=1');
            exit;
        }

        $activeTab = 'priority';
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Saved successfully.'];
}

$editCategoryId = isset($_GET['edit_category']) ? (int) $_GET['edit_category'] : 0;
$editStatusId = isset($_GET['edit_status']) ? (int) $_GET['edit_status'] : 0;
$editPriorityId = isset($_GET['edit_priority']) ? (int) $_GET['edit_priority'] : 0;

$categoryForm = ['category_id' => 0, 'category_name' => '', 'sort_order' => 0, 'status' => 1];
$statusForm = ['ticket_status_id' => 0, 'status_name' => '', 'color' => 'secondary', 'sort_order' => 0, 'status' => 1];
$priorityForm = ['priority_id' => 0, 'priority_name' => '', 'color' => 'secondary', 'sort_order' => 0, 'status' => 1];

if ($editCategoryId > 0) {
    $stmt = $pdo->prepare('SELECT category_id, category_name, sort_order, status FROM support_ticket_categories WHERE category_id = :id LIMIT 1');
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

if ($editStatusId > 0) {
    $stmt = $pdo->prepare('SELECT ticket_status_id, status_name, color, sort_order, status FROM support_ticket_statuses WHERE ticket_status_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editStatusId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $statusForm = [
            'ticket_status_id' => (int) $row['ticket_status_id'],
            'status_name' => (string) $row['status_name'],
            'color' => (string) $row['color'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'status';
    }
}

if ($editPriorityId > 0) {
    $stmt = $pdo->prepare('SELECT priority_id, priority_name, color, sort_order, status FROM support_ticket_priorities WHERE priority_id = :id LIMIT 1');
    $stmt->bindValue(':id', $editPriorityId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $priorityForm = [
            'priority_id' => (int) $row['priority_id'],
            'priority_name' => (string) $row['priority_name'],
            'color' => (string) $row['color'],
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
        $activeTab = 'priority';
    }
}

$categories = $pdo->query('SELECT category_id, category_name, sort_order, status FROM support_ticket_categories ORDER BY sort_order ASC, category_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$statuses = $pdo->query('SELECT ticket_status_id, status_name, color, sort_order, status FROM support_ticket_statuses ORDER BY sort_order ASC, status_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$priorities = $pdo->query('SELECT priority_id, priority_name, color, sort_order, status FROM support_ticket_priorities ORDER BY sort_order ASC, priority_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Ticket Mgmt</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage ticket categories, statuses, and priorities from one place.</p>
      </div>
    </div>
  </div>

  <?php if ($alert): ?>
    <div class="col-12">
      <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
        <?= htmlspecialchars($alert['message']) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12">
    <ul class="nav nav-pills mb-3">
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'category' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=category">Categories</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'status' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=status">Statuses</a></li>
      <li class="nav-item"><a class="nav-link <?= $activeTab === 'priority' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=priority">Priorities</a></li>
    </ul>
  </div>

  <?php if ($activeTab === 'category'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Ticket Categories</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Category</th>
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
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=category&edit_category=<?= (int) $row['category_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['category_name']) ?></td>
                      <td><?= (int) $row['sort_order'] ?></td>
                      <td><?= (int) $row['status'] === 1 ? '<span class="badge badge-subtle-success">Active</span>' : '<span class="badge badge-subtle-danger">Off</span>' ?></td>
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
          <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=category">
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
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=category">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $categoryForm['category_id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($activeTab === 'status'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Ticket Statuses</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Status</th>
                  <th class="text-800">Color</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($statuses)): ?>
                  <tr><td colspan="5" class="text-center py-3 text-600">No statuses found.</td></tr>
                <?php else: ?>
                  <?php foreach ($statuses as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=status&edit_status=<?= (int) $row['ticket_status_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['status_name']) ?></td>
                      <td><span class="badge badge-subtle-<?= htmlspecialchars((string) $row['color']) ?>"><?= htmlspecialchars($colorOptions[(string) $row['color']] ?? (string) $row['color']) ?></span></td>
                      <td><?= (int) $row['sort_order'] ?></td>
                      <td><?= (int) $row['status'] === 1 ? '<span class="badge badge-subtle-success">Active</span>' : '<span class="badge badge-subtle-danger">Off</span>' ?></td>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $statusForm['ticket_status_id'] > 0 ? 'Update Status' : 'Add Status' ?></h6></div>
        <div class="card-body">
          <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=status">
            <input type="hidden" name="action" value="save_status">
            <input type="hidden" name="ticket_status_id" value="<?= (int) $statusForm['ticket_status_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="status-name">Status Name</label>
              <input class="form-control" id="status-name" name="status_name" type="text" value="<?= htmlspecialchars((string) $statusForm['status_name']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="status-color">Color</label>
              <select class="form-select" id="status-color" name="color">
                <?php foreach ($colorOptions as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= (string) $statusForm['color'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="status-sort">Sort Order</label>
              <input class="form-control" id="status-sort" name="sort_order" type="number" value="<?= (int) $statusForm['sort_order'] ?>">
            </div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0" for="status-enabled">Status</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" id="status-enabled" type="checkbox" name="status" value="1" <?= (int) $statusForm['status'] === 1 ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=status">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $statusForm['ticket_status_id'] > 0 ? 'Update' : 'Add' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($activeTab === 'priority'): ?>
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header border-bottom border-200"><h6 class="mb-0">Ticket Priorities</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-sm fs-10 mb-0">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-800">Action</th>
                  <th class="text-800">Priority</th>
                  <th class="text-800">Color</th>
                  <th class="text-800">Sort</th>
                  <th class="text-800">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($priorities)): ?>
                  <tr><td colspan="5" class="text-center py-3 text-600">No priorities found.</td></tr>
                <?php else: ?>
                  <?php foreach ($priorities as $row): ?>
                    <tr>
                      <td>
                        <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=priority&edit_priority=<?= (int) $row['priority_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                      </td>
                      <td><?= htmlspecialchars((string) $row['priority_name']) ?></td>
                      <td><span class="badge badge-subtle-<?= htmlspecialchars((string) $row['color']) ?>"><?= htmlspecialchars($colorOptions[(string) $row['color']] ?? (string) $row['color']) ?></span></td>
                      <td><?= (int) $row['sort_order'] ?></td>
                      <td><?= (int) $row['status'] === 1 ? '<span class="badge badge-subtle-success">Active</span>' : '<span class="badge badge-subtle-danger">Off</span>' ?></td>
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
        <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $priorityForm['priority_id'] > 0 ? 'Update Priority' : 'Add Priority' ?></h6></div>
        <div class="card-body">
          <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=priority">
            <input type="hidden" name="action" value="save_priority">
            <input type="hidden" name="priority_id" value="<?= (int) $priorityForm['priority_id'] ?>">
            <div class="col-12">
              <label class="form-label" for="priority-name">Priority Name</label>
              <input class="form-control" id="priority-name" name="priority_name" type="text" value="<?= htmlspecialchars((string) $priorityForm['priority_name']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="priority-color">Color</label>
              <select class="form-select" id="priority-color" name="color">
                <?php foreach ($colorOptions as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= (string) $priorityForm['color'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="priority-sort">Sort Order</label>
              <input class="form-control" id="priority-sort" name="sort_order" type="number" value="<?= (int) $priorityForm['sort_order'] ?>">
            </div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0" for="priority-enabled">Status</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" id="priority-enabled" type="checkbox" name="status" value="1" <?= (int) $priorityForm['status'] === 1 ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/support-desk/ticket-mgmt.php?tab=priority">Reset</a>
              <button class="btn btn-primary btn-sm" type="submit"><?= $priorityForm['priority_id'] > 0 ? 'Update' : 'Add' ?></button>
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
