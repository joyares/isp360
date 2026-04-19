<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'ViewMapper.php';

use App\Core\ViewMapper;

$mapper = new ViewMapper(__DIR__);

$requestedView = (string) ($_GET['view'] ?? 'dashboard');

if ($requestedView === '' || $requestedView === '/') {
    $requestedView = 'dashboard';
}

try {
    $targetViewPath = $mapper->ispts_resolve_view($requestedView);
    $originalCwd = getcwd();
    $targetDir = dirname($targetViewPath);

    if ($targetDir !== '' && is_dir($targetDir)) {
        chdir($targetDir);
    }

    require basename($targetViewPath);

    if (is_string($originalCwd) && $originalCwd !== '') {
        chdir($originalCwd);
    }
} catch (Throwable $exception) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'View not found: ' . $requestedView . PHP_EOL;
    echo $exception->getMessage();
}
