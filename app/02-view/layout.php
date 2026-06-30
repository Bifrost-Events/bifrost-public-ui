<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var array<string, mixed> $tenant_context */
/** @var array<string, mixed> $cup_config */
/** @var array<string, mixed>|null $user */
/** @var list<array<string, mixed>> $nav_items */
/** @var list<array<string, mixed>> $user_menu_items */
/** @var string $current_path */
/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$tenantContext = $tenant_context ?? [];
$cupConfig = is_array($cup_config ?? null) ? $cup_config : \App\Support\CupConfigLoader::current();
$brand = is_array($cupConfig['brand'] ?? null) ? $cupConfig['brand'] : [];
$cssVars = \App\Support\CupConfigLoader::cssVariables($cupConfig);
$displayName = (string) ($cupConfig['name'] ?? ($tenantContext['display_name'] ?? 'Bifrost'));
$shortName = (string) ($cupConfig['short_name'] ?? $displayName);
$tagline = (string) ($brand['tagline'] ?? '');
$logoUrl = trim((string) ($brand['logo'] ?? ''));
$configMeta = is_array($cupConfig['_meta'] ?? null) ? $cupConfig['_meta'] : [];
$showDevBanner = \App\Support\CupConfigLoader::isDevelopmentBannerVisible();
$navItems = $nav_items ?? [];
$userMenuItems = $user_menu_items ?? [];
$currentPath = $current_path ?? '/';
$user = $user ?? null;
$userName = '';
if (is_array($user)) {
    $userName = trim((string) ($user['name'] ?? ''));
    if ($userName === '') {
        $userName = (string) ($user['email'] ?? 'Bruker');
    }
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $h($title) ?> – <?= $h($displayName) ?></title>
    <style>
        :root {
            --bg: <?= $h($cssVars['--bg']) ?>;
            --header: <?= $h($cssVars['--header']) ?>;
            --header-text: <?= $h($cssVars['--header-text']) ?>;
            --card: <?= $h($cssVars['--card']) ?>;
            --ink: <?= $h($cssVars['--ink']) ?>;
            --muted: <?= $h($cssVars['--muted']) ?>;
            --line: <?= $h($cssVars['--line']) ?>;
            --accent: <?= $h($cssVars['--accent']) ?>;
            --accent-soft: <?= $h($cssVars['--accent-soft']) ?>;
            --accent-light: <?= $h($cssVars['--accent-light']) ?>;
            --bad: <?= $h($cssVars['--bad']) ?>;
            --ok: <?= $h($cssVars['--ok']) ?>;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, Segoe UI, Roboto, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
        }
        a { color: var(--accent); }
        .site-header {
            background: var(--header);
            color: var(--header-text);
            border-bottom: 3px solid var(--accent);
        }
        .header-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .brand a {
            color: inherit;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .brand-logo {
            height: 48px;
            max-width: 140px;
            width: auto;
            object-fit: contain;
        }
        .brand small {
            display: block;
            font-weight: 400;
            font-size: 0.78rem;
            color: #b8c4bc;
            margin-top: 0.15rem;
        }
        .main-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem 0.5rem;
        }
        .main-nav a {
            color: var(--header-text);
            text-decoration: none;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.92rem;
        }
        .main-nav a:hover,
        .main-nav a.is-active {
            background: rgba(255,255,255,0.12);
        }
        .auth-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .auth-actions .btn {
            padding: 0.5rem 0.9rem;
            border-radius: 4px;
            font-size: 0.92rem;
            font-family: inherit;
            line-height: 1.2;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.14);
            color: var(--header-text);
        }
        .auth-actions .btn:hover {
            background: rgba(255,255,255,0.22);
        }
        .auth-actions .btn-primary {
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.45);
            font-weight: 600;
        }
        .auth-actions .btn-primary:hover {
            background: rgba(255,255,255,0.3);
        }
        .btn {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            border: 1px solid transparent;
            font-family: inherit;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .btn-outline {
            background: #fff;
            color: var(--accent);
            border: 1px solid var(--accent);
        }
        .btn-outline:hover { background: var(--accent-soft); }
        .dev-config-banner {
            background: #1e293b;
            color: #e2e8f0;
            font-size: 0.82rem;
            padding: 0.35rem 1.25rem;
            text-align: center;
        }
        .dev-config-banner code { color: #fbbf24; }
        .page-hero { padding: 0; overflow: hidden; border: none; }
        .page-hero-inner {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            min-height: 200px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 12%, #fff) 0%, color-mix(in srgb, var(--accent-light) 40%, #fff) 55%, #fff 100%);
            padding: 1.5rem 1.35rem;
        }
        @media (min-width: 720px) {
            .page-hero-inner { grid-template-columns: 1.2fr 0.8fr; align-items: center; }
        }
        .page-hero h1 { margin: 0 0 0.5rem; font-size: clamp(1.35rem, 3vw, 2rem); }
        .page-hero-subtitle { color: var(--muted); margin: 0 0 1rem; }
        .page-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .page-hero-visual {
            min-height: 120px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center right;
            opacity: 0.85;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            background: var(--accent-soft);
            border-radius: 6px;
            padding: 1rem;
        }
        @media (min-width: 640px) { .stats-bar { grid-template-columns: repeat(4, 1fr); } }
        .stat-item { text-align: center; }
        .stat-value { display: block; font-size: 1.35rem; font-weight: 700; color: var(--accent); }
        .stat-label { font-size: 0.85rem; color: var(--muted); }
        .stats-placeholder-note { margin: 0.75rem 0 0; font-size: 0.88rem; }
        .info-facts { display: grid; gap: 0.65rem; margin: 1rem 0 0; }
        @media (min-width: 640px) { .info-facts { grid-template-columns: repeat(2, 1fr); } }
        .info-fact { background: var(--accent-soft); padding: 0.65rem 0.85rem; border-radius: 6px; }
        .info-fact dt { font-size: 0.82rem; color: var(--muted); margin: 0; }
        .info-fact dd { margin: 0.15rem 0 0; font-weight: 600; }
        .role-card-grid { display: grid; gap: 0.65rem; margin-top: 1rem; }
        @media (min-width: 640px) { .role-card-grid { grid-template-columns: repeat(2, 1fr); } }
        .role-card {
            display: block;
            padding: 0.85rem 1rem;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
        }
        .role-card:hover { filter: brightness(1.05); }
        .placeholder-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .sponsors { margin: 0 0 1rem; }
        .home-sponsors-wrap { padding: 1.35rem 1.5rem; }
        .sponsors-heading { margin: 0 0 0.5rem; font-size: 1.35rem; }
        .sponsors-lead { margin: 0 0 1.25rem; line-height: 1.55; max-width: 52rem; }
        .sponsor-tier {
            margin-bottom: 1.15rem;
            padding: 1rem 1rem 1.1rem;
            background: var(--tier-soft, var(--accent-soft));
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border-top: 4px solid var(--tier-border, var(--line));
        }
        .sponsor-tier-title {
            margin: 0 0 0.85rem;
            font-size: 1.05rem;
            color: var(--tier-accent, var(--accent));
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .sponsor-tier-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: stretch;
            justify-content: center;
        }
        .sponsor-tile {
            flex: 1 1 var(--tile-flex-basis, 140px);
            max-width: var(--tile-max-w, 240px);
            min-height: var(--tile-min-h, 64px);
            background: var(--tile-bg, #fff);
            border: var(--tile-border, 1px solid rgba(0,0,0,0.08));
            border-radius: 6px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-sizing: border-box;
        }
        .sponsor-tile--featured {
            flex-basis: min(100%, 320px);
            max-width: 520px;
            min-height: 110px;
            border-color: var(--accent);
        }
        .sponsor-tile-link { text-decoration: none; color: inherit; }
        .sponsor-tile-link:hover { filter: brightness(0.98); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .sponsor-logo {
            display: block;
            width: auto;
            max-width: 100%;
            height: auto;
        }
        .sponsor-name { font-weight: 600; font-size: 0.9rem; }
        .sponsor-sub-label {
            display: block;
            margin-top: 6px;
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 600;
        }
        .sponsor-sub-logos {
            margin-top: 8px;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }
        .sponsor-sub-logo { display: block; width: auto; object-fit: contain; }
        .sponsors--prominent .sponsor-tier--hovedsponsor .sponsor-logo { max-height: 120px; }
        .sponsors--prominent.sponsors--hero .sponsor-tier { background: transparent; border: none; box-shadow: none; padding: 0; }
        .sponsors-footer { margin: 0.75rem 0 0; font-size: 0.9rem; line-height: 1.5; }
        .sponsor-cta { border-left: 4px solid var(--accent); }
        .brand-text { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
        .brand-title { font-size: 1.15rem; line-height: 1.2; }
        .dev-section-title { font-size: 1rem; margin-top: 0; }
        .user-menu summary {
            cursor: pointer;
            list-style: none;
            color: var(--header-text);
            font-size: 0.9rem;
        }
        .user-menu-trigger {
            padding: 0.5rem 0.9rem;
            border-radius: 4px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.14);
            font-weight: 600;
        }
        .user-menu-trigger:hover,
        .user-menu[open] .user-menu-trigger {
            background: rgba(255,255,255,0.22);
        }
        .user-menu summary::-webkit-details-marker { display: none; }
        .user-menu-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 0.35rem);
            min-width: 200px;
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 0.35rem 0;
            z-index: 20;
        }
        .user-menu-panel a {
            display: block;
            padding: 0.45rem 0.85rem;
            text-decoration: none;
            color: var(--ink);
        }
        .user-menu-panel a:hover { background: var(--accent-soft); }
        .user-menu-panel a.user-menu-logout { color: var(--bad); }
        .user-menu { position: relative; }
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 1.25rem 1.35rem;
            margin-bottom: 1rem;
        }
        .muted { color: var(--muted); }
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .flash-info { background: #eef4ff; border: 1px solid #c5d5f5; }
        .flash-error { background: #fdecec; border: 1px solid #f0c4c4; color: var(--bad); }
        .flash-success { background: var(--accent-soft); border: 1px solid #b8d4bc; color: var(--ok); }
        .status-ok { color: var(--ok); }
        .status-bad { color: var(--bad); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .data-table th, .data-table td { padding: 0.5rem 0.65rem; border-bottom: 1px solid var(--line); text-align: left; }
        .data-table th { color: var(--muted); font-weight: 600; }
        .link-list { list-style: none; margin: 0; padding: 0; }
        .link-list li { border-bottom: 1px solid var(--line); }
        .link-list a { display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0; text-decoration: none; color: inherit; }
        .link-list a:hover { color: var(--accent); }
        .form-group { margin-bottom: 0.85rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--line); border-radius: 4px; }
        .login-modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            z-index: 100; align-items: center; justify-content: center; padding: 1rem;
        }
        .login-modal-overlay.is-open { display: flex; }
        .login-modal-dialog {
            background: #fff; border-radius: 8px; width: min(420px, 100%);
            padding: 1.25rem; box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }
        .login-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .login-modal-close { border: none; background: transparent; font-size: 1.25rem; cursor: pointer; }
        .login-modal-error { background: #fdecec; color: var(--bad); padding: 0.5rem 0.65rem; border-radius: 4px; margin-bottom: 0.75rem; }
        .login-modal-submit { width: 100%; padding: 0.55rem; background: var(--accent); color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .login-modal-register-cta { margin-top: 0.75rem; font-size: 0.9rem; }
        .site-footer {
            border-top: 1px solid var(--line);
            padding: 1.25rem;
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
        }
        @media (max-width: 720px) {
            .header-inner { flex-direction: column; align-items: flex-start; }
            .main-nav ul { flex-direction: column; }
        }
        .onboarding { max-width: 720px; margin: 0 auto; }
        .onboarding h2 { margin-bottom: 0.75em; text-align: center; }
        .onboarding > p { margin-bottom: 0.75em; text-align: center; }
        .onboarding-cards { display: grid; gap: 16px; margin-top: 1.25em; }
        .onboarding-cards--single { grid-template-columns: 1fr; justify-items: center; }
        .onboarding-cards--single .onboarding-card { width: 100%; max-width: 520px; }
        .onboarding-cards--choose { grid-template-columns: 1fr; max-width: 560px; margin-left: auto; margin-right: auto; }
        @media (min-width: 640px) {
            .onboarding-cards--choose { grid-template-columns: repeat(2, minmax(0, 1fr)); max-width: 720px; }
        }
        .onboarding-card { background: #f8f9fa; border-radius: 8px; padding: 16px 18px; border: 1px solid var(--line); }
        .onboarding-card h3 { margin: 0 0 0.35em 0; }
        .onboarding-participant-details { margin: 0.25em 0 0.75em 1.1em; padding: 0; font-size: 0.95rem; }
        .onboarding-btn { display: inline-block; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-weight: 500; font-size: 0.95rem; border: 0; cursor: pointer; }
        .onboarding-btn.primary { background: var(--accent); color: #fff; }
        .onboarding-btn.secondary { background: #e2e8f0; color: #1f2933; margin-left: 8px; }
        .onboarding-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 0.5em; }
        .onboarding-progress { max-width: 720px; margin: 0.75em auto 1.25em; padding: 12px 14px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
        .onboarding-progress-top { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .onboarding-progress-steps { list-style: none; padding: 0; margin: 0; display: grid; gap: 8px; }
        .onboarding-step { display: flex; align-items: center; gap: 10px; color: var(--muted); }
        .onboarding-step .dot { width: 10px; height: 10px; border-radius: 999px; background: #cbd5e1; display: inline-block; }
        .onboarding-step.active { color: var(--text); font-weight: 600; }
        .onboarding-step.active .dot { background: var(--accent); }
        .onboarding-step.done .dot { background: #16a34a; }
        .onboarding-agreement { max-height: min(55vh, 520px); overflow: auto; border: 1px solid var(--line); border-radius: 6px; padding: 12px 14px; background: #fff; margin: 10px 0; }
        .onboarding-checkbox { display: flex; gap: 8px; align-items: flex-start; margin: 10px 0; }
        .onboarding-form-grid { display: grid; grid-template-columns: 1fr; gap: 10px; margin: 10px 0; }
        .onboarding-form-grid input { width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px; box-sizing: border-box; }
        .onboarding-form-grid label { display: block; font-size: 0.9rem; }
        .onboarding-muted { color: var(--muted); font-size: 0.95rem; }
        .onboarding-summary { margin: 0.25em 0 0.75em 1.1em; padding: 0; }
        @media (min-width: 640px) {
            .onboarding-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
<?php if ($showDevBanner): ?>
<div class="dev-config-banner" role="status">
    Cup config: <code><?= $h((string) ($configMeta['config_file'] ?? 'ukjent')) ?></code>
    · host <code><?= $h((string) ($configMeta['resolved_host'] ?? '')) ?></code>
    · mal <code><?= $h((string) (($cupConfig['layout']['template'] ?? 'default'))) ?></code>
</div>
<?php endif; ?>
<header class="site-header">
    <div class="header-inner">
        <div class="brand">
            <a href="/" class="brand-link">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= $h($logoUrl) ?>" alt="" class="brand-logo" aria-hidden="true">
                <?php endif; ?>
                <span class="brand-text">
                    <span class="brand-title"><?= $h($displayName) ?></span>
                </span>
            </a>
            <?php if (!($tenantContext['resolved'] ?? false) && ($tenantContext['error'] ?? null) !== null): ?>
                <small>Tenant ikke funnet for <?= $h((string) ($tenantContext['host'] ?? '')) ?></small>
            <?php endif; ?>
        </div>
        <nav class="main-nav" aria-label="Hovedmeny">
            <ul>
                <?php foreach ($navItems as $item): ?>
                    <?php if (!is_array($item)) { continue; } ?>
                    <?php
                    $url = (string) ($item['url'] ?? '#');
                    $label = (string) ($item['label'] ?? '');
                    $active = \App\Support\PublicMenu::isActive($url, $currentPath);
                    ?>
                    <li>
                        <a href="<?= $h($url) ?>"<?= $active ? ' class="is-active"' : '' ?>><?= $h($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="auth-actions" id="auth-actions">
            <?php
            include __DIR__ . '/partials/_auth_actions.php';
            ?>
        </div>
    </div>
</header>

<main>
    <?php include __DIR__ . '/partials/_flash.php'; ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <p><?= $h($displayName) ?><?php if ($tagline !== ''): ?> · <?= $h($tagline) ?><?php endif; ?></p>
</footer>

<div class="login-modal-overlay" id="login-modal-overlay" aria-hidden="true">
    <div class="login-modal-dialog" role="dialog" aria-labelledby="login-modal-title">
        <div class="login-modal-header">
            <h2 id="login-modal-title">Logg inn</h2>
            <button type="button" class="login-modal-close" id="login-modal-close" aria-label="Lukk">×</button>
        </div>
        <div id="login-modal-content"></div>
    </div>
</div>

<script>
(function() {
    var overlay = document.getElementById('login-modal-overlay');
    var content = document.getElementById('login-modal-content');
    var title = document.getElementById('login-modal-title');
    var closeBtn = document.getElementById('login-modal-close');
    if (!overlay || !content) return;

    function openModal() { overlay.classList.add('is-open'); overlay.setAttribute('aria-hidden', 'false'); }
    function closeModal() { overlay.classList.remove('is-open'); overlay.setAttribute('aria-hidden', 'true'); }

    function registerUrl() {
        return '/auth/register?return_to=' + encodeURIComponent(window.location.pathname);
    }

    function bindAuthModalTriggers() {
        var loginTrigger = document.getElementById('login-modal-trigger');
        var registerTrigger = document.getElementById('register-modal-trigger');
        if (loginTrigger) {
            loginTrigger.addEventListener('click', function() { openModal(); loadLogin(); });
        }
        if (registerTrigger) {
            registerTrigger.addEventListener('click', function() {
                window.location.href = registerUrl();
            });
        }
    }

    function bindAuthForm(formId, submitUrl) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; }
            fetch(submitUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function(res) {
                return res.json().then(function(data) { return { ok: res.ok, data: data }; });
            }).then(function(r) {
                if (r.data && r.data.success) {
                    closeModal();
                    window.location.href = r.data.returnTo || window.location.pathname;
                    return;
                }
                var err = (r.data && r.data.error) ? r.data.error : 'Forespørselen feilet.';
                content.insertAdjacentHTML('afterbegin', '<div class="login-modal-error">' + err.replace(/</g, '&lt;') + '</div>');
                if (btn) { btn.disabled = false; }
            }).catch(function() {
                content.insertAdjacentHTML('afterbegin', '<div class="login-modal-error">Kunne ikke koble til.</div>');
                if (btn) { btn.disabled = false; }
            });
        });
    }

    function loadLogin() {
        title.textContent = 'Logg inn';
        fetch('/auth/login-form?return_to=' + encodeURIComponent(window.location.pathname), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                content.innerHTML = d.html || '';
                bindAuthForm('login-modal-form', '/auth/login');
            });
    }

    bindAuthModalTriggers();
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });
})();
</script>
</body>
</html>
