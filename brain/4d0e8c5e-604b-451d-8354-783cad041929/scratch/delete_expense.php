<?php
require_once 'd:\laragon\www\isp360\app\Core\Database.php';
$pdo = App\Core\Database::getConnection();

$trx_id = 'EXP-PAY-VEN-20260501083149-000001';

try {
    $stmt = $pdo->prepare("DELETE FROM finance_expenses WHERE trx_id = :trx_id");
    $stmt->execute(['trx_id' => $trx_id]);
    $count = $stmt->rowCount();
    if ($count > 0) {
        echo "Successfully deleted $count record(s) with trx_id: $trx_id\n";
    } else {
        echo "No record found with trx_id: $trx_id\n";
    }
} catch (Exception $e) {
    echo "Error deleting record: " . $e->getMessage() . "\n";
}
