<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';

$appBasePath = ispts_resolve_app_base_path(dirname(__DIR__, 3));

ispts_start_session();
$authUriExt = $_SESSION['auth_uri_extension'] ?? '';

// We need the physical base prefix (/isp360) rather than the virtual one if we construct manually.
// $appBasePath here might already be virtualized (e.g. /isp360/friendsonline). 
// Let's get the true physical base path by removing the virtual segment if present.
$physicalBasePath = $appBasePath;
if ($authUriExt !== '') {
    $physicalBasePath = preg_replace('#/' . preg_quote($authUriExt, '#') . '$#', '', $physicalBasePath);
}

// Strip /sadmin if it exists at the end to get the true physical root
$physicalBasePath = preg_replace('#/sadmin$#', '', $physicalBasePath);

ispts_logout_admin();

if ($authUriExt !== '') {
    $redirectUrl = $physicalBasePath . '/' . $authUriExt . '/login.php';
} else {
    $redirectUrl = $physicalBasePath . '/sadmin/login.php';
}

// Clean up double slashes just in case
$redirectUrl = preg_replace('#/+#', '/', '/' . ltrim($redirectUrl, '/'));

header('Location: ' . $redirectUrl);
exit;
