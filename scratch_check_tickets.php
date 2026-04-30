<?php
require_once __DIR__ . '/public/app/Core/Database.php';
use App\Core\Database;
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT ticket_id, ticket_no, created_at, status FROM support_tickets");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
