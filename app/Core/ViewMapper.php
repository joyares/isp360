<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class ViewMapper
{
    private string $publicRoot;

    public function __construct(?string $publicRoot = null)
    {
        $this->publicRoot = $publicRoot ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    }

    /**
     * @return array<string, string>
     */
    public function ispts_map_all_views(): array
    {
        $map = [];

        $dashboardPath = $this->publicRoot . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($dashboardPath)) {
            $map['dashboard'] = $dashboardPath;
            $map['home'] = $dashboardPath;
        }

        $appRoot = $this->publicRoot . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($appRoot)) {
            return $map;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
                continue;
            }

            $absolutePath = $fileInfo->getPathname();
            $relativePath = str_replace($this->publicRoot . DIRECTORY_SEPARATOR, '', $absolutePath);
            $relativeWithoutExt = preg_replace('/\.php$/i', '', $relativePath);

            if (!is_string($relativeWithoutExt) || $relativeWithoutExt === '') {
                continue;
            }

            $dotAlias = str_replace([DIRECTORY_SEPARATOR, '/'], '.', $relativeWithoutExt);
            $dotAlias = preg_replace('/^app\./i', '', (string) $dotAlias);

            if (!is_string($dotAlias) || $dotAlias === '') {
                continue;
            }

            $map[$dotAlias] = $absolutePath;
            $map[$relativeWithoutExt] = $absolutePath;
            $map[str_replace('.', '/', $dotAlias)] = $absolutePath;
        }

        ksort($map);

        return $map;
    }

    public function ispts_resolve_view(string $viewKey): string
    {
        $normalizedKey = trim(str_replace('\\', '/', $viewKey), '/');
        if ($normalizedKey === '') {
            throw new RuntimeException('View key cannot be empty.');
        }

        $dotKey = str_replace('/', '.', $normalizedKey);
        $slashKey = str_replace('.', '/', $normalizedKey);

        $map = $this->ispts_map_all_views();

        $candidates = [
            $normalizedKey,
            $dotKey,
            $slashKey,
            'app/' . $slashKey,
        ];

        foreach ($candidates as $candidate) {
            if (isset($map[$candidate])) {
                return $map[$candidate];
            }
        }

        throw new RuntimeException(sprintf('View not mapped: %s', $viewKey));
    }
}
