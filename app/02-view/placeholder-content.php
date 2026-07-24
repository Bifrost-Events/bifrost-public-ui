<?php

declare(strict_types=1);

/** @var string $page_title */
/** @var string $page_description */
/** @var bool $hide_page_title */
/** @var string $cta_url */
/** @var string $cta_label */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$ctaUrl = trim((string) ($cta_url ?? ''));
$ctaLabel = trim((string) ($cta_label ?? ''));
if ($ctaLabel === '') {
    $ctaLabel = 'Åpne lenke';
}
?>
<section class="card">
    <?php if (empty($hide_page_title)): ?>
        <h1><?= $h($page_title) ?></h1>
    <?php endif; ?>
    <p class="muted"><?= nl2br($h($page_description), false) ?></p>
    <?php if ($ctaUrl !== ''): ?>
        <p style="margin-top: 1.25rem;">
            <a class="btn btn-primary" href="<?= $h($ctaUrl) ?>"><?= $h($ctaLabel) ?></a>
        </p>
    <?php endif; ?>
</section>
