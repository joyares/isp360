<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS locations (
        location_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(150) NOT NULL,
        location_type ENUM('district', 'thana-area', 'sub-area') NOT NULL,
        parent_location_id BIGINT UNSIGNED NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_locations_parent (parent_location_id),
        KEY idx_locations_type (location_type),
        CONSTRAINT fk_locations_parent
            FOREIGN KEY (parent_location_id)
            REFERENCES locations (location_id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$menuType = (string) ($_GET['type'] ?? 'district');
$allowedTypes = ['district', 'thana-area', 'sub-area'];
$requiredParentTypeByType = [
  'district' => null,
  'thana-area' => 'district',
  'sub-area' => 'thana-area',
];
$typeLabels = [
    'district' => 'District',
    'thana-area' => 'Thana/Area',
    'sub-area' => 'Sub area',
];
if (!in_array($menuType, $allowedTypes, true)) {
    $menuType = 'district';
}

$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/administration/locations.php';

$formData = [
    'location_id' => 0,
    'location_name' => '',
    'location_type' => $menuType,
    'parent_location_id' => null,
    'status' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ispts_csrf_validate();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_location') {
        $locationId = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        $locationName = trim((string) ($_POST['location_name'] ?? ''));
        $locationType = (string) ($_POST['location_type'] ?? 'district');
        $parentLocationId = isset($_POST['parent_location_id']) && $_POST['parent_location_id'] !== ''
            ? (int) $_POST['parent_location_id']
            : null;
        $status = isset($_POST['status']) ? 1 : 0;

        if (!in_array($locationType, $allowedTypes, true)) {
            $locationType = 'district';
        }

        $requiredParentType = $requiredParentTypeByType[$locationType] ?? null;
        if ($requiredParentType === null) {
          $parentLocationId = null;
        }

        if ($locationName === '') {
            $alert = ['type' => 'danger', 'message' => 'Location name is required.'];
        } elseif ($requiredParentType !== null && $parentLocationId === null) {
          $alert = ['type' => 'danger', 'message' => 'Parent location is required for ' . ($typeLabels[$locationType] ?? $locationType) . '.'];
        } elseif ($requiredParentType !== null && $locationId > 0 && $parentLocationId === $locationId) {
          $alert = ['type' => 'danger', 'message' => 'Location cannot be its own parent.'];
        } else {
          if ($requiredParentType !== null && $parentLocationId !== null) {
            $parentCheckStmt = $pdo->prepare(
              'SELECT location_type, status
               FROM locations
               WHERE location_id = :location_id
               LIMIT 1'
            );
            $parentCheckStmt->bindValue(':location_id', $parentLocationId, \PDO::PARAM_INT);
            $parentCheckStmt->execute();
            $parentLocation = $parentCheckStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$parentLocation || (int) ($parentLocation['status'] ?? 0) !== 1 || (string) ($parentLocation['location_type'] ?? '') !== $requiredParentType) {
              $alert = [
                'type' => 'danger',
                'message' => 'Invalid parent selected. ' . ($typeLabels[$locationType] ?? $locationType) . ' requires a parent of type ' . ($typeLabels[$requiredParentType] ?? $requiredParentType) . '.',
              ];
            }
          }

          if ($alert === null) {
            if ($locationId > 0) {
                $updateStmt = $pdo->prepare(
                    'UPDATE locations
                     SET location_name = :location_name,
                         location_type = :location_type,
                         parent_location_id = :parent_location_id,
                         status = :status
                     WHERE location_id = :location_id'
                );
                $updateStmt->bindValue(':location_name', $locationName);
                $updateStmt->bindValue(':location_type', $locationType);
                $updateStmt->bindValue(':parent_location_id', $parentLocationId, $parentLocationId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
                $updateStmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $updateStmt->bindValue(':location_id', $locationId, \PDO::PARAM_INT);
                $updateStmt->execute();

                header('Location: ' . $currentPath . '?type=' . urlencode($locationType) . '&saved=updated');
                exit;
            }

            $insertStmt = $pdo->prepare(
                'INSERT INTO locations (location_name, location_type, parent_location_id, status)
                 VALUES (:location_name, :location_type, :parent_location_id, :status)'
            );
            $insertStmt->bindValue(':location_name', $locationName);
            $insertStmt->bindValue(':location_type', $locationType);
            $insertStmt->bindValue(':parent_location_id', $parentLocationId, $parentLocationId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            $insertStmt->bindValue(':status', $status, \PDO::PARAM_INT);
            $insertStmt->execute();

            header('Location: ' . $currentPath . '?type=' . urlencode($locationType) . '&saved=created');
            exit;
          }
        }

        $formData = [
            'location_id' => $locationId,
            'location_name' => $locationName,
            'location_type' => $locationType,
            'parent_location_id' => $parentLocationId,
            'status' => $status,
        ];
    }

    if ($action === 'toggle_location_status') {
        $locationId = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        $targetStatus = isset($_POST['target_status']) && (int) $_POST['target_status'] === 1 ? 1 : 0;

        if ($locationId > 0) {
            $toggleStmt = $pdo->prepare('UPDATE locations SET status = :status WHERE location_id = :location_id');
            $toggleStmt->bindValue(':status', $targetStatus, \PDO::PARAM_INT);
            $toggleStmt->bindValue(':location_id', $locationId, \PDO::PARAM_INT);
            $toggleStmt->execute();
        }

        header('Location: ' . $currentPath . '?type=' . urlencode($menuType) . '&saved=status');
        exit;
    }
}

if ($alert === null) {
    $savedFlag = (string) ($_GET['saved'] ?? '');
    if ($savedFlag === 'created') {
        $alert = ['type' => 'success', 'message' => 'Location added successfully.'];
    } elseif ($savedFlag === 'updated') {
        $alert = ['type' => 'success', 'message' => 'Location updated successfully.'];
    } elseif ($savedFlag === 'status') {
        $alert = ['type' => 'success', 'message' => 'Location status updated successfully.'];
    }
}

if ($editId > 0) {
    $editStmt = $pdo->prepare(
        'SELECT location_id, location_name, location_type, parent_location_id, status
         FROM locations
         WHERE location_id = :location_id
         LIMIT 1'
    );
    $editStmt->bindValue(':location_id', $editId, \PDO::PARAM_INT);
    $editStmt->execute();
    $editRow = $editStmt->fetch(\PDO::FETCH_ASSOC);

    if ($editRow) {
        $formData = [
            'location_id' => (int) $editRow['location_id'],
            'location_name' => (string) $editRow['location_name'],
            'location_type' => (string) $editRow['location_type'],
            'parent_location_id' => $editRow['parent_location_id'] !== null ? (int) $editRow['parent_location_id'] : null,
            'status' => (int) $editRow['status'],
        ];
        $menuType = $formData['location_type'];
    }
}

$allLocationsStmt = $pdo->query(
    'SELECT l.location_id,
            l.location_name,
            l.location_type,
            l.parent_location_id,
            l.status,
            p.location_name AS parent_name
     FROM locations l
     LEFT JOIN locations p ON p.location_id = l.parent_location_id
     ORDER BY l.location_id DESC'
);
$allLocations = $allLocationsStmt->fetchAll(\PDO::FETCH_ASSOC);

$filteredLocations = array_values(array_filter(
    $allLocations,
    static fn(array $row): bool => (string) ($row['location_type'] ?? '') === $menuType
));

$formLocationType = (string) ($formData['location_type'] ?? 'district');
$requiredParentTypeForForm = $requiredParentTypeByType[$formLocationType] ?? null;

$parentOptions = array_values(array_filter(
    $allLocations,
  static function (array $row) use ($formData, $requiredParentTypeForForm): bool {
    if ($requiredParentTypeForForm === null) {
      return false;
    }

        if ((int) ($row['status'] ?? 0) !== 1) {
            return false;
        }

    if ((string) ($row['location_type'] ?? '') !== $requiredParentTypeForForm) {
      return false;
    }

        if ((int) ($formData['location_id'] ?? 0) > 0 && (int) $row['location_id'] === (int) $formData['location_id']) {
            return false;
        }

        return true;
    }
));

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item">Administration</li>
    <li class="breadcrumb-item active">Locations</li>
  </ol>
</nav>

<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Locations</h1>
    </div>
  </div>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-xl-2 col-lg-3">
    <div class="card h-100">
      <div class="card-body p-0">
        <div class="px-3 pt-3 pb-2 text-uppercase fw-semi-bold text-600 fs-11">Menu</div>
        <div class="list-group list-group-flush rounded-0">
          <a class="list-group-item list-group-item-action <?= $menuType === 'district' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/locations.php?type=district">District</a>
          <a class="list-group-item list-group-item-action <?= $menuType === 'thana-area' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/locations.php?type=thana-area">Thana/Area</a>
          <a class="list-group-item list-group-item-action <?= $menuType === 'sub-area' ? 'active' : '' ?>" href="<?= $appBasePath ?>/app/administration/locations.php?type=sub-area">Sub area</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-lg-6">
    <div class="card h-100">
      <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Location List (<?= htmlspecialchars($typeLabels[$menuType] ?? 'Location') ?>)</h5>
        <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/administration/locations.php?type=<?= urlencode($menuType) ?>">
          <span class="fas fa-sync-alt"></span>
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">ID</th>
                <th class="text-800">Location Name</th>
                <th class="text-800">Type</th>
                <th class="text-800">Parent</th>
                <th class="text-800 text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($filteredLocations)): ?>
                <tr>
                  <td colspan="6" class="text-center py-3 text-600">No locations found for <?= htmlspecialchars($typeLabels[$menuType] ?? 'selected type') ?>.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($filteredLocations as $location): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/administration/locations.php?type=<?= urlencode((string) $location['location_type']) ?>&edit_id=<?= (int) $location['location_id'] ?>#location-form" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                        <span class="fas fa-edit text-500"></span>
                      </a>
                      <form method="post" action="<?= $appBasePath ?>
            <?= ispts_csrf_field() ?>/app/administration/locations.php?type=<?= urlencode($menuType) ?>" class="d-inline ms-2 align-middle">
                        <input type="hidden" name="action" value="toggle_location_status" />
                        <input type="hidden" name="location_id" value="<?= (int) $location['location_id'] ?>" />
                        <input type="hidden" name="target_status" value="<?= (int) $location['status'] === 1 ? '0' : '1' ?>" />
                        <div class="form-check form-switch d-inline-flex m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Active/Inactive">
                          <input class="form-check-input" type="checkbox" id="locationStatusToggle<?= (int) $location['location_id'] ?>" name="status" value="1" <?= (int) $location['status'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                        </div>
                      </form>
                    </td>
                    <td><?= (int) $location['location_id'] ?></td>
                    <td><?= htmlspecialchars((string) $location['location_name']) ?></td>
                    <td><?= htmlspecialchars($typeLabels[(string) $location['location_type']] ?? (string) $location['location_type']) ?></td>
                    <td><?= htmlspecialchars((string) ($location['parent_name'] ?? '-')) ?></td>
                    <td class="text-center">
                      <?php if ((int) $location['status'] === 1): ?>
                        <span class="badge badge-subtle-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-subtle-danger">Inactive</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-2 col-lg-3">
    <div class="card h-100" id="location-form">
      <div class="card-header border-bottom border-200">
        <h6 class="mb-0">Location Add, Update form</h6>
      </div>
      <div class="card-body">
        <form class="row g-2" action="<?= $appBasePath ?>/app/administration/locations.php?type=<?= urlencode($menuType) ?>" method="post">
          <input type="hidden" name="action" value="save_location" />
          <input type="hidden" name="location_id" value="<?= (int) ($formData['location_id'] ?? 0) ?>" />

          <div class="col-12">
            <label class="form-label" for="locationName">Location Name</label>
            <input class="form-control" id="locationName" name="location_name" type="text" placeholder="Enter location" value="<?= htmlspecialchars((string) ($formData['location_name'] ?? '')) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label" for="locationType">Location Type</label>
            <input type="hidden" name="location_type" value="<?= htmlspecialchars((string) ($formData['location_type'] ?? $menuType)) ?>">
            <select class="form-select" id="locationType" disabled>
              <option value="district" <?= (string) ($formData['location_type'] ?? '') === 'district' ? 'selected' : '' ?>>District</option>
              <option value="thana-area" <?= (string) ($formData['location_type'] ?? '') === 'thana-area' ? 'selected' : '' ?>>Thana/Area</option>
              <option value="sub-area" <?= (string) ($formData['location_type'] ?? '') === 'sub-area' ? 'selected' : '' ?>>Sub area</option>
            </select>
            <small class="text-600">Type is controlled by selected left menu level.</small>
          </div>

          <div class="col-12">
            <label class="form-label" for="parentLocationId">Parent Location</label>
            <select class="form-select" id="parentLocationId" name="parent_location_id" <?= $requiredParentTypeForForm === null ? 'disabled' : '' ?>>
              <option value="" <?= (int) ($formData['parent_location_id'] ?? 0) === 0 ? 'selected' : '' ?>>
                <?= $requiredParentTypeForForm === null ? 'No parent required for District' : 'Select parent (required)' ?>
              </option>
              <?php foreach ($parentOptions as $parent): ?>
                <option value="<?= (int) $parent['location_id'] ?>" <?= (int) ($formData['parent_location_id'] ?? 0) === (int) $parent['location_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string) $parent['location_name']) ?> (<?= htmlspecialchars($typeLabels[(string) $parent['location_type']] ?? (string) $parent['location_type']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($requiredParentTypeForForm !== null): ?>
              <small class="text-600">Required parent type: <?= htmlspecialchars($typeLabels[$requiredParentTypeForForm] ?? $requiredParentTypeForForm) ?></small>
            <?php endif; ?>
          </div>

          <div class="col-12">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" id="locationStatus" name="status" type="checkbox" value="1" <?= (int) ($formData['status'] ?? 1) === 1 ? 'checked' : '' ?>>
              <label class="form-check-label" for="locationStatus">Active</label>
            </div>
          </div>

          <div class="col-12 d-grid gap-2">
            <button class="btn btn-primary btn-sm" type="submit"><?= (int) ($formData['location_id'] ?? 0) > 0 ? 'Update' : 'Save' ?></button>
            <?php if ((int) ($formData['location_id'] ?? 0) > 0): ?>
              <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/administration/locations.php?type=<?= urlencode($menuType) ?>">Cancel Edit</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>

