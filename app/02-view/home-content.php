<?php

declare(strict_types=1);

/** @var array<string, mixed> $portal_context */
/** @var array<string, mixed> $cup_config */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$cupConfig = is_array($cup_config ?? null) ? $cup_config : [];
$layout = is_array($cupConfig['layout'] ?? null) ? $cupConfig['layout'] : [];
$blocks = is_array($layout['frontpage_blocks'] ?? null) ? $layout['frontpage_blocks'] : ['intro', 'contact'];
$sponsorsCfg = is_array($cupConfig['sponsors'] ?? null) ? $cupConfig['sponsors'] : [];
$sponsorPlacements = is_array($sponsorsCfg['placements'] ?? null) ? $sponsorsCfg['placements'] : [];
$portalContext = $portal_context ?? [];
?>

<?php foreach ($blocks as $block): ?>
    <?php
    $block = is_string($block) ? $block : '';
    if ($block === '') {
        continue;
    }
    include __DIR__ . '/partials/_frontpage_block.php';
    ?>
<?php endforeach; ?>

<?php if (in_array('footer', $sponsorPlacements, true)): ?>
    <?php
    $placement = 'footer';
    include __DIR__ . '/partials/_sponsors.php';
    ?>
<?php endif; ?>

<?php if (\App\Support\CupConfigLoader::isDevelopmentBannerVisible()): ?>
<section class="card dev-backend-status">
    <h2 class="dev-section-title">Utvikling</h2>
    <p class="muted">
        Portal:
        <?php if (($portalContext['resolved'] ?? false) === true): ?>
            <span class="status-ok">resolvert</span>
            — app <code><?= $h((string) ($portalContext['application_key'] ?? '')) ?></code>
        <?php else: ?>
            <span class="status-bad">ikke resolvert</span>
            <?php if (($portalContext['error'] ?? '') !== ''): ?>
                — <?= $h((string) $portalContext['error']) ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>
</section>
<?php endif; ?>
