<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

// Keep schema in sync for this screen without requiring external migrations.
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS roles (
        role_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(100) NOT NULL,
        role_slug VARCHAR(120) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_roles_role_slug (role_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

  $columnCheckStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = :table_name
       AND COLUMN_NAME = :column_name'
  );

  $ensureColumn = static function (\PDO $pdo, \PDOStatement $columnCheckStmt, string $columnName, string $definition): void {
    $columnCheckStmt->execute([
      ':table_name' => 'roles',
      ':column_name' => $columnName,
    ]);

    $exists = (int) $columnCheckStmt->fetchColumn() > 0;
    if (!$exists) {
      $pdo->exec('ALTER TABLE roles ADD COLUMN ' . $definition);
    }
  };

    try {
      $ensureColumn($pdo, $columnCheckStmt, 'role_type', 'role_type VARCHAR(60) NULL AFTER role_name');
      $ensureColumn($pdo, $columnCheckStmt, 'role_description', 'role_description TEXT NULL AFTER role_type');
      $ensureColumn($pdo, $columnCheckStmt, 'menu_tree_json', 'menu_tree_json JSON NULL AFTER role_description');
    } catch (\Throwable $ignoredSchemaSyncError) {
      // Continue with available columns instead of crashing the page.
    }

    $availableColumnsStmt = $pdo->query('SHOW COLUMNS FROM roles');
    $availableColumns = [];
    foreach ($availableColumnsStmt->fetchAll(PDO::FETCH_ASSOC) as $columnMeta) {
      $fieldName = (string) ($columnMeta['Field'] ?? '');
      if ($fieldName !== '') {
        $availableColumns[$fieldName] = true;
      }
    }

    $hasRoleTypeColumn = isset($availableColumns['role_type']);
    $hasRoleDescriptionColumn = isset($availableColumns['role_description']);
    $hasMenuTreeJsonColumn = isset($availableColumns['menu_tree_json']);
    $hasCreatedAtColumn = isset($availableColumns['created_at']);

function ispts_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'role';
}

