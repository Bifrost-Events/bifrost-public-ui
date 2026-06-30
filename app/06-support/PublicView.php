<?php

declare(strict_types=1);

namespace App\Support;

use App\Service\BackendApiClient;

final class PublicView
{
    /**
     * @param array<string, mixed> $contentData
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function render(string $partial, array $contentData = [], ?string $title = null, int $status = 200): array
    {
        $tenantContext = TenantContext::current();
        $cupConfig = CupConfigLoader::current();
        $features = CupConfigLoader::featureFlags($cupConfig);
        $user = Auth::user();
        $currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $pageTitle = $title ?? (string) ($cupConfig['name'] ?? $tenantContext['display_name']);

        $contentData['tenant_context'] = $tenantContext;
        $contentData['cup_config'] = $cupConfig;
        $contentData['user'] = $user;
        $contentData['current_path'] = $currentPath;
        $content = Response::partial($partial, $contentData);

        $menuItems = CupConfigLoader::menuItems($cupConfig);
        if ($menuItems === []) {
            $menuItems = PublicMenu::mainItems($features);
        }

        return Response::view('layout', [
            'title' => $pageTitle,
            'content' => $content,
            'tenant_context' => $tenantContext,
            'cup_config' => $cupConfig,
            'user' => $user,
            'nav_items' => $menuItems,
            'user_menu_items' => PublicMenu::userItems(),
            'current_path' => $currentPath,
            'flash' => Session::pullFlash(),
            'backend_health' => null,
        ], $status);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    public static function renderNotFound(): array
    {
        return self::render('404-content', [], 'Ikke funnet', 404);
    }

    public static function partialHtml(string $partial, array $contentData = []): string
    {
        return Response::partial($partial, $contentData);
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function renderHome(): array
    {
        $tenantContext = TenantContext::current();
        $cupConfig = CupConfigLoader::current();
        $client = new BackendApiClient();

        return self::render('home-content', [
            'health' => $client->health(),
        ], (string) ($cupConfig['name'] ?? $tenantContext['display_name']));
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function renderPlaceholder(string $title, string $description): array
    {
        return self::render('placeholder-content', [
            'page_title' => $title,
            'page_description' => $description,
        ], $title);
    }
}
