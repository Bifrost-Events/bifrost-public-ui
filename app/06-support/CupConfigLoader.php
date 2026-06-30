<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Laster managed cup-config fra JSON basert på HTTP-host.
 */
final class CupConfigLoader
{
    /** @var array<string, string> host (uten port) => config-filnavn */
    private const HOST_MAP = [
        // Lokal utvikling / quality (forenklet hostnames)
        'slatlem.local' => 'slatlem-cup.json',
        'jaktfeltcup.local' => 'nasjonal-15m-jaktfeltcup.json',
        'namdal.local' => 'namdal-jaktfeltkarusell.json',
        // Legacy lokale hostnames (bakoverkompatibilitet)
        'slatlemcup.local' => 'slatlem-cup.json',
        'namdal.jaktfeltkarusell.local' => 'namdal-jaktfeltkarusell.json',
        // Test (sky, manuell demo)
        'test.slatlemcup.no' => 'slatlem-cup.json',
        'test.jaktfeltcup.no' => 'nasjonal-15m-jaktfeltcup.json',
        'test.namdal.jaktfeltkarusell.no' => 'namdal-jaktfeltkarusell.json',
        // Staging (automatisk quality)
        'staging.slatlemcup.no' => 'slatlem-cup.json',
        'staging.jaktfeltcup.no' => 'nasjonal-15m-jaktfeltcup.json',
        'staging.namdal.jaktfeltkarusell.no' => 'namdal-jaktfeltkarusell.json',
        // Produksjon
        'slatlemcup.no' => 'slatlem-cup.json',
        'www.slatlemcup.no' => 'slatlem-cup.json',
        'jaktfeltcup.no' => 'nasjonal-15m-jaktfeltcup.json',
        'www.jaktfeltcup.no' => 'nasjonal-15m-jaktfeltcup.json',
        'namdal.jaktfeltkarusell.no' => 'namdal-jaktfeltkarusell.json',
        'www.namdal.jaktfeltkarusell.no' => 'namdal-jaktfeltkarusell.json',
    ];

    /** @var array<string, mixed>|null */
    private static ?array $cached = null;

    /**
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $host = TenantContext::requestHost();
        $filename = self::HOST_MAP[$host] ?? 'default.json';
        $path = dirname(__DIR__, 2) . '/config/cups/' . $filename;

        if (!is_file($path)) {
            $path = dirname(__DIR__, 2) . '/config/cups/default.json';
            $filename = 'default.json';
        }

        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            $data = self::fallbackConfig();
            $filename = 'default.json (fallback)';
        }

        $data['_meta'] = [
            'config_file' => $filename,
            'resolved_host' => $host,
            'is_default' => !isset(self::HOST_MAP[$host]),
        ];

        self::$cached = $data;

        return self::$cached;
    }

    public static function isDevelopmentBannerVisible(): bool
    {
        return Environment::isDevelopment() || (bool) Config::get('app.debug', false);
    }

    /**
     * @return list<string>
     */
    public static function listManagedCups(): array
    {
        $dir = dirname(__DIR__, 2) . '/config/cups';
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) ?: [] as $file) {
            if (!str_ends_with($file, '.json') || $file === 'default.json') {
                continue;
            }
            $files[] = $file;
        }

        sort($files);

        return $files;
    }

    /**
     * Mapper cup-config features til navigasjons-/tenant-features.
     *
     * @param array<string, mixed> $cupConfig
     * @return array<string, bool>
     */
    public static function featureFlags(array $cupConfig): array
    {
        $features = is_array($cupConfig['features'] ?? null) ? $cupConfig['features'] : [];

        return [
            'signup' => (bool) ($features['signup'] ?? true),
            'results' => (bool) ($features['results'] ?? true),
            'standings' => (bool) ($features['standings'] ?? true),
            'sponsors' => (bool) ($features['sponsors'] ?? false),
            'sponsor_page' => (bool) ($features['sponsors'] ?? false),
            'news' => (bool) ($features['news'] ?? false),
            'custom_pages' => (bool) ($features['custom_pages'] ?? false),
            'map' => (bool) ($features['map'] ?? false),
            'archive' => (bool) ($features['archive'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed> $cupConfig
     * @return list<array{id: string, label: string, url: string, feature?: string}>
     */
    public static function menuItems(array $cupConfig): array
    {
        $layout = is_array($cupConfig['layout'] ?? null) ? $cupConfig['layout'] : [];
        $menu = is_array($layout['menu'] ?? null) ? $layout['menu'] : [];
        $features = self::featureFlags($cupConfig);
        $out = [];

        foreach ($menu as $item) {
            if (!is_array($item)) {
                continue;
            }
            $feature = trim((string) ($item['feature'] ?? ''));
            if ($feature !== '' && !($features[$feature] ?? false)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            $label = (string) ($item['label'] ?? '');
            $url = (string) ($item['url'] ?? '#');
            if ($id === '' || $label === '') {
                continue;
            }
            $entry = ['id' => $id, 'label' => $label, 'url' => $url];
            if ($feature !== '') {
                $entry['feature'] = $feature;
            }
            $out[] = $entry;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $cupConfig
     * @return array<string, string>
     */
    public static function cssVariables(array $cupConfig): array
    {
        $brand = is_array($cupConfig['brand'] ?? null) ? $cupConfig['brand'] : [];
        $primary = (string) ($brand['primary_color'] ?? '#2c5530');
        $secondary = (string) ($brand['secondary_color'] ?? $primary);
        $accent = (string) ($brand['accent_color'] ?? '#e8f0e9');
        $headerBg = (string) ($brand['header_bg'] ?? $secondary);
        $primaryLight = (string) ($brand['primary_light'] ?? $accent);

        return [
            '--bg' => '#f5f5f5',
            '--header' => $headerBg,
            '--header-text' => '#ffffff',
            '--card' => '#ffffff',
            '--ink' => '#1a1a18',
            '--muted' => '#5c5c58',
            '--line' => '#d4d8d2',
            '--accent' => $primary,
            '--accent-hover' => $secondary,
            '--accent-soft' => $accent,
            '--accent-light' => $primaryLight,
            '--bad' => '#9b2c2c',
            '--ok' => $primary,
            '--link' => $primary,
        ];
    }

    /** @return array<string, mixed> */
    private static function fallbackConfig(): array
    {
        return [
            'cup_id' => 'default',
            'name' => 'Bifrost Cup',
            'brand' => ['primary_color' => '#2c5530', 'secondary_color' => '#1e2a22', 'accent_color' => '#e8f0e9'],
            'layout' => ['frontpage_blocks' => ['intro', 'contact'], 'menu' => []],
            'features' => ['signup' => true, 'results' => true, 'standings' => true, 'sponsors' => false],
            'content' => ['frontpage_title' => 'Velkommen', 'intro_text' => '', 'about_text' => '', 'contact_text' => ''],
            'sponsors' => ['presentation_level' => 'minimal', 'placements' => [], 'tiers' => []],
        ];
    }
}