function ispts_unique_role_slug(\PDO $pdo, string $baseSlug, ?int $excludeRoleId = null): string
{
    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $sql = 'SELECT role_id FROM roles WHERE role_slug = :role_slug';
        if ($excludeRoleId !== null) {
            $sql .= ' AND role_id != :exclude_role_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':role_slug', $slug);
        if ($excludeRoleId !== null) {
            $stmt->bindValue(':exclude_role_id', $excludeRoleId, \PDO::PARAM_INT);
        }
        $stmt->execute();

        $exists = $stmt->fetchColumn();
        if (!$exists) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/roles.php';
$editRoleId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$savedFlag = $_GET['saved'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_role') {
        $incomingRoleId = isset($_POST['edit_role_id']) ? (int) $_POST['edit_role_id'] : 0;
        $roleName = trim((string) ($_POST['role_name'] ?? ''));
        $roleType = trim((string) ($_POST['role_type'] ?? ''));
        $status = isset($_POST['status']) ? 1 : 0;
        $roleDescription = trim((string) ($_POST['role_description'] ?? ''));
        $menuAccess = isset($_POST['menu_access']) && is_array($_POST['menu_access'])
            ? array_values(array_unique(array_map(static function ($item): string {
                return trim((string) $item);
            }, $_POST['menu_access'])))
            : [];

        if ($roleName === '' || $roleType === '') {
            $alert = ['type' => 'danger', 'message' => 'Role Name and Role Type are required.'];
            $editRoleId = $incomingRoleId;
        } else {
          // No forced restore for Super Daddy, allow all updates to persist

            $baseSlug = ispts_slugify($roleName);
            $roleSlug = ispts_unique_role_slug($pdo, $baseSlug, $incomingRoleId > 0 ? $incomingRoleId : null);
            $menuTreeJson = json_encode($menuAccess, JSON_UNESCAPED_SLASHES);

            if ($incomingRoleId > 0) {
              $updateSetParts = [
                'role_name = :role_name',
                'role_slug = :role_slug',
                'status = :status',
              ];

              if ($hasRoleTypeColumn) {
                $updateSetParts[] = 'role_type = :role_type';
              }
              if ($hasRoleDescriptionColumn) {
                $updateSetParts[] = 'role_description = :role_description';
              }
              if ($hasMenuTreeJsonColumn) {
                $updateSetParts[] = 'menu_tree_json = :menu_tree_json';
              }

              $updateStmt = $pdo->prepare(
                'UPDATE roles
                 SET ' . implode(', ', $updateSetParts) . '
                 WHERE role_id = :role_id'
              );
                $updateStmt->bindValue(':role_name', $roleName);
                $updateStmt->bindValue(':role_slug', $roleSlug);
              if ($hasRoleTypeColumn) {
                $updateStmt->bindValue(':role_type', $roleType);
              }
              if ($hasRoleDescriptionColumn) {
                $updateStmt->bindValue(':role_description', $roleDescription);
              }
              if ($hasMenuTreeJsonColumn) {
                $updateStmt->bindValue(':menu_tree_json', $menuTreeJson);
              }
                $updateStmt->bindValue(':status', $status, PDO::PARAM_INT);
                $updateStmt->bindValue(':role_id', $incomingRoleId, PDO::PARAM_INT);
                $updateStmt->execute();

                header('Location: ' . $currentPath . '?edit=' . $incomingRoleId . '&saved=updated');
                exit;
            }

            $insertColumns = ['role_name', 'role_slug', 'status'];
            $insertValues = [':role_name', ':role_slug', ':status'];
            if ($hasRoleTypeColumn) {
              $insertColumns[] = 'role_type';
              $insertValues[] = ':role_type';
            }
            if ($hasRoleDescriptionColumn) {
              $insertColumns[] = 'role_description';
              $insertValues[] = ':role_description';
            }
            if ($hasMenuTreeJsonColumn) {
              $insertColumns[] = 'menu_tree_json';
              $insertValues[] = ':menu_tree_json';
            }

            $insertStmt = $pdo->prepare(
              'INSERT INTO roles (' . implode(', ', $insertColumns) . ')
               VALUES (' . implode(', ', $insertValues) . ')'
            );
            $insertStmt->bindValue(':role_name', $roleName);
            $insertStmt->bindValue(':role_slug', $roleSlug);
            if ($hasRoleTypeColumn) {
              $insertStmt->bindValue(':role_type', $roleType);
            }
            if ($hasRoleDescriptionColumn) {
              $insertStmt->bindValue(':role_description', $roleDescription);
            }
            if ($hasMenuTreeJsonColumn) {
              $insertStmt->bindValue(':menu_tree_json', $menuTreeJson);
            }
            $insertStmt->bindValue(':status', $status, PDO::PARAM_INT);
            $insertStmt->execute();

            $newRoleId = (int) $pdo->lastInsertId();
            header('Location: ' . $currentPath . '?edit=' . $newRoleId . '&saved=created');
            exit;
        }
    }

    if ($action === 'toggle_role_status') {
      $targetRoleId = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
      $targetStatus = isset($_POST['target_status']) ? (int) $_POST['target_status'] : 0;
      $targetStatus = $targetStatus === 1 ? 1 : 0;

      if ($targetRoleId > 0) {
        $roleNameStmt = $pdo->prepare('SELECT role_name FROM roles WHERE role_id = :role_id LIMIT 1');
        $roleNameStmt->bindValue(':role_id', $targetRoleId, \PDO::PARAM_INT);
        $roleNameStmt->execute();
        $targetRoleName = (string) ($roleNameStmt->fetchColumn() ?: '');

        if (strcasecmp($targetRoleName, 'Super Daddy') !== 0) {
          $toggleStmt = $pdo->prepare('UPDATE roles SET status = :status WHERE role_id = :role_id');
          $toggleStmt->bindValue(':status', $targetStatus, \PDO::PARAM_INT);
          $toggleStmt->bindValue(':role_id', $targetRoleId, \PDO::PARAM_INT);
          $toggleStmt->execute();
        }
      }

      header('Location: ' . $currentPath . '?saved=status');
        exit;
    }
}

$editRole = null;
$selectedMenuKeys = [];
if ($editRoleId > 0) {
  $editSelectParts = [
    'role_id',
    'role_name',
    'status',
  ];
  $editSelectParts[] = $hasRoleTypeColumn ? 'role_type' : "'' AS role_type";
  $editSelectParts[] = $hasRoleDescriptionColumn ? 'role_description' : "'' AS role_description";
  $editSelectParts[] = $hasMenuTreeJsonColumn ? 'menu_tree_json' : "'[]' AS menu_tree_json";

    $editStmt = $pdo->prepare(
    'SELECT ' . implode(', ', $editSelectParts) . '
         FROM roles
         WHERE role_id = :role_id
         LIMIT 1'
    );
    $editStmt->bindValue(':role_id', $editRoleId, PDO::PARAM_INT);
    $editStmt->execute();
    $editRole = $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($editRole) {
        $decodedMenus = json_decode((string) ($editRole['menu_tree_json'] ?? '[]'), true);
        $selectedMenuKeys = is_array($decodedMenus) ? array_values(array_unique($decodedMenus)) : [];
    } else {
        $alert = ['type' => 'warning', 'message' => 'Selected role was not found.'];
        $editRoleId = 0;
    }
}

if ($alert === null) {
    if ($savedFlag === 'created') {
        $alert = ['type' => 'success', 'message' => 'Role has been created successfully.'];
    } elseif ($savedFlag === 'updated') {
        $alert = ['type' => 'success', 'message' => 'Role has been updated successfully.'];
    } elseif ($savedFlag === 'status') {
      $alert = ['type' => 'success', 'message' => 'Role status has been updated successfully.'];
    }
}

$formValues = [
    'role_name' => $editRole['role_name'] ?? '',
    'role_type' => $editRole['role_type'] ?? '',
    'status' => isset($editRole['status']) ? (int) $editRole['status'] : 0,
    'role_description' => $editRole['role_description'] ?? '',
    'edit_role_id' => $editRole['role_id'] ?? 0,
];

  $isSuperDaddyRole = false; // Always allow editing for Super Daddy

$listStmt = $pdo->query(
  'SELECT role_id,
      role_name,
      ' . ($hasRoleTypeColumn ? 'role_type' : "''") . ' AS role_type,
      ' . ($hasRoleDescriptionColumn ? 'role_description' : "''") . ' AS role_description,
      status,
      ' . ($hasCreatedAtColumn ? 'created_at' : 'NULL') . ' AS created_at
     FROM roles
     ORDER BY role_id DESC'
);
$roles = $listStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Roles</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Roles</h1>
    </div>
  </div>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Role List</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">#</th>
                <th class="text-800">Name</th>
                <th class="text-800">Role Type</th>
                <th class="text-800">Status</th>
                <th class="text-800">Added Date</th>
                <th class="text-800">Description</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($roles)): ?>
                <tr>
                  <td colspan="7" class="text-center py-3 text-600">No roles found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($roles as $index => $role): ?>
                  <?php $isEditingRow = $editRoleId > 0 && (int) $role['role_id'] === $editRoleId; ?>
                  <tr class="<?= $isEditingRow ? 'table-primary' : '' ?>">
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/administration/roles.php?edit=<?= (int) $role['role_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                        <span class="fas fa-edit <?= $isEditingRow ? 'text-primary' : 'text-500' ?>"></span>
                      </a>
                      <form method="post" class="d-inline ms-2 align-middle">
            <?= ispts_csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_role_status" />
                        <input type="hidden" name="role_id" value="<?= (int) $role['role_id'] ?>" />
                        <input type="hidden" name="target_status" value="<?= (int) $role['status'] === 1 ? '0' : '1' ?>" />
                        <div class="form-check form-switch d-inline-flex ms-2 m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                          <input class="form-check-input" type="checkbox" id="roleStatus<?= (int) $role['role_id'] ?>" name="status" value="1" <?= (int) $role['status'] === 1 ? 'checked' : '' ?> <?= strcasecmp((string) ($role['role_name'] ?? ''), 'Super Daddy') === 0 ? 'disabled' : '' ?> onchange="this.form.submit()">
                        </div>
                      </form>
                    </td>
                    <td><?= (int) ($index + 1) ?></td>
                    <td><?= htmlspecialchars((string) $role['role_name']) ?></td>
                    <td><?= htmlspecialchars((string) ($role['role_type'] ?: '-')) ?></td>
                    <td>
                      <?php if ((int) $role['status'] === 1): ?>
                        <span class="badge badge-subtle-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-danger">Inactive</span>
                      <?php endif; ?>
                      <?php if ($isEditingRow): ?>
                        <span class="badge badge-subtle-primary ms-1">Selected</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($role['created_at'])): ?>
                        <?= htmlspecialchars(date('Y-m-d', strtotime((string) $role['created_at']))) ?>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($role['role_description'] ?: '-')) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0"><?= $editRole ? 'Edit Role Form' : 'Create Role Form' ?></h5>
      </div>
      <div class="card-body">
        <form class="row g-3" action="" method="post">
            <?= ispts_csrf_field() ?>
          <input type="hidden" name="action" value="save_role" />
          <input type="hidden" name="edit_role_id" value="<?= (int) $formValues['edit_role_id'] ?>" />

          <div class="col-lg-4">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="roleName">Role Name</label>
                <input
                  class="form-control form-control-sm"
                  id="roleName"
                  name="role_name"
                  type="text"
                  placeholder="Enter role name"
                  value="<?= htmlspecialchars((string) $formValues['role_name']) ?>"
                  required
                />
              </div>

              <div class="col-12">
                <label class="form-label" for="roleType">Role Type</label>
                <select class="form-select form-select-sm" id="roleType" name="role_type" required>
                  <option value="" disabled <?= $formValues['role_type'] === '' ? 'selected' : '' ?>>Select Role Type</option>
                  <?php
                  $roleTypeOptions = [
                      'super_admin' => 'Super Admin',
                      'admin' => 'Admin',
                      'staff' => 'Staff',
                      'manager' => 'Manager',
                      'partner' => 'Partner',
                  ];
                  foreach ($roleTypeOptions as $optionValue => $optionLabel):
                  ?>
                    <option value="<?= $optionValue ?>" <?= $formValues['role_type'] === $optionValue ? 'selected' : '' ?>><?= $optionLabel ?></option>
                  <?php endforeach; ?>
                </select>

              </div>

              <div class="col-12">
                <label class="form-label d-block">Role Status</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="roleStatus" name="status" value="1" <?= (int) $formValues['status'] === 1 ? 'checked' : '' ?> />
                  <label class="form-check-label fs-10" for="roleStatus">Active</label>
                </div>

              </div>

              <div class="col-12">
                <label class="form-label" for="roleDescription">Description</label>
                <textarea class="form-control form-control-sm" id="roleDescription" name="role_description" rows="3" placeholder="Enter description"><?= htmlspecialchars((string) $formValues['role_description']) ?></textarea>
              </div>

              <div class="col-12 d-flex justify-content-end gap-2">
                <a href="<?= $appBasePath ?>/app/administration/roles.php" class="btn btn-falcon-default btn-sm">Clear</a>
                <button class="btn btn-primary btn-sm" type="submit">
                  <span class="fas fa-save me-1"></span><?= $editRole ? 'Update Role' : 'Save Role' ?>
                </button>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label d-block mb-0" for="selectMenusMaster">Select Menus</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="selectMenusMaster" />
              </div>
            </div>
            <div id="roleMenuTree" class="pt-1">
              <div class="text-600 fs-10">Loading menus...</div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var treeContainer = document.getElementById('roleMenuTree');
    var navRoot = document.getElementById('navbarVerticalNav');
    var masterToggle = document.getElementById('selectMenusMaster');
    if (!treeContainer || !navRoot || !masterToggle) return;

    var selectedMenuKeys = <?= json_encode(array_values(array_unique($selectedMenuKeys)), JSON_UNESCAPED_SLASHES) ?>;
    var lockMenuToggles = false;

    var toSlug = function (value) {
      return (value || '')
        .toLowerCase()
        .replace(/\.php$/i, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    };

    var getNodeLabel = function (link) {
      var textNode = link.querySelector('.nav-link-text');
      if (textNode) {
        return textNode.textContent.trim();
      }
      return link.textContent.trim();
    };

    var getNodeKey = function (link, fallbackLabel) {
      var href = link.getAttribute('href') || '';
      if (href && href.charAt(0) !== '#') {
        var path = href.split('?')[0].split('#')[0];
        var parts = path.split('/').filter(Boolean);
        var last = parts.length ? parts[parts.length - 1] : fallbackLabel;
        return toSlug(last || fallbackLabel);
      }
      return toSlug(fallbackLabel);
    };

    var readTree = function (ulElement) {
      var items = [];
      var childLis = ulElement.querySelectorAll(':scope > li.nav-item');
      childLis.forEach(function (li) {
        var link = li.querySelector(':scope > a.nav-link');
        if (!link) return;

        var label = getNodeLabel(link);
        if (!label) return;

        var childUl = li.querySelector(':scope > ul.nav');
        var children = childUl ? readTree(childUl) : [];

        items.push({
          label: label,
          key: getNodeKey(link, label),
          children: children
        });
      });
      return items;
    };

    var renderTree = function (nodes, depth) {
      var ul = document.createElement('ul');
      ul.className = depth > 0 ? 'list-unstyled mb-0 ms-3 ps-2 border-start fs-10' : 'list-unstyled mb-0 fs-10';

      nodes.forEach(function (node, index) {
        var li = document.createElement('li');
        li.className = 'mb-1';

        var row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between gap-2';

        var left = document.createElement('div');
        left.className = 'd-flex align-items-center gap-1';

        var childrenWrap = null;
        if (node.children.length > 0) {
          var toggle = document.createElement('button');
          toggle.type = 'button';
          toggle.className = 'btn btn-link p-0 text-decoration-none text-700';
          toggle.textContent = '+';
          toggle.setAttribute('aria-label', 'Expand ' + node.label);
          left.appendChild(toggle);

          childrenWrap = document.createElement('div');
          childrenWrap.style.display = 'none';
          childrenWrap.appendChild(renderTree(node.children, depth + 1));

          toggle.addEventListener('click', function () {
            var isOpen = childrenWrap.style.display !== 'none';
            childrenWrap.style.display = isOpen ? 'none' : 'block';
            toggle.textContent = isOpen ? '+' : '-';
          });
        } else {
          var spacer = document.createElement('span');
          spacer.className = 'd-inline-block';
          spacer.style.width = '10px';
          left.appendChild(spacer);
        }

        var label = document.createElement('span');
        label.className = 'small';
        label.textContent = node.label;
        left.appendChild(label);

        var switchWrap = document.createElement('div');
        switchWrap.className = 'form-check form-switch m-0';

        var input = document.createElement('input');
        input.className = 'form-check-input';
        input.type = 'checkbox';
        input.name = 'menu_access[]';
        input.value = node.key;
        input.id = 'menu_dynamic_' + node.key + '_' + depth + '_' + index;
        input.checked = selectedMenuKeys.indexOf(node.key) !== -1;
        input.disabled = lockMenuToggles;

        var inputLabel = document.createElement('label');
        inputLabel.className = 'visually-hidden';
        inputLabel.setAttribute('for', input.id);
        inputLabel.textContent = node.label;

        switchWrap.appendChild(input);
        switchWrap.appendChild(inputLabel);

        row.appendChild(left);
        row.appendChild(switchWrap);
        li.appendChild(row);

        if (childrenWrap) {
          var childSwitches = childrenWrap.querySelectorAll('input.form-check-input');
          input.addEventListener('change', function () {
            childSwitches.forEach(function (childSwitch) {
              childSwitch.checked = input.checked;
            });
          });

          if (!input.checked) {
            childSwitches.forEach(function (childSwitch) {
              childSwitch.checked = false;
            });
          }

          li.appendChild(childrenWrap);
        }

        ul.appendChild(li);
      });

      return ul;
    };

    var sourceNodes = readTree(navRoot);
    treeContainer.innerHTML = '';
    treeContainer.appendChild(renderTree(sourceNodes, 0));

    var getAllMenuSwitches = function () {
      return treeContainer.querySelectorAll('input.form-check-input[name="menu_access[]"]');
    };

    var syncMasterToggleState = function () {
      var menuSwitches = getAllMenuSwitches();
      if (!menuSwitches.length) {
        masterToggle.checked = false;
        return;
      }

      var allChecked = true;
      menuSwitches.forEach(function (menuSwitch) {
        if (!menuSwitch.checked) {
          allChecked = false;
        }
      });

      masterToggle.checked = allChecked;
    };

    masterToggle.addEventListener('change', function () {
      if (lockMenuToggles) return;
      var checked = masterToggle.checked;
      var menuSwitches = getAllMenuSwitches();
      menuSwitches.forEach(function (menuSwitch) {
        menuSwitch.checked = checked;
      });
    });

    treeContainer.addEventListener('change', function (event) {
      var target = event.target;
      if (!target || !target.matches('input.form-check-input[name="menu_access[]"]')) return;
      syncMasterToggleState();
    });

    syncMasterToggleState();

    if (lockMenuToggles) {
      masterToggle.disabled = true;
    }
  })();
</script>
<?php
require '../../includes/footer.php';
?>
