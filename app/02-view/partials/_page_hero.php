<?php

declare(strict_types=1);

/**
 * Kompakt side-hero (V1/V2-palett) for undersider.
 *
 * @var string $page_hero_title
 * @var string $page_hero_subtitle
 * @var string $page_hero_logo
 */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$heroTitle = trim((string) ($page_hero_title ?? ''));
$heroSubtitle = trim((string) ($page_hero_subtitle ?? ''));
$heroLogo = trim((string) ($page_hero_logo ?? ''));
if ($heroTitle === '') {
    return;
}
$heroStyle = $heroLogo !== ''
    ? ' style="--page-hero-logo: url(\'' . $h($heroLogo) . '\')"'
    : '';
?>
<section class="page-hero page-hero--compact" aria-labelledby="page-hero-heading"<?= $heroStyle ?>>
    <div class="page-hero-inner">
        <div class="page-hero-text">
            <h1 id="page-hero-heading"><?= $h($heroTitle) ?></h1>
            <?php if ($heroSubtitle !== ''): ?>
                <p class="page-hero-subtitle"><?= $h($heroSubtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
