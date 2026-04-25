<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_vendors (
        vendor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_name VARCHAR(180) NOT NULL,
        contact_person VARCHAR(180) DEFAULT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        email VARCHAR(180) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_inventory_vendors_name (vendor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$vendorCount = (int) $pdo->query('SELECT COUNT(*) FROM inventory_vendors')->fetchColumn();
if ($vendorCount === 0) {
    $pdo->exec(
        "INSERT INTO inventory_vendors (vendor_name, contact_person, phone, email, address, sort_order, status)
         VALUES
            ('ABC Supplies Ltd', 'Rahim', '01711000001', 'contact@abcsupplies.com', 'Dhaka', 10, 1),
            ('XYZ Trading Co', 'Karim', '01711000002', 'sales@xyztrading.com', 'Chattogram', 20, 1)"
    );
}

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/inventory/vendors-suppliers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_vendor') {
        $vendorId = isset($_POST['vendor_id']) ? (int) $_POST['vendor_id'] : 0;
        $vendorName = trim((string) ($_POST['vendor_name'] ?? ''));
        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $sortOrder = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($vendorName === '') {
            $alert = ['type' => 'danger', 'message' => 'Vendor/Supplier name is required.'];
        } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $alert = ['type' => 'danger', 'message' => 'Please enter a valid email address.'];
        } else {
            if ($vendorId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE inventory_vendors
                     SET vendor_name = :vendor_name,
                         contact_person = :contact_person,
                         phone = :phone,
                         email = :email,
                         address = :address,
                         sort_order = :sort_order,
                         status = :status
                     WHERE vendor_id = :vendor_id'
                );
                $stmt->bindValue(':vendor_id', $vendorId, \PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO inventory_vendors (vendor_name, contact_person, phone, email, address, sort_order, status)
                     VALUES (:vendor_name, :contact_person, :phone, :email, :address, :sort_order, :status)'
                );
            }

            try {
                $stmt->bindValue(':vendor_name', $vendorName);
                $stmt->bindValue(':contact_person', $contactPerson !== '' ? $contactPerson : null, \PDO::PARAM_STR);
                $stmt->bindValue(':phone', $phone !== '' ? $phone : null, \PDO::PARAM_STR);
                $stmt->bindValue(':email', $email !== '' ? $email : null, \PDO::PARAM_STR);
                $stmt->bindValue(':address', $address !== '' ? $address : null, \PDO::PARAM_STR);
                $stmt->bindValue(':sort_order', $sortOrder, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
                $stmt->execute();

                header('Location: ' . $currentPath . '?saved=1');
                exit;
            } catch (\PDOException $exception) {
                $alert = ['type' => 'danger', 'message' => 'Unable to save vendor. Please use a unique vendor name.'];
            }
        }
    }
}

if ($alert === null && isset($_GET['saved'])) {
    $alert = ['type' => 'success', 'message' => 'Saved successfully.'];
}

$editVendorId = isset($_GET['edit_vendor']) ? (int) $_GET['edit_vendor'] : 0;
$vendorForm = [
    'vendor_id' => 0,
    'vendor_name' => '',
    'contact_person' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'sort_order' => 0,
    'status' => 1,
];

if ($editVendorId > 0) {
    $stmt = $pdo->prepare(
        'SELECT vendor_id, vendor_name, contact_person, phone, email, address, sort_order, status
         FROM inventory_vendors
         WHERE vendor_id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $editVendorId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        $vendorForm = [
            'vendor_id' => (int) $row['vendor_id'],
            'vendor_name' => (string) $row['vendor_name'],
            'contact_person' => (string) ($row['contact_person'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'sort_order' => (int) $row['sort_order'],
            'status' => (int) $row['status'],
        ];
    }
}

$vendors = $pdo->query(
    'SELECT vendor_id, vendor_name, contact_person, phone, email, sort_order, status
     FROM inventory_vendors
     ORDER BY sort_order ASC, vendor_name ASC'
)->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/app/inventory/product-categories.php">Inventory</a></li>
    <li class="breadcrumb-item active">Vendors &amp; Suppliers</li>
  </ol>
</nav>

<div class="row gx-3 gy-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom border-200">
        <h5 class="mb-0">Vendors &amp; Suppliers</h5>
      </div>
      <div class="card-body">
        <p class="text-700 mb-0">Manage vendors and suppliers with add, update, and status on/off.</p>
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
      <div class="card-header border-bottom border-200"><h6 class="mb-0">Vendor List</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0">
            <thead class="bg-body-tertiary">
              <tr>
                <th class="text-800">Action</th>
                <th class="text-800">Vendor/Supplier</th>
                <th class="text-800">Contact Person</th>
                <th class="text-800">Phone</th>
                <th class="text-800">Email</th>
                <th class="text-800">Sort</th>
                <th class="text-800">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($vendors)): ?>
                <tr><td colspan="7" class="text-center py-3 text-600">No vendors found.</td></tr>
              <?php else: ?>
                <?php foreach ($vendors as $row): ?>
                  <tr>
                    <td>
                      <a class="btn btn-link p-0" href="<?= $appBasePath ?>/app/inventory/vendors-suppliers.php?edit_vendor=<?= (int) $row['vendor_id'] ?>" data-bs-toggle="tooltip" title="Edit"><span class="fas fa-edit text-500"></span></a>
                    </td>
                    <td><?= htmlspecialchars((string) $row['vendor_name']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['contact_person'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['phone'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['email'] ?? '-')) ?></td>
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
      <div class="card-header border-bottom border-200"><h6 class="mb-0"><?= $vendorForm['vendor_id'] > 0 ? 'Update Vendor/Supplier' : 'Add Vendor/Supplier' ?></h6></div>
      <div class="card-body">
        <form class="row g-2" method="post" action="<?= $appBasePath ?>/app/inventory/vendors-suppliers.php">
          <input type="hidden" name="action" value="save_vendor">
          <input type="hidden" name="vendor_id" value="<?= (int) $vendorForm['vendor_id'] ?>">

          <div class="col-12">
            <label class="form-label" for="vendor-name">Vendor/Supplier Name</label>
            <input class="form-control" id="vendor-name" name="vendor_name" type="text" value="<?= htmlspecialchars((string) $vendorForm['vendor_name']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label" for="contact-person">Contact Person</label>
            <input class="form-control" id="contact-person" name="contact_person" type="text" value="<?= htmlspecialchars((string) $vendorForm['contact_person']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" type="text" value="<?= htmlspecialchars((string) $vendorForm['phone']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars((string) $vendorForm['email']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars((string) $vendorForm['address']) ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label" for="sort-order">Sort Order</label>
            <input class="form-control" id="sort-order" name="sort_order" type="number" value="<?= (int) $vendorForm['sort_order'] ?>">
          </div>

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label mb-0" for="vendor-status">Status</label>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" id="vendor-status" type="checkbox" name="status" value="1" <?= (int) $vendorForm['status'] === 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-falcon-default btn-sm" href="<?= $appBasePath ?>/app/inventory/vendors-suppliers.php">Reset</a>
            <button class="btn btn-primary btn-sm" type="submit"><?= $vendorForm['vendor_id'] > 0 ? 'Update' : 'Add' ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
require '../../includes/footer.php';
?>

