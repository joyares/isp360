<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';

$appBasePath = ispts_resolve_app_base_path(dirname(__DIR__, 3));

ispts_logout_admin();
header('Location: ' . ispts_get_login_path($appBasePath));
exit;
