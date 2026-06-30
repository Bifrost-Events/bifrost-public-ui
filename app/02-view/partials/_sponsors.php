<?php

declare(strict_types=1);

/**
 * Sponsor-renderer for cup-config.
 *
 * @var array<string, mixed> $cup_config
 * @var string $placement
 */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$cupConfig = is_array($cup_config ?? null) ? $cup_config : [];
$sponsorsCfg = is_array($cupConfig['sponsors'] ?? null) ? $cupConfig['sponsors'] : [];
$placement = (string) ($placement ?? 'frontpage_middle');
$placements = is_array($sponsorsCfg['placements'] ?? null) ? $sponsorsCfg['placements'] : [];

$showSponsors = ($cupConfig['features']['sponsors'] ?? false)
    && in_array($placement, $placements, true);

if (!$showSponsors) {
    return;
}

$tiers = is_array($sponsorsCfg['tiers'] ?? null) ? $sponsorsCfg['tiers'] : [];
$level = (string) ($sponsorsCfg['presentation_level'] ?? 'standard');
$heading = (string) ($sponsorsCfg['heading'] ?? 'Våre sponsorer');
$lead = (string) ($sponsorsCfg['lead'] ?? '');
$footerText = (string) ($sponsorsCfg['footer_text'] ?? '');

$tierOrder = ['hovedsponsor', 'gull', 'sølv', 'bronse', 'samarbeidspartner'];
$tierLabels = [
    'hovedsponsor' => 'Hovedsponsorer',
    'gull' => 'Gullsponsorer',
    'sølv' => 'Sølvsponsorer',
    'bronse' => 'Bronssponsorer',
    'samarbeidspartner' => 'Samarbeidspartnere',
];
$tierMeta = [
    'hovedsponsor' => [
        'accent' => '#1f2937',
        'accent_soft' => '#f6f7f9',
        'border' => '#111827',
        'logo_max_h' => 100,
        'tile_min_h' => '120px',
        'tile_max_w' => '480px',
        'tile_flex_basis' => '280px',
        'sub_logo_h' => 30,
    ],
    'gull' => [
        'accent' => '#b8860b',
        'accent_soft' => '#fff9e6',
        'border' => '#d4af37',
        'logo_max_h' => 88,
        'tile_min_h' => '140px',
        'tile_max_w' => '420px',
        'tile_flex_basis' => '260px',
        'sub_logo_h' => 32,
    ],
    'sølv' => [
        'accent' => '#5c6b73',
        'accent_soft' => '#f4f6f8',
        'border' => '#adb5bd',
        'logo_max_h' => 56,
        'tile_min_h' => '72px',
        'tile_max_w' => '240px',
        'tile_flex_basis' => '140px',
        'sub_logo_h' => 26,
    ],
    'bronse' => [
        'accent' => '#8b5a2b',
        'accent_soft' => '#faf6f0',
        'border' => '#cd7f32',
        'logo_max_h' => 52,
        'tile_min_h' => '64px',
        'tile_max_w' => '220px',
        'tile_flex_basis' => '130px',
        'sub_logo_h' => 24,
    ],
    'samarbeidspartner' => [
        'accent' => '#64748b',
        'accent_soft' => '#f8fafc',
        'border' => '#94a3b8',
        'logo_max_h' => 40,
        'tile_min_h' => '52px',
        'tile_max_w' => '180px',
        'tile_flex_basis' => '110px',
        'sub_logo_h' => 22,
    ],
];

$hasAny = false;
foreach ($tierOrder as $tierKey) {
    $items = is_array($tiers[$tierKey] ?? null) ? $tiers[$tierKey] : [];
    if ($items !== []) {
        $hasAny = true;
        break;
    }
}

$modifier = 'sponsors--' . $level . ' sponsors--' . preg_replace('/[^a-z0-9_-]/', '', $placement);
$isHero = $placement === 'hero';
$isFooter = $placement === 'footer';
$isHome = $placement === 'frontpage_middle' || $placement === 'frontpage_top';
$activeTierOrder = $isHero ? ['hovedsponsor'] : $tierOrder;
$sectionId = $isHome ? ' id="sponsors"' : '';
$wrapCard = $isHome && !$isHero;

