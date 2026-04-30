<?php

declare(strict_types=1);

require_once '../../includes/auth.php';

use App\Core\Database;

$pdo = Database::getConnection();
ispts_ensure_customers_table($pdo);

$canManageCustomers = ispts_can_manage_customers();

// Search / filter
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = $_GET['status'] ?? 'all';

// Pagination
$allowedPerPage = [20, 50, 100, 500];
$perPageRaw = $_GET['per_page'] ?? '20';
$perPage = $perPageRaw === 'all' ? 'all' : (int) $perPageRaw;
if ($perPage !== 'all' && !in_array($perPage, $allowedPerPage, true)) {
    $perPage = 20;
}
$page = max(1, (int) ($_GET['page'] ?? 1));

$conditions = [];
$params = [];

if ($search !== '') {
    $searchFields = ['c.username', 'c.full_name', 'c.phone_no', 'c.email', 'c.radious_id', 'c.nid', 'c.address'];
    $searchConds = [];
    foreach ($searchFields as $idx => $field) {
        $pName = ':q' . $idx;
        $searchConds[] = "$field LIKE $pName";
        $params[$pName] = '%' . $search . '%';
    }
    $conditions[] = '(' . implode(' OR ', $searchConds) . ')';
}

if ($statusFilter === '1') {
    $conditions[] = 'c.status = 1';
} elseif ($statusFilter === '0') {
    $conditions[] = 'c.status = 0';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c $where");
$countStmt->execute($params);
$totalRecords = (int) $countStmt->fetchColumn();

if ($perPage === 'all') {
    $totalPages = 1;
    $page = 1;
    $limitClause = '';
} else {
    $totalPages = (int) ceil($totalRecords / $perPage);
    $page = min($page, max(1, $totalPages));
    $offset = ($page - 1) * $perPage;
    $limitClause = "LIMIT $perPage OFFSET $offset";
}

$listStmt = $pdo->prepare(
    "SELECT c.*, b.branch_name
     FROM customers c
     LEFT JOIN branches b ON c.branch_id = b.branch_id
     $where
     ORDER BY c.customer_id DESC
     $limitClause"
);
$listStmt->execute($params);
$customers = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: build URL preserving current filters
function buildPageUrl(int|string $p, int|string $pp, string $q, string $status): string {
    return 'customers.php?' . http_build_query(array_filter([
        'page'     => $p,
        'per_page' => $pp,
        'q'        => $q,
        'status'   => $status !== 'all' ? $status : '',
    ], fn($v) => $v !== '' && $v !== null));
}

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
      <h1 class="page-header-title">Customers
        <span class="badge bg-primary ms-2 fs-11"><?= count($customers) ?></span>
      </h1>
    </div>
    <div class="col-auto d-flex gap-2">
      <a href="customer-registration.php" class="btn btn-primary btn-sm<?= $canManageCustomers ? '' : ' disabled' ?>"<?= $canManageCustomers ? '' : ' aria-disabled="true"' ?>>
        <span class="fas fa-plus me-1"></span>New Customer
      </a>
    </div>
  </div>
</div>

<!-- Search & Filter Bar -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form class="row g-2 align-items-center" method="get" id="filterForm">
      <input type="hidden" name="page" value="1">
      <div class="col">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><span class="fas fa-search"></span></span>
          <input type="text" class="form-control" name="q" id="customerSearch"
            placeholder="Search by name, username, phone, email, Radious ID, NID…"
            value="<?= htmlspecialchars($search) ?>">
          <?php if ($search): ?>
            <a href="customers.php" class="btn btn-falcon-default btn-sm">Clear</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-auto">
        <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
          <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
          <option value="1"   <?= $statusFilter === '1'   ? 'selected' : '' ?>>Active</option>
          <option value="0"   <?= $statusFilter === '0'   ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-auto">
        <div class="input-group input-group-sm">
          <label class="input-group-text" for="perPageSelect">Show</label>
          <select class="form-select form-select-sm" name="per_page" id="perPageSelect" onchange="this.form.submit()">
            <option value="20"  <?= ($perPage === 20  || $perPage === '20')  ? 'selected' : '' ?>>20</option>
            <option value="50"  <?= ($perPage === 50  || $perPage === '50')  ? 'selected' : '' ?>>50</option>
            <option value="100" <?= ($perPage === 100 || $perPage === '100') ? 'selected' : '' ?>>100</option>
            <option value="500" <?= ($perPage === 500 || $perPage === '500') ? 'selected' : '' ?>>500</option>
            <option value="all" <?= $perPage === 'all' ? 'selected' : '' ?>>All</option>
          </select>
        </div>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary btn-sm" type="submit">
          <span class="fas fa-filter me-1"></span>Filter
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center py-2">
    <h5 class="mb-0">Customer List</h5>
    <small class="text-500">
      Showing <?= count($customers) ?> of <?= $totalRecords ?> record(s)
      <?php if ($perPage !== 'all'): ?>
        &nbsp;&middot;&nbsp; Page <?= $page ?> / <?= $totalPages ?>
      <?php endif; ?>
    </small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm table-hover fs-10 mb-0" id="customersTable">
        <thead class="bg-body-tertiary sticky-top">
          <tr>
            <th class="text-800 text-nowrap">#</th>
            <th class="text-800 text-nowrap">Action</th>
            <th class="text-800 text-nowrap">Radious ID</th>
            <th class="text-800 text-nowrap">Username</th>
            <th class="text-800 text-nowrap">Full Name</th>
            <th class="text-800 text-nowrap">Phone No</th>
            <th class="text-800 text-nowrap">Email</th>
            <th class="text-800 text-nowrap">NID</th>
            <th class="text-800 text-nowrap">Address</th>
            <th class="text-800 text-nowrap">Area / Sub Area</th>
            <th class="text-800 text-nowrap">Branch</th>
            <th class="text-800 text-nowrap">Package</th>
            <th class="text-800 text-nowrap">Activate Date</th>
            <th class="text-800 text-nowrap">Expire Date</th>
            <th class="text-800 text-nowrap">Reg. Date</th>
            <th class="text-800 text-nowrap text-end">Deposit</th>
            <th class="text-800 text-nowrap text-end">Connection</th>
            <th class="text-800 text-nowrap text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr>
              <td colspan="18" class="text-center py-4 text-600">
                <span class="fas fa-inbox fs-3 d-block mb-2"></span>
                No customers found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($customers as $i => $c): ?>
              <tr>
                <td class="text-500"><?= $i + 1 ?></td>
                <td class="text-nowrap">
                  <a class="btn btn-link p-0 me-1" href="customer-details.php?id=<?= (int) $c['customer_id'] ?>"
                    data-bs-toggle="tooltip" title="View Details">
                    <span class="fas fa-eye text-primary"></span>
                  </a>
                  <?php if ($canManageCustomers): ?>
                    <a class="btn btn-link p-0" href="customer-registration.php?id=<?= (int) $c['customer_id'] ?>"
                      data-bs-toggle="tooltip" title="Edit">
                      <span class="fas fa-edit text-500"></span>
                    </a>
                  <?php endif; ?>
                </td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['radious_id'] ?? '-')) ?></td>
                <td class="text-nowrap fw-medium"><?= htmlspecialchars((string)$c['username']) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['full_name'] ?? '-')) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['phone_no'] ?? '-')) ?></td>
                <td class="text-nowrap">
                  <?php $email = $c['email'] ?? ''; ?>
                  <?= $email ? '<a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>' : '-' ?>
                </td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['nid'] ?? '-')) ?></td>
                <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                    title="<?= htmlspecialchars((string)($c['address'] ?? '')) ?>">
                  <?= htmlspecialchars((string)($c['address'] ?? '-')) ?>
                </td>
                <td class="text-nowrap">
                  <?= htmlspecialchars((string)($c['area'] ?? '-')) ?>
                  <?php if (!empty($c['sub_area'])): ?>
                    <span class="text-500">/</span><?= htmlspecialchars((string)$c['sub_area']) ?>
                  <?php endif; ?>
                </td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['branch_name'] ?? '-')) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['package_id'] ?? '-')) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['package_activate_date'] ?? '-')) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)($c['package_expire_date'] ?? '-')) ?></td>
                <td class="text-nowrap"><?= htmlspecialchars((string)$c['registered_date']) ?></td>
                <td class="text-end text-nowrap"><?= number_format((float)($c['deposit_money'] ?? 0), 2) ?></td>
                <td class="text-end text-nowrap"><?= number_format((float)($c['connection_charge'] ?? 0), 2) ?></td>
                <td class="text-center">
                  <?php if ((int)($c['status'] ?? 1) === 1): ?>
                    <span class="badge badge-soft-success">Active</span>
                  <?php else: ?>
                    <span class="badge badge-soft-danger">Inactive</span>
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

