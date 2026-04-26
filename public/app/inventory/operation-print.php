<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

$opId = (int)($_GET['op_id'] ?? 0);
if ($opId <= 0) die('Invalid ID');

$stmt = $pdo->prepare("
    SELECT o.*, 
           b.branch_name,
           c.username AS customer_name,
           u.full_name AS staff_name,
           creator.full_name AS creator_name
    FROM inventory_operations o
    LEFT JOIN branches b ON b.branch_id = o.branch_id
    LEFT JOIN customers c ON c.customer_id = o.customer_id
    LEFT JOIN admin_users u ON u.admin_user_id = o.staff_id
    LEFT JOIN admin_users creator ON creator.admin_user_id = o.created_by
    WHERE o.op_id = :id
");
$stmt->execute(['id' => $opId]);
$op = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$op) die('Operation not found');

$stmt = $pdo->prepare("
    SELECT oi.*, p.product_name
    FROM inventory_operation_items oi
    JOIN inventory_products p ON p.product_id = oi.product_id
    WHERE oi.op_id = :id
");
$stmt->execute(['id' => $opId]);
$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$serialsStmt = $pdo->prepare("
    SELECT os.*, sn.serial_ref
    FROM inventory_operation_serials os
    JOIN inventory_serial_numbers sn ON sn.serial_id = os.serial_id
    WHERE os.op_item_id = :iid
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Operation #<?= $opId ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Print This Invoice</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h1>INVENTORY <?= strtoupper($op['op_type']) ?> INVOICE</h1>
        <p>Operation ID: #<?= $opId ?> | Date: <?= $op['created_at'] ?></p>
    </div>

    <div class="details">
        <p><strong>Purpose:</strong> <?= htmlspecialchars($op['purpose']) ?></p>
        <p><strong>Branch:</strong> <?= htmlspecialchars($op['branch_name']) ?></p>
        <?php if($op['customer_name']): ?>
            <p><strong>Customer:</strong> <?= htmlspecialchars($op['customer_name']) ?></p>
        <?php endif; ?>
        <?php if($op['staff_name']): ?>
            <p><strong>Staff:</strong> <?= htmlspecialchars($op['staff_name']) ?></p>
        <?php endif; ?>
        <p><strong>Operator:</strong> <?= htmlspecialchars($op['creator_name']) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Serials</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): ?>
                <?php
                    $serialsStmt->execute(['iid' => $it['op_item_id']]);
                    $serials = $serialsStmt->fetchAll(\PDO::FETCH_ASSOC);
                    $serialRefs = array_column($serials, 'serial_ref');
                ?>
                <tr>
                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                    <td><?= $it['quantity'] ?></td>
                    <td><?= implode(', ', $serialRefs) ?: 'N/A' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if($op['notes']): ?>
        <div style="margin-top: 20px;">
            <strong>Notes:</strong><br>
            <?= nl2br(htmlspecialchars($op['notes'])) ?>
        </div>
    <?php endif; ?>

    <div class="footer">
        <div class="sig">Authorized Signature</div>
        <div class="sig">Recipient Signature</div>
    </div>

    <script>
        window.onload = function() {
            // Optional: window.print();
        }
    </script>
</body>
</html>
