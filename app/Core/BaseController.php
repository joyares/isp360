<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class BaseController
{
    private const RBAC_HELPER_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'rbac_helper.php';

    protected string $viewsRoot;

    protected string $layoutsRoot;

    public function __construct(?string $viewsRoot = null)
    {
        $this->viewsRoot = $viewsRoot ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views';
        $this->layoutsRoot = $this->viewsRoot . DIRECTORY_SEPARATOR . 'layouts';

        if (is_file(self::RBAC_HELPER_PATH)) {
            require_once self::RBAC_HELPER_PATH;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $viewName, array $data = []): void
    {
        $headerPath = $this->layoutsRoot . DIRECTORY_SEPARATOR . 'header.php';
        $sidenavPath = $this->layoutsRoot . DIRECTORY_SEPARATOR . 'sidenav.php';
        $footerPath = $this->layoutsRoot . DIRECTORY_SEPARATOR . 'footer.php';
        $viewPath = $this->resolveViewPath($viewName);

        $data['breadcrumbs'] = $this->buildBreadcrumbTrail($data['breadcrumb'] ?? []);

        extract($data, EXTR_SKIP);

        $this->requireFile($headerPath);
        $this->requireFile($sidenavPath);
        $this->requireFile($viewPath);
        $this->requireFile($footerPath);
    }

    protected function resolveViewPath(string $viewName): string
    {
        $normalizedView = str_replace(['.', '\\'], DIRECTORY_SEPARATOR, $viewName);
        $path = $this->viewsRoot . DIRECTORY_SEPARATOR . $normalizedView;

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
            $path .= '.php';
        }

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('View file not found: %s', $path));
        }

        return $path;
    }

    /**
     * @param mixed $breadcrumb
     * @return array<int, array{label: string, url: ?string, active: bool}>
     */
    protected function buildBreadcrumbTrail($breadcrumb): array
    {
        $segments = [];

        if (is_string($breadcrumb)) {
            $segments = array_values(array_filter(array_map('trim', explode('>', $breadcrumb)), static fn (string $item): bool => $item !== ''));
        } elseif (is_array($breadcrumb)) {
            $segments = $breadcrumb;
        }

        $trail = [];
        $lastIndex = count($segments) - 1;

        foreach ($segments as $index => $item) {
            if (is_string($item)) {
                $label = trim($item);
                $url = null;
            } elseif (is_array($item)) {
                $label = trim((string) ($item['label'] ?? ''));
                $url = isset($item['url']) ? (string) $item['url'] : null;
            } else {
                continue;
            }

            if ($label === '') {
                continue;
            }

            $trail[] = [
                'label' => $label,
                'url' => $index === $lastIndex ? null : $url,
                'active' => $index === $lastIndex,
            ];
        }

        return $trail;
    }

    protected function requireFile(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Required layout file not found: %s', $path));
        }

        require $path;
    }

    /**
     * Enforce that the current user holds the given permission slug.
     * Throws a RuntimeException with HTTP 403 context when permission is absent.
     */
    protected function requirePermission(string $slug): void
    {
        if (!function_exists('has_permission')) {
            throw new RuntimeException('RBAC helper not loaded.');
        }

        if (!has_permission($slug)) {
            http_response_code(403);
            throw new RuntimeException(
                sprintf('Access denied: permission "%s" is required.', $slug)
            );
        }
    }
}