<!-- Pagination -->
<?php if ($perPage !== 'all' && $totalPages > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
  <small class="text-500">
    Showing <?= (($page - 1) * $perPage) + 1 ?>–<?= min($page * $perPage, $totalRecords) ?> of <?= $totalRecords ?> customers
  </small>
  <nav aria-label="Customer pagination">
    <ul class="pagination pagination-sm mb-0">
      <!-- First -->
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= htmlspecialchars(buildPageUrl(1, $perPage, $search, $statusFilter)) ?>">&laquo;</a>
      </li>
      <!-- Prev -->
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= htmlspecialchars(buildPageUrl(max(1, $page - 1), $perPage, $search, $statusFilter)) ?>">&lsaquo;</a>
      </li>

      <?php
      $window = 2;
      $start  = max(1, $page - $window);
      $end    = min($totalPages, $page + $window);
      if ($start > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildPageUrl(1, $perPage, $search, $statusFilter)) ?>">1</a></li>
        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <?php endif; ?>

      <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars(buildPageUrl($p, $perPage, $search, $statusFilter)) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildPageUrl($totalPages, $perPage, $search, $statusFilter)) ?>"><?= $totalPages ?></a></li>
      <?php endif; ?>

      <!-- Next -->
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= htmlspecialchars(buildPageUrl(min($totalPages, $page + 1), $perPage, $search, $statusFilter)) ?>">&rsaquo;</a>
      </li>
      <!-- Last -->
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= htmlspecialchars(buildPageUrl($totalPages, $perPage, $search, $statusFilter)) ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
</div>
<?php endif; ?>

<?php
require '../../includes/footer.php';
?>
