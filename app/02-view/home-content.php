<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant_context */
/** @var array<string, mixed> $cup_config */
/** @var array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null}|null $health */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$cupConfig = is_array($cup_config ?? null) ? $cup_config : [];
$layout = is_array($cupConfig['layout'] ?? null) ? $cupConfig['layout'] : [];
$blocks = is_array($layout['frontpage_blocks'] ?? null) ? $layout['frontpage_blocks'] : ['intro', 'contact'];
$sponsorsCfg = is_array($cupConfig['sponsors'] ?? null) ? $cupConfig['sponsors'] : [];
$sponsorPlacements = is_array($sponsorsCfg['placements'] ?? null) ? $sponsorsCfg['placements'] : [];
$tenantContext = $tenant_context ?? [];
$health = $health ?? null;
$backendOk = is_array($health) && ($health['ok'] ?? false);
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
        Backend:
        <?php if ($backendOk): ?>
            <span class="status-ok">tilkoblet</span>
        <?php else: ?>
            <span class="status-bad">ikke tilgjengelig</span>
            <?php if (is_array($health) && ($health['error'] ?? '') !== ''): ?>
                — <?= $h((string) $health['error']) ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>
    <?php if (($tenantContext['resolved'] ?? false) === false && ($tenantContext['error'] ?? null) !== null): ?>
        <p class="status-bad muted">Tenant: <?= $h((string) $tenantContext['error']) ?></p>
    <?php endif; ?>
</section>
<?php endif; ?>
