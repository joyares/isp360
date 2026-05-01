<?php
$dirs = ['d:/laragon/www/isp360/public/app', 'd:/laragon/www/isp360/views'];
$count = 0;
foreach($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach($it as $f) {
        if($f->getExtension()!=='php') continue;
        $c = file_get_contents($f->getPathname());
        $orig = $c;
        // remove the injected token and any leading newline/spaces up to it
        $c = preg_replace('/\r?\n\s*<\?= ispts_csrf_field\(\) \?>/', '', $c);
        if($orig !== $c) {
            file_put_contents($f->getPathname(), $c);
            $count++;
            echo 'FIXED: ' . $f->getPathname() . PHP_EOL;
        }
    }
}
echo 'Total fixed: ' . $count . PHP_EOL;
