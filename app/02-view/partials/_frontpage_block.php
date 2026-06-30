<?php

declare(strict_types=1);

/**
 * Renderer én forsidemodul fra cup-config.
 *
 * @var array<string, mixed> $cup_config
 * @var string $block
 */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$cupConfig = is_array($cup_config ?? null) ? $cup_config : [];
$content = is_array($cupConfig['content'] ?? null) ? $cupConfig['content'] : [];
$brand = is_array($cupConfig['brand'] ?? null) ? $cupConfig['brand'] : [];
$block = (string) ($block ?? '');

switch ($block) {
    case 'hero':
        $heroTitle = (string) ($content['frontpage_title'] ?? $cupConfig['name'] ?? '');
        $heroSubtitle = (string) ($content['hero_subtitle'] ?? $brand['tagline'] ?? '');
        $heroImage = trim((string) ($brand['hero_image'] ?? $brand['logo'] ?? ''));
        ?>
        <section class="page-hero card">
            <div class="page-hero-inner">
                <div class="page-hero-text">
                    <h1><?= $h($heroTitle) ?></h1>
                    <?php if ($heroSubtitle !== ''): ?>
                        <p class="page-hero-subtitle"><?= $h($heroSubtitle) ?></p>
                    <?php endif; ?>
                    <div class="page-hero-actions">
                        <a href="/results" class="btn btn-primary">Se resultater</a>
                        <a href="/calendar" class="btn btn-outline">Stevnekalender</a>
                    </div>
                </div>
                <?php if ($heroImage !== ''): ?>
                    <div class="page-hero-visual" style="background-image: url('<?= $h($heroImage) ?>');" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <?php
            $placement = 'hero';
            include __DIR__ . '/_sponsors.php';
            ?>
        </section>
        <?php
        break;

    case 'intro':
        $title = (string) ($content['frontpage_title'] ?? '');
        $intro = (string) ($content['intro_text'] ?? '');
        $tagline = (string) ($brand['tagline'] ?? '');
        ?>
        <section class="card home-intro">
            <?php if ($title !== ''): ?>
                <h1><?= $h($title) ?></h1>
            <?php endif; ?>
            <?php if ($tagline !== '' && $title === ''): ?>
                <p class="home-tagline"><?= $h($tagline) ?></p>
            <?php endif; ?>
            <?php foreach (preg_split('/\n\n+/', $intro) ?: [] as $para): ?>
                <?php $para = trim($para); if ($para === '') { continue; } ?>
                <p><?= nl2br($h($para)) ?></p>
            <?php endforeach; ?>
        </section>
        <?php
        break;

    case 'stats':
        ?>
        <section class="card home-stats" aria-label="Sesongstatistikk">
            <div class="stats-bar">
                <div class="stat-item"><span class="stat-value">—</span><span class="stat-label">arrangører</span></div>
                <div class="stat-item"><span class="stat-value">—</span><span class="stat-label">stevner</span></div>
                <div class="stat-item"><span class="stat-value">—</span><span class="stat-label">deltakere</span></div>
                <div class="stat-item"><span class="stat-value">—</span><span class="stat-label">påmeldinger</span></div>
            </div>
            <p class="muted stats-placeholder-note">Statistikk kobles til backend i neste fase.</p>
        </section>
        <?php
        break;

    case 'sponsors':
        $placement = 'frontpage_middle';
        include __DIR__ . '/_sponsors.php';
        break;

    case 'sponsor_cta':
        $cta = is_array($content['sponsor_cta'] ?? null) ? $content['sponsor_cta'] : [];
        if ($cta === []) {
            break;
        }
        ?>
        <section class="card sponsor-cta">
            <h2><?= $h((string) ($cta['title'] ?? 'Bli partner')) ?></h2>
            <p><?= $h((string) ($cta['body'] ?? '')) ?></p>
            <?php if (!empty($cta['button_url'])): ?>
                <a href="<?= $h((string) $cta['button_url']) ?>" class="btn btn-primary">
                    <?= $h((string) ($cta['button_label'] ?? 'Les mer')) ?>
                </a>
            <?php endif; ?>
        </section>
        <?php
        break;

    case 'about':
        $about = trim((string) ($content['about_text'] ?? ''));
        if ($about === '') {
            break;
        }
        ?>
        <section class="card home-about">
            <h2>Om cupen</h2>
            <p><?= nl2br($h($about)) ?></p>
        </section>
        <?php
        break;

    case 'contact':
        $contact = trim((string) ($content['contact_text'] ?? ''));
        if ($contact === '') {
            break;
        }
        ?>
        <section class="card home-contact">
            <h2>Kontakt</h2>
            <p><?= nl2br($h($contact)) ?></p>
        </section>
        <?php
        break;

    case 'info':
        $info = is_array($content['info_box'] ?? null) ? $content['info_box'] : [];
        if ($info === []) {
            break;
        }
        $facts = is_array($info['facts'] ?? null) ? $info['facts'] : [];
        ?>
        <section class="card home-info-box">
            <h2><?= $h((string) ($info['title'] ?? 'Om cupen')) ?></h2>
            <p><?= $h((string) ($info['lead'] ?? '')) ?></p>
            <?php if ($facts !== []): ?>
                <dl class="info-facts">
                    <?php foreach ($facts as $fact): ?>
                        <?php if (!is_array($fact)) { continue; } ?>
                        <div class="info-fact">
                            <dt><?= $h((string) ($fact['label'] ?? '')) ?></dt>
                            <dd><?= $h((string) ($fact['value'] ?? '')) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>
        <?php
        break;

    case 'highlight':
        $hl = is_array($content['highlight'] ?? null) ? $content['highlight'] : [];
        if ($hl === []) {
            break;
        }
        ?>
        <section class="card home-highlight">
            <h2><?= $h((string) ($hl['title'] ?? '')) ?></h2>
            <p><?= nl2br($h((string) ($hl['body'] ?? ''))) ?></p>
        </section>
        <?php
        break;

    case 'role_cards':
        $rc = is_array($content['role_cards'] ?? null) ? $content['role_cards'] : [];
        $cards = is_array($rc['cards'] ?? null) ? $rc['cards'] : [];
        if ($cards === []) {
            break;
        }
        ?>
        <section class="card home-role-cards">
            <h2><?= $h((string) ($rc['title'] ?? '')) ?></h2>
            <?php if (!empty($rc['lead'])): ?>
                <p class="muted"><?= $h((string) $rc['lead']) ?></p>
            <?php endif; ?>
            <div class="role-card-grid">
                <?php foreach ($cards as $card): ?>
                    <?php if (!is_array($card)) { continue; } ?>
                    <a href="<?= $h((string) ($card['url'] ?? '#')) ?>" class="role-card">
                        <?= $h((string) ($card['label'] ?? '')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        break;

    case 'signup_teaser':
        ?>
        <section class="card placeholder-block">
            <h2>Påmelding</h2>
            <p class="muted">Påmelding til stevner kobles til backend. Registrer bruker for å være klar når påmelding åpner.</p>
            <a href="/auth/register" class="btn btn-primary">Registrer deg</a>
        </section>
        <?php
        break;

    case 'results_teaser':
        ?>
        <section class="card placeholder-block">
            <h2>Resultater og sammenlagt</h2>
            <p class="muted">Offisielle resultater og cupstilling vises her når backend er koblet på.</p>
            <div class="placeholder-actions">
                <a href="/results" class="btn btn-outline">Stevneresultater</a>
                <a href="/sammenlagt" class="btn btn-outline">Sammenlagt</a>
            </div>
        </section>
        <?php
        break;

    default:
        break;
}
