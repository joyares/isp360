<?php
$dirs = ['d:/laragon/www/isp360/public/app', 'd:/laragon/www/isp360/views'];
$count = 0;
foreach($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach($it as $f) {
        if($f->getExtension()!=='php') continue;
        $c = file_get_contents($f->getPathname());
        $orig = $c;
        
        $offset = 0;
        while (($pos = stripos($c, '<form', $offset)) !== false) {
            // Check if it's a post form
            // We need to find the end of this tag first
            $inPhp = false;
            $inQuote = false;
            $quoteChar = '';
            $tagEnd = -1;
            
            for ($i = $pos; $i < strlen($c); $i++) {
                $char = $c[$i];
                
                if (!$inPhp && !$inQuote && $char === '<' && substr($c, $i, 2) === '<?') {
                    $inPhp = true;
                } elseif ($inPhp && $char === '?' && substr($c, $i, 2) === '?>') {
                    $inPhp = false;
                    $i++; // skip '>'
                    continue;
                }
                
                if (!$inPhp) {
                    if (!$inQuote && ($char === '"' || $char === "'")) {
                        $inQuote = true;
                        $quoteChar = $char;
                    } elseif ($inQuote && $char === $quoteChar) {
                        $inQuote = false;
                    }
                    
                    if (!$inQuote && $char === '>') {
                        $tagEnd = $i;
                        break;
                    }
                }
            }
            
            if ($tagEnd !== -1) {
                $formTag = substr($c, $pos, $tagEnd - $pos + 1);
                if (stripos($formTag, 'method="post"') !== false || stripos($formTag, "method='post'") !== false || stripos($formTag, 'method=post') !== false) {
                    // It's a POST form, inject CSRF after the tag
                    $inject = "\n" . '            <?= ispts_csrf_field() ?>';
                    
                    // But first check if it already has it right after
                    $after = substr($c, $tagEnd + 1, 100);
                    if (strpos($after, 'ispts_csrf_field()') === false) {
                        $c = substr($c, 0, $tagEnd + 1) . $inject . substr($c, $tagEnd + 1);
                    }
                }
            }
            $offset = $pos + 5; // move past '<form'
        }
        
        if($orig !== $c) {
            file_put_contents($f->getPathname(), $c);
            $count++;
            echo 'FIXED: ' . $f->getPathname() . PHP_EOL;
        }
    }
}
echo 'Total injected: ' . $count . PHP_EOL;
