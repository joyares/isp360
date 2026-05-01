<?php
require_once __DIR__ . '/app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();
    
    echo "Starting database update...<br>";
    
    $queries = [
        "ALTER TABLE companies ADD COLUMN IF NOT EXISTS auth_uri_extension VARCHAR(255) DEFAULT NULL AFTER company",
        "ALTER TABLE companies ADD COLUMN IF NOT EXISTS logo_icon VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE companies ADD COLUMN IF NOT EXISTS logo_main VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "Successfully executed: $sql <br>";
        } catch (Exception $e) {
            echo "Notice: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br>Done! You can delete this file now.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
