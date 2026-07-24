<?php

declare(strict_types=1);

namespace App\Support;

final class PublicView
{
    /**
     * @param array<string, mixed> $contentData
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function render(string $partial, array $contentData = [], ?string $title = null, int $status = 200): array
    {
        $portalContext = PublicPortalContext::current();
        $cupConfig = CupConfigLoader::current();
        $features = CupConfigLoader::featureFlags($cupConfig);
        $user = Auth::user();
        $currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $pageTitle = $title ?? (string) ($cupConfig['name'] ?? $portalContext['display_name']);
        $isHome = $partial === 'home-content' || $currentPath === '/';
        $brand = is_array($cupConfig['brand'] ?? null) ? $cupConfig['brand'] : [];

        $contentData['portal_context'] = $portalContext;
        $contentData['cup_config'] = $cupConfig;
        $contentData['user'] = $user;
        $contentData['current_path'] = $currentPath;
        $contentData['labels'] = $contentData['labels'] ?? $portalContext['labels'];
        $contentData['hide_page_title'] = !$isHome;
        $content = Response::partial($partial, $contentData);

        $menuItems = CupConfigLoader::menuItems($cupConfig);
        if ($menuItems === []) {
            $menuItems = PublicMenu::mainItems($features);
        }

        return Response::view('layout', [
            'title' => $pageTitle,
            'content' => $content,
            'portal_context' => $portalContext,
            'cup_config' => $cupConfig,
            'user' => $user,
            'nav_items' => $menuItems,
            'user_menu_items' => PublicMenu::userItems(),
            'current_path' => $currentPath,
            'flash' => Session::pullFlash(),
            'show_page_hero' => !$isHome,
            'page_hero_title' => $pageTitle,
            'page_hero_subtitle' => '',
            'page_hero_logo' => (string) ($brand['logo'] ?? $brand['hero_image'] ?? ''),
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
        $portalContext = PublicPortalContext::current();
        $cupConfig = CupConfigLoader::current();

        return self::render('home-content', [
            'portal_context' => $portalContext,
        ], (string) ($cupConfig['name'] ?? $portalContext['display_name']));
    }

    /**
     * @param array{cta_url?: string, cta_label?: string} $extras
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function renderPlaceholder(string $title, string $description, array $extras = []): array
    {
        return self::render('placeholder-content', [
            'page_title' => $title,
            'page_description' => $description,
            'cta_url' => trim((string) ($extras['cta_url'] ?? '')),
            'cta_label' => trim((string) ($extras['cta_label'] ?? '')),
        ], $title);
    }
}
