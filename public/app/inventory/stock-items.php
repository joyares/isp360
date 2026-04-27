<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

// ── SESSION ───────────────────────────────────────────────────────────────────

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/inventory/stock-items.php';

// ── AJAX HANDLER (DETAIL VIEW) ────────────────────────────────────────────────

if (isset($_GET['get_details'])) {
    $productId = (int)$_GET['product_id'];
    $branchId  = (int)($_GET['branch_id'] ?? 0);
    
    $where = "sn.product_id = :pid AND sn.status = 1";
    $params = ['pid' => $productId];
    
    if ($branchId > 0) {
        $where .= " AND inv.branch_id = :bid";
        $params['bid'] = $branchId;
    }

    $stmt = $pdo->prepare("
        SELECT 
            sn.serial_ref,
            v.vendor_name,
            inv.invoice_date,
            ii.warranty_period
        FROM inventory_serial_numbers sn
        JOIN inventory_stock_invoices inv ON inv.invoice_id = sn.invoice_id
        JOIN inventory_vendors v ON v.vendor_id = inv.vendor_id
        JOIN inventory_stock_invoice_items ii ON ii.invoice_id = sn.invoice_id AND ii.product_id = sn.product_id
        WHERE $where
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $results = [];
    foreach ($items as $item) {
        $warrantyLeft = 'No Warranty';
        if ($item['warranty_period']) {
            $purchaseDate = new DateTime($item['invoice_date']);
            // warranty_period is stored as "1 Years", "6 Months", etc.
            try {
                $expiryDate = clone $purchaseDate;
                $expiryDate->modify('+' . $item['warranty_period']);
                $now = new DateTime();
                if ($expiryDate > $now) {
                    $diff = $now->diff($expiryDate);
                    if ($diff->y > 0) $warrantyLeft = $diff->y . 'y ' . $diff->m . 'm left';
                    else if ($diff->m > 0) $warrantyLeft = $diff->m . 'm ' . $diff->d . 'd left';
                    else $warrantyLeft = $diff->d . 'd left';
                } else {
                    $warrantyLeft = 'Expired';
                }
            } catch (\Exception $e) {
                $warrantyLeft = $item['warranty_period'];
            }
        }
        $results[] = [
            'serial' => $item['serial_ref'],
            'vendor' => $item['vendor_name'],
            'warranty' => $warrantyLeft
        ];
    }
    echo json_encode($results);
    exit;
}

// ── DATA LOADING & FILTERING ──────────────────────────────────────────────────

$catFilter    = (int)($_GET['category_id']    ?? 0);
$subCatFilter = (int)($_GET['sub_category_id'] ?? 0);
$branchFilter = (int)($_GET['branch_id']      ?? 0);
$brandFilter  = trim((string)($_GET['brand']  ?? ''));

$whereParts = ["p.status = 1"];
$params = [];

if ($catFilter > 0) {
    $whereParts[] = "p.category_id = :cid";
    $params['cid'] = $catFilter;
}
if ($subCatFilter > 0) {
    $whereParts[] = "p.sub_category_id = :scid";
    $params['scid'] = $subCatFilter;
}
if ($branchFilter > 0) {
    // Branch filtering requires checking which items are currently at that branch
    // Serials are linked to invoices which have branch_id
    $whereParts[] = "sn.invoice_id IN (SELECT invoice_id FROM inventory_stock_invoices WHERE branch_id = :bid)";
    $params['bid'] = $branchFilter;
}

$whereClause = implode(" AND ", $whereParts);

$query = "
    SELECT 
        p.product_id, 
        p.product_name, 
        c.category_name, 
        sc.sub_category_name,
        inv.branch_id,
        b.branch_name,
        COALESCE(NULLIF(comp.company, ''), comp.firstname, comp.username, '-') AS company_name,
        COUNT(sn.serial_id) as total_qty
    FROM inventory_products p
    LEFT JOIN inventory_categories c ON c.category_id = p.category_id
    LEFT JOIN inventory_sub_categories sc ON sc.sub_category_id = p.sub_category_id
    LEFT JOIN inventory_serial_numbers sn ON sn.product_id = p.product_id AND sn.status = 1
    LEFT JOIN inventory_stock_invoices inv ON inv.invoice_id = sn.invoice_id
    LEFT JOIN branches b ON b.branch_id = inv.branch_id
    LEFT JOIN companies comp ON comp.id = b.partner_id
    WHERE $whereClause
    GROUP BY p.product_id, inv.branch_id
    ORDER BY p.product_name ASC, b.branch_name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$stockItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Grouping stock items by branch
$groupedStock = [];
foreach ($stockItems as $item) {
    $bId = (int) ($item['branch_id'] ?? 0);
    $bName = $item['branch_name'] ?: 'General / Unassigned';
    if (!isset($groupedStock[$bId])) {
        $groupedStock[$bId] = [
            'name' => $bName,
            'items' => []
        ];
    }
    $groupedStock[$bId]['items'][] = $item;
}
ksort($groupedStock); // Keep it somewhat ordered

$categories = $pdo->query("SELECT category_id, category_name FROM inventory_categories ORDER BY category_name ASC")->fetchAll(\PDO::FETCH_ASSOC);
$subCategories = $pdo->query("SELECT sub_category_id, sub_category_name FROM inventory_sub_categories ORDER BY sub_category_name ASC")->fetchAll(\PDO::FETCH_ASSOC);
$branches = $pdo->query("SELECT branch_id, branch_name FROM branches WHERE status = 1 ORDER BY branch_name ASC")->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item active">Stock Items</li>
  </ol>
</nav>

<div class="mb-4">
    <h2 class="text-bold">Current Stock Items</h2>
    <p class="text-600">Overview of all products currently available in stock.</p>
</div>

<div class="card mb-3">
    <div class="card-body p-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-2">
                <label class="form-label fs-11">Category</label>
                <select class="form-select form-select-sm" name="category_id">
                    <option value="0">All Categories</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= $catFilter === (int)$c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-11">Sub Category</label>
                <select class="form-select form-select-sm" name="sub_category_id">
                    <option value="0">All Sub Categories</option>
                    <?php foreach($subCategories as $sc): ?>
                        <option value="<?= (int)$sc['sub_category_id'] ?>" <?= $subCatFilter === (int)$sc['sub_category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sc['sub_category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-11">Branch</label>
                <select class="form-select form-select-sm" name="branch_id">
                    <option value="0">All Branches</option>
                    <?php foreach($branches as $b): ?>
                        <option value="<?= (int)$b['branch_id'] ?>" <?= $branchFilter === (int)$b['branch_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">
                    <span class="fas fa-filter me-1"></span>Filter
                </button>
            </div>
            <div class="col-md-2">
                <a class="btn btn-sm btn-falcon-default w-100" href="<?= $currentPath ?>">
                    <span class="fas fa-undo me-1"></span>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs border-0" id="branchTabs" role="tablist">
            <?php if (empty($groupedStock)): ?>
                <li class="nav-item"><a class="nav-link active px-3 py-2 fs-10" href="#">No Stock</a></li>
            <?php else: ?>
                <?php $isFirst = true; foreach ($groupedStock as $bId => $data): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $isFirst ? 'active' : '' ?> px-3 py-2 fs-10" id="branch-tab-<?= $bId ?>" data-bs-toggle="tab" data-bs-target="#branch-pane-<?= $bId ?>" type="button" role="tab">
                            <span class="fas fa-store me-1"></span><?= htmlspecialchars($data['name']) ?> 
                            <span class="badge rounded-pill badge-subtle-primary ms-1"><?= count($data['items']) ?></span>
                        </button>
                    </li>
                <?php $isFirst = false; endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="branchTabsContent">
            <?php if (empty($groupedStock)): ?>
                <div class="text-center py-5">
                    <p class="text-600 mb-0">No stock items found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php $isFirst = true; foreach ($groupedStock as $bId => $data): ?>
                    <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?>" id="branch-pane-<?= $bId ?>" role="tabpanel" aria-labelledby="branch-tab-<?= $bId ?>">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm table-striped fs-10 mb-0">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th class="text-800 ps-3">Product Name</th>
                                        <th class="text-800">Category</th>
                                        <th class="text-800">Sub Category</th>
                                        <th class="text-800">Company</th>
                                        <th class="text-800 text-end">Available Qty</th>
                                        <th class="text-800 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['items'] as $item): ?>
                                        <tr>
                                            <td class="fw-bold ps-3"><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= htmlspecialchars($item['category_name'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['sub_category_name'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['company_name'] ?: '-') ?></td>
                                            <td class="text-end fw-bold">
                                                <span class="badge rounded-pill <?= (int) $item['total_qty'] > 0 ? 'badge-subtle-success' : 'badge-subtle-danger' ?>">
                                                    <?= (int) $item['total_qty'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-link p-0" type="button" 
                                                        onclick="showStockDetails(<?= (int) $item['product_id'] ?>, '<?= addslashes($item['product_name']) ?>', <?= (int) $item['branch_id'] ?>)"
                                                        data-bs-toggle="tooltip" title="View Serials">
                                                    <span class="fas fa-eye text-primary"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php $isFirst = false; endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for Serials Details -->
<div class="modal fade" id="serialDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs-10 mb-0" id="detailsTable">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th>Serial / Ref</th>
                                <th>Vendor</th>
                                <th>Warranty Left</th>
                            </tr>
                        </thead>
                        <tbody id="detailsBody">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div id="detailsLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showStockDetails(productId, productName, branchId) {
    const modal = new bootstrap.Modal(document.getElementById('serialDetailsModal'));
    const body = document.getElementById('detailsBody');
    const loader = document.getElementById('detailsLoader');
    const title = document.getElementById('modalTitle');

    title.textContent = 'Stock Details: ' + productName;
    body.innerHTML = '';
    loader.classList.remove('d-none');
    modal.show();

    fetch('<?= $currentPath ?>?get_details=1&product_id=' + productId + '&branch_id=' + branchId)
        .then(r => r.json())
        .then(data => {
            loader.classList.add('d-none');
            if (data.length === 0) {
                body.innerHTML = '<tr><td colspan="3" class="text-center py-3">No available serials found.</td></tr>';
                return;
            }
            data.forEach(item => {
                body.innerHTML += `
                    <tr>
                        <td>${item.serial}</td>
                        <td>${item.vendor}</td>
                        <td><span class="badge badge-subtle-info">${item.warranty}</span></td>
                    </tr>
                `;
            });
        });
}
</script>

<?php require '../../includes/footer.php'; ?>