if ($isFooter && $footerText === '') {
    return;
}
?>
<?php if ($wrapCard): ?><section class="card home-sponsors-wrap"><?php endif; ?>
<section class="sponsors <?= $h($modifier) ?>"<?= $sectionId ?> aria-label="<?= $h($heading) ?>">
    <?php if (!$isFooter): ?>
        <?php if ($isHero && $level === 'prominent'): ?>
            <p class="sponsors-hero-label muted">Hovedsponsor</p>
        <?php elseif (!$isHero): ?>
            <h2 class="sponsors-heading"><?= $h($heading) ?></h2>
        <?php endif; ?>
        <?php if ($lead !== '' && !$isHero): ?>
            <p class="sponsors-lead"><?= $h($lead) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isFooter): ?>
        <?php if ($footerText !== ''): ?>
            <p class="sponsors-footer muted"><?= $h($footerText) ?></p>
        <?php endif; ?>
    <?php elseif (!$hasAny && $level !== 'minimal'): ?>
        <p class="muted sponsors-empty">Sponsorinformasjon kommer snart.</p>
    <?php else: ?>
        <?php foreach ($activeTierOrder as $tierKey): ?>
            <?php
            $items = is_array($tiers[$tierKey] ?? null) ? $tiers[$tierKey] : [];
            if ($items === []) {
                continue;
            }
            $meta = $tierMeta[$tierKey] ?? $tierMeta['samarbeidspartner'];
            $tierLabel = $tierLabels[$tierKey] ?? $tierKey;
            ?>
            <div class="sponsor-tier sponsor-tier--<?= $h($tierKey) ?>"
                 style="--tier-accent: <?= $h($meta['accent']) ?>; --tier-soft: <?= $h($meta['accent_soft']) ?>; --tier-border: <?= $h($meta['border']) ?>;">
                <?php if (!$isHero): ?>
                    <h3 class="sponsor-tier-title"><?= $h($tierLabel) ?></h3>
                <?php endif; ?>
                <div class="sponsor-tier-grid">
                    <?php foreach ($items as $item): ?>
                        <?php if (!is_array($item)) { continue; } ?>
                        <?php
                        $name = (string) ($item['name'] ?? '');
                        $url = trim((string) ($item['url'] ?? ''));
                        $logo = trim((string) ($item['logo'] ?? ''));
                        $tileBg = (string) ($item['tile_bg'] ?? '#ffffff');
                        $tileBorder = (string) ($item['tile_border'] ?? '1px solid rgba(0,0,0,0.08)');
                        $logoFit = (string) ($item['logo_fit'] ?? 'contain');
                        $logoScalePct = (int) ($item['logo_scale_pct'] ?? 100);
                        $logoScalePct = max(10, min(200, $logoScalePct));
                        $logoH = (int) round($meta['logo_max_h'] * ($logoScalePct / 100));
                        $subLogos = is_array($item['sub_logos'] ?? null) ? $item['sub_logos'] : [];
                        $subLogosLabel = trim((string) ($item['sub_logos_label'] ?? 'I samarbeid med:'));
                        $featured = !empty($item['featured']);
                        $tileStyle = '--tile-bg:' . $tileBg . ';--tile-border:' . $tileBorder
                            . ';--tile-min-h:' . $meta['tile_min_h']
                            . ';--tile-max-w:' . $meta['tile_max_w']
                            . ';--tile-flex-basis:' . $meta['tile_flex_basis'] . ';';
                        ?>
                        <?php if ($url !== ''): ?><a href="<?= $h($url) ?>" target="_blank" rel="noopener noreferrer" class="sponsor-tile sponsor-tile-link<?= $featured ? ' sponsor-tile--featured' : '' ?>" style="<?= $h($tileStyle) ?>"><?php else: ?><div class="sponsor-tile<?= $featured ? ' sponsor-tile--featured' : '' ?>" style="<?= $h($tileStyle) ?>"><?php endif; ?>
                            <?php if ($logo !== ''): ?>
                                <img src="<?= $h($logo) ?>" alt="<?= $h($name) ?>" class="sponsor-logo" style="height: <?= $logoH ?>px; object-fit: <?= $h($logoFit) ?>;" loading="lazy" decoding="async">
                            <?php elseif ($name !== ''): ?>
                                <span class="sponsor-name"><?= $h($name) ?></span>
                            <?php endif; ?>
                            <?php if ($subLogos !== []): ?>
                                <?php if ($subLogosLabel !== ''): ?>
                                    <span class="sponsor-sub-label"><?= $h($subLogosLabel) ?></span>
                                <?php endif; ?>
                                <div class="sponsor-sub-logos">
                                    <?php foreach ($subLogos as $sub): ?>
                                        <?php if (!is_array($sub)) { continue; } ?>
                                        <?php
                                        $subName = (string) ($sub['name'] ?? '');
                                        $subLogo = trim((string) ($sub['logo'] ?? ''));
                                        $subScale = max(10, min(200, (int) ($sub['scale_pct'] ?? 100)));
                                        $subH = (int) round($meta['sub_logo_h'] * ($subScale / 100));
                                        if ($subLogo === '') { continue; }
                                        ?>
                                        <img src="<?= $h($subLogo) ?>" alt="<?= $h($subName) ?>" class="sponsor-sub-logo" style="height: <?= $subH ?>px;" loading="lazy" decoding="async">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php if ($url !== ''): ?></a><?php else: ?></div><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($footerText !== '' && $placement === 'frontpage_middle'): ?>
        <p class="sponsors-footer muted"><?= $h($footerText) ?></p>
    <?php endif; ?>
</section>
<?php if ($wrapCard): ?></section><?php endif; ?>
