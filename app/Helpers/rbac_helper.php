<?php

declare(strict_types=1);

if (!function_exists('has_permission')) {
    function has_permission(string $slug): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $userPermissions = $_SESSION['user_permissions'] ?? [];

        if (!is_array($userPermissions)) {
            return false;
        }

        foreach ($userPermissions as $permission) {
            if (is_string($permission) && $permission === $slug) {
                return true;
            }

            if (is_array($permission) && isset($permission['slug']) && (string) $permission['slug'] === $slug) {
                return true;
            }
        }

        return false;
    }
}
