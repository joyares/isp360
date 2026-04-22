<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

if (!function_exists('ispts_start_session')) {
    function ispts_start_session(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('ispts_is_authenticated')) {
    function ispts_is_authenticated(): bool
    {
        return isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0;
    }
}

if (!function_exists('ispts_get_login_path')) {
    function ispts_get_login_path(string $appBasePath = ''): string
    {
        $prefix = '/' . trim($appBasePath, '/');
        if ($prefix === '/') {
            $prefix = '';
        }

        return $prefix . '/pages/authentication/split/login.php';
    }
}

if (!function_exists('ispts_get_logout_path')) {
    function ispts_get_logout_path(string $appBasePath = ''): string
    {
        $prefix = '/' . trim($appBasePath, '/');
        if ($prefix === '/') {
            $prefix = '';
        }

        return $prefix . '/pages/authentication/split/logout.php';
    }
}

if (!function_exists('ispts_require_authentication')) {
    function ispts_require_authentication(string $appBasePath = ''): void
    {
        ispts_start_session();

        if (ispts_is_authenticated()) {
            return;
        }

        header('Location: ' . ispts_get_login_path($appBasePath));
        exit;
    }
}

if (!function_exists('ispts_extract_enabled_slugs')) {
    function ispts_extract_enabled_slugs(array $tree): array
    {
        $slugs = [];

        $walker = function (array $nodes) use (&$walker, &$slugs): void {
            foreach ($nodes as $node) {
                if (!is_array($node)) {
                    continue;
                }

                $enabled = !isset($node['enabled']) || (bool) $node['enabled'] === true;
                if ($enabled && isset($node['slug']) && is_string($node['slug']) && $node['slug'] !== '') {
                    $slugs[] = $node['slug'];
                }

                if (isset($node['children']) && is_array($node['children'])) {
                    $walker($node['children']);
                }
            }
        };

        $walker($tree);

        return array_values(array_unique($slugs));
    }
}

if (!function_exists('ispts_attempt_admin_login')) {
    function ispts_attempt_admin_login(string $username, string $password): array
    {
        ispts_start_session();

        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and Password are required.'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT au.admin_user_id,
                    au.role_id,
                    au.full_name,
                    au.username,
                    au.password_hash,
                    au.status,
                    r.role_name,
                    r.menu_tree_json
             FROM admin_users au
             LEFT JOIN roles r ON r.role_id = au.role_id
             WHERE au.username = :username
             LIMIT 1'
        );
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int) ($user['status'] ?? 0) !== 1) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        $passwordHash = (string) ($user['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = (int) $user['admin_user_id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        $_SESSION['admin_full_name'] = (string) ($user['full_name'] ?? 'Admin');
        $_SESSION['admin_role_id'] = (int) ($user['role_id'] ?? 0);
        $_SESSION['admin_role_name'] = (string) ($user['role_name'] ?? '');

        $permissions = [];
        $menuTreeRaw = (string) ($user['menu_tree_json'] ?? '');
        if ($menuTreeRaw !== '') {
            $decoded = json_decode($menuTreeRaw, true);
            if (is_array($decoded)) {
                $permissions = ispts_extract_enabled_slugs($decoded);
            }
        }
        $_SESSION['user_permissions'] = $permissions;

        return ['success' => true, 'message' => 'Login successful.'];
    }
}

