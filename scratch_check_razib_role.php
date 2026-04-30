<?php
require_once __DIR__ . '/app/Core/Database.php';
use App\Core\Database;

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT su.username, su.role_id, r.role_name, r.menu_tree_json 
                          FROM staff_users su 
                          JOIN roles r ON su.role_id = r.role_id 
                          WHERE su.username = :u');
    $stmt->execute([':u' => 'razib']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Username: " . $user['username'] . "\n";
        echo "Role Name: " . $user['role_name'] . "\n";
        echo "Role ID: " . $user['role_id'] . "\n";
        echo "JSON: " . $user['menu_tree_json'] . "\n";
    } else {
        echo "User 'razib' not found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
