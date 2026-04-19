<?php

declare(strict_types=1);

require_once '../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();
ispts_ensure_customers_table($pdo);

$canManageCustomers = ispts_can_manage_customers();

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $customerId > 0;

$formData = [
    'username' => '',
    'phone_no' => '',
    'registered_date' => date('Y-m-d'),
    'address' => '',
    'area' => '',
    'sub_area' => '',
    'package_id' => '',
    'package_activate_date' => '',
    'package_expire_date' => '',
    'nid_other_documents' => '',
    'deposit_money' => '0.00',
    'connection_charge' => '0.00',
    'assigned_devices' => '',
    'support_ticket' => '',
    'documents' => '',
    'payment' => '',
    'invoices' => '',
    'notes' => '',
    'branch' => '',
];

$alert = null;

if ($isEdit) {
    $editStmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = :customer_id LIMIT 1');
    $editStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
    $editStmt->execute();
    $existing = $editStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        header('Location: customers.php');
        exit;
    }

    foreach ($formData as $key => $value) {
        if (array_key_exists($key, $existing)) {
            $formData[$key] = (string) ($existing[$key] ?? '');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $value) {
        $formData[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if (!$canManageCustomers) {
      $alert = ['type' => 'danger', 'message' => 'You do not have permission to register or update customers.'];
    } elseif ($formData['username'] === '' || $formData['phone_no'] === '' || $formData['registered_date'] === '') {
        $alert = ['type' => 'danger', 'message' => 'Username, Phone No, and Registered Date are required.'];
    } else {
        if ($isEdit) {
            $updateStmt = $pdo->prepare(
                'UPDATE customers SET
                    username = :username,
                    phone_no = :phone_no,
                    registered_date = :registered_date,
                    address = :address,
                    area = :area,
                    sub_area = :sub_area,
                    package_id = :package_id,
                    package_activate_date = :package_activate_date,
                    package_expire_date = :package_expire_date,
                    nid_other_documents = :nid_other_documents,
                    deposit_money = :deposit_money,
                    connection_charge = :connection_charge,
                    assigned_devices = :assigned_devices,
                    support_ticket = :support_ticket,
                    documents = :documents,
                    payment = :payment,
                    invoices = :invoices,
                    notes = :notes,
                    branch = :branch,
                    updated_by = :updated_by
                 WHERE customer_id = :customer_id'
            );
            $updateStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $updateStmt->bindValue(':updated_by', (int) ($_SESSION['admin_user_id'] ?? 0), PDO::PARAM_INT);
            $successQuery = 'updated';
            $stmt = $updateStmt;
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO customers (
                    username,
                    phone_no,
                    registered_date,
                    address,
                    area,
                    sub_area,
                    package_id,
                    package_activate_date,
                    package_expire_date,
                    nid_other_documents,
                    deposit_money,
                    connection_charge,
                    assigned_devices,
                    support_ticket,
                    documents,
                    payment,
                    invoices,
                    notes,
                    branch,
                    created_by,
                    updated_by,
                    status
                 ) VALUES (
                    :username,
                    :phone_no,
                    :registered_date,
                    :address,
                    :area,
                    :sub_area,
                    :package_id,
                    :package_activate_date,
                    :package_expire_date,
                    :nid_other_documents,
                    :deposit_money,
                    :connection_charge,
                    :assigned_devices,
                    :support_ticket,
                    :documents,
                    :payment,
                    :invoices,
                    :notes,
                    :branch,
                    :created_by,
                    :updated_by,
                    1
                 )'
            );
            $insertStmt->bindValue(':created_by', (int) ($_SESSION['admin_user_id'] ?? 0), PDO::PARAM_INT);
            $insertStmt->bindValue(':updated_by', (int) ($_SESSION['admin_user_id'] ?? 0), PDO::PARAM_INT);
            $successQuery = 'created';
            $stmt = $insertStmt;
        }

        $stmt->bindValue(':username', $formData['username']);
        $stmt->bindValue(':phone_no', $formData['phone_no']);
        $stmt->bindValue(':registered_date', $formData['registered_date']);
        $stmt->bindValue(':address', $formData['address'] !== '' ? $formData['address'] : null);
        $stmt->bindValue(':area', $formData['area'] !== '' ? $formData['area'] : null);
        $stmt->bindValue(':sub_area', $formData['sub_area'] !== '' ? $formData['sub_area'] : null);
        $stmt->bindValue(':package_id', $formData['package_id'] !== '' ? $formData['package_id'] : null);
        $stmt->bindValue(':package_activate_date', $formData['package_activate_date'] !== '' ? $formData['package_activate_date'] : null);
        $stmt->bindValue(':package_expire_date', $formData['package_expire_date'] !== '' ? $formData['package_expire_date'] : null);
        $stmt->bindValue(':nid_other_documents', $formData['nid_other_documents'] !== '' ? $formData['nid_other_documents'] : null);
        $stmt->bindValue(':deposit_money', (float) ($formData['deposit_money'] === '' ? '0' : $formData['deposit_money']));
        $stmt->bindValue(':connection_charge', (float) ($formData['connection_charge'] === '' ? '0' : $formData['connection_charge']));
        $stmt->bindValue(':assigned_devices', $formData['assigned_devices'] !== '' ? $formData['assigned_devices'] : null);
        $stmt->bindValue(':support_ticket', $formData['support_ticket'] !== '' ? $formData['support_ticket'] : null);
        $stmt->bindValue(':documents', $formData['documents'] !== '' ? $formData['documents'] : null);
        $stmt->bindValue(':payment', $formData['payment'] !== '' ? $formData['payment'] : null);
        $stmt->bindValue(':invoices', $formData['invoices'] !== '' ? $formData['invoices'] : null);
        $stmt->bindValue(':notes', $formData['notes'] !== '' ? $formData['notes'] : null);
        $stmt->bindValue(':branch', $formData['branch'] !== '' ? $formData['branch'] : null);

        $stmt->execute();

        if (!$isEdit) {
            $customerId = (int) $pdo->lastInsertId();
        }

        header('Location: customer-registration.php?id=' . $customerId . '&saved=' . $successQuery);
        exit;
    }
}

if ($alert === null && isset($_GET['saved'])) {
    if ($_GET['saved'] === 'created') {
        $alert = ['type' => 'success', 'message' => 'Customer registered successfully.'];
    }
    if ($_GET['saved'] === 'updated') {
        $alert = ['type' => 'success', 'message' => 'Customer updated successfully.'];
    }
}

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Support Desk</a></li>
    <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
    <li class="breadcrumb-item active"><?= $isEdit ? 'Edit Customer' : 'Customer Registration' ?></li>
  </ol>
</nav>
<div class="page-header mb-3 d-flex justify-content-between align-items-center">
  <h1 class="page-header-title mb-0"><?= $isEdit ? 'Edit Customer' : 'Customer Registration' ?></h1>
  <a href="customers.php" class="btn btn-falcon-default btn-sm">Back to Customer List</a>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header border-bottom border-200">
    <h5 class="mb-0">Customer Registration/Edit Form</h5>
  </div>
  <div class="card-body">
    <form class="row g-3" method="post" action="">
      <div class="col-md-4">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" type="text" value="<?= htmlspecialchars($formData['username']) ?>" required <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="phoneNo">Phone No</label>
        <input class="form-control" id="phoneNo" name="phone_no" type="text" value="<?= htmlspecialchars($formData['phone_no']) ?>" required <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="registeredDate">Registered Date</label>
        <input class="form-control" id="registeredDate" name="registered_date" type="date" value="<?= htmlspecialchars($formData['registered_date']) ?>" required <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="address">Address</label>
        <textarea class="form-control" id="address" name="address" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['address']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="area">Area</label>
        <input class="form-control" id="area" name="area" type="text" value="<?= htmlspecialchars($formData['area']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="subArea">Sub Area</label>
        <input class="form-control" id="subArea" name="sub_area" type="text" value="<?= htmlspecialchars($formData['sub_area']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="packageId">Package ID</label>
        <input class="form-control" id="packageId" name="package_id" type="text" value="<?= htmlspecialchars($formData['package_id']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="packageActivateDate">Package Activate Date</label>
        <input class="form-control" id="packageActivateDate" name="package_activate_date" type="date" value="<?= htmlspecialchars($formData['package_activate_date']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="packageExpireDate">Package Expire Date</label>
        <input class="form-control" id="packageExpireDate" name="package_expire_date" type="date" value="<?= htmlspecialchars($formData['package_expire_date']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="nidOtherDocuments">NID, Other Documents</label>
        <textarea class="form-control" id="nidOtherDocuments" name="nid_other_documents" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['nid_other_documents']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="depositMoney">Deposit Money</label>
        <input class="form-control" id="depositMoney" name="deposit_money" type="number" step="0.01" value="<?= htmlspecialchars($formData['deposit_money']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="connectionCharge">Connection Charge</label>
        <input class="form-control" id="connectionCharge" name="connection_charge" type="number" step="0.01" value="<?= htmlspecialchars($formData['connection_charge']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="assignedDevices">Assigned Devices</label>
        <textarea class="form-control" id="assignedDevices" name="assigned_devices" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['assigned_devices']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="supportTicket">Support Ticket</label>
        <textarea class="form-control" id="supportTicket" name="support_ticket" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['support_ticket']) ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="documents">Documents</label>
        <textarea class="form-control" id="documents" name="documents" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['documents']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="payment">Payment</label>
        <textarea class="form-control" id="payment" name="payment" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['payment']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="invoices">Invoices</label>
        <textarea class="form-control" id="invoices" name="invoices" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['invoices']) ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="2" <?= $canManageCustomers ? '' : 'disabled' ?>><?= htmlspecialchars($formData['notes']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="branch">Branch</label>
        <input class="form-control" id="branch" name="branch" type="text" value="<?= htmlspecialchars($formData['branch']) ?>" <?= $canManageCustomers ? '' : 'disabled' ?> />
      </div>

      <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3 mt-1">
        <button class="btn btn-falcon-default btn-sm" type="reset" <?= $canManageCustomers ? '' : 'disabled' ?>>Reset</button>
        <button class="btn btn-primary btn-sm" type="submit" <?= $canManageCustomers ? '' : 'disabled' ?>>
          <span class="fas fa-save me-1"></span><?= $isEdit ? 'Update Customer' : 'Register Customer' ?>
        </button>
      </div>
    </form>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>