if (!function_exists('ispts_logout_admin')) {
    function ispts_logout_admin(): void
    {
        ispts_start_session();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('ispts_is_employee_user')) {
    function ispts_is_employee_user(): bool
    {
        ispts_start_session();

        $roleName = strtolower((string) ($_SESSION['admin_role_name'] ?? ''));
        return $roleName !== '' && strpos($roleName, 'employee') !== false;
    }
}

if (!function_exists('ispts_can_manage_customers')) {
    function ispts_can_manage_customers(): bool
    {
        if (ispts_is_authenticated()) {
            $roleName = strtolower((string) ($_SESSION['admin_role_name'] ?? ''));
            if (
                $roleName === 'super admin' ||
                $roleName === 'super daddy' ||
                strpos($roleName, 'super admin') !== false ||
                strpos($roleName, 'super daddy') !== false
            ) {
                return true;
            }
        }

        if (ispts_is_employee_user()) {
            return true;
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        if (!is_array($permissions)) {
            return false;
        }

        $allowedSlugs = [
            'customer_create',
            'customer_edit',
            'customer_registration',
            'support_customer_create',
            'support_customer_edit',
        ];

        foreach ($permissions as $permission) {
            if (is_string($permission) && in_array($permission, $allowedSlugs, true)) {
                return true;
            }

            if (is_array($permission) && isset($permission['slug']) && in_array((string) $permission['slug'], $allowedSlugs, true)) {
                return true;
            }
        }

        return ispts_is_authenticated();
    }
}

if (!function_exists('ispts_ensure_customers_table')) {
    function ispts_ensure_customers_table(PDO $pdo): void
    {
        $hasColumn = static function (PDO $pdo, string $table, string $column): bool {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
            );
            $stmt->bindValue(':table', $table);
            $stmt->bindValue(':column', $column);
            $stmt->execute();

            return (int) $stmt->fetchColumn() > 0;
        };

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customers (
                customer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(120) NOT NULL,
                phone_no VARCHAR(30) NOT NULL,
                registered_date DATE NOT NULL,
                address TEXT NULL,
                area VARCHAR(120) NULL,
                sub_area VARCHAR(120) NULL,
                package_id VARCHAR(100) NULL,
                package_activate_date DATE NULL,
                package_expire_date DATE NULL,
                nid_other_documents TEXT NULL,
                deposit_money DECIMAL(12,2) NOT NULL DEFAULT 0,
                connection_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
                assigned_devices TEXT NULL,
                support_ticket TEXT NULL,
                documents TEXT NULL,
                payment TEXT NULL,
                invoices TEXT NULL,
                notes TEXT NULL,
                branch VARCHAR(120) NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_customers_username (username),
                KEY idx_customers_phone_no (phone_no),
                KEY idx_customers_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $legacySafeColumns = [
            'username' => "ALTER TABLE customers ADD COLUMN username VARCHAR(120) NOT NULL DEFAULT ''",
            'phone_no' => "ALTER TABLE customers ADD COLUMN phone_no VARCHAR(30) NOT NULL DEFAULT ''",
            'registered_date' => "ALTER TABLE customers ADD COLUMN registered_date DATE NULL",
            'address' => "ALTER TABLE customers ADD COLUMN address TEXT NULL",
            'area' => "ALTER TABLE customers ADD COLUMN area VARCHAR(120) NULL",
            'sub_area' => "ALTER TABLE customers ADD COLUMN sub_area VARCHAR(120) NULL",
            'package_id' => "ALTER TABLE customers ADD COLUMN package_id VARCHAR(100) NULL",
            'package_activate_date' => "ALTER TABLE customers ADD COLUMN package_activate_date DATE NULL",
            'package_expire_date' => "ALTER TABLE customers ADD COLUMN package_expire_date DATE NULL",
            'nid_other_documents' => "ALTER TABLE customers ADD COLUMN nid_other_documents TEXT NULL",
            'deposit_money' => "ALTER TABLE customers ADD COLUMN deposit_money DECIMAL(12,2) NOT NULL DEFAULT 0",
            'connection_charge' => "ALTER TABLE customers ADD COLUMN connection_charge DECIMAL(12,2) NOT NULL DEFAULT 0",
            'assigned_devices' => "ALTER TABLE customers ADD COLUMN assigned_devices TEXT NULL",
            'support_ticket' => "ALTER TABLE customers ADD COLUMN support_ticket TEXT NULL",
            'documents' => "ALTER TABLE customers ADD COLUMN documents TEXT NULL",
            'payment' => "ALTER TABLE customers ADD COLUMN payment TEXT NULL",
            'invoices' => "ALTER TABLE customers ADD COLUMN invoices TEXT NULL",
            'notes' => "ALTER TABLE customers ADD COLUMN notes TEXT NULL",
            'branch' => "ALTER TABLE customers ADD COLUMN branch VARCHAR(120) NULL",
            'created_by' => "ALTER TABLE customers ADD COLUMN created_by BIGINT UNSIGNED NULL",
            'updated_by' => "ALTER TABLE customers ADD COLUMN updated_by BIGINT UNSIGNED NULL",
            'created_at' => "ALTER TABLE customers ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            'status' => "ALTER TABLE customers ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1",
        ];

        foreach ($legacySafeColumns as $column => $sql) {
            if (!$hasColumn($pdo, 'customers', $column)) {
                $pdo->exec($sql);
            }
        }

        // Backfill null legacy rows so list queries remain stable.
        $pdo->exec('UPDATE customers SET registered_date = CURDATE() WHERE registered_date IS NULL');
    }
}
