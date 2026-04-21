<?php

declare(strict_types=1);

// Hosting bridge: works when document root is project root instead of /public.
chdir(__DIR__ . DIRECTORY_SEPARATOR . 'public');
require __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
