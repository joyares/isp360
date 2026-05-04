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
        $physicalBase = ispts_get_physical_base_path();
        
        ispts_start_session();
        $uriPath = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
        
        // 1. Check for explicit extension in URL or session
        $ext = $_GET['ext'] ?? '';
        if ($ext === '' && !empty($_SESSION['auth_uri_extension'])) {
            $ext = $_SESSION['auth_uri_extension'];
        }
        
        if ($ext !== '') {
            return preg_replace('#/+#', '/', $physicalBase . '/' . $ext . '/login.php');
        }

        // 2. Check if we are in an admin context
        if (strpos($uriPath, $physicalBase . '/sadmin') !== false || strpos($uriPath, '/sadmin') !== false) {
             return preg_replace('#/+#', '/', $physicalBase . '/sadmin/login.php');
        }

        // 3. Default to sadmin login if no context found
        return preg_replace('#/+#', '/', $physicalBase . '/sadmin/login.php');
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

if (!function_exists('ispts_get_physical_base_path')) {
    /**
     * Returns the physical root of the application relative to the domain (e.g. /isp360).
     */
    function ispts_get_physical_base_path(?string $publicRootPath = null): string
    {
        static $physicalPath = null;
        if ($physicalPath !== null && $publicRootPath === null) {
            return $physicalPath;
        }

        $documentRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $resolvedPublicRootPath = $publicRootPath !== null ? realpath($publicRootPath) : realpath(dirname(__DIR__));

        if (!$documentRootPath || !$resolvedPublicRootPath) {
            return '';
        }

        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRootPath), '/');
        $normalizedPublicRoot = rtrim(str_replace('\\', '/', $resolvedPublicRootPath), '/');

        if ($normalizedDocumentRoot === '' || strpos($normalizedPublicRoot, $normalizedDocumentRoot) !== 0) {
            $path = '';
        } else {
            $rawPath = substr($normalizedPublicRoot, strlen($normalizedDocumentRoot));
            // Strip /public from the end to find the project root
            $path = preg_replace('#/public$#', '', '/' . trim((string) $rawPath, '/'));
            $path = '/' . trim($path, '/');
            if ($path === '/') {
                $path = '';
            }
        }

        if ($publicRootPath === null) {
            $physicalPath = $path;
        }
        return $path;
    }
}

