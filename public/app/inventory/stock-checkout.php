<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

// ── CREATE TABLES ─────────────────────────────────────────────────────────────

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_operations (
        op_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        op_type         ENUM('checkout','checkin') NOT NULL,
        purpose         VARCHAR(255)    NOT NULL,
        branch_id       BIGINT UNSIGNED NOT NULL,
        customer_id     BIGINT UNSIGNED DEFAULT NULL,
        staff_id        BIGINT UNSIGNED DEFAULT NULL,
        notes           TEXT            DEFAULT NULL,
        created_by      BIGINT UNSIGNED NOT NULL,
        created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_operation_items (
        op_item_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        op_id         BIGINT UNSIGNED NOT NULL,
        product_id    BIGINT UNSIGNED NOT NULL,
        quantity      INT             NOT NULL DEFAULT 1,
        KEY idx_op_items_op (op_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS inventory_operation_serials (
        id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        op_item_id    BIGINT UNSIGNED NOT NULL,
        serial_id     BIGINT UNSIGNED NOT NULL,
        KEY idx_op_serials_item (op_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// No need to add status_val if status already exists. 
// We will use status: 1=Available, 2=CheckedOut, 0=Inactive/Removed


// ── SESSION ───────────────────────────────────────────────────────────────────

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$loggedInUserId = (int) ($_SESSION['admin_user_id'] ?? 0);
$loggedInName   = (string) ($_SESSION['admin_full_name'] ?? 'Unknown');

$alert = null;
$currentPath = $_SERVER['PHP_SELF'] ?? '/app/inventory/stock-checkout.php';

// ── AJAX HANDLER ──────────────────────────────────────────────────────────────

if (isset($_GET['ajax_get_serials'])) {
    $productId = (int) $_GET['product_id'];
    $branchId  = (int) $_GET['branch_id'];
    
    // Fetch available serials (status = 1)
    $stmt = $pdo->prepare('SELECT serial_id, serial_ref FROM inventory_serial_numbers WHERE product_id = :p AND status = 1');
    $stmt->execute(['p' => $productId]);
    echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
    exit;
}

// ── POST HANDLER ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_operation') {
    $opType     = 'checkout';
    $purpose    = trim((string)($_POST['purpose'] ?? ''));
    $branchId   = (int)($_POST['branch_id'] ?? 0);
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $staffId    = (int)($_POST['staff_id'] ?? 0);
    $notes      = trim((string)($_POST['notes'] ?? ''));
    $rawItems   = $_POST['items'] ?? [];

    $errors = [];
    if ($purpose === '') $errors[] = 'Purpose is required.';
    if ($branchId <= 0)  $errors[] = 'Branch is required.';
    if ($customerId <= 0 && $staffId <= 0) $errors[] = 'Either Customer or Staff must be selected.';
    if (empty($rawItems)) $errors[] = 'At least one product must be added.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO inventory_operations (op_type, purpose, branch_id, customer_id, staff_id, notes, created_by) VALUES (:t, :p, :b, :c, :s, :n, :u)');
            $stmt->execute([
                't' => $opType,
                'p' => $purpose,
                'b' => $branchId,
                'c' => $customerId ?: null,
                's' => $staffId    ?: null,
                'n' => $notes,
                'u' => $loggedInUserId
            ]);
            $opId = (int)$pdo->lastInsertId();

            foreach ($rawItems as $item) {
                $pId = (int)$item['product_id'];
                $qty = (int)$item['quantity'];
                $serials = $item['serials'] ?? [];

                $stmt = $pdo->prepare('INSERT INTO inventory_operation_items (op_id, product_id, quantity) VALUES (:oid, :pid, :q)');
                $stmt->execute(['oid' => $opId, 'pid' => $pId, 'q' => $qty]);
                $opItemId = (int)$pdo->lastInsertId();

                foreach ($serials as $sId) {
                    $stmt = $pdo->prepare('INSERT INTO inventory_operation_serials (op_item_id, serial_id) VALUES (:oiid, :sid)');
                    $stmt->execute(['oiid' => $opItemId, 'sid' => (int)$sId]);

                    // Update serial status to 2 (Checked Out)
                    $stmt = $pdo->prepare('UPDATE inventory_serial_numbers SET status = 2 WHERE serial_id = :sid');
                    $stmt->execute(['sid' => (int)$sId]);
                }
            }

            $pdo->commit();
            header("Location: $currentPath?saved=1&op_id=$opId");
            exit;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $alert = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
        }
    } else {
        $alert = ['type' => 'danger', 'message' => implode(' ', $errors)];
    }
}

// ── DATA LOADING ──────────────────────────────────────────────────────────────

$branches = $pdo->query('SELECT branch_id, branch_name FROM branches WHERE status = 1 ORDER BY branch_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$customers = $pdo->query('SELECT customer_id, username FROM customers WHERE status = 1 ORDER BY username ASC')->fetchAll(\PDO::FETCH_ASSOC);
$staff = $pdo->query('SELECT admin_user_id, full_name FROM admin_users WHERE status = 1 ORDER BY full_name ASC')->fetchAll(\PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT product_id, product_name FROM inventory_products WHERE status = 1 ORDER BY product_name ASC')->fetchAll(\PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item active">Stock Checkout</li>
  </ol>
</nav>

<div class="mb-4">
    <h2 class="text-bold">Stock Checkout</h2>
    <p class="text-600">Issue items from inventory to customers or staff members.</p>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success py-2 mb-3 d-flex justify-content-between align-items-center">
        <span>Operation completed successfully. ID: <?= (int)$_GET['op_id'] ?></span>
        <a href="operation-print.php?op_id=<?= (int)$_GET['op_id'] ?>" target="_blank" class="btn btn-sm btn-light">
            <span class="fas fa-print me-1"></span>Print Invoice
        </a>
    </div>
<?php endif; ?>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?> py-2 mb-3"><?= htmlspecialchars($alert['message']) ?></div>
<?php endif; ?>

<form method="post" action="<?= $currentPath ?>" id="checkoutForm">
    <input type="hidden" name="action" value="save_operation">
    
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header border-bottom border-200 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Operation Details</h6>
                    <button type="button" class="btn btn-sm btn-falcon-success" id="addItemRow">
                        <span class="fas fa-plus me-1"></span>Add Item
                    </button>
                </div>
                <div class="card-body p-0" id="itemsContainer">
                    <!-- Dynamic rows here -->
                </div>
                <div class="card-footer bg-body-tertiary">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                        <div class="col-md-6 text-end d-flex align-items-end justify-content-end gap-2">
                             <button class="btn btn-primary" type="submit">Complete Checkout</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-header border-bottom border-200"><h6 class="mb-0">Header Info</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <select class="form-select" name="purpose" required>
                            <option value="" disabled selected>Select Purpose</option>
                            <option value="Issue to Customer">Issue to Customer</option>
                            <option value="Issue to Staff">Issue to Staff</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Replacement">Replacement</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select class="form-select" name="branch_id" id="branch_id" required>
                            <option value="" disabled selected>Select Branch</option>
                            <?php foreach($branches as $b): ?>
                                <option value="<?= (int)$b['branch_id'] ?>"><?= htmlspecialchars((string)$b['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Select Customer</label>
                        <select class="form-select js-choices" name="customer_id" id="customer_id">
                            <option value="">None / Search Customer</option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars((string)$c['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Staff</label>
                        <select class="form-select js-choices" name="staff_id" id="staff_id">
                            <option value="">None / Search Staff</option>
                            <?php foreach($staff as $s): ?>
                                <option value="<?= (int)$s['admin_user_id'] ?>"><?= htmlspecialchars((string)$s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-subtle-info fs-11 m-0">One of Customer or Staff is mandatory.</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header border-bottom border-200"><h6 class="mb-0">Session Info</h6></div>
                <div class="card-body py-2">
                    <div class="fs-10 d-flex justify-content-between"><span>Operator:</span><span class="fw-bold"><?= htmlspecialchars($loggedInName) ?></span></div>
                    <div class="fs-10 d-flex justify-content-between"><span>Date:</span><span class="fw-bold"><?= date('Y-m-d') ?></span></div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    'use strict';
    const PRODUCTS = <?= json_encode($products) ?>;
    let rowIdx = 0;

    function buildRow(idx) {
        let opt = PRODUCTS.map(p => `<option value="${p.product_id}">${p.product_name}</option>`).join('');
        return `
        <div class="op-row border-bottom p-3" data-idx="${idx}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fs-11">Product</label>
                    <select class="form-select form-select-sm js-prod-select" name="items[${idx}][product_id]" required onchange="loadSerials(${idx})">
                        <option value="" disabled selected>Search Product...</option>
                        ${opt}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11">Qty</label>
                    <input class="form-control form-control-sm" type="number" name="items[${idx}][quantity]" value="1" min="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-11">Available Serials</label>
                    <select class="form-select form-select-sm js-serial-select" name="items[${idx}][serials][]" multiple data-placeholder="Select Serials">
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-link text-danger p-0 mb-1" onclick="this.closest('.op-row').remove()"><span class="fas fa-times-circle"></span></button>
                </div>
            </div>
        </div>`;
    }

    document.getElementById('addItemRow').addEventListener('click', function() {
        let idx = rowIdx++;
        document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', buildRow(idx));
        let sel = document.querySelector(`[data-idx="${idx}"] .js-prod-select`);
        if(window.Choices) new window.Choices(sel, { searchEnabled: true, itemSelectText: '' });
    });

    window.loadSerials = function(idx) {
        let pId = document.querySelector(`[data-idx="${idx}"] .js-prod-select`).value;
        let bId = document.getElementById('branch_id').value;
        let serSel = document.querySelector(`[data-idx="${idx}"] .js-serial-select`);
        
        if(!pId) return;

        fetch(`${window.location.pathname}?ajax_get_serials=1&product_id=${pId}&branch_id=${bId}`)
            .then(r => r.json())
            .then(data => {
                serSel.innerHTML = data.map(s => `<option value="${s.serial_id}">${s.serial_ref}</option>`).join('');
                if(window.Choices) {
                    if(serSel._choices) serSel._choices.destroy();
                    serSel._choices = new window.Choices(serSel, { removeItemButton: true, itemSelectText: '' });
                }
            });
    };

    // Initial row
    document.getElementById('addItemRow').click();

})();
</script>

<?php require '../../includes/footer.php'; ?>
