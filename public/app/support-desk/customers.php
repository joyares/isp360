<?php

declare(strict_types=1);

require_once '../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();
ispts_ensure_customers_table($pdo);

$canManageCustomers = ispts_can_manage_customers();

$listStmt = $pdo->query(
    'SELECT customer_id,
            username,
            phone_no,
            registered_date,
            area,
            sub_area,
            package_id,
            package_activate_date,
            package_expire_date,
            deposit_money,
            connection_charge,
            branch
     FROM customers
     WHERE status = 1
     ORDER BY customer_id DESC'
);
$customers = $listStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>
<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Support Desk</a></li>
    <li class="breadcrumb-item active">Customers</li>
  </ol>
</nav>
<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">Customers</h1>
    </div>
    <div class="col-auto">
      <a href="customer-registration.php" class="btn btn-primary btn-sm<?= $canManageCustomers ? '' : ' disabled' ?>"<?= $canManageCustomers ? '' : ' aria-disabled="true"' ?>>
        <span class="fas fa-plus me-1"></span>Customer Registration
      </a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Customer List</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm fs-10 mb-0">
        <thead class="bg-body-tertiary">
          <tr>
            <th class="text-800">Action</th>
            <th class="text-800">Username</th>
            <th class="text-800">Phone No</th>
            <th class="text-800">Registered Date</th>
            <th class="text-800">Area / Sub Area</th>
            <th class="text-800">Package</th>
            <th class="text-800">Activate / Expire</th>
            <th class="text-800 text-end">Deposit / Connection</th>
            <th class="text-800">Branch</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr>
              <td colspan="9" class="text-center py-3 text-600">No customers found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($customers as $customer): ?>
              <tr>
                <td>
                  <a class="btn btn-link p-0 me-2" href="customer-details.php?id=<?= (int) $customer['customer_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details">
                    <span class="fas fa-eye text-primary"></span>
                  </a>
                  <?php if ($canManageCustomers): ?>
                    <a class="btn btn-link p-0" href="customer-registration.php?id=<?= (int) $customer['customer_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit">
                      <span class="fas fa-edit text-500"></span>
                    </a>
                  <?php else: ?>
                    <span class="text-500">-</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) $customer['username']) ?></td>
                <td><?= htmlspecialchars((string) $customer['phone_no']) ?></td>
                <td><?= htmlspecialchars((string) $customer['registered_date']) ?></td>
                <td>
                  <?= htmlspecialchars((string) ($customer['area'] ?? '-')) ?>
                  <span class="text-500">/</span>
                  <?= htmlspecialchars((string) ($customer['sub_area'] ?? '-')) ?>
                </td>
                <td><?= htmlspecialchars((string) ($customer['package_id'] ?? '-')) ?></td>
                <td>
                  <?= htmlspecialchars((string) ($customer['package_activate_date'] ?? '-')) ?>
                  <span class="text-500">/</span>
                  <?= htmlspecialchars((string) ($customer['package_expire_date'] ?? '-')) ?>
                </td>
                <td class="text-end">
                  <?= number_format((float) ($customer['deposit_money'] ?? 0), 2) ?>
                  <span class="text-500">/</span>
                  <?= number_format((float) ($customer['connection_charge'] ?? 0), 2) ?>
                </td>
                <td><?= htmlspecialchars((string) ($customer['branch'] ?? '-')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
require '../../includes/footer.php';
?>
