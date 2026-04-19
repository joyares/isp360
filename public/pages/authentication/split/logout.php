<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';

$documentRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$projectRootPath = realpath(dirname(__DIR__, 3));

$appBasePath = '';
if ($documentRootPath && $projectRootPath) {
    $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRootPath), '/');
    $normalizedProjectRoot = rtrim(str_replace('\\', '/', $projectRootPath), '/');
    if ($normalizedDocumentRoot !== '' && strpos($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
        $appBasePath = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
    }
}

$appBasePath = '/' . trim($appBasePath, '/');
if ($appBasePath === '/') {
    $appBasePath = '';
}

ispts_logout_admin();
header('Location: ' . ispts_get_login_path($appBasePath));
exit;
