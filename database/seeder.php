<?php

/**
 * ISP360 Global Database Seeder
 * -----------------------------
 * Runs all schema migrations and seed data in dependency order.
 * Every operation is idempotent — safe to run multiple times.
 *
 * Usage (CLI):
 *   php database/seeder.php
 *
 * Usage (web – dev only, remove in production):
 *   http://localhost/isp360/database/seeder.php
 */

declare(strict_types=1);

define('SEEDER_START', microtime(true));

require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

$log   = [];
$errors = [];

function seeder_log(string $message): void
{
    global $log;
    $log[] = $message;
    $isCli = PHP_SAPI === 'cli';
    echo ($isCli ? '' : '') . $message . ($isCli ? "\n" : "<br>\n");
    flush();
}

function seeder_error(string $message): void
{
    global $errors;
    $errors[] = $message;
    $isCli = PHP_SAPI === 'cli';
    echo ($isCli ? '[ERROR] ' : '<b style="color:red">[ERROR]</b> ') . $message . ($isCli ? "\n" : "<br>\n");
    flush();
}

/**
 * Run a DDL/DML statement, logging result.
 */
function run(PDO $pdo, string $label, string $sql): bool
{
    try {
        $pdo->exec($sql);
        seeder_log("  [OK]  {$label}");
        return true;
    } catch (PDOException $e) {
        seeder_error("{$label} — " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a column exists on a table.
 */
function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Add a column only if it does not exist yet.
 */
function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        run($pdo, "ALTER {$table} ADD {$column}", "ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    } else {
        seeder_log("  [--]  Column `{$table}`.`{$column}` already exists — skip");
    }
}

// ─────────────────────────────────────────────
// Connect
// ─────────────────────────────────────────────

try {
    $pdo = Database::getConnection();
    seeder_log('Connected to database: ' . (getenv('DB_NAME') ?: 'isp360'));
} catch (RuntimeException $e) {
    seeder_error('Cannot connect: ' . $e->getMessage());
    exit(1);
}

// ─────────────────────────────────────────────
// STEP 1 — Core RBAC tables
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 1: Core RBAC tables ===');

run($pdo, 'CREATE TABLE roles', "
    CREATE TABLE IF NOT EXISTS roles (
        role_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_name        VARCHAR(100)    NOT NULL,
        role_slug        VARCHAR(120)    NOT NULL,
        status           TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
        created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_roles_role_slug (role_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Extended role columns
ensure_column($pdo, 'roles', 'role_type',        'role_type        VARCHAR(60)  NULL AFTER role_name');
ensure_column($pdo, 'roles', 'role_description', 'role_description TEXT         NULL AFTER role_type');
ensure_column($pdo, 'roles', 'menu_tree_json',   'menu_tree_json   JSON         NULL AFTER role_description');

run($pdo, 'CREATE TABLE permissions', "
    CREATE TABLE IF NOT EXISTS permissions (
        permission_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(120)    NOT NULL,
        permission_slug VARCHAR(150)    NOT NULL,
        module          VARCHAR(80)     NULL COMMENT 'Grouping label, e.g. inventory',
        status          TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
        created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_permissions_permission_slug (permission_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

ensure_column($pdo, 'permissions', 'module', "module VARCHAR(80) NULL COMMENT 'Grouping label' AFTER permission_slug");

run($pdo, 'CREATE TABLE role_permission', "
    CREATE TABLE IF NOT EXISTS role_permission (
        role_id       BIGINT UNSIGNED NOT NULL,
        permission_id BIGINT UNSIGNED NOT NULL,
        status        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (role_id, permission_id),
        KEY idx_rp_permission_id (permission_id),
        CONSTRAINT fk_rp_role_id
            FOREIGN KEY (role_id) REFERENCES roles (role_id) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_rp_permission_id
            FOREIGN KEY (permission_id) REFERENCES permissions (permission_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ─────────────────────────────────────────────
// STEP 2 — Admin Users table
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 2: Admin Users table ===');

run($pdo, 'CREATE TABLE admin_users', "
    CREATE TABLE IF NOT EXISTS admin_users (
        admin_user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_id       BIGINT UNSIGNED NOT NULL,
        full_name     VARCHAR(150)    NOT NULL,
        username      VARCHAR(100)    NOT NULL,
        password_hash VARCHAR(255)    NOT NULL,
        status        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_admin_users_username (username),
        KEY idx_admin_users_role_id (role_id),
        CONSTRAINT fk_admin_users_role_id
            FOREIGN KEY (role_id) REFERENCES roles (role_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

ensure_column($pdo, 'admin_users', 'email',         'email         VARCHAR(190) NULL AFTER full_name');
ensure_column($pdo, 'admin_users', 'mobile',        'mobile        VARCHAR(30)  NULL AFTER email');
ensure_column($pdo, 'admin_users', 'user_type',     'user_type     VARCHAR(30)  NULL AFTER mobile');
ensure_column($pdo, 'admin_users', 'department_id', 'department_id BIGINT UNSIGNED NULL AFTER user_type');
ensure_column($pdo, 'admin_users', 'last_login_at', 'last_login_at TIMESTAMP    NULL DEFAULT NULL AFTER updated_at');
ensure_column($pdo, 'admin_users', 'last_login_ip', 'last_login_ip VARCHAR(45)  NULL DEFAULT NULL AFTER last_login_at');

// ─────────────────────────────────────────────
// STEP 3 — Location hierarchy tables
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 3: Location hierarchy tables ===');

run($pdo, 'CREATE TABLE countries', "
    CREATE TABLE IF NOT EXISTS countries (
        country_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        country_name VARCHAR(120)    NOT NULL,
        country_code VARCHAR(10)     NULL,
        status       TINYINT(1)      NOT NULL DEFAULT 1,
        created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE divisions', "
    CREATE TABLE IF NOT EXISTS divisions (
        division_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        country_id    BIGINT UNSIGNED NOT NULL,
        division_name VARCHAR(120)    NOT NULL,
        status        TINYINT(1)      NOT NULL DEFAULT 1,
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_divisions_country_id FOREIGN KEY (country_id) REFERENCES countries (country_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE districts', "
    CREATE TABLE IF NOT EXISTS districts (
        district_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        division_id   BIGINT UNSIGNED NOT NULL,
        district_name VARCHAR(120)    NOT NULL,
        status        TINYINT(1)      NOT NULL DEFAULT 1,
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_districts_division_id FOREIGN KEY (division_id) REFERENCES divisions (division_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE upazilas', "
    CREATE TABLE IF NOT EXISTS upazilas (
        upazila_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        district_id  BIGINT UNSIGNED NOT NULL,
        upazila_name VARCHAR(120)    NOT NULL,
        status       TINYINT(1)      NOT NULL DEFAULT 1,
        created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_upazilas_district_id FOREIGN KEY (district_id) REFERENCES districts (district_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE areas', "
    CREATE TABLE IF NOT EXISTS areas (
        area_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        upazila_id  BIGINT UNSIGNED NOT NULL,
        area_name   VARCHAR(120)    NOT NULL,
        status      TINYINT(1)      NOT NULL DEFAULT 1,
        created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_areas_upazila_id FOREIGN KEY (upazila_id) REFERENCES upazilas (upazila_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE blocks', "
    CREATE TABLE IF NOT EXISTS blocks (
        block_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        area_id    BIGINT UNSIGNED NOT NULL,
        block_name VARCHAR(120)    NOT NULL,
        status     TINYINT(1)      NOT NULL DEFAULT 1,
        created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_blocks_area_id FOREIGN KEY (area_id) REFERENCES areas (area_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE roads', "
    CREATE TABLE IF NOT EXISTS roads (
        road_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        block_id   BIGINT UNSIGNED NOT NULL,
        road_name  VARCHAR(120)    NOT NULL,
        status     TINYINT(1)      NOT NULL DEFAULT 1,
        created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_roads_block_id FOREIGN KEY (block_id) REFERENCES blocks (block_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ─────────────────────────────────────────────
// STEP 4 — Branches & Partners
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 4: Branches & Partners tables ===');

run($pdo, 'CREATE TABLE partners', "
    CREATE TABLE IF NOT EXISTS partners (
        partner_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        partner_name VARCHAR(150)    NOT NULL,
        email        VARCHAR(190)    NULL,
        mobile       VARCHAR(30)     NULL,
        address      TEXT            NULL,
        status       TINYINT(1)      NOT NULL DEFAULT 1,
        created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE TABLE branches', "
    CREATE TABLE IF NOT EXISTS branches (
        branch_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        partner_id   BIGINT UNSIGNED NULL,
        branch_name  VARCHAR(150)    NOT NULL,
        branch_type  VARCHAR(60)     NULL COMMENT 'e.g. Head Office, Sub Office',
        email        VARCHAR(190)    NULL,
        mobile       VARCHAR(30)     NULL,
        address      TEXT            NULL,
        ratio        DECIMAL(5,2)    NULL DEFAULT 0.00 COMMENT 'Revenue share %',
        status       TINYINT(1)      NOT NULL DEFAULT 1,
        created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_branches_partner_id FOREIGN KEY (partner_id) REFERENCES partners (partner_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ─────────────────────────────────────────────
// STEP 5 — Customers
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 5: Customers table ===');

run($pdo, 'CREATE TABLE customers', "
    CREATE TABLE IF NOT EXISTS customers (
        customer_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        branch_id     BIGINT UNSIGNED NULL,
        full_name     VARCHAR(150)    NOT NULL,
        email         VARCHAR(190)    NULL,
        mobile        VARCHAR(30)     NOT NULL,
        address       TEXT            NULL,
        customer_type VARCHAR(40)     NULL COMMENT 'residential, corporate',
        status        TINYINT(1)      NOT NULL DEFAULT 1,
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_customers_mobile (mobile),
        CONSTRAINT fk_customers_branch_id FOREIGN KEY (branch_id) REFERENCES branches (branch_id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ─────────────────────────────────────────────
// STEP 6 — Seed data
// ─────────────────────────────────────────────

seeder_log('');
seeder_log('=== STEP 6: Seed data ===');

// Roles
$pdo->exec("
    INSERT IGNORE INTO roles (role_name, role_slug, role_type, role_description, status) VALUES
    ('Super Admin', 'super-admin', 'system',     'Full access system administrator', 1),
    ('Manager',     'manager',     'management', 'Branch / operations manager',      1),
    ('Finance',     'finance',     'department', 'Finance & billing staff',          1),
    ('Support',     'support',     'department', 'Customer support agent',           1),
    ('Technician',  'technician',  'department', 'Field & network technician',       1)
");
seeder_log('  [OK]  Seeded roles');

// Permissions
$permissions = [
    // Administration
    ['Administration', 'admin_users_view',   'admin'],
    ['Administration', 'admin_users_add',    'admin'],
    ['Administration', 'admin_users_edit',   'admin'],
    ['Administration', 'admin_users_off',    'admin'],
    ['Administration', 'roles_view',         'admin'],
    ['Administration', 'roles_add',          'admin'],
    ['Administration', 'roles_edit',         'admin'],
    ['Administration', 'roles_off',          'admin'],
    // Inventory
    ['Inventory', 'inventory_view',          'inventory'],
    ['Inventory', 'inventory_add',           'inventory'],
    ['Inventory', 'inventory_edit',          'inventory'],
    ['Inventory', 'inventory_off',           'inventory'],
    // Finance
    ['Finance', 'finance_view',              'finance'],
    ['Finance', 'finance_add_invoice',       'finance'],
    ['Finance', 'finance_edit_invoice',      'finance'],
    ['Finance', 'finance_off_invoice',       'finance'],
    // Support
    ['Support', 'support_view',              'support'],
    ['Support', 'support_add_ticket',        'support'],
    ['Support', 'support_edit_ticket',       'support'],
    ['Support', 'support_close_ticket',      'support'],
    // HR / Payroll
    ['HR', 'hr_view',                        'hr'],
    ['HR', 'hr_add_employee',                'hr'],
    ['HR', 'hr_edit_employee',               'hr'],
    ['HR', 'hr_off_employee',                'hr'],
    ['HR', 'hr_view_salary',                 'hr'],
    ['HR', 'hr_process_payroll',             'hr'],
    // Partners
    ['Partners', 'partners_view',            'partners'],
    ['Partners', 'partners_add',             'partners'],
    ['Partners', 'partners_edit',            'partners'],
    ['Partners', 'partners_off',             'partners'],
    // Reports
    ['Reports', 'reports_view',              'reports'],
    ['Reports', 'reports_export',            'reports'],
    // Logs
    ['Logs', 'logs_view',                    'logs'],
];

$permStmt = $pdo->prepare(
    'INSERT IGNORE INTO permissions (permission_name, permission_slug, module, status) VALUES (:name, :slug, :module, 1)'
);
foreach ($permissions as [$name, $slug, $module]) {
    $permStmt->execute([':name' => $name . ' — ' . str_replace('_', ' ', $slug), ':slug' => $slug, ':module' => $module]);
}
seeder_log('  [OK]  Seeded ' . count($permissions) . ' permissions');

// Assign all permissions to Super Admin
$pdo->exec("
    INSERT IGNORE INTO role_permission (role_id, permission_id, status)
    SELECT r.role_id, p.permission_id, 1
    FROM roles r
    CROSS JOIN permissions p
    WHERE r.role_slug = 'super-admin'
      AND p.status = 1
");
seeder_log('  [OK]  Assigned all permissions to Super Admin role');

// Default admin user: joyares / joyares
$adminExists = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'joyares'")->fetchColumn();
if ($adminExists === 0) {
    $hash = password_hash('joyares', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (role_id, full_name, username, password_hash, status)
        SELECT r.role_id, 'Mostafa Joy', 'joyares', :hash, 1
        FROM roles r WHERE r.role_slug = 'super-admin' LIMIT 1
    ");
    $stmt->execute([':hash' => $hash]);
    seeder_log('  [OK]  Seeded admin user: joyares');
} else {
    seeder_log('  [--]  Admin user joyares already exists — skip');
}

// Default country: Bangladesh
$pdo->exec("INSERT IGNORE INTO countries (country_name, country_code, status) VALUES ('Bangladesh', 'BD', 1)");
seeder_log('  [OK]  Seeded default country (Bangladesh)');

// ─────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────

seeder_log('');
$elapsed = round((microtime(true) - SEEDER_START) * 1000, 2);
$errorCount = count($errors);

if ($errorCount === 0) {
    seeder_log("=== Seeder completed successfully in {$elapsed}ms ===");
} else {
    seeder_log("=== Seeder completed with {$errorCount} error(s) in {$elapsed}ms ===");
}
