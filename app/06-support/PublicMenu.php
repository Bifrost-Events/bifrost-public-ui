<?php

declare(strict_types=1);

namespace App\Support;

final class PublicMenu
{
    /**
     * @param array<string, bool> $features
     * @return list<array{id: string, label: string, url: string, feature?: string}>
     */
    public static function mainItems(array $features = []): array
    {
        $items = Config::get('navigation.main', []);
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $feature = trim((string) ($item['feature'] ?? ''));
            if ($feature !== '' && !($features[$feature] ?? false)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @return list<array{id: string, label: string, url: string, class?: string}>
     */
    public static function userItems(): array
    {
        $items = Config::get('navigation.user', []);

        return is_array($items) ? $items : [];
    }

    public static function isActive(string $url, string $currentPath): bool
    {
        if ($url === '/') {
            return $currentPath === '/';
        }

        return $currentPath === $url || str_starts_with($currentPath, rtrim($url, '/') . '/');
    }
}