if (!function_exists('ispts_resolve_app_base_path')) {
    /**
     * Returns the current virtual base path (e.g. /isp360/friendsonline or /isp360/sadmin).
     */
    function ispts_resolve_app_base_path(?string $publicRootPath = null): string
    {
        $physicalBase = ispts_get_physical_base_path($publicRootPath);
        
        ispts_start_session();
        $userType = $_SESSION['user_type'] ?? '';
        $uriPath = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];

        $virtualPath = $physicalBase;

        // 1. Logged in Staff Context
        if ($userType === 'staff' && !empty($_SESSION['auth_uri_extension'])) {
            $virtualPath = $physicalBase . '/' . $_SESSION['auth_uri_extension'];
        } 
        // 2. Logged in Admin Context
        elseif ($userType === 'admin') {
            $virtualPath = $physicalBase . '/sadmin';
        }
        // 3. Unauthenticated URL-based Context Detection
        else {
            if (isset($_GET['ext']) && $_GET['ext'] !== '') {
                $virtualPath = $physicalBase . '/' . trim($_GET['ext']);
            } elseif (strpos($uriPath, $physicalBase . '/sadmin') !== false || strpos($uriPath, '/sadmin') !== false) {
                $virtualPath = $physicalBase . '/sadmin';
            }
        }

        return preg_replace('#/+#', '/', '/' . ltrim($virtualPath, '/'));
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
                if (is_string($node) && $node !== '') {
                    $slugs[] = $node;
                    continue;
                }
                
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
        
        // 1. Try Admin Users
        $stmt = $pdo->prepare(
            'SELECT au.admin_user_id AS user_id,
                    au.role_id,
                    au.full_name,
                    au.username,
                    au.password_hash,
                    au.status,
                    r.role_name,
                    r.menu_tree_json,
                    \'admin\' AS user_type
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

        $_SESSION['admin_user_id'] = (int) $user['user_id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        $_SESSION['admin_full_name'] = (string) ($user['full_name'] ?? 'User');
        $_SESSION['admin_role_id'] = (int) ($user['role_id'] ?? 0);
        $_SESSION['admin_role_name'] = (string) ($user['role_name'] ?? '');
        $_SESSION['user_type'] = (string) $user['user_type'];

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

if (!function_exists('ispts_attempt_staff_login')) {
    function ispts_attempt_staff_login(string $username, string $password, string $companyAuthUriExt): array
    {
        ispts_start_session();

        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and Password are required.'];
        }

        $pdo = Database::getConnection();

        // 1. Verify Company by Auth URI Ext
        $stmt = $pdo->prepare('SELECT id, company, logo_icon, logo_main FROM companies WHERE auth_uri_extension = :ext AND enabled = 1 AND deleted_at IS NULL LIMIT 1');
        $stmt->bindValue(':ext', $companyAuthUriExt);
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            return ['success' => false, 'message' => 'Invalid or inactive Company URL.'];
        }

        // 2. Try Staff Users in this company
        $stmt = $pdo->prepare(
            'SELECT su.staff_user_id AS user_id,
                    su.role_id,
                    su.full_name,
                    su.username,
                    su.password_hash,
                    su.status,
                    r.role_name,
                    r.menu_tree_json,
                    \'staff\' AS user_type
             FROM staff_users su
             LEFT JOIN roles r ON r.role_id = su.role_id
             WHERE su.username = :username AND su.company_id = :company_id
             LIMIT 1'
        );
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':company_id', $company['id']);
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

        $_SESSION['admin_user_id'] = (int) $user['user_id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        $_SESSION['admin_full_name'] = (string) ($user['full_name'] ?? 'User');
        $_SESSION['admin_role_id'] = (int) ($user['role_id'] ?? 0);
        $_SESSION['admin_role_name'] = (string) ($user['role_name'] ?? '');
        $_SESSION['user_type'] = (string) $user['user_type'];
        $_SESSION['company_id'] = (int) $company['id'];
        $_SESSION['company_name'] = (string) $company['company'];
        $_SESSION['company_logo'] = (string) ($company['logo_icon'] ?? '');
        $_SESSION['company_logo_main'] = (string) ($company['logo_main'] ?? '');
        $_SESSION['auth_uri_extension'] = $companyAuthUriExt;

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

if (!function_exists('has_permission')) {
    function has_permission(string $slug): bool
    {
        ispts_start_session();

        if (!ispts_is_authenticated()) {
            return false;
        }

        $roleName = strtolower((string) ($_SESSION['admin_role_name'] ?? ''));
        if (
            $roleName === 'super admin' ||
            $roleName === 'super daddy' ||
            strpos($roleName, 'super admin') !== false ||
            strpos($roleName, 'super daddy') !== false
        ) {
            return true;
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        if (!is_array($permissions)) {
            return false;
        }

        return in_array($slug, $permissions, true);
    }
}

if (!function_exists('ispts_can_manage_customers')) {
    function ispts_can_manage_customers(): bool
    {
        if (has_permission('customer') || has_permission('customer_registration')) {
            return true;
        }

        $allowedSlugs = [
            'customer_create',
            'customer_edit',
            'customer_registration',
            'support_customer_create',
            'support_customer_edit',
        ];

        foreach ($allowedSlugs as $slug) {
            if (has_permission($slug)) {
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
                radious_id VARCHAR(120) NULL,
                nid VARCHAR(120) NULL,
                email VARCHAR(120) NULL,
                full_name VARCHAR(255) NULL,
                mobile VARCHAR(30) NULL,
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
                company_id BIGINT UNSIGNED NULL,
                branch_id BIGINT UNSIGNED NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_customers_username (username),
                UNIQUE KEY uk_customers_radious_id (radious_id),
                UNIQUE KEY uk_customers_nid (nid),
                KEY idx_customers_phone_no (phone_no),
                KEY idx_customers_company (company_id),
                KEY idx_customers_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Rename branch to branch_id if it exists
        if ($hasColumn($pdo, 'customers', 'branch') && !$hasColumn($pdo, 'customers', 'branch_id')) {
            $pdo->exec("ALTER TABLE customers CHANGE COLUMN branch branch_id BIGINT UNSIGNED NULL");
        }

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
            'company_id' => "ALTER TABLE customers ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER customer_id",
            'branch_id' => "ALTER TABLE customers ADD COLUMN branch_id BIGINT UNSIGNED NULL",
            'created_by' => "ALTER TABLE customers ADD COLUMN created_by BIGINT UNSIGNED NULL",
            'updated_by' => "ALTER TABLE customers ADD COLUMN updated_by BIGINT UNSIGNED NULL",
            'created_at' => "ALTER TABLE customers ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE customers ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            'status' => "ALTER TABLE customers ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1",
            'radious_id' => "ALTER TABLE customers ADD COLUMN radious_id VARCHAR(120) NULL",
            'nid' => "ALTER TABLE customers ADD COLUMN nid VARCHAR(120) NULL",
            'email' => "ALTER TABLE customers ADD COLUMN email VARCHAR(120) NULL",
            'full_name' => "ALTER TABLE customers ADD COLUMN full_name VARCHAR(255) NULL",
            'mobile' => "ALTER TABLE customers ADD COLUMN mobile VARCHAR(30) NULL",
        ];

        $addUniqueConstraint = static function (PDO $pdo, string $table, string $column): void {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint');
            $stmt->execute([':table' => $table, ':constraint' => 'uk_customers_' . $column]);
            if ((int) $stmt->fetchColumn() === 0) {
                try {
                    $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT uk_customers_{$column} UNIQUE ({$column})");
                } catch (\PDOException $e) {}
            }
        };

        foreach ($legacySafeColumns as $column => $sql) {
            if (!$hasColumn($pdo, 'customers', $column)) {
                $pdo->exec($sql);
            }
        }

        $addUniqueConstraint($pdo, 'customers', 'radious_id');
        $addUniqueConstraint($pdo, 'customers', 'nid');

        // Backfill null legacy rows so list queries remain stable.
        $pdo->exec('UPDATE customers SET registered_date = CURDATE() WHERE registered_date IS NULL');
    }
}

// ─── CSRF Protection ──────────────────────────────────────────────

if (!function_exists('ispts_csrf_token')) {
    /**
     * Generate or return the current CSRF token for the session.
     */
    function ispts_csrf_token(): string
    {
        ispts_start_session();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('ispts_csrf_field')) {
    /**
     * Return an HTML hidden input containing the CSRF token.
     * Drop this inside any <form> that uses POST.
     */
    function ispts_csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(ispts_csrf_token()) . '">';
    }
}

if (!function_exists('ispts_csrf_validate')) {
    /**
     * Validate the CSRF token on a POST request.
     * Call this at the top of any POST handler.
     * Returns true if valid, false otherwise.
     * When $abort is true (default), it will die() with a 403 on failure.
     */
    function ispts_csrf_validate(bool $abort = true): bool
    {
        ispts_start_session();
        $submitted = $_POST['_csrf_token'] ?? '';
        $expected  = $_SESSION['_csrf_token'] ?? '';

        if ($expected === '' || !hash_equals($expected, $submitted)) {
            if ($abort) {
                http_response_code(403);
                die('Invalid or missing CSRF token. Please reload the page and try again.');
            }
            return false;
        }
        return true;
    }
}
